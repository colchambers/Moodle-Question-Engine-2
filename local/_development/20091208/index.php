<?php
    require_once("../../../config.php");
    require_once($CFG->dirroot.'/synch/setup.php');
    
    require_login();
    require_capability('moodle/site:doanything',get_context_instance(CONTEXT_SYSTEM));
    $configs = new Object();
    
    global $CFG;
    $configs->location_url = $CFG->wwwroot.'/local/_development/20091208';
    $configs->date_url = '';
    $configs->date = '';
    global $Out;
    
    function updateDateUrl($configs){
        $configs->date_url = $configs->location_url.'/'.$configs->date;
    }
    
?>
<html>
	<head>
		<title>CC5983: VLE: 20091023: Server 1: Development</title>
	</head>
	<body>
		<h1>CC5983: VLE: 20091023: Server 1: Development</h1>
		<h2>Dates</h2>
	
		<?php 
		$configs->date = '20091208';
		updateDateUrl($configs);?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
			<li>
				<a href="<?php echo($configs->location_url)?>/SchoolService_LocalAuthorityTest.php">SchoolService_LocalAuthorityTest.php</a>:
				Quick test for Vital web service. Local Authority. 
			</li>
			<li>
                <a href="<?php echo($configs->location_url)?>/SchoolService_QuerySchoolsTest.php">SchoolService_QuerySchoolsTest.php</a>:
                Quick test for Vital web service. Schools. 
            </li>
		</ul>
		


		
	</body>
</html>