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
 * Matching question renderer class.
 *
 * @package qtype_match
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Generates the output for matching questions.
 *
 * @copyright © 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_match_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();
        $response = $qa->get_last_qt_data();

        $choices = $this->format_choices($question);

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext(),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::start_tag('table', array('class' => 'answer'));
        $result .= html_writer::start_tag('tbody');

        $parity = 0;
        foreach ($stemorder as $key => $stemid) {

            $result .= html_writer::start_tag('tr', array('class' => 'r' . $parity));
            $fieldname = 'sub' . $key;

            $result .= html_writer::tag('td', $question->format_text($question->stems[$stemid]),
                    array('class' => 'text'));

            $classes = 'control';
            $feedbackimage = '';

            if (array_key_exists($fieldname, $response)) {
                $selected = $response[$fieldname];
            } else {
                $selected = 0;
            }

            $fraction = $selected && $selected == $question->get_right_choice_for($stemid);

            if ($options->feedback && $selected) {
                $classes .= ' ' . question_get_feedback_class($fraction);
                $feedbackimage = question_get_feedback_image($fraction);
            }

            $result .= html_writer::tag('td',
                    choose_from_menu($choices, $qa->get_qt_field_name('sub' . $key), $selected,
                            'choose', '', '0', true, $options->readonly) . $feedbackimage,
                    array('class' => $classes));

            $result .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
        }
        $result .= html_writer::end_tag('tbody');
        $result .= html_writer::end_tag('table');

        $result .= html_writer::end_tag('div'); // ablock

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div', $question->get_validation_error($response),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        return '';
    }

    protected function format_choices($question) {
        $choices = array();
        foreach ($question->get_choice_order() as $key => $choiceid) {
            $choices[$key] = strip_tags($question->format_text($question->choices[$choiceid]));
        }
        return $choices;
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $stemorder = $question->get_stem_order();

        $choices = $this->format_choices($question);
        $right = array();
        foreach ($stemorder as $key => $stemid) {
            $right[] = $question->format_text($question->stems[$stemid]) . ' – ' .
                    $choices[$question->get_right_choice_for($stemid)];
        }

        if (!empty($right)) {
            return get_string('correctansweris', 'qtype_match',
                    implode(', ', $right));
        }
    }
}