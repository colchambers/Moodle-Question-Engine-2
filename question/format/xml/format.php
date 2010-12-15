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
 * Code for exporting questions as Moodle XML.
 *
 * @package qformat_xml
 * @copyright 2010 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot . '/question/format.php');
require_once($CFG->libdir . '/xmlize.php');


/**
 * The Moodle XML import/export format.
 *
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qformat_xml extends qformat_default {

    function provide_import() {
        return true;
    }

    function provide_export() {
        return true;
    }

    // IMPORT FUNCTIONS START HERE

    /**
     * Translate human readable format name
     * into internal Moodle code number
     * @param string name format name from xml file
     * @return integer Moodle format code
     */
    function trans_format($name) {
        $name = trim($name);

        if ($name == 'moodle_auto_format') {
            return FORMAT_MOODLE;
        } else if ($name == 'html') {
            return FORMAT_HTML;
        } else if ($name == 'plain_text') {
            return FORMAT_PLAIN;
        } else if ($name == 'wiki_like') {
            return FORMAT_WIKI;
        } else if ($name == 'markdown') {
            return FORMAT_MARKDOWN;
        } else {
            return 0; // or maybe warning required
        }
    }

    /**
     * Translate human readable single answer option
     * to internal code number
     * @param string name true/false
     * @return integer internal code number
     */
    function trans_single($name) {
        $name = trim($name);
        if ($name == "false" || !$name) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * process text string from xml file
     * @param array $text bit of xml tree after ['text']
     * @return string processed text.
     */
    function import_text($text) {
        // quick sanity check
        if (empty($text)) {
            return '';
        }
        $data = $text[0]['#'];
        return addslashes(trim($data));
    }

    /**
     * return the value of a node, given a path to the node
     * if it doesn't exist return the default value
     * @param array xml data to read
     * @param array path path to node expressed as array
     * @param mixed default
     * @param boolean istext process as text
     * @param string error if set value must exist, return false and issue message if not
     * @return mixed value
     */
    function getpath($xml, $path, $default, $istext = false, $error = '') {
        foreach ($path as $index) {
            if (!isset($xml[$index])) {
                if (!empty($error)) {
                    $this->error($error);
                    return false;
                } else {
                    return $default;
                }
            }

            $xml = $xml[$index];
        }

        if ($istext) {
            if (!is_string($xml)) {
                $this->error(get_string('invalidxml', 'qformat_xml'));
            }
            $xml = addslashes(trim($xml));
        }

        return $xml;
    }


    /**
     * import parts of question common to all types
     * @param $question array question question array from xml tree
     * @return object question object
     */
    function import_headers($question) {
        // This routine initialises the question object
        $qo = $this->defaultquestion();

        // Question name
        $qo->name = $this->getpath($question,
                array('#', 'name', 0, '#', 'text', 0, '#'), '', true,
                get_string('xmlimportnoname', 'quiz'));
        $qo->questiontext = $this->getpath($question,
                array('#', 'questiontext', 0, '#', 'text', 0, '#'), '', true);
        $qo->questiontextformat = $this->trans_format($this->getpath(
                $question, array('#', 'questiontext', 0, '@', 'format'), ''));
        $qo->image = $this->getpath($question, array('#', 'image', 0, '#'), $qo->image);
        $image_base64 = $this->getpath($question, array('#', 'image_base64', '0', '#'), '');
        if (!empty($image_base64)) {
            $qo->image = $this->importimagefile($qo->image, stripslashes($image_base64));
        }
        $qo->generalfeedback = $this->getpath($question,
                array('#', 'generalfeedback', 0, '#', 'text', 0, '#'), $qo->generalfeedback, true);
        $qo->defaultmark = $this->getpath($question, array('#', 'defaultgrade', 0, '#'), $qo->defaultmark);
        $qo->penalty = $this->getpath($question, array('#', 'penalty', 0, '#'), $qo->penalty);

        // Fix problematic rounding from old files:
        if (abs($qo->penalty - 0.3333333) < 0.005) {
            $qo->penalty = 0.3333333;
        }

        return $qo;
    }

    /**
     * Import the common parts of a single answer
     * @param array answer xml tree for single answer
     * @return object answer object
     */
    function import_answer($answer) {
        $fraction = $this->getpath($answer, array('@', 'fraction'), 0);
        $text = $this->getpath($answer, array('#', 'text', 0, '#'), '', true);
        $feedback = $this->getpath($answer,
                array('#', 'feedback', 0, '#', 'text', 0, '#'), '', true);

        $ans = null;
        $ans->answer = $text;
        $ans->fraction = $fraction / 100;
        $ans->feedback = $feedback;
        return $ans;
    }

    /**
     * Import the common overall feedback fields.
     * @param object $question the part of the XML relating to this question.
     * @param object $qo the question data to add the fields to.
     * @param boolean $withshownumpartscorrect include the shownumcorrect field.
     */
    public function import_combined_feedback($qo, $questionxml, $withshownumpartscorrect = false) {
        $qo->correctfeedback = $this->getpath($questionxml,
                array('#', 'correctfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->partiallycorrectfeedback = $this->getpath($questionxml,
                array('#', 'partiallycorrectfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->incorrectfeedback = $this->getpath($questionxml,
                array('#', 'incorrectfeedback', 0, '#', 'text', 0, '#'), '', true);

        if ($withshownumpartscorrect) {
            $qo->shownumcorrect = array_key_exists('shownumcorrect', $questionxml['#']);

            // Backwards compatibility:
            if (array_key_exists('correctresponsesfeedback', $questionxml['#'])) {
                $qo->shownumcorrect = $this->trans_single($this->getpath($questionxml,
                        array('#', 'correctresponsesfeedback', 0, '#'), 1));
            }
        }
    }

    /**
     * Import a question hint
     * @param array $hintxml hint xml fragment.
     * @return object hint for storing in the database.
     */
    public function import_hint($hintxml) {
        if (array_key_exists('hintcontent', $hintxml['#'])) {
            // Backwards compatibility:

            $hint = new stdClass;
            $hint->hint = $this->getpath($hintxml,
                    array('#', 'hintcontent', 0, '#', 'text' ,0, '#'), '', true);
            $hint->shownumcorrect = $this->getpath($hintxml,
                    array('#', 'statenumberofcorrectresponses', 0, '#'), 0);
            $hint->clearwrong = $this->getpath($hintxml,
                    array('#', 'clearincorrectresponses', 0, '#'), 0);
            $hint->options = $this->getpath($hintxml,
                    array('#', 'showfeedbacktoresponses', 0, '#'), 0);

            return $hint;
        }

        $hint = new stdClass;
        $hint->hint = $this->getpath($hintxml, array('#', 'text', 0 , '#'), '', true);
        $hint->shownumcorrect = array_key_exists('shownumcorrect', $hintxml['#']);
        $hint->clearwrong = array_key_exists('clearwrong', $hintxml['#']);
        $hint->options = $this->getpath($hintxml, array('#', 'options', 0 , '#'), '', true);

        return $hint;
    }

    /**
     * Import all the question hints
     *
     * @param object $qo the question data that is being constructed.
     * @param array $hintsxml hints xml fragment.
     */
    public function import_hints($qo, $questionxml, $withparts = false, $withoptions = false) {
        if (!isset($questionxml['#']['hint'])) {
            return;
        }

        foreach ($questionxml['#']['hint'] as $hintxml) {
            $hint = $this->import_hint($hintxml);
            $qo->hint[] = $hint->hint;

            if ($withparts) {
                $qo->hintshownumcorrect[] = $hint->shownumcorrect;
                $qo->hintclearwrong[] = $hint->clearwrong;
            }

            if ($withoptions) {
                $qo->hintoptions[] = $hint->options;
            }
        }
    }

    /**
     * import multiple choice question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_multichoice($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // 'header' parts particular to multichoice
        $qo->qtype = MULTICHOICE;
        $single = $this->getpath($question, array('#', 'single', 0, '#'), 'true');
        $qo->single = $this->trans_single($single);
        $shuffleanswers = $this->getpath($question, array('#', 'shuffleanswers', 0, '#'), 'false');
        $qo->answernumbering = $this->getpath($question, array('#', 'answernumbering', 0, '#'), 'abc');
        $qo->shuffleanswers = $this->trans_single($shuffleanswers);

        // There was a time on the 1.8 branch when it could output an empty answernumbering tag, so fix up any found.
        if (empty($qo->answernumbering)) {
            $qo->answernumbering = 'abc';
        }

        // Run through the answers
        $answers = $question['#']['answer'];
        $acount = 0;
        foreach ($answers as $answer) {
            $ans = $this->import_answer($answer);
            $qo->answer[$acount] = $ans->answer;
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            ++$acount;
        }

        $this->import_combined_feedback($qo, $question, true);
        $this->import_hints($qo, $question, true);

        return $qo;
    }

    /**
     * Import cloze type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_multianswer($questions) {
        $questiontext = $questions['#']['questiontext'][0]['#']['text'];
        $qo = qtype_multianswer_extract_question($this->import_text($questiontext));

        // 'header' parts particular to multianswer
        $qo->qtype = MULTIANSWER;
        $qo->course = $this->course;
        $qo->generalfeedback = $this->getpath($questions,
                array('#', 'generalfeedback', 0, '#', 'text', 0, '#'), '', true);

        if (!empty($questions)) {
            $qo->name = $this->import_text($questions['#']['name'][0]['#']['text']);
        }

        $this->import_hints($qo, $question, true);

        return $qo;
    }

    /**
     * Import true/false type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_truefalse($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // 'header' parts particular to true/false
        $qo->qtype = TRUEFALSE;

        // In the past, it used to be assumed that the two answers were in the file
        // true first, then false. Howevever that was not always true. Now, we
        // try to match on the answer text, but in old exports, this will be a localised
        // string, so if we don't find true or false, we fall back to the old system.
        $first = true;
        $warning = false;
        foreach ($question['#']['answer'] as $answer) {
            $answertext = $this->getpath($answer, array('#', 'text', 0, '#'), '', true);
            $feedback = $this->getpath($answer, array('#', 'feedback', 0, '#', 'text', 0, '#'), '', true);
            if ($answertext != 'true' && $answertext != 'false') {
                // Old style file, assume order is true/false.
                $warning = true;
                if ($first) {
                    $answertext = 'true';
                } else {
                    $answertext = 'false';
                }
            }

            if ($answertext == 'true') {
                $qo->correctanswer = ($answer['@']['fraction'] == 100);
                $qo->feedbacktrue = $feedback;
            } else {
                $qo->correctanswer = ($answer['@']['fraction'] != 100);
                $qo->feedbackfalse = $feedback;
            }
            $first = false;
        }

        if ($warning) {
            $a = new stdClass;
            $a->questiontext = $qo->questiontext;
            $a->answer = get_string($qo->correctanswer ? 'true' : 'false', 'quiz');
            notify(get_string('truefalseimporterror', 'quiz', $a));
        }

        $this->import_hints($qo, $question);

        return $qo;
    }

    /**
     * Import short answer type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_shortanswer($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // header parts particular to shortanswer
        $qo->qtype = SHORTANSWER;

        // get usecase
        $qo->usecase = $this->getpath($question, array('#', 'usecase', 0, '#'), $qo->usecase);

        // rRn through the answers
        $answers = $question['#']['answer'];
        $acount = 0;
        foreach ($answers as $answer) {
            $ans = $this->import_answer($answer);
            $qo->answer[$acount] = $ans->answer;
            $qo->fraction[$acount] = $ans->fraction;
            $qo->feedback[$acount] = $ans->feedback;
            ++$acount;
        }

        $this->import_hints($qo, $question);

        return $qo;
    }

    /**
     * Import description type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_description($question) {
        // get common parts
        $qo = $this->import_headers($question);
        // header parts particular to shortanswer
        $qo->qtype = DESCRIPTION;
        $qo->defaultmark = 0;
        $qo->length = 0;
        return $qo;
    }

    /**
     * Import numerical type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_numerical($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // header parts particular to numerical
        $qo->qtype = NUMERICAL;

        // get answers array
        $answers = $question['#']['answer'];
        $qo->answer = array();
        $qo->feedback = array();
        $qo->fraction = array();
        $qo->tolerance = array();
        foreach ($answers as $answer) {
            // answer outside of <text> is deprecated
            $answertext = trim($this->getpath($answer, array('#', 0), ''));
            $qo->answer[] = $this->getpath($answer, array('#', 'text', 0, '#'), $answertext, true);
            if (empty($qo->answer)) {
                $qo->answer = '*';
            }
            $qo->feedback[] = $this->getpath($answer,
                    array('#', 'feedback', 0, '#', 'text', 0, '#'), '', true);
            $qo->tolerance[] = $this->getpath($answer, array('#', 'tolerance', 0, '#'), 0);

            // fraction as a tag is deprecated
            $fraction = $this->getpath($answer, array('@', 'fraction'), 0) / 100;
            $qo->fraction[] = $this->getpath($answer, array('#', 'fraction', 0, '#'), $fraction); // deprecated
        }

        // Get the units array
        $qo->unit = array();
        $units = $this->getpath($question, array('#', 'units', 0, '#', 'unit'), array());
        if (!empty($units)) {
            $qo->multiplier = array();
            foreach ($units as $unit) {
                $qo->multiplier[] = $this->getpath($unit, array('#', 'multiplier', 0, '#'), 1);
                $qo->unit[] = $this->getpath($unit, array('#', 'unit_name', 0, '#'), '', true);
            }
        }

        $this->import_hints($qo, $question);

        return $qo;
    }

    /**
     * Import matching type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_matching($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // header parts particular to matching
        $qo->qtype = MATCH;
        $qo->shuffleanswers = $this->trans_single($this->getpath($question,
                array('#', 'shuffleanswers', 0, '#'), 1));

        // get subquestions
        $subquestions = $question['#']['subquestion'];
        $qo->subquestions = array();
        $qo->subanswers = array();

        // run through subquestions
        foreach ($subquestions as $subquestion) {
            $qo->subquestions[] = $this->getpath($subquestion, array('#', 'text', 0, '#'), '', true);
            $qo->subanswers[] = $this->getpath($subquestion,
                    array('#', 'answer', 0, '#', 'text', 0, '#'), '', true);
        }

        $this->import_combined_feedback($qo, $question, true);
        $this->import_hints($qo, $question, true);

        return $qo;
    }

    /**
     * Import essay type question
     * @param array question question array from xml tree
     * @return object question object
     */
    function import_essay($question) {
        // get common parts
        $qo = $this->import_headers($question);

        // header parts particular to essay
        $qo->qtype = ESSAY;

        // get feedback
        $qo->feedback = $this->getpath($question,
                array('#', 'answer', 0, '#', 'feedback', 0, '#', 'text', 0, '#'), '', true);

        // get fraction - <fraction> tag is deprecated
        $qo->fraction = $this->getpath($question, array('@', 'fraction'), 0) / 100;
        $qo->fraction = $this->getpath($question, array('#', 'fraction', 0, '#'), $qo->fraction);

        return $qo;
    }

    function import_calculated($question) {
    // import numerical question

        // get common parts
        $qo = $this->import_headers($question);

        // header parts particular to numerical
        $qo->qtype = CALCULATED;

        // get answers array
        $answers = $question['#']['answer'];
        $qo->answers = array();
        $qo->feedback = array();
        $qo->fraction = array();
        $qo->tolerance = array();
        $qo->tolerancetype = array();
        $qo->correctanswerformat = array();
        $qo->correctanswerlength = array();
        $qo->feedback = array();
        foreach ($answers as $answer) {
            // answer outside of <text> is deprecated
            if (!empty($answer['#']['text'])) {
                $answertext = $this->import_text($answer['#']['text']);
            }
            else {
                $answertext = trim($answer['#'][0]);
            }
            if ($answertext == '') {
                $qo->answers[] = '*';
            } else {
                $qo->answers[] = $answertext;
            }
            $qo->feedback[] = $this->import_text($answer['#']['feedback'][0]['#']['text']);
            $qo->tolerance[] = $answer['#']['tolerance'][0]['#'];
            // fraction as a tag is deprecated
            if (!empty($answer['#']['fraction'][0]['#'])) {
                $qo->fraction[] = $answer['#']['fraction'][0]['#'];
            }
            else {
                $qo->fraction[] = $answer['@']['fraction'] / 100;
            }
            $qo->tolerancetype[] = $answer['#']['tolerancetype'][0]['#'];
            $qo->correctanswerformat[] = $answer['#']['correctanswerformat'][0]['#'];
            $qo->correctanswerlength[] = $answer['#']['correctanswerlength'][0]['#'];
        }
        // get units array
        $qo->unit = array();
        if (isset($question['#']['units'][0]['#']['unit'])) {
            $units = $question['#']['units'][0]['#']['unit'];
            $qo->multiplier = array();
            foreach ($units as $unit) {
                $qo->multiplier[] = $unit['#']['multiplier'][0]['#'];
                $qo->unit[] = $unit['#']['unit_name'][0]['#'];
            }
        }
        $datasets = $question['#']['dataset_definitions'][0]['#']['dataset_definition'];
        $qo->dataset = array();
        $qo->datasetindex= 0 ;
        foreach ($datasets as $dataset) {
            $qo->datasetindex++;
            $qo->dataset[$qo->datasetindex] = new stdClass();
            $qo->dataset[$qo->datasetindex]->status = $this->import_text($dataset['#']['status'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->name = $this->import_text($dataset['#']['name'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->type =  $dataset['#']['type'][0]['#'];
            $qo->dataset[$qo->datasetindex]->distribution = $this->import_text($dataset['#']['distribution'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->max = $this->import_text($dataset['#']['maximum'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->min = $this->import_text($dataset['#']['minimum'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->length = $this->import_text($dataset['#']['decimals'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->distribution = $this->import_text($dataset['#']['distribution'][0]['#']['text']);
            $qo->dataset[$qo->datasetindex]->itemcount = $dataset['#']['itemcount'][0]['#'];
            $qo->dataset[$qo->datasetindex]->datasetitem = array();
            $qo->dataset[$qo->datasetindex]->itemindex = 0;
            $qo->dataset[$qo->datasetindex]->number_of_items=$dataset['#']['number_of_items'][0]['#'];
            $datasetitems = $dataset['#']['dataset_items'][0]['#']['dataset_item'];
            foreach ($datasetitems as $datasetitem) {
                $qo->dataset[$qo->datasetindex]->itemindex++;
                $qo->dataset[$qo->datasetindex]->datasetitem[$qo->dataset[$qo->datasetindex]->itemindex] = new stdClass();
                $qo->dataset[$qo->datasetindex]->datasetitem[$qo->dataset[$qo->datasetindex]->itemindex]->itemnumber =  $datasetitem['#']['number'][0]['#']; //[0]['#']['number'][0]['#'] ; // [0]['numberitems'] ;//['#']['number'][0]['#'];// $datasetitems['#']['number'][0]['#'];
                $qo->dataset[$qo->datasetindex]->datasetitem[$qo->dataset[$qo->datasetindex]->itemindex]->value = $datasetitem['#']['value'][0]['#'] ;//$datasetitem['#']['value'][0]['#'];
            }
        }

        $this->import_hints($qo, $question);

        return $qo;
    }

    /**
     * This is not a real question type. It's a dummy type used to specify the
     * import category. The format is:
     * <question type="category">
     *     <category>tom/dick/harry</category>
     * </question>
     */
    function import_category($question) {
        $qo = new stdClass;
        $qo->qtype = 'category';
        $qo->category = $this->import_text($question['#']['category'][0]['#']['text']);
        return $qo;
    }

    /**
     * Parse the array of lines into an array of questions
     * this *could* burn memory - but it won't happen that much
     * so fingers crossed!
     * @param array of lines from the input file.
     * @return array (of objects) question objects.
     */
    function readquestions($lines) {
        // We just need it as one big string
        $text = implode($lines, ' ');
        unset($lines);

        // This converts xml to big nasty data structure
        // the 0 means keep white space as it is (important for markdown format)
        try {
            $xml = xmlize($text, 0, 'UTF-8', true);
        } catch (xml_format_exception $e){
            $this->error($e->getMessage(), '');
            return false;
        }
        // Set up array to hold all our questions
        $questions = array();

        // Iterate through questions
        foreach ($xml['quiz']['#']['question'] as $question) {
            $questiontype = $question['@']['type'];

            if ($questiontype == 'multichoice') {
                $qo = $this->import_multichoice($question);
            } else if ($questiontype == 'truefalse') {
                $qo = $this->import_truefalse($question);
            } else if ($questiontype == 'shortanswer') {
                $qo = $this->import_shortanswer($question);
            } else if ($questiontype == 'numerical') {
                $qo = $this->import_numerical($question);
            } else if ($questiontype == 'description') {
                $qo = $this->import_description($question);
            } else if ($questiontype == 'matching') {
                $qo = $this->import_matching($question);
            } else if ($questiontype == 'cloze') {
                $qo = $this->import_multianswer($question);
            } else if ($questiontype == 'essay') {
                $qo = $this->import_essay($question);
            } else if ($questiontype == 'calculated') {
                $qo = $this->import_calculated($question);
            } else if ($questiontype == 'category') {
                $qo = $this->import_category($question);

            } else {
                // Not a type we handle ourselves. See if the question type wants
                // to handle it.
                if (!$qo = $this->try_importing_using_qtypes(
                        $question, null, null, $questiontype)) {
                    $this->error(get_string('xmltypeunsupported', 'quiz', $questiontype));
                    $qo = null;
                }
            }

            // Stick the result in the $questions array
            if ($qo) {
                $questions[] = $qo;
            }
        }
        return $questions;
    }

    // EXPORT FUNCTIONS START HERE

    function export_file_extension() {
        return ".xml";
    }

    /**
     * Turn the internal question code into a human readable form
     * (The code used to be numeric, but this remains as some of
     * the names don't match the new internal format)
     * @param mixed $typeid Internal code
     * @return string question type string
     */
    function get_qtype($typeid) {
        switch($typeid) {
            case TRUEFALSE:
                return 'truefalse';
            case MULTICHOICE:
                return 'multichoice';
            case SHORTANSWER:
                return 'shortanswer';
            case NUMERICAL:
                return 'numerical';
            case MATCH:
                return 'matching';
            case DESCRIPTION:
                return 'description';
            case MULTIANSWER:
                return 'cloze';
            case ESSAY:
                return 'essay';
            case CALCULATED:
                return 'calculated';
            default:
                return false;
        }
    }

    /**
     * Convert internal Moodle text format code into
     * human readable form
     * @param int id internal code
     * @return string format text
     */
    function get_format($id) {
        switch($id) {
            case FORMAT_MOODLE:
                return 'moodle_auto_format';
            case FORMAT_HTML:
                return 'html';
            case FORMAT_PLAIN:
                return 'plain_text';
            case FORMAT_WIKI:
                return 'wiki_like';
            case FORMAT_MARKDOWN:
                return 'markdown';
            default:
                return 'unknown';
        }
    }

    /**
     * Convert internal single question code into
     * human readable form
     * @param int id single question code
     * @return string single question string
     */
    function get_single($id) {
        switch($id) {
            case 0:
                return 'false';
            case 1:
                return 'true';
            default:
                return 'unknown';
        }
    }

    /**
     * Generates <text></text> tags, processing raw text therein
     * @param string $raw the content to output.
     * @param int $indent the current indent level.
     * @param boolean $short stick it on one line.
     * @return string formatted text.
     */
    function writetext($raw, $indent = 0, $short = true) {
        $indent = str_repeat('  ', $indent);

        // if required add CDATA tags
        if (!empty($raw) && htmlspecialchars($raw) != $raw) {
            $raw = "<![CDATA[$raw]]>";
        }

        if ($short) {
            $xml = "$indent<text>$raw</text>\n";
        } else {
            $xml = "$indent<text>\n$raw\n$indent</text>\n";
        }

        return $xml;
    }

    function presave_process($content) {
        // Override to allow us to add xml headers and footers
        return '<?xml version="1.0" encoding="UTF-8"?>
<quiz>
' . $content . '</quiz>';
    }

    /**
     * Include an image encoded in base 64.
     * @param string $imagepath The location of the image file.
     * @return string xml code segment.
     */
    function writeimage($imagepath) {
        global $CFG;

        if (empty($imagepath)) {
            return '';
        }

        $courseid = $this->course->id;
        if (!$binary = file_get_contents(
                $CFG->dataroot . '/' . $courseid . '/' . $imagepath)) {
            return '';
        }

        return "    <image_base64>\n" .
                addslashes(base64_encode($binary)) . "\n" .
                "    </image_base64>\n";
    }

    /**
     * Turns question into an xml segment
     * @param object $question the question data.
     * @return string xml segment
     */
    function writequestion($question) {
        global $CFG, $QTYPES;

        $expout = '';

        // Add a comment linking this to the original question id.
        $expout .= "<!-- question: $question->id  -->\n";

        // Check question type
        if (!$questiontype = $this->get_qtype($question->qtype)) {
            // must be a plugin then, so just accept the name supplied
            $questiontype = $question->qtype;
        }

        // add opening tag
        // generates specific header for Cloze and category type question
        if ($question->qtype == 'category') {
            $categorypath = $this->writetext($question->category);
            $expout .= "  <question type=\"category\">\n";
            $expout .= "    <category>\n";
            $expout .= "        $categorypath\n";
            $expout .= "    </category>\n";
            $expout .= "  </question>\n";
            return $expout;

        } else if ($question->qtype != MULTIANSWER) {
            // for all question types except Close
            $name_text = $this->writetext($question->name, 3);
            $qtformat = $this->get_format($question->questiontextformat);
            $question_text = $this->writetext($question->questiontext, 3);
            $generalfeedback = $this->writetext($question->generalfeedback, 3);
            $expout .= "  <question type=\"$questiontype\">\n";
            $expout .= "    <name>\n";
            $expout .= $name_text;
            $expout .= "    </name>\n";
            $expout .= "    <questiontext format=\"$qtformat\">\n";
            $expout .= $question_text;
            $expout .= "    </questiontext>\n";
            $expout .= "    <generalfeedback>\n";
            $expout .= $generalfeedback;
            $expout .= "    </generalfeedback>\n";
            $expout .= "    <defaultgrade>{$question->defaultmark}</defaultgrade>\n";
            $expout .= "    <penalty>{$question->penalty}</penalty>\n";
            $expout .= "    <hidden>{$question->hidden}</hidden>\n";

        } else {
            // for Cloze type only
            $name_text = $this->writetext($question->name);
            $question_text = $this->writetext($question->questiontext);
            $generalfeedback = $this->writetext($question->generalfeedback);
            $expout .= "  <question type=\"$questiontype\">\n";
            $expout .= "    <name>$name_text</name>\n";
            $expout .= "    <questiontext>\n";
            $expout .= $question_text;
            $expout .= "    </questiontext>\n";
            $expout .= "    <generalfeedback>\n";
            $expout .= $generalfeedback;
            $expout .= "    </generalfeedback>\n";
        }

        // output depends on question type
        switch($question->qtype) {
        case 'category':
            // not a qtype really - dummy used for category switching
            break;

        case TRUEFALSE:
            $trueanswer = $question->options->answers[$question->options->trueanswer];
            $expout .= $this->write_answer(new question_answer(
                    'true', $trueanswer->fraction, $trueanswer->feedback));

            $falseanswer = $question->options->answers[$question->options->falseanswer];
            $expout .= $this->write_answer(new question_answer(
                    'false', $falseanswer->fraction, $falseanswer->feedback));
            break;

        case MULTICHOICE:
            $expout .= "    <single>" . $this->get_single($question->options->single) . "</single>\n";
            $expout .= "    <shuffleanswers>" . $this->get_single($question->options->shuffleanswers) . "</shuffleanswers>\n";
            $expout .= "    <answernumbering>{$question->options->answernumbering}</answernumbering>\n";
            $expout .= $this->write_combined_feedback($question->options);
            $expout .= $this->write_answers($question->options->answers);
            break;

        case SHORTANSWER:
            $expout .= "    <usecase>{$question->options->usecase}</usecase>\n";
            $expout .= $this->write_answers($question->options->answers);
            break;

        case NUMERICAL:
            foreach ($question->options->answers as $answer) {
                $expout .= $this->write_answer($answer,
                        "      <tolerance>$answer->tolerance</tolerance>\n");
            }

            $units = $question->options->units;
            if (count($units)) {
                $expout .= "<units>\n";
                foreach ($units as $unit) {
                    $expout .= "  <unit>\n";
                    $expout .= "    <multiplier>{$unit->multiplier}</multiplier>\n";
                    $expout .= "    <unit_name>{$unit->unit}</unit_name>\n";
                    $expout .= "  </unit>\n";
                }
                $expout .= "</units>\n";
            }
            break;

        case MATCH:
            $expout .= "    <shuffleanswers>" . $this->get_single($question->options->shuffleanswers) . "</shuffleanswers>\n";
            $expout .= $this->write_combined_feedback($question->options);
            foreach ($question->options->subquestions as $subquestion) {
                $expout .= "    <subquestion>\n";
                $expout .= $this->writetext($subquestion->questiontext, 3);
                $expout .= "      <answer>\n";
                $expout .= $this->writetext($subquestion->answertext, 4);
                $expout .= "      </answer>\n";
                $expout .= "    </subquestion>\n";
            }
            break;

        case DESCRIPTION:
            // Nothing else to do.
            break;

        case MULTIANSWER:
            $acount = 1;
            foreach ($question->options->questions as $question) {
                $thispattern = addslashes("{#".$acount."}");
                $thisreplace = $question->questiontext;
                $expout = ereg_replace($thispattern, $thisreplace, $expout);
                $acount++;
            }
            break;

        case ESSAY:
            // Nothing else to do.
            break;

        case CALCULATED:
            foreach ($question->options->answers as $answer) {
                $tolerance = $answer->tolerance;
                $tolerancetype = $answer->tolerancetype;
                $correctanswerlength= $answer->correctanswerlength ;
                $correctanswerformat= $answer->correctanswerformat;
                $percent = 100 * $answer->fraction;
                $expout .= "<answer fraction=\"$percent\">\n";
                // "<text/>" tags are an added feature, old files won't have them
                $expout .= "    <text>{$answer->answer}</text>\n";
                $expout .= "    <tolerance>$tolerance</tolerance>\n";
                $expout .= "    <tolerancetype>$tolerancetype</tolerancetype>\n";
                $expout .= "    <correctanswerformat>$correctanswerformat</correctanswerformat>\n";
                $expout .= "    <correctanswerlength>$correctanswerlength</correctanswerlength>\n";
                $expout .= "    <feedback>".$this->writetext($answer->feedback)."</feedback>\n";
                $expout .= "</answer>\n";
            }
            $units = $question->options->units;
            if (count($units)) {
                $expout .= "<units>\n";
                foreach ($units as $unit) {
                    $expout .= "  <unit>\n";
                    $expout .= "    <multiplier>{$unit->multiplier}</multiplier>\n";
                    $expout .= "    <unit_name>{$unit->unit}</unit_name>\n";
                    $expout .= "  </unit>\n";
                }
                $expout .= "</units>\n";
            }
            if (isset($question->options->datasets)&&count($question->options->datasets)) {// there should be
                $expout .= "<dataset_definitions>\n";
                foreach ($question->options->datasets as $def) {
                    $expout .= "<dataset_definition>\n";
                    $expout .= "    <status>".$this->writetext($def->status)."</status>\n";
                    $expout .= "    <name>".$this->writetext($def->name)."</name>\n";
                    $expout .= "    <type>calculated</type>\n";
                    $expout .= "    <distribution>".$this->writetext($def->distribution)."</distribution>\n";
                    $expout .= "    <minimum>".$this->writetext($def->minimum)."</minimum>\n";
                    $expout .= "    <maximum>".$this->writetext($def->maximum)."</maximum>\n";
                    $expout .= "    <decimals>".$this->writetext($def->decimals)."</decimals>\n";
                    $expout .= "    <itemcount>$def->itemcount</itemcount>\n";
                    if ($def->itemcount > 0) {
                        $expout .= "    <dataset_items>\n";
                        foreach ($def->items as $item) {
                              $expout .= "        <dataset_item>\n";
                              $expout .= "           <number>".$item->itemnumber."</number>\n";
                              $expout .= "           <value>".$item->value."</value>\n";
                              $expout .= "        </dataset_item>\n";
                        }
                        $expout .= "    </dataset_items>\n";
                        $expout .= "    <number_of_items>".$def-> number_of_items."</number_of_items>\n";
                     }
                    $expout .= "</dataset_definition>\n";
                }
                $expout .= "</dataset_definitions>\n";
            }
            break;

        default:
            // try support by optional plugin
            if (!$data = $this->try_exporting_using_qtypes($question->qtype, $question)) {
                notify(get_string('unsupportedexport', 'qformat_xml', $question->qtype));
            }
            $expout .= $data;
        }

        // Output any hints.
        $expout .= $this->write_hints($question);

        // close the question tag
        $expout .= "  </question>\n";

        return $expout;
    }

    public function write_answers($answers) {
        if (empty($answers)) {
            return;
        }
        $output = '';
        foreach ($answers as $answer) {
            $output .= $this->write_answer($answer);
        }
        return $output;
    }

    public function write_answer($answer, $extra = '') {
        $percent = $answer->fraction * 100;
        $output = '';
        $output .= "    <answer fraction=\"$percent\">\n";
        $output .= $this->writetext($answer->answer, 3);
        $output .= "      <feedback>\n";
        $output .= $this->writetext($answer->feedback, 4);
        $output .= "      </feedback>\n";
        $output .= $extra;
        $output .= "    </answer>\n";
        return $output;
    }

    public function write_hints($question) {
        if (empty($question->hints)) {
            return '';
        }

        $output = '';
        foreach ($question->hints as $hint) {
            $output .= $this->write_hint($hint);
        }
        return $output;
    }

    public function write_hint($hint) {
        $output = '';
        $output .= "    <hint>\n";
        $output .= '      ' . $this->writetext($hint->hint);
        if (!empty($hint->shownumcorrect)) {
            $output .= "      <shownumcorrect/>\n";
        }
        if (!empty($hint->clearwrong)) {
            $output .= "      <clearwrong/>\n";
        }
        if (!empty($hint->options)) {
            $output .= '      <options>' . htmlspecialchars($hint->options) . "</options>\n";
        }
        $output .= "    </hint>\n";
        return $output;
    }

    public function write_combined_feedback($questionoptions) {
        $output = "    <correctfeedback>
      {$this->writetext($questionoptions->correctfeedback)}    </correctfeedback>
    <partiallycorrectfeedback>
      {$this->writetext($questionoptions->partiallycorrectfeedback)}    </partiallycorrectfeedback>
    <incorrectfeedback>
      {$this->writetext($questionoptions->incorrectfeedback)}    </incorrectfeedback>\n";
        if (!empty($questionoptions->shownumcorrect)) {
            $output .= "    <shownumcorrect/>\n";
        }
        return $output;
    }
}
