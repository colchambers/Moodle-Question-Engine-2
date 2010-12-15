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
    
    class GetClientInfo {
      public $GetClientInfo;
	  /* string */
      public $sUserAgent;
      /* string */
      public $sUserIP;
    }
    
    class ClientInfoIn {
      public $GetClientInfo;
	  /* string */
      public $sUserAgent;
      /* string */
      public $sUserIP;
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
		
		$functions = $webService->__getFunctions();
		$Out->print_r($functions, '$functions = ');
		$response=null;
		$Out->append('$user_agent = '.$user_agent);

		$parameters = new GetClientInfo();
		$parameters->sUserAgent = $user_agent;
		$parameters->sUserIP = $user_ip;
		
		$parameters = new ClientInfoIn();
		$parameters->sUserAgent = $user_agent;
		$parameters->sUserIP = $user_ip;
		
//		$parameters->GetClientInfo->sUserAgent = $user_agent;
//		$parameters->GetClientInfo->sUserIP = $user_ip;
//        $parameters = new Object();
//        $parameters->GetClientInfo = new Object();
//		$parameters->GetClientInfo->sUserAgent = $user_agent;
//		$parameters->GetClientInfo->sUserIP = $user_ip;
//		$response = $webService->GetClientInfo($user_agent);

	    /*$parameters = new Object();
		$parameters->Operation='GetClientInfo';
        $parameters->i->sUserAgent = $user_agent;*/

//$params->i->courses = '';
$parameters = array('cii'=>$parameters);
		try{
		    $response = $webService->GetClientInfo($parameters);
		}
		catch(Exception $e){
		    $Out->append('webservice generated exception');
		    $Out->print_r($e, '$e = ');
		    
		}
		
		$request_headers = $webService->__getLastRequestHeaders();
		$Out->print_r($request_headers, '$request_headers = ');
		
		$request = $webService->__getLastRequest();
		$Out->append_html('$request = '.$request);
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
    test_webservice($user_agent, $user_ip);
    
    function test_webservice_get_temp(){
        global $CFG;
        $client = new SoapClient
      ("http://www.xmethods.net/sd/2001/DemoTemperatureService.wsdl", array(
                'proxy_host'=>$CFG->proxyhost,
                'proxy_port'=>(int)$CFG->proxyport, 
			    'trace'=>1));
   echo("\nReturning value of getTemp() call: ".
      $client->getTemp("12345"));
    }
//     test_webservice_get_temp();

    function test_webservice_voice(){
        $params = new Object();
        $params->Operation='x2faq';
        $params->i->key = 'hy7FEp3';
        $params->i->area = 'L';
        $code = '1-4GIL55';
        $pcode = '1-4GIL3X';
        $params->i->category = $code;
        $params->i->parent = $pcode;
        
        $params->i->courses = '';
        //$client = new SoapClient("http://msds.open.ac.uk/your-record/x2faq.wsdl");
        $wsdl = 'http://msds.open.ac.uk/your-record/x2faq.wsdl';
//        $wsdl = "voice.wsdl";
        $client = new SoapClient($wsdl,array(
                'proxy_host'=>$CFG->proxyhost,
                'proxy_port'=>(int)$CFG->proxyport, 
			    'trace'=>1));
        $response = $client->x2faq($params);
        $Out->print_r($response, '$response = ');
    }
    test_webservice_voice();
    test_print_cfg();
//    test_print_server();
//test_print('CFG');
    
//    test_print_fullme();
    
//    test_print_cookies();
    
    $function_name='ou_is_browser_mobile';
//    test_function($function_name);
    
    function test_reset_mobile_cookies(){
        global $Out;
        $cookies = $_COOKIE;
        $names = array('OUMOBILE', 'OUFULLSIZE');
        $removed = array();
        foreach($names as $name){
            if(isset($cookies[$name])){
                unset($_COOKIE[$name]);
                setcookie($name, '', time() - 3600);
                $removed[] = $name;
            }
        }
        
        $Out->print_r($removed, '$removed = ');
        test_print_cookies();
    }
    test_reset_mobile_cookies();
    
    $function_name='ou_is_browser_mobile';
    test_function($function_name);
    $Out->append('Script completed');
    $Out->flush();
    //print_footer('none');
?>
