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
 * True-false question definition class.
 *
 * @package qtype_match
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Represents a matching question.
 *
 * @copyright © 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_match_question extends question_graded_automatically {
    /** @var boolean Whether the question stems should be shuffled. */
    public $shufflestems;
    /** @var array of question stems. */
    public $stems;
    /** @var array of choices that can be matched to each stem. */
    public $choices;
    /** @var array index of the right choice for each stem. */
    public $right;

    /** @var array shuffled stem indexes. */
    protected $stemorder;
    /** @var array shuffled choice indexes. */
    protected $choiceorder;

    public function init_first_step(question_attempt_step $step) {
        if ($step->has_qt_var('_stemorder')) {
            $this->stemorder = explode(',', $step->get_qt_var('_stemorder'));
            $choiceorder = explode(',', $step->get_qt_var('_choiceorder'));
        } else {
            $this->stemorder = array_keys($this->stems);
            $choiceorder = array_keys($this->choices);
            if ($this->shufflestems) {
                shuffle($this->stemorder);
            }
            shuffle($choiceorder);
            $step->set_qt_var('_stemorder', implode(',', $this->stemorder));
            $step->set_qt_var('_choiceorder', implode(',', $choiceorder));
        }
        $this->choiceorder = array();
        foreach ($choiceorder as $key => $value) {
            $this->choiceorder[$key + 1] = $value;
        }
    }

    /**
     * @param integer $key stem number
     * @return string the question-type variable name.
     */
    protected function field($key) {
        return 'sub' . $key;
    }

    public function get_expected_data() {
        $vars = array();
        foreach ($this->stemorder as $key => $notused) {
            $vars[$this->field($key)] = PARAM_INTEGER;
        }
        return $vars;
    }

    public function get_correct_response() {
        $response = array();
        foreach ($this->stemorder as $key => $stemid) {
            $response[$this->field($key)] = $this->get_right_choice_for($stemid);
        }
        return $response;
    }

    public function get_right_choice_for($stemid) {
        foreach ($this->choiceorder as $choicekey => $choiceid) {
            if ($this->right[$stemid] == $choiceid) {
                return $choicekey;
            }
        }
    }

    public function is_complete_response(array $response) {
        $complete = true;
        foreach ($this->stemorder as $key => $stemid) {
            $complete = $complete && !empty($response[$this->field($key)]);
        }
        return $complete;
    }

    public function is_gradable_response(array $response) {
        foreach ($this->stemorder as $key => $stemid) {
            if (!empty($response[$this->field($key)])) {
                return true;
            }
        }
        return false;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->stemorder as $key => $notused) {
            $fieldname = $this->field($key);
            if (!array_key_exists($fieldname, $newresponse) == array_key_exists($fieldname, $prevresponse) &&
                    (!array_key_exists($fieldname, $prevresponse) || $newresponse[$fieldname] == $prevresponse[$fieldname])) {
                return false;
            }
        }
        return true;
    }

    public function grade_response(array $response) {
        $fraction = $this->get_num_right($response) / count($this->stemorder);
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function get_num_right(array $response) {
        $numright = 0;
        foreach ($this->stemorder as $key => $stemid) {
            $fieldname = $this->field($key);
            if (!array_key_exists($fieldname, $response)) {
                continue;
            }

            $choice = $response[$fieldname];
            if ($this->choiceorder[$choice] == $this->right[$stemid]) {
                $numright += 1;
            }
        }
        return $numright;
    }

    public function get_stem_order() {
        return $this->stemorder;
    }

    public function get_choice_order() {
        return $this->choiceorder;
    }
}