<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * This file contains the code to analyse all the responses to a particular
 * question.
 *
 * @package quiz_statistics
 * @copyright 2010 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * This class can store and compute the analysis of the responses to a particular
 * question.
 *
 * @copyright 2010 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_statistics_response_analyser {
    /** @var object the data from the database that defines the question. */
    protected $questiondata;
    protected $loaded = false;

    /**
     * @var array This is a multi-dimensional array that stores the results of
     * the analysis.
     *
     * The description of {@link question_type::get_possible_responses()} should
     * help understand this description.
     *
     * $this->responses[$subpartid][$responseclassid][$response] is an
     * object with two fields, ->count and ->fraction.
     */
    public $responses = array();

    /**
     * @var array An array of 
     * $this->fractions[$subpartid][$responseclassid] is an object with two
     * fields, ->responseclass and ->fraction.
     */
    public $responseclasses = array();

    /**
     * Create a new instance of this class for holding/computing the statistics
     * for a particular question.
     * @param object $questiondata the data from the database defining this question.
     */
    public function __construct($questiondata) {
        $this->questiondata = $questiondata;

        $this->responseclasses = question_bank::get_qtype($questiondata->qtype)->
                get_possible_responses($questiondata);
        foreach ($this->responseclasses as $subpartid => $responseclasses) {
            foreach ($responseclasses as $responseclassid => $notused) {
                $this->responses[$subpartid][$responseclassid] = array();
            }
        }
    }

    /**
     * @return boolean whether this analysis has more than one subpart.
     */
    public function has_subparts() {
        return count($this->responseclasses) > 1;
    }

    /**
     * @return boolean whether this analysis has (a subpart with) more than one
     *      response class.
     */
    public function has_response_classes() {
        foreach ($this->responseclasses as $partclasses) {
            if (count($partclasses) > 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return boolean whether this analysis has a response class more than one
     *      different acutal response.
     */
    public function has_actual_responses() {
        foreach ($this->responseclasses as $subpartid => $partclasses) {
            foreach ($partclasses as $responseclassid => $notused) {
                if (count($this->responses[$subpartid][$responseclassid]) > 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Analyse all the response data for for all the specified attempts at
     * this question.
     * @param $qubaids which attempts to consider.
     */
    public function analyse($qubaids) {
        // Load data.
        $dm = new question_engine_data_mapper();
        $questionattempts = $dm->load_attempts_at_question($this->questiondata->id, $qubaids);

        // Analyse it.
        foreach ($questionattempts as $qa) {
            $this->add_data_from_one_attempt($qa);
        }

        $this->loaded = true;
    }

    /**
     * Analyse the data from one question attempt.
     * @param question_attempt $qa the data to analyse.
     */
    protected function add_data_from_one_attempt(question_attempt $qa) {
        $blankresponse = question_classified_response::no_response();

        $partresponses = $qa->classify_response();
        foreach ($partresponses as $subpartid => $partresponse) {
            if (!isset($this->responses[$subpartid][$partresponse->responseclassid][$partresponse->response])) {
                $resp = new stdClass;
                $resp->count = 0;
                if (!is_null($partresponse->fraction)) {
                    $resp->fraction = $partresponse->fraction;
                } else {
                    $resp->fraction = $this->responseclasses[$subpartid]
                            [$partresponse->responseclassid]->fraction;
                }

                $this->responses[$subpartid][$partresponse->responseclassid]
                        [$partresponse->response] = $resp;
            }

            $this->responses[$subpartid][$partresponse->responseclassid]
                    [$partresponse->response]->count += 1;
        }
    }

    /**
     * Store the computed response analysis in the quiz_question_response_stats
     * table.
     * @param integer $quizstatisticsid the cached quiz statistics to load the
     * data corresponding to.
     * @return boolean true if cached data was found in the database and loaded,
     * otherwise false, to mean no data was loaded.
     */
    public function load_cached($quizstatisticsid) {
        $rows = get_records_select('quiz_question_response_stats',
                "quizstatisticsid = $quizstatisticsid AND questionid = {$this->questiondata->id}");
        if (!$rows) {
            return false;
        }

        foreach ($rows as $row) {
            $this->responses[$row->subqid][$row->aid][$row->response]->count = $row->rcount;
            $this->responses[$row->subqid][$row->aid][$row->response]->fraction = $row->credit;
        }
        $this->loaded = true;
        return true;
    }

    /**
     * Store the computed response analysis in the quiz_question_response_stats
     * table.
     * @param integer $quizstatisticsid the cached quiz statistics this correspons to.
     */
    public function store_cached($quizstatisticsid) {
        if (!$this->loaded) {
            throw new coding_exception('Question responses have not been analyised. Cannot store in the database.');
        }

        foreach ($this->responses as $subpartid => $partdata) {
            foreach ($partdata as $responseclassid => $classdata) {
                foreach ($classdata as $response => $data) {
                    $row = new stdClass;
                    $row->quizstatisticsid = $quizstatisticsid;
                    $row->questionid = $this->questiondata->id;
                    $row->subqid = $subpartid;
                    if ($responseclassid === '') {
                        $row->aid = null;
                    } else {
                        $row->aid = $responseclassid;
                    }
                    $row->response = addslashes($response);
                    $row->rcount = $data->count;
                    $row->credit = $data->fraction;
                    insert_record('quiz_question_response_stats', $row, false);
                }
            }
        }
    }
}