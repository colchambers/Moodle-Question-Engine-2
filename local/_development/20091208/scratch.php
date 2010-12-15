<?php
 /*
 *
 * @copyright &copy; 2007 The Open University
 * @author c.chambers@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package synch
 * 
 * This script reloads a course back to its original status. It does it by
 * deleting the previous version and restoring a new copy from backup. 
 */
 
    require_once('../../../config.php');
    require_once($CFG->dirroot .'/_tools/synch/setup.php'); // Debug library
    require_once(dirname(__FILE__).'/lib.php');
    require_once($CFG->dirroot.'/synch/setup.php');
    
    require_login();
    require_capability('moodle/site:doanything',get_context_instance(CONTEXT_SYSTEM));
    
    if(!isadmin()) {
        error('Not allowed to access this page');
    }

    global $Out;
    
    function testAndroidViewPort(){
        global $CFG;
        global $Out;
        ?>
        
        <html>
        	<head>
        		<meta name="viewport" content="width=device-width, initial- 
scale=0.4812, maximum-scale=2.0, user-scalable=yes"/>  
        	</head>
        	<body>
        	
            	<div id="page">
            		<div id="content">
            			<table id="layout-table">
            				<tr>
            					<td style="width: 510px;" >Left column reallylongtextwithnospacestoforcethewidth</td>
            					<td>middle column</td>
            					<td style="width: 210px;" >right column reallylongtextwithnospacestoforcethewidth</td>
            				</tr>
            			</table>
            		</div>
            	</div>
        	</body>
        </html>
        
        <?php 
        
    }
    

//    testAndroidViewPort();
/*
    // Get a recordset and print to screen
    function getAndPrintRecords($table){
        global $Out;
        $records = get_records($table);
        $Out->print_records($records, $table.' = ');
    }
  */  
    function testQueryForums(){
        global $Out;
//        getAndPrintRecords('forum_read');
//        getAndPrintRecords('forum_posts');
//        getAndPrintRecords('forum_discussions');
//        getAndPrintRecords('forum');
//        getAndPrintRecords('forum_track_prefs');
//        getAndPrintRecords('forumng');
//        getAndPrintRecords('forumng_read');
        
//        getAndPrintRecords('user');
        
        getAndPrintRecords('block');
        
//        $Out->print_r();
    }
//    testQueryForums();
    
    function testAddForumTrackingRecords(){
        $table = 'forum_track_prefs';
        $data = new Object();
        $data->userid = 3;
        $data->forumid = 6;
        insert_record($table, $data);
        
        $data->userid = 12;
        $data->forumid = 6;
        insert_record($table, $data);
    }
//    testAddForumTrackingRecords();

    
    function testForumBlockPage(){
        
        //  Display the course forum block.
    
//        require_once('../config.php');
    // ou-specific begins
        global $CFG;
        require_once($CFG->dirroot.'/local/studenthome.php');
        
        global $Out;
        $id=optional_param('id',0,PARAM_INT);
        $name=optional_param('name', '', PARAM_RAW);  

        require_once ($CFG->dirroot.'/course/ou_course.php');
    // ou-specific ends
        require_once('lib.php');
        require_once($CFG->libdir.'/blocklib.php');
        require_once($CFG->libdir.'/ajax/ajaxlib.php');
        require_once($CFG->dirroot.'/mod/forum/lib.php');
    
        $id          = optional_param('id', 0, PARAM_INT);
        $id = 6;
        if (empty($id) && empty($name) && empty($idnumber)) {
            error("Must specify course id, short name or idnumber");
        }
    
        if (!empty($name)) {
            if (! ($course = get_record('course', 'shortname', $name)) ) {
    // ou-specific begins
                if ($CFG->ousite == 'OCI') {
                    // try uppercasing it and then see if you can find it
                    $name = strtoupper($name);
                    if (! ($course = get_record('course', 'shortname', $name)) ) {
                        error('Invalid short course name');
                    }
                } else {
    // ou-specific ends                    
                error('Invalid short course name');
    // ou-specific begins
                }
    // ou-specific ends
            }
        } else if (!empty($idnumber)) {
            if (! ($course = get_record('course', 'idnumber', $idnumber)) ) {
                error('Invalid course idnumber');
            }
        } else {
            if (! ($course = get_record('course', 'id', $id)) ) {
                error('Invalid course id');
            }
        }
    
        preload_course_contexts($course->id);
        if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) {
            print_error('nocontext');
        }
        require_login($course);
        $PAGE = page_create_object(PAGE_COURSE_VIEW, $course->id);
        $pageblocks = blocks_setup($PAGE, BLOCKS_PINNED_BOTH);
        
        $Out->print_r($pageblocks, '$pageblocks = ');
    }
