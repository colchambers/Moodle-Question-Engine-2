<?php
class Out{

	private $stream;
	private $display; // Turn on if debugging: Deprecated in lieu of enabled;
	private $silent; // Turn on if no headers should be sent: don't output anything;
	private $globalDisplay; // If set to false will not allow debugging;
	protected $recording = true;
	protected $enabled = true;
    protected $autoFlush = true;

	function __constructor(){

		$stream = "";
		$display = false; // Turn on if debugging;
		$silent = false;
		$globalDisplay = false; // If set to false will not allow debugging;
	}

	function append($text = "", $level=0, $flush=false){
		if(!$this->isEnabled() || !$this->isRecording()){
			return false;
		}
		$level++;

		$text = $this->getBacktraceAsString($level).": ".$text;

		$this->stream .= $text."<br />\n";

        if($this->isAutoFlushEnabled() || $flush){
        	$this->flush();
        }
		return true;
	}
	
    function append_html($text = "", $level=0, $flush=false){
		if(!$this->isEnabled() || !$this->isRecording()){
			return false;
		}
		$level++;

        return $this->append(htmlentities($text), $level, $flush);
	}

	function isEnabled(){
		return $this->enabled;
	}
	function enable($level = 0){
		$this->enabled = true;
		$level++;
		if($this->getDisplay()){
			echo "\n<!-- Output enabled - Called by ".$this->getBacktraceAsString($level)." -->\n";
		}
	}

	function disable($level = 0){
		$this->enabled = false;
		$level++;
		if($this->getDisplay()){
			echo "\n<!-- Output disabled - Called by ".$this->getBacktraceAsString($level)." -->\n";
		}
	}
    
    function isAutoFlushEnabled(){
        return $this->autoFlush;
    }
    
    function enableAutoFlush($level = 0){
        $this->autoFlush = true;
        $level++;
    }

    function disableAutoFlush($level = 0){
        $this->autoFlush = false;
        $level++;
    }

	function isRecording(){
		return $this->recording;
	}

	function start($level = 0){
		$level++;
		$this->record($level);
	}
	function record($level = 0){
		$this->recording = true;
		$level++;
		if(!$this->getSilent() && $this->getDisplay()){
			echo "\n<!-- begin recording Output - Called by ".$this->getBacktraceAsString($level)." -->\n";
		}
	}

	function stop($level = 0){
		$this->recording = false;
		$level++;
		if($this->getDisplay()){
			echo "\n<!-- stopped recording Output - Called by ".$this->getBacktraceAsString($level)." -->\n";
		}
	}
    
    function pause($level = 0){
        $this->recording = false;
        $level++;
        if(!$this->getSilent() && $this->getDisplay()){
            echo "\n<!-- paused Output - Called by ".$this->getBacktraceAsString($level)." -->\n";
        }
    }

	function getBacktraceAsString($level){
		//$level++;
		$backtrace = GetBacktraceFromStack($level);
		$backtrace2 = GetBacktraceFromStack($level+1);
		return $backtrace["file"].": ".$backtrace2["function"]." (".$backtrace["line"].")";
	}

	function getGlobalDisplay(){
		return $this->globalDisplay;
	}

	function setGlobalDisplay($newGlobalDisplay){
		$this->globalDisplay = $newGlobalDisplay;

	}

	function getDisplay(){
		return $this->display;
	}

	function setDisplay($newDisplay){
		$this->display = $newDisplay;
	}
	
    function getSilent(){
		return $this->silent;
	}

	function setSilent($newSilent){
		$this->silent = $newSilent;
	}

	function clean(){
		$this->stream="";
	}

	function flush($flushAsp = false, $level = 0){
		$level++;
		if($this->getDisplay()){
			echo "\n<!-- Flushing Output - Called by ".$this->getBacktraceAsString($level)." -->\n";
			echo $this->stream;
		}
		$this->clean();
		if($flushAsp){
		//	Response.Flush();
		}
	}
    
    /*
     * Rather than just terminating a script without any knowledge of where the
     * termination occurred. During debugging use this method to record exactly
     * where the script exits and ensure the $Out stream is flushed. 
     */
    function exitScript(){
    	$this->append('Exiting script', 1);
        $this->flush();
        exit();
    }


	// object o, String text, boolean full
	function iterate($o, $text = "", $full = false, $level = 0){
		$level++;

		if($o){
			$o = (object) $o;
			$this->append("Properties in " . $text.":", $level);
			//$this->append("Out.iterate: o.toString() " . $o->toString());
			$property = "";
			foreach($o as $name => $value){
				$property = $name;
				if($full){
				//$this->append("Out.iterate: gettype(\$value) = ".gettype($value));
				//$this->flush();
					if(gettype($value)=="object"){
						$property.=": ".get_class($value);
					}
					else {
						$property.=": ".$value;
					}
				}
				$this->append($property, $level);
			}
		}
		else {
			$this->append($text." is empty.", $level);
		}
;
	}

