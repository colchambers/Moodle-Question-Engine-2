<?php
 /*
 *
 * @copyright &copy; 2007 The Open University
 * @author c.chambers@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package synch
 * 
 * File description to go here
 */
 
    // Get a recordset and print to screen
    function getAndPrintRecords($table, $level=0){
        global $Out;
        $records = get_records($table);
//        $Out->print_r($records, '$records = ');
        $level++;
        $Out->print_records($records, $table.' = ', $level);
    }
    
    function getAndPrintRecord($table, $id){
        global $Out;
        $records = get_records($table, 'id', $id);
        $Out->print_records($records, $table.' = '); 
    }
    
    function getAndPrintRecordAsObject($table, $id, $key='id'){
        global $Out;
        $records = get_records($table, $key, $id);
        $Out->print_r($records, $table.' = '); 
    }
    
    function test_print($name){
        global $Out;
        $variable = null;
        switch($name){
            case 'CFG':
               global $CFG; 
               $variable = $CFG;
               break;
           case '_SERVER':
               global $_SERVER; 
               $variable = $_SERVER;
               break;
        }
        
        $Out->print_r($variable, '$'.$name.' = ', 1);
    }
    function test_print_cfg(){
        global $CFG, $Out;
        $Out->print_r($CFG, '$CFG = ', 1);
    }
    
    function test_print_site(){
        global $CFG, $Out;
        $site = get_site();
        
        $Out->print_r($site, '$site = ');
    }
    
    /*
     * Print contents of a given list of tables
     * @param array $table array of tables to return
     * @return void
     * @example usage
     * $tables=array(
                    'course'
                    );
		test_print_tables($tables);
     */
    function test_print_tables($tables=array(), $prefix='', $level=0){
        
        global $CFG;
        global $Out;
        $stored_prefix = $CFG->prefix;
        $CFG->prefix = empty($prefix)? $CFG->prefix: $prefix;
        $level++;
        foreach($tables as $table){
            $Out->append('Printing table \''.$table.'\' from '.$CFG->prefix.'.', $level);
            getAndPrintRecords($table, $level);
        }
        $CFG->prefix = $stored_prefix;
        
    }

    /*
     * @example test_print_record_as_object('course', '357')
     */
    function test_print_record_as_object($table, $id){
        
        getAndPrintRecordAsObject($table, $id);
    }
    
    function test_get_record_sql($sql){
        global $Out;
        $record = get_record_sql($sql);
        $Out->print_r($record, '$record = ');
    }
    
    function test_get_records_sql($sql, $text='$records = ', $level=0){
        global $Out;
        $records = get_records_sql($sql);
        $Out->print_records($records, $text, ++$level);
        
        return $records;
    }
    
    function test_execute_sql($sql, $text='$records = ', $feedback=true, $level=0){
        global $Out;
        $result = execute_sql($sql, $feedback);
        $Out->append($text.$result, ++$level);
        $Out->print_records($records, $text, ++$level);
        
        return $records;
    }   

    function test_get_recordset_sql($sql, $text='$records = ', $level=0){
        global $Out;
        $records = get_recordset_sql($sql);
        $Out->print_recordset($records, $text, ++$level);
        
        return $records;
    }  
    
    /*
     * get a list of tables from the database containing a keyword if entered.
     * 
     * @example test_list_tables('course'); retrieve a table containing the word 'course'
     */
    function test_list_tables($like='', $text='', $prefix='', $level=0){
        global $CFG;
        $prefix = empty($prefix)?$CFG->prefix:$prefix;
        // taken from http://bytes.com/topic/postgresql/answers/172978-sql-command-list-tables
        $sql = 'select c.relname FROM pg_catalog.pg_class c
                LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                WHERE c.relkind IN (\'r\',\'\') AND n.nspname NOT IN (\'pg_catalog\', \'pg_toast\')
                AND pg_catalog.pg_table_is_visible(c.oid) 
                AND c.relname LIKE \'%'.$prefix.'%\'
                AND c.relname LIKE \'%'.$like.'%\'';
        test_get_records_sql($sql, $text, ++$level);
    }
    
    function test_list_table_columns($table, $prefix='', $level=0){
        global $CFG;
        $prefix = empty($prefix)?$CFG->prefix:$prefix;
        $sql = 'SELECT column_name, data_type, is_nullable, column_default, numeric_precision FROM information_schema.columns WHERE table_name =\''.$prefix.$table.'\'';
        test_get_records_sql($sql, $table.' columns = ', ++$level);
    }
    
    /*
     * Delete contents of a given list of tables
     * @param array $table array of tables to delete contents from
     * @return void
     * @example usage
     * $tables=array(
                    'course'
                    );
		test_delete_records($tables);
     */
    function test_delete_records($tables){
    	foreach($tables as $table){
            $Out->append('Deleting from table \''.$table.'\'.');    
    		delete_records('report_exportdisk_templates');
    	}
    }
    
    /*
     * @example
     * test_delete_record('course_extended_meta', 'courseid', 267 );
     */
    function test_delete_record($table, $field, $value){
//        delete_records('report_exportdisk_templates', 'id', 38 );
        delete_records($table, $field, $value);
    }
    
    /*
     * just set a moodle config programatically. 
     */
    function test_set_config(){
        global $Out;
        
        // Get the config
        $ocicallerip = get_config(null, 'ocicallerip');
        
        // manipulate it
        $ocicallerips = explode(' ',$ocicallerip); 
        $ocicallerips[] = '137.108.141.33';
        $ocicallerip = implode($ocicallerips, ' ');
        
        // set it again
        set_config('ocicallerip', $ocicallerip);
    }

    function test_unzip_file(){
        $path = 'temp/backup/1253628484/1253628484.zip';
//        $destination = '/fs2/www/html/cc5983/OCI/20091023/server-2/data/temp/backup/1253629199/temp';
        unzip_file($path);
    }
    
    function test_print_fullme(){
        global $FULLME, $Out;
        $Out->print_r($FULLME, '$FULLME = ');
    }
    
    function test_print_cookies($text='', $names=null){
        global $Out;
        $cookies = $_COOKIE;
        
        if(empty($names)){
            $Out->print_r($cookies, $text.': $_COOKIE = ');
            return;
        }
        
        $pairs = array();
        foreach($names as $name){
            if(!isset($cookies[$name])){
                $pairs[$name] = 'null';   
            }
            $pairs[$name] = $cookies[$name];
        }
        
        $Out->print_r($pairs, $text.': $_COOKIE = ');
    }
    
    function test_function($name){
        global $Out;
        $return = $name();
        $Out->print_r($return, '$return = ');
    }
    
    function test_set_database($database){
        GLOBAL $db;
        $db = $database;    
    
    }
    
    function test_set_alternate_database($name=''){
        global $CFG,$alternatedb, $db, $orginaldb, $originalprefix;
        
        $details = new object;
        $details->host = $CFG->dbhost;
        $details->username = $CFG->dbuser;
        $details->password = $CFG->dbpass;
        $details->instance = null;
        switch($name){
            case 'ociacct':
                $details->name = 'moodle_oci_learn_acct';
                $details->prefix = 'mdl_';
                break;
            default:
                $details->name = 'jmg324';
                $details->prefix = "dcfs_";
                break;
        }
        
        
        $originalprefix = $CFG->prefix;
        $CFG->prefix = $details->prefix;
        
        global $Out;
        $Out->print_r($details, '$details = ');
        
        $orginaldb = $db;
        $alternatedb = $db = test_connect_to_database($details);
        
    }
    
    function test_set_default_database(){
        GLOBAL $CFG, $orginaldb, $originalprefix;
        global $Out;
        //$Out->type($CFG->synch->databases->client->instance, "\$CFG->synch->databases->client->instance = .");
        if($orginaldb){
            test_set_database($orginaldb);
            $CFG->prefix = $originalprefix;
        }
    
    }
    
    function test_connect_to_database($details){
        
        GLOBAL $CFG;
        $db = &ADONewConnection($CFG->dbtype);
        
        // See MDL-6760 for why this is necessary. In Moodle 1.8, once we start using NULLs properly,
        // we probably want to change this value to ''.
        //$db->null2null = 'A long random string that will never, ever match something we want to insert into the database, I hope. \'';
    
        if (!isset($CFG->dbpersist) or !empty($CFG->dbpersist)) {    // Use persistent connection (default)
            $dbconnected = $db->PConnect($details->host,$details->username,$details->password,$details->name);
        } else {                                                     // Use single connection
            $dbconnected = $db->Connect($details->host,$details->username,$details->password,$details->name);
        }
        if (! $dbconnected) {
            // In the name of protocol correctness, monitoring and performance
            // profiling, set the appropriate error headers for machine comsumption
            if (isset($_SERVER['SERVER_PROTOCOL'])) { 
                // Avoid it with cron.php. Note that we assume it's HTTP/1.x
                header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');        
            }
            // and then for human consumption...
            echo '<html><body>';
            echo '<table align="center"><tr>';
            echo '<td style="color:#990000; text-align:center; font-size:large; border-width:1px; '.
                 '    border-color:#000000; border-style:solid; border-radius: 20px; border-collapse: collapse; '.
                 '    -moz-border-radius: 20px; padding: 15px">';
            echo '<p>Error: Database connection failed.</p>';
            echo '<p>It is possible that the database is overloaded or otherwise not running properly.</p>';
            echo '<p>The site administrator should also check that the database details have been correctly specified in config.php</p>';
            echo '</td></tr></table>';
            echo '</body></html>';
    
            if (!empty($CFG->emailconnectionerrorsto)) {
                mail($CFG->emailconnectionerrorsto, 
                     'WARNING: Database connection error: '.$CFG->wwwroot, 
                     'Connection error: '.$CFG->wwwroot);
            }
            die;
        }

        return $db;
    }
    
    
?>
