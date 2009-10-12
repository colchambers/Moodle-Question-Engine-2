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
 * This file the Moodle question engine.
 *
 * @package moodlecore
 * @subpackage questionengine
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/compatibility.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once(dirname(__FILE__) . '/testquestiontype.php');
require_once(dirname(__FILE__) . '/../interaction/deferredfeedback/model.php');


/**
 * This static class provides access to the other question engine classes.
 *
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_engine {
    /**
     * 
     * @param $owningplugin
     * @return question_usage_by_activity
     */
    public static function make_questions_usage_by_activity($owningplugin) {
        return new question_usage_by_activity($owningplugin);
    }

    public static function load_questions_usage_by_activity($attemptid) {
        
    }

    /**
     * Get the question type class for a particular question type.
     * @param string $typename the question type name. For example 'multichoice' or 'shortanswer'.
     * @return default_questiontype the corresponding question type class.
     */
    public static function get_qtype($typename) {
        global $QTYPES;
        return $QTYPES[$typename];
    }
}


/**
 * An enumration representing the states a question can be in after a step.
 *
 * With some useful methods to help manipulate states.
 *
 * @copyright © 2006 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_state {
    const NOT_STARTED = -1;
    const UNPROCESSED = 0;
    const INCOMPLETE = 1;
    const COMPLETE = 2;
    const NEEDS_GRADING = 16;
    const FINISHED = 17;
    const GAVE_UP = 18;
    const GRADED_INCORRECT = 24;
    const GRADED_PARTCORRECT = 25;
    const GRADED_CORRECT = 26;
    const FINISHED_COMMENTED = 49;
    const GAVE_UP_COMMENTED = 50;
    const MANUALLY_GRADED_INCORRECT = 56;
    const MANUALLY_GRADED_PARTCORRECT = 57;
    const MANUALLY_GRADED_CORRECT = 58;

    public static function is_active($state) {
        return $state == self::INCOMPLETE || $state == self::COMPLETE;
    }

    public static function is_finished($state) {
        return !in_array($state,
                array(self::NOT_STARTED, self::INCOMPLETE, self::COMPLETE));
    }

    public static function graded_state_for_grade($grade) {
        if ($grade < 0.0000001) {
            return self::GRADED_INCORRECT;
        } else if ($grade > 0.9999999) {
            return self::GRADED_CORRECT;
        } else {
            return self::GRADED_PARTCORRECT;
        }
    }

    public static function manually_graded_state_for_other_state($state, $grade) {
        $oldstate = $state & 0xFFFFFFDF;
        switch ($oldstate) {
            case self::FINISHED:
                return FINISHED_COMMENTED;
            case self::GAVE_UP:
                return self::GAVE_UP_COMMENTED;
            case self::GRADED_INCORRECT:
            case self::GRADED_PARTCORRECT:
            case self::GRADED_CORRECT:
                return self::graded_state_for_grade($grade) + 32;
            default:
                throw new Exception('Illegal state transition.');
        }
    }
}


/**
 * This class contains all the options that controls how a question is displayed.
 *
 * @copyright © 2006 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_display_options {
    public $flags = QUESTION_FLAGSSHOWN;
    public $readonly = false;
    public $feedback = false;
    public $correct_responses = false;
    public $generalfeedback = false;
    public $responses = true;
    public $scores = true;
    public $history = false;
}


/**
 * This class keeps track of a group of questions that are being attempted,
 * and which state each one is currently in.
 *
 * A quiz attempt or a lesson attempt could use an instance of this class to
 * keep track of all the questions in the attempt and process student submissions.
 * It is basically a collection of {@question_attempt} objects.
 *
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_usage_by_activity {
    protected $id = null;
    protected $preferredmodel = null;
    protected $owningplugin;
    protected $questionattempts = array();

    public function __construct($owningplugin) {
        $this->owningplugin = $owningplugin;
    }

    public function set_preferred_interaction_model($model) {
        $this->preferredmodel = $model;
    }

    public function get_id() {
        if (is_null($this->id)) {
            $this->id = random_string(10);
        }
        return $this->id;
    }

    public function add_question($question) {
        $qa = new question_attempt($question);
        if (count($this->questionattempts) == 0) {
            $this->questionattempts[1] = $qa;
        } else {
            $this->questionattempts[] = $qa;
        }
        $qa->set_number_in_usage(end(array_keys($this->questionattempts)));
        return $qa->get_number_in_usage();
    }

    public function question_count() {
        return count($this->questionattempts);
    }

    public function get_question_attempt($qnumber) {
        if (!array_key_exists($qnumber, $this->questionattempts)) {
            throw new exception("There is no question_attempt number $qnumber in this attempt.");
        }
        return $this->questionattempts[$qnumber];
    }

    public function get_question_state($qnumber) {
        return $this->get_question_attempt($qnumber)->get_state();
    }

    public function get_question_grade($qnumber) {
        return $this->get_question_attempt($qnumber)->get_grade();
    }

    public function render_question($qnumber, $options) {
        return $this->get_question_attempt($qnumber)->render($options);
    }

    public function get_field_prefix($qnumber) {
        $this->get_question_attempt($qnumber); // Validate $qnumber.
        return 'q' . $this->get_id() . ',' . $qnumber . '_';
    }

    public function start_all_questions() {
        foreach ($this->questionattempts as $qa) {
            $qa->start($this->preferredmodel);
        }
    }

    public function extract_responses($qnumber, $postdata) {
        $prefix = $this->get_field_prefix($qnumber);
        $prefixlen = strlen($prefix);
        $submitteddata = array();
        foreach ($postdata as $name => $value) {
            if (substr($name, 0, $prefixlen) == $prefix) {
                $submitteddata[substr($name, $prefixlen)] = $value;
            }
        }
        return $submitteddata;
    }

    public function process_action($qnumber, $submitteddata) {
        $this->get_question_attempt($qnumber)->process_action($submitteddata);
    }

    public function finish_all_questions() {
        foreach ($this->questionattempts as $qa) {
            $qa->finish();
        }
    }

    public function manual_grade($qnumber, $comment, $grade) {
        $this->get_question_attempt($qnumber)->manual_grade($grade, $comment);
    }
}

/**
 * Tracks an attempt at one particular question.
 *
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempt {
    private $id = null;
    private $numberinusage = null;
    private $interactionmodel = null;
    private $question;
    private $qtype;
    private $maxgrade;
    private $responsesummary = '';
    private $steps = array();
    private $flagged = false;
    private $pendingstep = null;

    const KEEP = true;
    const DISCARD = false;

    public function __construct($question) {
        $this->question = $question;
        $this->qtype = question_engine::get_qtype($question->qtype);
        if (!empty($question->maxgrade)) {
            $this->maxgrade = $question->maxgrade;
        } else {
            $this->maxgrade = $question->defaultgrade;
        }
    }

    public function set_number_in_usage($qnumber) {
        $this->numberinusage = $qnumber;
    }

    public function get_number_in_usage() {
        return $this->numberinusage;
    }

    public function set_fagged($flagged) {
        $this->flagged = $flagged;
    }

    public function is_flagged() {
        return $this->flagged;
    }

    public function get_last_step() {
        if (count($this->steps) == 0) {
            return new question_null_step();
        }
        return end($this->steps);
    }

    public function get_state() {
        return $this->get_last_step()->get_state();
    }

    public function get_grade() {
        $grade = $this->get_last_step()->get_grade();
        if (!is_null($grade)) {
            $grade *= $this->maxgrade;
        }
        return $grade;
    }

    public function get_question() {
        return $this->question;
    }

    public function get_qtype() {
        return $this->qtype;
    }

    public function render($options) {
        $qoutput = renderer_factory::get_renderer('core', 'question');
        $qimoutput = $this->interactionmodel->get_renderer();
        $qtoutput = $this->qtype->get_renderer($this->question);
        return $qoutput->question($this, $qimoutput, $qtoutput, $options);
    }

    protected function add_step($state) {
        $this->steps[] = $state;
    }

    public function start($preferredmodel) {
        $this->interactionmodel =
                $this->qtype->get_interaction_model($this, $preferredmodel);
        $firststep = new question_attempt_step();
        $firststep->set_state(question_state::INCOMPLETE);
        $this->interactionmodel->init_first_step($firststep);
        $this->add_step($firststep);
    }

    public function process_action($submitteddata) {
        $pendingstep = new question_attempt_step($submitteddata);
        if ($this->interactionmodel->process_action($pendingstep) == self::KEEP) {
            $this->add_step($pendingstep);
        }
    }

    public function finish() {
        $this->process_action(array('!finish' => 1));
    }

    public function manual_grade($comment, $grade) {
        $submitteddata = array('!comment' => $comment);
        if (!is_null($grade)) {
            $submitteddata['!grade'] = $grade;
            $submitteddata['!maxgrade'] = $this->maxgrade;
        }
        $this->process_action($submitteddata);
    }
}


/**
 * Stores one step in a {@link question_attempt}.
 *
 * @copyright © 2006 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_attempt_step {
    private $id = null;
    private $state = question_state::UNPROCESSED;
    private $grade = null;
    private $timestamp;
    private $userid;
    private $data;

    public function __construct($data = array(), $timestamp = null, $userid = null) {
        global $USER;
        $this->data = $data;
        if (is_null($timestamp)) {
            $this->timestamp = time();
        } else {
            $this->timestamp = $timestamp;
        }
        if (is_null($userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid = $userid;
        }
    }

    public function get_state() {
        return $this->state;
    }

    public function set_state($state) {
        $this->state = $state;
    }

    public function get_qt_var($name) {
        return $this->data[$name];
    }

    public function set_qt_var($name) {
        if ($name[0] != '_') {
            throw new Exception('Cannot set question type data ' . $name . ' on an attempt step. You can only set variables with names begining with _.');
        }
        return $this->data[$name];
    }

    public function get_qt_data() {
        $result = array();
        foreach ($this->data as $name => $value) {
            if ($name[0] != '!') {
                $result[$name] = $value;
            }
        }
        return $result;
    }

    public function has_im_var($name) {
        return array_key_exists('!' . $name, $this->data);
    }

    public function get_im_var($name) {
        return $this->data['!' . $name];
    }

    public function set_im_var($name) {
        if ($name[0] != '_') {
            throw new Exception('Cannot set question type data ' . $name . ' on an attempt step. You can only set variables with names begining with _.');
        }
        return $this->data['!' . $name];
    }

    public function get_grade() {
        return $this->grade;
    }

    public function set_grade($grade) {
        $this->grade = $grade;
    }
}


/**
 * A null {@link question_attempt_step} returned from
 * {@link question_attempt::get_last_step()} etc. when a an attempt has just been
 * started and there is no acutal step.
 *
 * @copyright © 2006 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_null_step {
    public function get_state() {
        return question_state::NOT_STARTED;
    }

    public function set_state($state) {
        throw new Exception('This question has not been started.');
    }

    public function get_grade() {
        return NULL;
    }
}


/**
 * The base class for question interaction models.
 *
 * A question interaction model controls the flow of actions a student can
 * take as they work through a question, and later, as a teacher manually grades it.
 *
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_interaction_model {
    protected $qa;

    public function __construct(question_attempt $qa) {
        $this->qa = $qa;
    }

    public function get_renderer() {
        list($ignored, $type) = explode('_', get_class($this), 3);
        return renderer_factory::get_renderer('qim_' . $type);
    }

    public function init_first_step($step) {
    }

    public abstract function process_action(question_attempt_step $pendingstep);

    public function process_comment(question_attempt_step $pendingstep) {
        $laststep = $this->qa->get_last_step();

        if ($pendingstep->has_im_var('grade')) {
            $pendingstep->set_grade($pendingstep->get_im_var('grade') /
                    $pendingstep->get_im_var('maxgrade'));
        }
        $pendingstep->set_state(question_state::manually_graded_state_for_other_state(
                $laststep->get_state(), $pendingstep->get_grade()));
        return question_attempt::KEEP;
    }
}


