<?php
    require_once("../../config.php");
    
    require_login();
    require_capability('moodle/site:doanything',get_context_instance(CONTEXT_SYSTEM));
    $configs = new Object();
    
    global $CFG;
    $configs->location_url = $CFG->wwwroot.'/local/_development';
    $configs->local_url = $CFG->wwwroot.'/local';
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
		
		<h2>Core</h2>
		<?php 
        $configs->date = 'Local';
        updateDateUrl($configs);
        ?>
		<h3><?php echo($configs->date);?></h3>
        <ul>
            <li>
                <a href="<?php echo($configs->local_url)?>/services/stupid.php">services/stupid.php</a>:
                Basic test of web services.
            </li>
            <li>
                <a href="<?php echo($configs->local_url)?>/iplookup/index.php">iplookup/index.php</a>:
                Basic test of web services.
            </li>
            
        </ul>
        
		<h2>Dates</h2>
		
		<?php 
        $configs->date = '20091208';
        updateDateUrl($configs);
        ?>
        <h3><?php echo($configs->date);?></h3>
        <ul>
            <li>
                <a href="<?php echo($configs->date_url)?>/index.php">index.php</a>:
                Index file.
            </li>
            <li>
                <a href="<?php echo($configs->date_url)?>/scratch.php">scratch.php</a>:
                Scratch file for testing concepts etc.
            </li>
            <li>
                <a href="<?php echo($configs->date_url)?>/tables.php">tables.php</a>:
                Simple interface onto the db.
            </li>
            <li>
                <a href="<?php echo($configs->date_url)?>/tables.2.php">tables.2.php</a>:
                Another simple interface onto the db.
            </li>
            <li>
                <a href="<?php echo($configs->date_url)?>/view.php.mobile.20091210 1026.htm">view.php.mobile.20091210 1026.htm</a>:
                Static page for mobile css testing.
            </li>
        </ul>
        
		<?php 
		$configs->date = '20090827';
		updateDateUrl($configs);
		?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
		    <li>
                <a href="<?php echo($configs->date_url)?>/index.php">index.php</a>:
                Index file.
            </li>
			<li>
				<a href="<?php echo($configs->date_url)?>/scratch.php">scratch.php</a>:
				Scratch file for testing concepts etc.
			</li>
			<li>
                <a href="<?php echo($configs->date_url)?>/tables.php">tables.php</a>:
                Simple interface onto the db.
            </li>
		</ul>
		
		<?php 
		$configs->date = '20080917';
		updateDateUrl($configs);?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
			<li>
				<a href="<?php echo($configs->date_url)?>/reloadCourse.php">reloadCourse.php</a>:
				Set of tools for backing up and restoring courses easily. 
			</li>
		</ul>
		
		<?php 
		$configs->date = '20090227';
		updateDateUrl($configs);?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
			<li>
				<a href="<?php echo($configs->date_url)?>/scratch.php">scratch.php</a>:
				Scratch file for testing concepts etc.
			</li>
			<li>
				<a href="<?php echo($configs->date_url)?>/set_vle_defaults.php">set_vle_defaults.php</a>:
				Set the defaults for the VLE install to match learn.open.ac.uk
			</li>
			<li>
				<a href="<?php echo($configs->date_url)?>/set_vle_defaults_sa.php">set_vle_defaults_sa.php</a>: 
				Set the required defaults for the structured authoring system for the VLE install. Sets the promises password to allow publishing content etc.
			</li>
		</ul>
		
		<?php 
		$configs->date = '20090602';
		updateDateUrl($configs);
		?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
			<li>
				<a href="<?php echo($configs->date_url)?>/scratch.php">scratch.php</a>:
				Scratch file for testing concepts etc.
			</li>
		</ul>
		
		<?php 
		$configs->date = '20090715';
		updateDateUrl($configs);
		?>
		<h3><?php echo($configs->date);?></h3>
		<ul>
			<li>
				<a href="<?php echo($configs->date_url)?>/editor.php">editor.php</a>:
				File for testing html editor concepts etc.
			</li>
			<li>
				<a href="<?php echo($configs->date_url)?>/editor_2.php">editor_2.php</a>:
				File for testing html editor concepts etc.
			</li>
		</ul>
		
	</body>
</html>