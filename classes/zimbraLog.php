<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Günter Homolka 2010 (g.homolka@belisk.com)
*  All rights reserved
*
*  The zimbraControl project is free software; you can redistribute 
*  it and/or modify it under the terms of the GNU General Public 
*  License as published by the Free Software Foundation; 
*  either version 2 of the License, or(at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once 'zimbraConfig.php';
/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Logging Actions
 * 
 * @package    zimbraControl
 * @author     Günter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraActions
 * @see        zimbraActionsAdmin_common
 * @todo       Tests that need to be made:
 *              - 
 */
class zlog{
	public static $debugLog='';

	public function log($log, $params='',$file='zimbraLog.txt'){
		if($params!=''){
		    ob_start();
		    echo "<pre>";
		    echo "$log\n";
		    print_r($params);
		    echo "</pre>";
		    $log=ob_get_clean();
		}

		$f="--------------- ".date('d.m.Y H:i')."---------------\n\n".$log."\n";

		zlog::addToLogFile($file,$log);
		
		
	}
	
	public function soapErrLog($log, $params=''){
		
	    zlog::log($log,$params,'SOAPErr.txt');
	}

	public function shelllog($errlog,$cmdlog){

		$errlog="errlog\n$errlog\n";
		$cmdlog="errlog\n$errlog\n";

		zlog::addToLogFile('shelllogCMD.txt',$errlog);
		zlog::addToLogFile('shelllogErr.txt',$cmdlog);

	}

	/**
	 * Makes a debugLog of Vars
	 *
	 * @param  $cmd
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return $cmdarray
	 */
	public function debugLog($msg, $log){

		ob_start();
		echo "<pre>";
		echo "$msg\n";
		print_r($log);
		echo "</pre>";
		$f=ob_get_clean();

		$f="--------------- ".date('d.m.Y H:i')."---------------\n\n".$f."\n";
		zlog::$debugLog=zlog::$debugLog.$f;

		echo $f;
	}

	function hardError($log, $params){
	    zlog::log($log,$params,'SOAPErr.txt');
	    echo "Hard Failure. Check LogFiles. ID:";
	    exit;
	}
	function addToLogFile($file,$log){
		if(zimbraConfig::debug){
			echo "<pre>".$file."\n".$log."</pre>";
		}else{
			$file=dirname(__FILE__).'../log/'.$file;
			
			if(file_exists($file)){
				$f=file_get_contents($file);
			}

			$f="--------------- ".date('d.m.Y H:i')."---------------\n\n".$log."\n".$f;
			file_put_contents($file,$f);
		}
	}
}
?>
