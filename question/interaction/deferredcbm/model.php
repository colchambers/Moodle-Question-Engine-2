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
 * Question iteraction model that is like the deferred feedback model, but with
 * certainly based marking. That is, in addition to the other controls, there are
 * where the student can indicate how certain they are that their answer is right.
 *
 * @package qim_deferredcbm
 * @copyright 2009 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Question interaction model for deferred feedback with certainty based marking.
 *
 * The student enters their response during the attempt, and it is saved. Later,
 * when the whole attempt is finished, their answer is graded.
 *
 * @copyright © 2006 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qim_deferredcbm extends qim_deferredfeedback {
    const LOW = 1;
    const MED = 2;
    const HIGH = 3;
    const LOW_OFFSET = 0;
    const LOW_FACTOR = 0.333333333333333;
    const MED_OFFSET = -0.666666666666667;
    const MED_FACTOR = 1.333333333333333;
    const HIGH_OFFSET = -2;
    const HIGH_FACTOR = 3;

    public static $certainties = array(self::LOW, self::MED, self::HIGH);

    protected static $factor = array(
        self::LOW => self::LOW_FACTOR,
        self::MED => self::MED_FACTOR,
        self::HIGH => self::HIGH_FACTOR,
    );

    protected static $offset = array(
        self::LOW => self::LOW_OFFSET,
        self::MED => self::MED_OFFSET,
        self::HIGH => self::HIGH_OFFSET,
    );

    protected function adjust_fraction($fraction, $certainty) {
        return self::$offset[$certainty] + self::$factor[$certainty] * $fraction;
    }

    public function get_min_fraction() {
        return $this->adjust_fraction(parent::get_min_fraction(), self::HIGH);
    }

    public function get_expected_data() {
        return array('certainty' => PARAM_INT);
    }

    public function process_action(question_attempt_step $pendingstep) {
        if ($pendingstep->has_im_var('comment')) {
            return $this->process_comment($pendingstep);
        } else if ($pendingstep->has_im_var('finish')) {
            return $this->process_finish($pendingstep);
        } else {
            return $this->process_save($pendingstep);
        }
    }

    protected function is_same_response($pendingstep) {
        return parent::is_same_response($pendingstep) &&
                $this->qa->get_last_im_var('certainty') == $pendingstep->get_im_var('certainty');
    }

    protected function is_complete_response($pendingstep) {
        return parent::is_complete_response($pendingstep) && $pendingstep->has_im_var('certainty');
    }

    public function process_finish(question_attempt_step $pendingstep) {
        $status = parent::process_finish($pendingstep);
        if ($status == question_attempt::KEEP) {
            $fraction = $pendingstep->get_fraction();
            if ($this->qa->get_last_step()->has_im_var('certainty')) {
                $certainty = $this->qa->get_last_step()->get_im_var('certainty');
            } else {
                $certainty = self::LOW;
                $pendingstep->set_im_var('_assumedcertainty', $certainty);
            }
            if (!is_null($fraction)) {
                $pendingstep->set_fraction($this->adjust_fraction($fraction, $certainty));
            }
        }
        return $status;
    }
}