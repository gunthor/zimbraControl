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


/* Uses CMDs defined in zimbraCMD.php
 */
require_once 'zimbraCMD.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Parse CMD Commands to an array, which zimbraGETapi can execute
 * 
 * @package    zimbraControl
 * @author     Günter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraGETapi
 * @todo       documentate it
 * @todo       Tests that need to be made:
 *              - 
 */
class CMDParser extends zimbraCMD{

	/**
	 * Decodes an Command String to the array
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return $cmdarray
	 */
	function decode($command){
		
		$cmd=$this->splitdecoded($command);
		
		// if cmd exists...
		if(isset($this->conf[$cmd[0]])){

                        // get Conf Set
			$set=$this->conf[$cmd[0]];
			
			$ncmd=array();
			$ncmd['class']=$set['class'];
			$ncmd['action']=$set['action'];
			
			// dynamic params, from cmd
			$params=explode(',',$set['params'][0]);
			$x=1;
			$param1=array();
			foreach($params as $param){
				
				// when many "{..}"
				if(substr($param,0,1)=='{'){
					//if it's not many ({)not an array...)
					if(!is_array($cmd[$x])){
						$cmd[$x]=array($cmd[$x]);
					}
					$par=substr($param,1,-1);
				
				// optional "[..]" todo: mark it..?
				}else if(substr($param,0,1)=='['){
                                        // When many..
					if(substr($param,1,1)=='{'){
                                            //if it's not an array...
                                            if(!is_array($cmd[$x])){
                                                    $cmd[$x]=array($cmd[$x]);
                                            }
                                            $par=substr($param,2,-2);
                                        // not many   
                                        }else{
                                            //if it's an array...
                                            if(is_array($cmd[$x])){
                                                    $cmd[$x]='';
                                            }
                                            $par=substr($param,1,-1);
                                        }
				// normal, must
				}else{
					$par=$param;
					//if it's an array...
					//if(is_array($cmd[$x])){
					//	$cmd[$x]='';
					//}
				}
				// save it to params[config_key]=cmdparam1
				$param1[$par]=$cmd[$x];
				$x++; // nect config_key
			}
			$ncmd['params'][]=$param1;
			
			// static params...
			$x=0;
			foreach($set['params'] as $param){
				// first is the dynamic...
				if($x!=0){
					$ncmd['params'][]=$param;
				}else{
					$x=1;
				}
			}
			return array(null,$ncmd);
		}else{
			return array(zerror::err('fail: command does not exist in the CMD Configs of zimbraCMD.php',$cmd[0]),null);
		}
	}

	/**
	 * Split decoded
         * split by "," and then, when given, by ":" array[n][k] (k optional, when ":" given)
         * do not split when escaped ("#,", "#:")
         * replace escaped values to values ("#," => ",", "#:" => ":)
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function splitdecoded($cmd){
		// split decoded... , => yes, #, => no
		// then: [## => #][#, =>,][#: => :]
		preg_match_all('/,((?:[^#,]|#.)*)/',','.$cmd,$splits,PREG_PATTERN_ORDER);
		
		$rep=array(
			array('#,','##','#:'),
			array(',','#',':')
		);
		
		$cmds=array();
		
		// every , seperator
		foreach($splits[1] as $split){
			// split decoded... : => yes, #: => no
			preg_match_all('/:((?:[^#:]|#.)*)/',':'.$split,$splits2,PREG_PATTERN_ORDER);
			
			// everey : separator..
			// if ":" found process values...
			if(count($splits2[1])>1){
				$cmds2=array();
				foreach($splits2[1] as $split2){
					$cmds2[]=str_replace($rep[0],$rep[1],$split2);
				}
				$cmds[]=$cmds2;
			//else add value
			}else{
				$cmds[]=str_replace($rep[0],$rep[1],$split);
			}
		}
		return $cmds;
	}
	
	
	
	
	
	// Little Helpers...
	// Todo: use them...
	function getemail($email){
		if(strpos($email,'@')===false){
			$email=$email.'@'.$this->domain;
		}
			
		return $this->cleanmail($email);
	}
	
	function cleanval($value){
		return trim($value);
	}
	function cleanmail($value){
		$rep=array(array('ä','ö','ü'),array('ae','oe','ue'));
		return str_replace($rep[0],$rep[1],$value);
	}
	
	function checkmail($value){
		$email = preg_match("/[\.a-z0-9_-]+@[a-z0-9-]{2,}\.[a-z]{2,4}$/i",$value);
		return ($email!==false &&$value!='')?true:false;
	}
}
?>
