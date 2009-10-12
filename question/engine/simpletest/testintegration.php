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
 * This file contains integration tests for the Moodle question engine.
 *
 * These tests exercise the system working as a whole, rather than individual units.
 *
 * @package question-engine
 * @copyright © 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../lib.php');

class question_engine_integration_test extends UnitTestCase {

    public function setUp() {
        
    }

    public function tearDown() {
        
    }

    public function make_a_truefalse_question() {
        global $USER;

        $tf = new question_truefalse();
        $tf->id = 0;
        $tf->category = 0;
        $tf->parent = 0;
        $tf->name = 'True/false question';
        $tf->questiontext = 'The answer is true.';
        $tf->questiontextformat = FORMAT_HTML;
        $tf->image = '';
        $tf->generalfeedback = 'You should have selected true.';
        $tf->defaultgrade = 1;
        $tf->penalty = 1;
        $tf->qtype = question_engine::get_qtype('truefalse');
        $tf->length = 1;
        $tf->stamp = make_unique_id_code();
        $tf->version = make_unique_id_code();
        $tf->hidden = 0;
        $tf->timecreated = time();
        $tf->timemodified = time();
        $tf->createdby = $USER->id;
        $tf->modifiedby = $USER->id;

        $tf->rightanswer = true;
        $tf->truefeedback = 'This is the right answer.';
        $tf->falsefeedback = 'This is the wrong answer.';

        return $tf;
    }

    public function test_delayed_feedback_truefalse() {
        // Create a true-false question with correct answer true.
        $tf = $this->make_a_truefalse_question();
        $displayoptions = new question_display_options();

        // Start a delayed feedback attempt and add the question to it.
        $tf->maxmark = 2;
        $quba = question_engine::make_questions_usage_by_activity('unit_test');
        $quba->set_preferred_interaction_model('delayedfeedback');
        $qnumber = $quba->add_question($tf);
        // Different from $tf->id since the same question may be used twice in
        // the same attempt.

        // Verify.
        $this->assertEqual($qnumber, 1);
        $this->assertEqual($quba->question_count(), 1);
        $this->assertEqual($quba->get_question_state($qnumber), question_state::NOT_STARTED);

        // Begin the attempt. Creates an initial state for each question.
        $quba->start_all_questions();

        // Output the question in the initial state.
        $html = $quba->render_question($qnumber, $displayoptions);

        // Verify.
        $this->assertEqual($quba->get_question_state($qnumber), question_state::INCOMPLETE);
        $this->assertNull($quba->get_question_mark($qnumber));
        $this->assertPattern('/' . preg_quote($tf->questiontext) . '/', $html);

        // Simulate some data submitted by the student.
        $prefix = $quba->get_field_prefix($qnumber);
        $answername = $prefix . 'answer';
        $getdata = array(
            $answername => 1,
            'irrelevant' => 'should be ignored',
        );
        $submitteddata = $quba->extract_responses($qnumber, $getdata);

        // Verify.
        $this->assertEqual(array('answer' => 1), $submitteddata);

        // Process the data extracted for this question.
        $quba->process_action($qnumber, $submitteddata);
        $html = $quba->render_question($qnumber, $displayoptions);

        // Verify.
        $this->assertEqual($quba->get_question_state($qnumber), question_state::COMPLETE);
        $this->assertNull($quba->get_question_mark($qnumber));
        $this->assert(new ContainsTagWithAttributes('input',
                array('name' => $answername, 'value' => 1)), $html);
        $this->assertNoPattern('/class=\"correctness/', $html);

        // Process the same data again, check it does not create a new step.
        $numsteps = $quba->get_question_attempt($qnumber)->get_num_steps();
        $quba->process_action($qnumber, $submitteddata);
        $this->assertEqual($quba->get_question_attempt($qnumber)->get_num_steps(), $numsteps);

        // Process different data, check it creates a new step.
        $quba->process_action($qnumber, array('answer' => 0));
        $this->assertEqual($quba->get_question_attempt($qnumber)->get_num_steps(), $numsteps + 1);
        $this->assertEqual($quba->get_question_state($qnumber), question_state::COMPLETE);

        // Change back, check it creates a new step.
        $quba->process_action($qnumber, array('answer' => 1));
        $this->assertEqual($quba->get_question_attempt($qnumber)->get_num_steps(), $numsteps + 2);

        // Finish the attempt.
        $quba->finish_all_questions();
        $html = $quba->render_question($qnumber, $displayoptions);

        // Verify.
        $this->assertEqual($quba->get_question_state($qnumber), question_state::GRADED_CORRECT);
        $this->assertEqual($quba->get_question_mark($qnumber), 2);
        $this->assertPattern(
                '/' . preg_quote(get_string('correct', 'question')) . '/',
                $html);

        // Process a manual comment.
        $quba->manual_grade($qnumber, 1, 'Not good enough!');
        $html = $quba->render_question($qnumber, $displayoptions);

        // Verify.
        $this->assertEqual($quba->get_question_state($qnumber), question_state::MANUALLY_GRADED_PARTCORRECT);
        $this->assertEqual($quba->get_question_mark($qnumber), 1);
        $this->assertPattern('/' . preg_quote('Not good enough!') . '/', $html);

        // Now change the correct answer to the question, and regrade.
        $tf->rightanswer = false;
        $html = $quba->regrade_all_questions($qnumber);

        // Verify.
        $this->assertEqual($quba->get_question_state($qnumber), question_state::MANUALLY_GRADED_PARTCORRECT);
        $this->assertEqual($quba->get_question_mark($qnumber), 1);
        $numsteps = $quba->get_question_attempt($qnumber)->get_num_steps();
        $autogradedstep = $quba->get_question_attempt($qnumber)->get_step($numsteps - 2);
        $this->assertWithinMargin($autogradedstep->get_fraction(), 0, 0.0000001);
    }

}