<?PHP
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
 * Shell Actions
 * 
 * @package    zimbraControl
 * @author     Günter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraGETapi
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraShellApi{
	
	# Internal Vars
	var $sysuser;
	var $vuser;
	var $activeuser;
	var $docmdlog=true;
	
	var $config;
	var $shell;
	
	var $errlog='';
	var $cmdlog='';
	var $readylog=array();
	
	var $isinitialized=false;
	

	/**
	 * Constructor
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return void
	 */
	private function init(){
		
		if(zimbraConfig::_ssh_server=='' || zimbraConfig::_ssh_port=='' ||zimbraConfig::_ssh_user=='' ||zimbraConfig::_ssh_pass=='' ||zimbraConfig::_ssh_domain=='' || zimbraConfig::_shellZimbraUser==''){
			return array(zerror::err('False Configuration',null,zerror::critical),null);
		}
	
		$this->sysuser=zimbraConfig::_ssh_user.'@'.zimbraConfig::_ssh_domain.':';
		$this->vuser=zimbraConfig::_shellZimbraUser.'@'.zimbraConfig::_ssh_domain.':';
		
		// do connect...
		if(!($con=ssh2_connect(zimbraConfig::_ssh_server,zimbraConfig::_ssh_port))){
			return array(zerror::err('fail: unable to establish connection',null,zerror::critical),null);
			
		}else{
			if(!ssh2_auth_password($con,zimbraConfig::_ssh_user,zimbraConfig::_ssh_pass)) {
				return array(zerror::err('fail: unable to authenticate',null,zerror::critical),null);
				
			}else{
				$this->cmdlog.="connection established\n";
				
				if(!($this->shell=ssh2_shell($con,'ssh',null,100,100,SSH2_TERM_UNIT_CHARS))){
					return array(zerror::err('fail: unable to establish shell',null,zerror::critical),null);
				
				}else{
					$this->cmdlog.="shell established\n\n";
				}
			}
		}

		
		// CMD LOG disable
		//$this->docmdlog(false);
			
		// read welcome message				
		list($err,$ret)=$this->doread($this->sysuser);
		if($err){
			return array($err,null);
		}
		// CMD LOG enable
		$this->docmdlog(true);
		
		// welcome message	
		#echo "-".$ret."-\n";
		
		
		// change user to zimbrauser
		$this->activeuser=$this->vuser;
			
		list($err,$ret)=$this->dowrite('sudo su '.zimbraConfig::_shellZimbraUser);
		
		if($err || strpos($ret,'~~~')!==false){
			return array(zerror::err($err.$ret,null,zerror::critical),null);
		}
	
		// blank return => all okay.	
		return array(null,true);
	}
	
	/**
	 * Destructor
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function __destruct(){
		try{
			fclose($this->shell);
			zlog::shelllog($this->errlog,$this->cmdlog);
		}catch(Exception $e){
		}
	}

	/**
	 *
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function do_cmd($cmd){
		list($err,$output)=$this->dowrite($cmd);
		if($err){
			return array($err,null);
		}
		
		// Wenn Fehler aufgetreten
		if(strpos($output,'ERROR')!==false){
			$this->errlog($cmd."\n".$ret);
		}
		return array(null,$output);
	}
	
	/**
	 *
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function dowrite($cmd){

		try{
			fwrite($this->shell,$cmd."\n");
		}catch(Exception $e){
			return array(zerror::err('fail: unable to write command',$e,zerror::critical),null);
		}
		list($err,$output)=$this->doread($this->activeuser,$cmd);
		
		//echo "\n\n<pre>KK--$output--$cmd--".$this->activeuser.'/$--KK</pre>'."\n\n";

		// Remove command, next commandline and trim the result..
		$t2=str_replace(trim($cmd),'',trim($output));
		$t2=str_replace($this->activeuser.'/$','',$t2);
		$t2=str_replace($this->activeuser.'/root$','',$t2);
		$t2=trim($t2);
		
		//echo "\n\n<pre>return--$t2--</pre>\n\n";
		
		return array(null,$t2);
	}
	
	
	/**
	 *
	 *
	 * @param  $endstr 
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function doread($endstr='',$cmd=''){
		$ts=time();
		$mtime=10;
		$t='';$t1='';
			
		if($endstr==''){
			return array(zerror::err('fail: Enddelimiter failed',null,zerror::critical),null);
		}else{
			do{
				sleep(0.2);
				try{
					$t1=fread($this->shell,40);
				}catch(Exception $e){
					return array(zerror::err('fail: unable to read shell output',$e,zerror::critical),null);
				}
				// it may wants a password up and there...
				if(strpos($t1,'[sudo] password for')!==false){
					try{
						fwrite($this->shell,zimbraConfig::_ssh_pass."\n");
						$t1.=" Have written sys-password!\n";
					}catch(Exception $e){
						return array(zerror::err('fail: unable to write password)',$e,zerror::critical),null);
					}
				}
				
				$t.=$t1;
			}while(strpos($t,$endstr)===false && (time()-$ts)<$mtime);
			
			//Clean wenn Command was over 50characters...
			//if(strrpos($t,'')!==false){
			if(strrpos($t,chr(0x08))!==false){
				$t=$this->over50normalize($t);
			}
		}	
		
		if((time()-$ts)>=$mtime){
			$t.="\nTimeout Error maxtime: ".$mtime."s\n";
			zlog::errlog(zerror::err("\nTimeout Error maxtime: ".$mtime."s\n",null,zerror::notcritical),$t);
		}
		if($this->docmdlog){
			$this->cmdlog.=$t;
		}else{
			$this->cmdlog.=$cmd."\nZimbraSSH: Return Log momentary for this command disabled\n".strrchr($t,$endstr);
			$this->cmdlog2=$t;
		}
		return array(null,$t);		
	}
	
	/**
	 *
	 *
	 * @param  $str 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function over50normalize($str){
		$c='';
		$zeilen=explode(chr(0x0D),$str);
		foreach($zeilen as $z){
			$z=str_replace(chr(0x0D),'',$z);
			$t1=strrpos($z,chr(0x08));
			if($t1===false){
				$c.=$z;
			}else{
				//Pos von drei leerzeichen - 1 zeichen (das davor wird gebraucht!)
				//Letztes Backspace Zeichen (davor) + das Backspacezeichen, (das danach wird gebraucht!)
				$c.=substr($z,strpos($z,'   ')-1,1).substr($z,$t1+1,strlen($z)-($t1+1));
			}
		
		}
		return $c;
	}
	
	/**
	 *
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function getcmdlog(){
		return $this->cmdlog;	
	}

	/**
	 *
	 *
	 * @param  $doit 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function docmdlog($doit){
		$this->docmdlog=($doit)?true:false;
	}
}
?>