//    testForumBlockPage();
    
    function testGetFastModInfo(){
        global $COURSE;
        global $Out;
         
        $modinfo =& get_fast_modinfo($COURSE);
        $Out->print_r($modinfo, '$modinfo = ');
    }
//    testGetFastModInfo();

    function testShowCookies(){
        global $Out;
        $cookies = $_COOKIE;
        $Out->print_r($cookies, '$cookies = ');
        
        $mobile = !empty($cookies['OUMOBILE']);
        $Out->append('$mobile = '.$mobile);
    }
//    testShowCookies();
    
    function testIsMobile(){
        global $Out;
        
        $is_mobile = false;
        $is_mobile = ou_get_is_mobile_from_cookies();
        
        $Out->append('$is_mobile = '.$is_mobile);
    }
//    testIsMobile();
    
    function getIsMobileFromCookies(){
        
        $cookies = $_COOKIE;
        
        if(!empty($cookies['OUMOBILE'])){
            return true;
        }
        
        if(!empty($cookies['OUFULLSIZE'])){
            return false;
        }
        
        /*
         * If OUMOBILE is not set and OUFULLSIZE is not set then a browser 
         * detect has not yet been done; carry out a browser detect.
         */
        // do browser detect
        
        return false;
    }
    
    
    function test_webservice($user_agent, $user_ip){
        Global $Out;
        global $CFG;
        
        
        // for debugging turn caching off
        ini_set("soap.wsdl_cache_enabled", 0);
        
        $request = 'http://python.open.ac.uk/your-record/p14.dll?WS';
        $wsdl = 'http://python.open.ac.uk/your-record/GetClientInfo.wsdl';
        
        //soap client to the OU webservice
        try{
			$webService=new SoapClient($wsdl,array(
                'location'=>'http://python.open.ac.uk/your-record/p14.dll?WS',
                'proxy_host'=>$CFG->proxyhost,
                'proxy_port'=>(int)$CFG->proxyport, 
			    'trace'=>1));
		}
		catch(Exception $e){
			$initialised = false; //there has been an error
		}
		
//		$functions = $webService->__getFunctions();
//		$Out->print_r($functions, '$functions = ');
		$response=null;
//		$Out->append('$user_agent = '.$user_agent);

		$parameters = new Object();
		$parameters->sUserAgent = $user_agent;
		$parameters->sUserIP = $user_ip;
		

//$params->i->courses = '';
        $parameters = array('cii'=>$parameters);
		try{
		    $response = $webService->GetClientInfo($parameters);
		}
		catch(Exception $e){
		    $Out->append('webservice generated exception');
		    $Out->print_r($e, '$e = ');
		    
		}
		
//		$request_headers = $webService->__getLastRequestHeaders();
//		$Out->print_r($request_headers, '$request_headers = ');
		
//		$request = $webService->__getLastRequest();
//		$Out->append_html('$request = '.$request);
		/*
		if (is_soap_fault($response)) {
		    $Out->append('webservice call errored');
		}
		*/
		$Out->print_r($response->GetClientInfoResult, '$response->GetClientInfoResult = ');
		$Out->print_r($response, '$response = ');
    }
//    test_print('_SERVER');
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $user_ip = $_SERVER['REMOTE_ADDR'];
//    test_webservice($user_agent, $user_ip);
    
//    test_print_cfg();
//    test_print_server();
//test_print('CFG');
    
//    test_print_fullme();
    
//    test_print_cookies();
//    $Out->disableAutoFlush();
    
    function test_setcookie(){
        $domain = '.open.ac.uk';
//        setcookie('OUMOBILE', 1, time() + 3600, '/', $domain);
//        ou_set_cookie('OUFULLSIZE', 'F', time()+3600);
//         setcookie('OUMOBILE', 1);
        ou_set_cookie('OUFULLSIZE', 'F', time()-3600);
        ou_set_cookie('OUMOBILE', 1, time()-3600);
    }
//    test_print_cookies('before test_setcookie');
//    test_setcookie();
//    test_print_cookies('before test_reset_mobile_cookies');
    $function_name='ou_is_browser_mobile';
//    test_function($function_name);
    
    function test_print_server_cookie(){
        global $Out;
        $cookies = explode( ";", $_SERVER['HTTP_COOKIE']);
        $Out->print_r($cookies, '$cookies = ');
    }
//    test_print_server_cookie();
    
    function test_reset_mobile_cookies(){
        global $Out;
        $cookies = $_COOKIE;
        $names = array('OUMOBILE', 'OUFULLSIZE');
        $domain = 'open.ac.uk';
        $removed = array();
        foreach($names as $name){
            if(isset($cookies[$name])){
//                unset($_COOKIE[$name]);
                setcookie($name, '', time() - 3600, '/', $domain);
                setcookie($name, '', time() - 3600);
                $removed[] = $name;
            }
        }
        
        $Out->print_r($removed, '$removed = ');
        test_print_cookies();
    }
