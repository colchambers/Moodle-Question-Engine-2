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
 
    global $CFG;
    require_once($CFG->dirroot .'/_tools/synch/import_all.php');
    
     /*
     * Setup some a simple debugging class that uses standard php echo and
     * print_r functionality but adds the file name and path and line number
     * from where the debugging occurs. Generally the debugging output is cached
     * and displayed at the end of the page to make it easier to read and so as
     * not to ruin the display.
     */
    global $Out;
    $Out = new Out();
    /*
     * turn outputting on for the class. If this is off it will never output
     * anything. Useful if you need to stop the class outputting completely
     */
    $Out->setGlobalDisplay(true);
    /*
     * Stop and start recording at a page or method level. It is possible to
     * turn recording off at the start of a script and only turn it on at the
     * point(s) it is needed. 
     */
    $Out->setDisplay(true);
    
    /*
     * Set to automatically flush output to the screen instead of waiting until
     * flush() is called.
     */
    $Out->enableAutoFlush();
    
    /*
     * Examples of using the debugging class
     * @ 1) simply print out a variable or any string
     * @ 2) use print_r functionality to display a compex item
     * @ 3) flush the debugger stream to the screen in cases where the script
     * doesn't finish.
     */
     //$Out->append('$CFG->synch->session_file_name = '.$CFG->synch->session_file_name);
     //$Out->print_r($CFG, '$CFG = ');
     // $Out-flush();
     
    $Out->disable_xdebug();
?>
