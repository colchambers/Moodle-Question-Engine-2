<?php
 /*
 *
 * @copyright &copy; 2007 The Open University
 * @author c.chambers@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package synch
 * 
 * Stores methods local to the moodle distribution. Methods contain snippets of
 * code called from variuos places within moodle to make merging easier. 
 */
 
 /*
  * We're using some dev tool so we need to set some config values that
  * are relevant
  */
// ou specific begins
$CFG->devdeletecourses = true;
$CFG->devservername = 'OFOUME';
// ou specific ends

 
 /// Delete the specified courses
 function cc5983_course_delete_courses($context=null, $deletecourses=null){
    global $CFG;
    global $Out;
    
    // ADoes the config allow deleting in this way?
    if (!isset($CFG->devdeletecourses) || !$CFG->devdeletecourses){
    	return false;
    }
    
    // Is the delete flag set?
    if( empty($deletecourses) || !$deletecourses){
    	return false;
    }
    
    // Are there any courses to delete
    if(!($data = data_submitted()) || !confirm_sesskey()) {   // Some courses are being moved
        return false;
    }
    
    // user must have category update to perform this
    require_capability('moodle/category:update', $context);
    
    $courses = array(); 
    foreach ( $data as $key => $value ) {
        
        if (preg_match('/^c\d+$/', $key)) {
            $course = get_record("course", "id", substr($key, 1));
            if(!empty($course)){
                delete_course($course->id, false);
            
                // MDL-9983
                events_trigger('course_deleted', $course);
            }
        }
        
    } 
    
    fix_course_sortorder(); //update course count in categories
        
 }
 
 /*
  * Print a delete course submit button
  */
 function cc5983_course_print_delete_course($numcourses=0){
    global $CFG;
    if ((isset($CFG->devdeletecourses) && $CFG->devdeletecourses) &&
            has_capability('moodle/category:update', get_context_instance(CONTEXT_SYSTEM, SITEID)) and $numcourses > 1
            && function_exists('print_submit_button')) { 
        print_submit_button('deletecourses', 'Delete Selected Courses', null);
    } 
 }
?>