//    test_reset_mobile_cookies();
    
    $function_name='ou_is_browser_mobile';
//    test_function($function_name);

    function test_url_replace(){
        global $Out;
//        $url = 'test.php?sectionnum=4&section=43545345fsdf&end=32';
        $url = 'test.php?sectionnum=4';
        $key = 'section';
        $value = '10';
        $new_url = ou_replace_url_parameter($url, $key, $value);
        $Out->append('$new_url = '.$new_url);
    }
//    test_url_replace();

    function test_print_course_tables(){
//        getAndPrintRecords('course');
//        getAndPrintRecords('course_sections;');
//        getAndPrintRecords('course_modules;');
        getAndPrintRecords('block;');
    }
//    test_print_course_tables();
    
    function test_ou_course_get_blocknames_to_id(){
        global $CFG;
        global $Out;
        require_once($CFG->dirroot .'/course/ou_lib.php');
        $table = 'block;';
        $records = get_records($table);
//        $Out->print_r($records, $table.' = ');
        
        $names_to_ids = ou_course_get_blocknames_to_ids();
        $Out->print_r($names_to_ids, '$names_to_ids (1) = '); 
        
        $names_to_ids = ou_course_get_blocknames_to_ids();
        $Out->print_r($names_to_ids, '$names_to_ids (2) = '); 
    }
//    test_ou_course_get_blocknames_to_id();
    
    function test_ou_highlight_num($file){ 
      $lines = implode(range(1, count(file($file))), '<br />'); 
      $content = highlight_file($file, true); 
    
      
      echo ' 
        <style type="text/css"> 
            .num { 
            float: left; 
            color: gray; 
            font-size: 13px;    
            font-family: monospace; 
            text-align: right; 
            margin-right: 6pt; 
            padding-right: 6pt; 
            border-right: 1px solid gray;} 
    
            body {margin: 0px; margin-left: 5px;} 
            td {vertical-align: top;} 
            code {white-space: nowrap;} 
        </style>'; 
        
        
        
        echo "<table><tr><td class=\"num\">\n$lines\n</td><td>\n$content\n</td></tr></table>"; 
    } 
    $path = '/fs2/www/includes/header-vle2.php';
//    test_ou_highlight_num($path);
    function test_ou_show_source(){
        
        global $Out;
        $path = '/fs2/www/includes/header-vle2.php';
        
        $Out->append('Showing the source of \''.$path.'\'');
        show_source($path);
    }
//    test_ou_show_source();

    function test_highlight_file_with_line_numbers($file) { 
          //Strip code and first span
        $code = substr(highlight_file($file, true), 36, -15);
        //Split lines
        $lines = explode('<br />', $code);
        //Count
        $lineCount = count($lines);
        //Calc pad length
        $padLength = strlen($lineCount);
        
        //Re-Print the code and span again
        echo "<code><span style=\"color: #000000\">";
        
        //Loop lines
        foreach($lines as $i => $line) {
            //Create line number
            $lineNumber = str_pad($i + 1,  $padLength, '0', STR_PAD_LEFT);
            //Print line
            echo sprintf('<br><span style="color: #999999">%s | </span>%s', $lineNumber, $line);
        }
        
        //Close span
        echo "</span></code>";
    }
    
    $path = '/fs2/www/includes/header-vle2.php';
