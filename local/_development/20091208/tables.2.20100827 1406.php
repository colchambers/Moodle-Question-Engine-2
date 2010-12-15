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
    
    $score_prefix = 'scr_20100326_sr1_';
    
    $tables = array();
    // tables
//    $tables[] = 'access';
//    $tables[] = 'actions';
//    $tables[] = 'context';
//    $tables[] = 'actions_aid';
//    $tables[] = 'users';
//    $tables[] = 'users_roles';
    $tables[] = 'role';
    $tables[] = 'role_assignments';
//    $tables[] = 'permission';
//    $tables[] = 'node_access';
//    $tables[] = 'menu_router';
//    $tables[] = 'menu_links';
//    $tables[] = 'system';

//    $tables[] = 'authmap';
    
//    test_print_tables($tables, $score_prefix);
//    test_list_tables('', 'listing tables', $score_prefix);
test_print_tables($tables);
// test_list_tables('context', 'listing tables');

    function test_print_forumng_showreaders(){
        global $Out;
        
        $context = new object();
    $context->instanceid = 746;
    $context->id = 934;
    $context->contextlevel = 70;
    $context->path = '/1/4/880/934';
    $context->depth = 4;
    
    $context_ids = explode('/', $context->path);
    array_shift($context_ids);

    $sql = 'SELECT 
                    fr.id, u.id as u_id,u.username as u_username,u.firstname as u_firstname,
                    u.lastname as u_lastname,u.picture as u_picture,u.url as u_url,u.imagealt as u_imagealt,
                    u.idnumber as u_idnumber, fr.time, u.idnumber AS u_idnumber 
                FROM 
                    vle_20091023_sr1_mdl_forumng_read fr 
                    INNER JOIN vle_20091023_sr1_mdl_user u ON u.id = fr.userid 
                    INNER JOIN vle_20091023_sr1_mdl_groups_members gm ON gm.userid=fr.userid 
                    INNER JOIN vle_20091023_sr1_mdl_groups g ON gm.groupid = g.id
                    INNER JOIN vle_20091023_sr1_mdl_role_assignments ra ON ra.userid = u.id  
                WHERE 
                    fr.discussionid = 6 
                    AND ra.contextid = '.$context->id.'
                ORDER BY fr.time DESC';
        
        $result = get_records_sql($sql);
//        $Out->print_r($sql, '$sql = ');
//        $Out->print_r($result, '$result = ');
        
        // get users with a certain conteextid
        $sql_users_context_role = 'SELECT userid FROM vle_20091023_sr1_mdl_role_assignments ra  WHERE ra.contextid in('.implode(',', $context_ids).')';
        $result = get_records_sql($sql_users_context_role);
//        $Out->print_r($sql_users_context_role, '$sql_users_context_role = ');
        $Out->print_r($result, '$result = ');
        
         $sql_users = 'SELECT u.id, u.username 
                        FROM vle_20091023_sr1_mdl_user u
                            INNER JOIN vle_20091023_sr1_mdl_role_assignments ra ON ra.userid = u.id  
                        WHERE ra.userid in('.$sql_users_context_role.')';
        $result = get_records_sql($sql_users);
        $Out->print_r($sql_users, '$sql_users = ');
        $Out->print_r($result, '$result = ');
        
        // 
        
        
        
    }
    test_print_forumng_showreaders();
    
//    test_list_tables('user');
    $Out->append('Script completed');
    $Out->flush();
    //print_footer('none');
?>
