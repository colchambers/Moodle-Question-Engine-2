<?php
 /*
 *
 * @copyright &copy; 2007 The Open University
 * @author c.chambers@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package synch
 * 
 * The point of this file is to make working with the db easier. In time I might build an interface onto it but for now it's just a set of files 
 */
 
    require_once('../../../config.php');
    require_once($CFG->dirroot .'/_tools/synch/setup.php'); // Debug library
    require_once(dirname(__FILE__).'/lib.php');
    require_once($CFG->dirroot.'/synch/setup.php');
    require_once($CFG->dirroot.'/_tools/vital/lib.php');
    
    require_login();
    require_capability('moodle/site:doanything',get_context_instance(CONTEXT_SYSTEM));
    
    if(!isadmin()) {
        error('Not allowed to access this page');
    }

    global $Out;
    
    function test_print_course_tables(){
//        getAndPrintRecords('course');
//        getAndPrintRecords('course_sections;');
//        getAndPrintRecords('course_modules;');
        getAndPrintRecords('block;');
    }
//    test_print_course_tables();

    function test_print_user(){
        
    }
    
    function test_print_user_preferences(){
        global $CFG;
        
        $sql = 'SELECT * FROM '.$CFG->prefix.'user_preferences WHERE userid=3 and name=\'studyplan_allweeks\'';
        test_get_records_sql($sql);
        
    }
    test_print_user_preferences();
    

    $tables = array();
    // ou wiki tables
//  $tables[] = 'ouwiki_pages';
//  $tables[] = 'ouwiki_sections';
//  $tables[] = 'ouwiki_versions';
//  $tables[] = 'ouwiki_links';  

    
    $tables[] = 'user';
//  $tables[] = 'user_info_data';
//  $tables[] = 'user_info_field';
//  $tables[] = 'course_extended_meta';   

//    $tables[] = 'modules';
    test_print_tables($tables);

    test_print_record_as_object('user_preferences', 1);

                    
    
    $oci_prefix = 'oci_20091023_sr1_mdl_';
//    test_print_tables($tables, $oci_prefix);
//    test_list_tables('cat');

//    test_list_tables('user');
    $Out->append('Script completed');
    $Out->flush();
    //print_footer('none');
?>