//    test_highlight_file_with_line_numbers($path);

    

    function test_insert_duplicate_key(){
        global $Out;
        $sql = 'INSERT INTO vle_20091023_sr2_mdl_backup_ids ( BACKUP_CODE, TABLE_NAME, OLD_ID, INFO )
        			 VALUES ( 1256211587, \'user\', 4, \'needed\' )';
        $feedback = execute_sql($sql, true);
        
        $Out->print_r($feedback, '$feedback = ');
    }
//    test_insert_duplicate_key();
    
//    $tables = array('backup_ids');

//    test_print_record_as_object('user', 26);
    $tables = array(
                'user',            
                'user_info_field',
                'user_info_category',
                'user_info_data',
                'user_metadata'
                    );
                    
   /* $tables = array(
                'block'
                    );*/
//    test_print_tables($tables);

    
//    $sql = 'SELECT * FROM '.$CFG->prefix.'user_info_category WHERE id = \'\'';
//  test_get_record_sql($sql);
    $sql = 'SELECT * FROM '.$CFG->prefix.'user_info_data 
                JOIN '.$CFG->prefix.'user_info_field ON '.$CFG->prefix.'user_info_field.id='.$CFG->prefix.'user_info_data.fieldid  WHERE userid = \'28\'';
//    $Out->print_r($sql, '$sql = ');
//    test_get_records_sql($sql);
    

    function test_vital_web_services(){
        global $CFG;
        global $Out;
        ini_set("soap.wsdl_cache_enabled", 0);

        Class ListLocalAuthoritiesRequestMessage
        {
            public $MaxResults = 0;
        }
    
        Class QuerySchoolsReqeustMessage
        {
            public $SchoolName = null;
            public $LocalAuthority = null;
            public $PostCode = null;
        }
        
        function display_result($response, $name){
            global $Out;
            $results = null;
            $Out->print_r($response->$name->results, '$response->$name->results (1) = ');
            if(isset($response->$name->results)){
                $results = $response->$name->results;
            }
            
            $Out->print_r($results, '$results (1) = ');
        }
    
        /*$client = new SoapClient("http://e-skills.platform.services/SchoolService.svc?wsdl",array(
        'proxy_host'=>$CFG->proxyhost,
            'proxy_port'=>(int)$CFG->proxyport));*/
        $client = new SoapClient("http://e-skills.services.msdev.fsite.com/SchoolService.svc?wsdl",array(
        'proxy_host'=>$CFG->proxyhost,
            'proxy_port'=>(int)$CFG->proxyport));
        $request = new QuerySchoolsReqeustMessage();
//        $request->LocalAuthority = 'Somerset';
        $request->SchoolName = 'Witham Friary Playgroup';
        
        $response = $client->QuerySchools(array('message' => $request));
//        $name = 'QuerySchoolsResult';
//        $results = $response->QuerySchoolsResult->;
        
//        display_result($response, $name);
        $Out->print_r($response, '$response (1) = ');
        
        $response = $client->ListLocalAuthorities(array('message' => new ListLocalAuthoritiesRequestMessage()));
        $Out->print_r($response, '$response (2) = ');
    
    }
//    test_vital_web_services();

    function ou_test_get_users_by_email($email_addresses){
        global $Out;
        global $CFG;
        
        $email_address = 'test1@test.com';
        if(!is_array($email_addresses)){
            return array();
            //            $email_addresses = array('test1@test.com', 'test2@test.com');
        }
        
        $key = 'email';
        $table = 'user';
        
        // print user table
//        $tables = array($table);
//        test_print_tables($tables);
        
        // get a single record
//        getAndPrintRecordAsObject($table, $email_address, $key);

        // get multiple records
        $sql = 'SELECT id, email FROM '.$CFG->prefix.$table.' WHERE email in (\''.implode('\',\'', $email_addresses).'\')';
        $Out->print_r($sql, '$sql = ');
        return get_records_sql($sql);
        
    }
//    ou_test_get_users_by_email();

    
     $tables = array(
                'role_assignments',
                'log'
                    );
//    test_print_tables($tables);
    
    function ou_test_fputcsv(){
        global $CFG;
        global $Out;
        $list = array (
            'aaa1 ,bbb,ccc,dddd',
            '123,456,789',
            '"aaa","bbb"'
        );
        $file_path = $CFG->dataroot.'/temp/fputcsv.test.csv';
        
        $fp = fopen($file_path, 'w');
        
        foreach ($list as $line) {
            $Out->print_r($line, '$line = ');
            fputcsv($fp, split(',', $line));
        }
        
        fclose($fp);
    }
    
    function ou_remove_extra_characters_from_string($string, $append_char=true){
        global $Out;
        
        $string_length = strlen($string);
        $new_string = '';
        for($i=0;$i<$string_length; $i++){
            $char = $string[$i];
//            $Out->append('$char ('.$i.') = '.$char);
            if($append_char){
                $new_string .=$char;
            }
            
            $append_char = $append_char?false:true;
        }
        
        return $new_string;
    }
    
    function ou_get_vital_users_from_file($file_path, $as_records=false, $row_delimiter){
        global $Out;
        $contents = FileSystem::getFileContents($file_path);
        $contents = ou_remove_extra_characters_from_string($contents);
        $Out->print_r($contents, '$contents = ');
        
        if(!$as_records){
            return $contents;
        }
        
        return explode($row_delimiter, $contents);
    }
    
    $input_file_path = $CFG->dataroot.'/temp/pre_registe_pipe.20091123 1457.csv';
    $output_file_path = $CFG->dataroot.'/temp/pre_registe_pipe.20091109 1447.output.csv';
    function ou_test_load_users_from_vital_csv_2($input_file_path, $output_file_path){
        global $CFG;
        global $Out;
        
//        $file_path = $CFG->dataroot.'/temp/pre_register_comma_text.20091109 1447.csv';
//        $file_path = $CFG->dataroot.'/temp/pre_registe_pipe.20091109 1447.csv';
        
        $fieldnames_required = array('username', 'password', 'firstname', 'lastname', 'email');
//        $fieldnames_default = array('institution', 'department', 'city', 'country', 'lang', 'auth', 'timezone');
        $fieldnames_default = array('city', 'country', 'auth');
        
        $field_names = array("?", "Serial","SID","Time","IP Address","UID","Username","First name",
                                "Last name","Email address","Town or City","Postcode","Role",
                                "early","KS1","KS2","KS3","KS4","1416","16+","yes","yes","?", "?"
                            );
        $row_delimiter = "\n"; 
        $records = ou_get_vital_users_from_file($input_file_path, true, $row_delimiter);

        $users = array();
        $field_count = 0;
        $row_count = 0;
        foreach($records as $record){
            $values = explode('|', $record);
            
            // ignore extraneous lines
            if(strpos($values[0], 'Pre-re') || strpos($values[0], 'Submission')){
                $Out->append('skipping pre register');
                
                continue;
            }
            
            $Out->append('$values[0] = '.$values[0]);
            // replace the header line to fit upload user
            if(strpos($values[0], $field_names[1])){
                $Out->append('Adding header fields');
                $user = array_merge($fieldnames_required, $fieldnames_default);
            }
            else {
                $username = $password = $first_name = $last_name = $email = '';
                $city = '';
                $country = 'GB';
                $auth = 'email'; 
                 
    //            $Out->print_r($record, '$record = ');
                $field_count = 0;
                foreach($values as $value){
//                    $Out->append('$value ('.$field_count.') = '.$value);

                    $value = str_replace('"', '', $value);
                    switch($field_count){
                        case 6: //First name
                            $first_name = $value;
                            break;
                        case 7: //First name
                            $last_name = $value;
                            break;
                        case 8: //Email
                            $username = $value;
                            $email = $value;
                            break;
                        case 9: //City
                            $city = $value;
                            break;
                    }
                    
                    $field_count++;
                }
                
                // set password
                $password = 'testing';
                $user = array($username, $password, $first_name, $last_name, $email, $city, $country, $auth);
            }
            $Out->print_r($user, '$user = ');
            $users[] = implode(',', $user);
            $row_count++;
        }

        $Out->print_r($records, '$records = ');
        $Out->print_r($users, '$users = ');
        // save ouptut file
//        $file_path = $CFG->dataroot.'/temp/pre_registe_pipe.20091109 1447.output.csv';
        $contents = implode($row_delimiter, $users);
        $contents = FileSystem::putFileContents($output_file_path, $contents, true, true);
    }
    
//    ou_test_load_users_from_vital_csv_2($input_file_path, $output_file_path);
//    ou_test_fputcsv();

    function ou_vital_get_phase_from_value($value, $field_count, &$phase, &$messages){
        /* phases
                 * Not specified
Early years/foundation stage
Key Stage 1
Key Stage 2
Key Stage 3
Key Stage 4
14 to 19
Post 16
                 */
        if(empty($value)){
            return $value;
        }
        
        $new_value = '';
        switch($field_count){
            case 12: //early
                $new_value = 'Early years/foundation stage';
                break;
            case 13: //KS1
                $new_value = 'Key Stage 1';
                break;
            case 14: //KS2
                $new_value = 'Key Stage 2';
                break;
            case 15: //KS3
                $new_value = 'Key Stage 3';
                break;
            case 16: //KS4
                $new_value = 'Key Stage 4';
                break;
            case 17: //1416
                $new_value = '14 to 19';
                break;
            case 18: //16+
                $new_value = 'Post 16';
                break;
        }
        
        if(empty($new_value)){
            $messages[] = 'phase '.$value.' could not be recognised';
            return;
        }
        
        $phase[] = $new_value;
        
    }

    function ou_test_update_custom_fields_from_csv($input_file_path){
        global $CFG;
        global $Out;
        
        
        $fieldnames_required = array('username', 'password', 'firstname', 'lastname', 'email');
//        $fieldnames_default = array('institution', 'department', 'city', 'country', 'lang', 'auth', 'timezone');
        $fieldnames_default = array('city', 'country', 'lang', 'auth');
        
        $field_names = array("?", "Serial","SID","Time","IP Address","UID","Username","First name",
                                "Last name","Email address","Town or City","Postcode","Role",
                                "early","KS1","KS2","KS3","KS4","1416","16+","yes","yes","?", "?"
                            );
        $row_delimiter = "\n"; 
        $records = ou_get_vital_users_from_file($input_file_path, true, $row_delimiter);

        $users = array();
        $emails = array();
        $field_count = 0;
        $row_count = 0;
        foreach($records as $record){
            $values = explode('|', $record);
            
            // ignore extraneous lines
            if(strpos($values[0], 'Pre-re') || strpos($values[0], 'Submission')){
                $Out->append('skipping pre register');
                
                continue;
            }
            
            $Out->append('$values[0] = '.$values[0]);
            // replace the header line to fit upload user
            if(strpos($values[0], $field_names[1])){
                $Out->append('skipping header fields');
                continue;
            }
            else {
                $username = $password = $first_name = $last_name = $email = '';
                $role = '';
                $country = 'GB';
                $auth = 'email'; 
                $phase = array();
                 
                $field_count = 0;
                
                foreach($values as $value){

                    $value = str_replace('"', '', $value);
                    switch($field_count){
                        case 8: //Email
                            $email = $value;
                            break;
                        case 11: //Role
                            $role = $value;
                            break;
                        case 12: //Phase
                        case 13: //Phase
                        case 14: //Phase
                        case 15: //Phase
                        case 16: //Phase
                        case 17: //Phase
                        case 18: //Phase
                            ou_vital_get_phase_from_value($value, $field_count, $phase, $messages);
                            break;
                            
                    }
                    
                    $field_count++;
                }
                
                // set password
                $password = 'testing';
                $user = array($role, $phase);
            }
            $users[$email] = $user; //implode(',', $user);
            $emails[] = $email;
            $row_count++;
        }

        // get user ids from db by email
        $user_ids_to_email = ou_test_get_users_by_email($emails);
        
        //get user info fields
        $user_info_fields = get_records_select('user_info_field');
        $user_info_fields_to_ids = array();
        foreach($user_info_fields as $record){
            $user_info_fields_to_ids[$record->shortname]=$record->id;
        }
        
        // update db with new records
        $messages = array();
        /*
         * all we need to do is create a new record in user_info_data with user, fieldid and data 
         * fieldid links to user_info_field and userid links to user. We just have to confirm no record already exists.
         * If one does do we update it/ignore? 
         * 
         * Loop through $user_ids_to_email. foreach id find the user details in users by email address. Then loop through each field
         * and add/update as needed 
         */
        if(is_array($user_ids_to_email)){
            foreach($user_ids_to_email as $id => $user_id_to_email){
                $Out->print_r($email, '$email = ');
                $email = '';
                if(isset($user_id_to_email->email)){
                    $email = $user_id_to_email->email;
                }
                
                if(!isset($users[$email])){
                    $messages[]='Unable to find '.$email.' id '.$id.' in file. Custom fields not added.';
                    continue;
                }
                $user = $users[$email];
                $user_id = $id;
                // update role
                // user_info_data table columns userid fieldid data
                $number_of_fields = count($user);
                for($field_count = 0; $field_count<$number_of_fields; $field_count++){
                    
                    $data_record = new Object();
                    $data_record->fieldid = 0;
                    $data_record->data = '';
                    $data_record->userid = $user_id;
                    switch($field_count){
                        case 0:
                            $data_record->fieldid = $user_info_fields_to_ids['vitalrole'];
                            $data_record->data = $user[0];
                            break;
                        case 1:
                            $data_record->fieldid = $user_info_fields_to_ids['vitalphase'];
                            $data_record->data = implode(',', $user[1]);
                            break;
                    }
                    $Out->append('$field_count = '.$field_count);
                    $Out->print_r($data_record, '$data_record = ');
                    
                    $sql = 'SELECT id FROM '.$CFG->prefix.'user_info_data WHERE userid='.$data_record->userid.' and 
                            fieldid='.$data_record->fieldid.'';
                    $records = get_records_sql($sql, 0, 1);
    //                $Out->print_r($records, '$records = ');
                    if(count($records)){
                       $record = array_pop($records);
    //                   $Out->print_r($record, '$record = ');
                       
                       // update record
                       $data_record->id = $record->id;
                       if(!update_record('user_info_data', $data_record)){
                           $messages[]='Unable to update record data to '.$data_record->data.' in user_info_data where userid= 
                                        '.$data_record->userid.' and fieldid '.$data_record->fieldid.' . Custom field not updated.'; 
                       }
                    }
                    else {
                       // insert record
                       if(!insert_record('user_info_data', $data_record)){
                           $messages[]='Unable to insert record into user_info_data. userid= '.$data_record->user_id.' 
                                        fieldid '.$data_record->fieldid.' data='.$data_record->data.'. Custom field not added.';
                       }
                    }
                    
                    
                }
                
                
            }
        }
        else {
            $messages[]='Unable to find any users with the following email addresses '.implode(',', $emails).'.';
        }
        $Out->print_r($messages, '$messages = ');
    }
    
//    ou_test_update_custom_fields_from_csv($input_file_path);

    // sql from tag/lib.php tag_get_tags()
    $sql = 'SELECT tg.id, tg.tagtype, tg.name, tg.rawname, tg.flag, ti.ordering 
                FROM vle_20091224_sr1_mdl_tag_instance ti INNER JOIN vle_20091224_sr1_mdl_tag tg 
                ON tg.id = ti.tagid WHERE ti.itemtype = \'user\' AND ti.itemid = \'29\' ORDER BY ti.ordering ASC';
//    test_get_records_sql($sql);

    function ou_test_enrolled_users_and_courses(){
        global $CFG;
        
         

        $time_start=1256515200;
        $time_end=1257983999;
        $core_sql = 'FROM '.$CFG->prefix.'role_assignments ra 
            INNER JOIN '.$CFG->prefix.'user u ON u.id=ra.userid 
            INNER JOIN '.$CFG->prefix.'role r ON r.id=ra.roleid
            INNER JOIN '.$CFG->prefix.'context ctx ON ctx.id=ra.contextid
            INNER JOIN '.$CFG->prefix.'course c ON c.id=ctx.instanceid 
            WHERE r.shortname=\'student\'
            and (ra.timestart > '.$time_start
            .'AND ra.timeend < '.$time_end.') '
            .'and u.deleted!=1'
            .'and u.confirmed=1'
            .'and ctx.contextlevel='.CONTEXT_COURSE
            .'AND c.metacourse!=1'
            .'AND c.visible=1';
        
        // Retrieve count of all users assigned a student role in a course
        // users not deleted. 
        $sql = 'SELECT count(u.id) '; /*FROM '.$CFG->prefix.'role_assignments ra 
            INNER JOIN '.$CFG->prefix.'user u ON u.id=ra.userid 
            INNER JOIN '.$CFG->prefix.'role r ON r.id=ra.roleid
            WHERE r.shortname=\'student\'
            and (ra.timestart > '.$time_start
            .'AND ra.timeend < '.$time_end.') '
            .'and u.deleted!=1'
            .'and u.confirmed=1';*/
        $sql.=$core_sql;
//        test_get_record_sql($sql);
        
        
        // enrolled courses
        // count courses where a role assigment with context=CONTEXT_COURSE exists
        $sql = $sql = 'SELECT COUNT(DISTINCT c.id) ';
        $sql.=$core_sql;
//        test_get_records_sql($sql);

        // Minutes spent on site for All users
        $sql = 'SELECT u.id asuseridd, l.time  FROM '.$CFG->prefix.'user u 
                    INNER JOIN '.$CFG->prefix.'log l ON u.id=l.userid 
                ORDER By u.id, l.time';
        
        $sql = 'SELECT l.time, u.id as user_id FROM '.$CFG->prefix.'user u 
                    INNER JOIN '.$CFG->prefix.'log l ON u.id=l.userid 
                    ORDER By u.id, l.time';
        test_get_records_sql($sql, $sql);

            $tables = array(
//                'course',       
//                'role',
//                'role_assignments',
                'log',
//                'user',
                'context'
                    );
        test_print_tables($tables);
        
//        test_list_tables('context');
        
//        test_list_table_columns('course');
//        test_list_table_columns('user');
//        test_list_table_columns('role');
        $sql = 'select id, username, deleted FROM '.$CFG->prefix.'user WHERE confirmed!=1 AND deleted!=1';
//        test_get_records_sql($sql, $sql);
    }
//    ou_test_enrolled_users_and_courses();

    function ou_test_compare_dates(){
        global $Out;
        $start = 1256566327;
        $end = 1256564737;
        
        // difference between two dates in seconds
        $difference = $start - $end;
        $Out->append('$difference = '.$difference);
        
        // difference in minutes 
        $difference = $difference/60;
        $Out->append('$difference = '.$difference);
        
        // gap between sessions in seconds
        $session_gap = 60*60;
        $Out->append('$session_gap = '.$session_gap);
    }
//    ou_test_compare_dates();
    
    $tables = array(
//                'user', 
                'course_extended_meta'      
                    );
//    test_print_tables($tables);
    $oci_prefix = 'oci_20091023_sr1_mdl_';
//    test_print_tables($tables, $oci_prefix);
//    test_list_tables('config');

    function ou_test_fix_lang_db_users(){
        global $CFG;
        global $Out;
        $sql = 'UPDATE '.$CFG->prefix.'user SET lang=\'en_dc\'';
        $feedback = execute_sql($sql, true);
        $Out->print_r($feedback, '$feedback = ');
    }
//    ou_test_fix_lang_db_users();
    
    /*
     * figure out how many days, weeks months etc. are in a given number of seconds
     */
    function out_test_datetime_in_seconds(){
        global $Out;
        $interval_seconds = 1233456789;
        
        $interval = new Object();
        $interval->seconds = $interval_seconds%60;
        $interval_minutes = floor($interval_seconds/60);
//        $interval->minutes = floor($interval_seconds/60);
        $interval->minutes = $interval_minutes%60;
        
        $interval_hours = floor($interval_minutes/60);
        $interval->hours = $interval_hours%60;
        
        $time_periods = array('seconds', 'minutes', 'hours', 'days', 'weeks');
        
        
        
        $Out->print_r($interval, '$interval = ');
    }
    
    /*
     * Modules can be enabled and disabled in the score database through editing the system table  
     */
    function ou_test_update_score_system(){
        global $Out;
        $score_prefix = 'scr_20100326_sr1_';
        $prefix = $score_prefix;
        $sql = 'UPDATE '.$prefix.'system 
                SET status=0 
                WHERE name=\'sams\'';
        $feedback = execute_sql($sql, true);
        $Out->print_r($feedback, '$feedback = ');
    }
    
    /*
     * Menu items can be adjusted through the menu_router table  
     */
    function ou_test_update_score_menu_router(){
        global $Out;
        $score_prefix = 'scr_20100326_sr1_';
        $prefix = $score_prefix;
        // value was drupal_not_found
        /*$sql = 'UPDATE '.$prefix.'menu_router 
                SET page_callback=\'user_logout\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);
        $Out->print_r($feedback, '$feedback = ');*/
        
        /*$sql = 'UPDATE '.$prefix.'menu_router 
                SET access_callback=\'user_access\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);*/
        
        // original value a:0:{}
        $sql = 'UPDATE '.$prefix.'menu_router 
                SET access_arguments=\'a:1:{i:0;s:14:"access content";}\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);
    
        $Out->print_r($feedback, '$feedback = ');
    }
    
//    ou_test_update_score_menu_router();
    
    /*
     * Menu items can be adjusted through the menu_router table  
     */
    function ou_test_update_score_theme(){
        global $Out;
        $score_prefix = 'scr_20100326_sr1_';
        $prefix = $score_prefix;
        // value was drupal_not_found
        /*$sql = 'UPDATE '.$prefix.'menu_router 
                SET page_callback=\'user_logout\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);
        $Out->print_r($feedback, '$feedback = ');*/
        
        /*$sql = 'UPDATE '.$prefix.'menu_router 
                SET access_callback=\'user_access\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);*/
        
        // original value a:0:{}
        /*$sql = 'UPDATE '.$prefix.'menu_router 
                SET access_arguments=\'a:1:{i:0;s:14:"access content";}\' 
                WHERE path=\'logout\'';
        $feedback = execute_sql($sql, true);*/
        
//        $sql = 'select * FROM '.$prefix.'system WHERE type=\'theme\'';
//        test_get_records_sql($sql, $sql);

        $sql = 'select * FROM '.$prefix.'system WHERE type=\'theme\' AND name=\'zen_score\'';
        $record = get_record_sql($sql, $sql);
        
        $Out->print_r($record, '$record = ');
        $settings = unserialize($record->info);
        unset($settings['base theme']);
        
        $Out->print_r($settings, '$settings = ');
        
        $record->info = serialize($settings);
        $Out->print_r($record, '$record (2)= ');
        
        $sql = 'UPDATE '.$prefix.'system 
                SET info=\''.$record->info.'\' 
                WHERE type=\'theme\' AND name=\'zen_score\'';
        $feedback = execute_sql($sql, true);
        
        
    
        $Out->print_r($feedback, '$feedback = ');
    }
//    ou_test_update_score_theme();

    function ou_test_reg_exp(){
        global $Out;
        $reg_exp = '/^[a-z]+[0-9]+$/';
        $string = 'cc5983';
//        $string = 'cc5983.';
        
        $matches = preg_match($reg_exp, $string);
        $Out->print_r($matches, '$matches = ');
    }
//    ou_test_reg_exp();

    function ou_test_create_logbatch(){
        global $CFG;
        
        $file_path = $CFG->dataroot.'/admin/logbatch/log.batch';
        $contents = 'log batch file';
         $contents = FileSystem::putFileContents($file_path, $contents, true, true);
    }
//    ou_test_create_logbatch();

    function ou_test_getInnerHTML(){
    global $Out;
    $Out->append('1');
        ?> 
        <html>
        <body>
        <a href="test.php" id="clickedLink" onClick="clickedLink(); return false;" >link text</a>
        <script>
        function clickedLink(){
            element = document.getElementById('clickedLink');
            element.innerHTML = 'clicked';
            alert('element.innerHTML = '+element.innerHTML);
        }
        </script>
        </body>
        </html>
        
        <?php    
    }
//    ou_test_getInnerHTML();
    
    $Out->append('Script completed');
    $Out->flush();
    //print_footer('none');
?>