	// array a, String text
	function loop($a, $text = "", $level = 0){
		$level++;
		if($a){
			$this->append("Properties in " . $text, $level);
			$index = "";
			for($x=0;$x<count($a);$x++){
				$index = $x .": ".$a[$x];
				$this->append($index, $level);
			}
		}
		else {
			$this->append($text." is empty.", $level);
		}
	}

	// array a, String text
	function showDataArray($a, $text = "", $level = 0){
		$level++;
		if($a){
			$this->append("Out.showDataArray: Properties in " . $text, $level);
			$index = "";
			for($x=0;$x<count($a);$x++){
				$index = $x .": ".$a[$x];
				$this->append($index, $level);
				//$this->append("Out.showDataArray: Before iterate", $level);
				$this->iterate($a[$x], $x, true, $level);
				//append("Out.showDataArray: After iterate", $level);
			}
		}
		else {
			$this->append("Out.showDataArray: ".$text." is empty.", $level);
		}
	}

	function boolean($var, $text="", $level=0){
		$level++;

		if(gettype($var)=="NULL"){
			$this->append("Out.boolean: ".$text." is null.", $level);
			return;
		}

		if(gettype($var)!="boolean"){
			$this->append("Out.boolean: ".$text." is $var.", $level);
			return;
		}

		if($var){
			$this->append("Out.boolean: ".$text." is true.", $level);
			return;
		}
		$this->append("Out.boolean: ".$text." is false.", $level);
			return;
	}

	function backtrace($traceLevel=0, $text="The calling backtrace is ", $level=0){
		$traceLevel++;
		$level++;
		//$this->append($text.": ".$this->getBacktraceAsString($traceLevel), $level);
		$this->append($text.": ", $level);
		$trace = '';
		for($i=$level;$i<$traceLevel;$i++){
			$trace.="Level {$i}: ".$this->getBacktraceAsString($i)."<br />";
		}
		$this->append($trace, $level);
	}

	function type($var, $text="", $level=0){
		$level++;
		$this->append($text." is of type: ".gettype($var), $level);
	}

	function print_r($var, $text="", $level=0, $flush=false){
		$level++;
		
		if(empty($var)){
			$this->append("$text empty", $level, $flush);
			return;
		}
		$this->append("$text<pre>".print_r($var, true)."</pre>", $level, $flush);
	}

	function var_dump($var, $text="", $level=0){
		$level++;
		ob_start();
		var_dump($var, true);
		$dump = ob_get_contents();
		ob_clean();
		$this->append("$text<pre>".$dump."</pre>", $level);
	}

	function var_export($var, $text="", $level=0){
		$level++;
		$this->append("$text<pre>".var_export($var, true)."</pre>", $level);
	}
    
    function traverse_xmlize($var, $text="", $level=0){
    	// Moodle specific method. Not sure if it will return the output to add to the stream. 
        $level++;
        $output = traverse_xmlize($var);
        $this->append("$text<pre>".var_export($var, true)."</pre>", $level);
    }
    
    /*
     * Print a moodle recordset in a table for easy viewing
     */
    function print_records($records, $text="", $level=0, $flush=false){
        
        $level++;
        
        if(empty($records)){
            $this->append("$text empty", $level, $flush);
			return;
        }
        
        $output = '<table>';
        $value = null;
        $record = null;
        
        // create header row
        if($record = current($records)){
            $output .= '<tr>';
            foreach($record as $name => $field){
                $value = '';
                if(!empty($name)){
                    $value = $name; 
                }
                $output .= '<td>'.$value.'</td>';
            }
            $output .= '</tr>';
        }
        
        // create child rows
        foreach($records as $record){
            $output .= '<tr>';
            
            foreach($record as $field){
                $value = '';
                if(!empty($field)){
                    $value = $field; 
                }
                $output .= '<td>'.$value.'</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</table>';
        
        $this->append("$text<pre>".$output."</pre>", $level, $flush);
    }
    
/*
     * Print a moodle recordset in a table for easy viewing
     */
    function print_recordset($recordset, $text="", $level=0, $flush=false){
        
        $level++;
        
        if (!$recordset || $recordset->RecordCount() < 0) {            
            $this->append("$text empty", $level, $flush);
            return;
        }
        
        $text.= '('.$recordset->RecordCount().')';
        
        $output = '<table>';
        $value = null;
        $record = null;
        
        // create header row
        if($fields = $recordset->fields){
            $output .= '<tr>';
            foreach($fields as $name => $value){
                $output .= '<td>'.$name.'</td>';
            }
            $output .= '</tr>';
        }
        
        // create child rows
        while (!$recordset->EOF) {
            $output .= '<tr>';
            $record = $recordset->fields;
            foreach($record as $key => $value){
                $output .= '<td>'.$value.'</td>';
            }
            $output .= '</tr>';
            $recordset->MoveNext();
        }
        $output .= '</table>';
        
        $this->append("$text<pre>".$output."</pre>", $level, $flush);
    }
    
    // just adding the helper function here until I find some where better
    function disable_xdebug(){
        xdebug_disable();
    }

}

?>