<?PHP
/***************************************************************
*  Copyright notice
*
*  (c) 2010 G�nter Homolka 2010 (g.homolka@belisk.com)
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
require_once 'zimbraSoapApi.php';
require_once 'zimbraConfig.php';
require_once 'zimbraError.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Base Class for SOAP Actions
 * Logging, Caching, Errorlog, Base Control
 * user id uid is always the email address! (when @ doesn't exist't, the default domain get's added.
 *
 * @package    zimbraControl
 * @author     G�nter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraSoapApi
 * @todo       documentate it
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraActions{
	
	var $zimbraSoap; // ZimbraSoapAPI Objekt...
	var $errstring='';
	var $cache=false;

	/**
	 * Constructor
	 *
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return void
	 */
	public function __construct(){
		$this->zimbraSoap=new zimbraSoapApi();
		$this->path=dirname(__FILE__).'/../';
	}
	

	/**
	 * Destructor
	 * save Cache
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function __destruct(){
		if($this->errstring!=''){
			doLog($this->errstring);
		}
		$this->saveCache();
	}
	
	/**
	 * Execute Admin Soap
	 *
	 * @param  $soap $soap[0]=action, $soap[1]=restliches soap
	 * @param  $showerrorsonly
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function doAdminSoap($soap,$showerrorsonly=0){
		//$username,$password,$action,$soap,$parse=false
		$config=array();
		$ret=$this->zimbraSoap->doadmin(zimbraConfig::adminAccount_user,zimbraConfig::adminAccount_password,$soap[0],$soap[1],$config);
		
		if($showerrorsonly){
			return (isset($ret['soap:Body']['soap:Fault']['soap:faultstring']))?$ret['soap:Body']['soap:Fault']['soap:faultstring']:'';
		}
		
		return $ret;
	}
	

	/**
	 * Execute User soap
	 *
	 * @param  $user userid
	 * @param  $soap $soap[0]=action, $soap[1]=urn, $soap[2]=rest of soap
	 * @param  $naskey donnotaskey: Ausschalten: bei <a n="zimbramailirgendwas">Value</a> => array[zimbramailirgendwas]=Value
	
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function doUserSoap($user,$soap,$naskey=false){
		//$username,$action,$urn,$soap,$parse=false
		$config=array('donnotaskey'=>!$naskey);
		$ret=$this->zimbraSoap->douser($user,$soap[0],$soap[1],$soap[2],$config);
		
		
		return $ret;
	}
	
	
	
	/**
	 * Logger...
	 *
	 * @param  $file 
	 * @param  $val 
	 * @param  $debug 
	 * @param  true 
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function doLog($val){
		$a="----------------------- ".date('d.m.Y H:i:s')." -----------------------\n\n".$val;
		zlog::log($a);
	}
	
	// Cacheing;
	/**
	 * Set the value of cache indexed by group and key
	 * unset = true => value get deleted.
	 * 
	 * @param  $cachegroup group of cache
	 * @param  $key  key of cached value
	 * @param  $val 
	 * @param  $unset 
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return  void
	 */
	function setCache($cachegroup, $key, $val,$unset=false){
		if(!$this->cache)$this->initCache();
		if($unset){
			//unset($this->cache[$cachegroup][$key]);
			$this->cache[$cachegroup][$key]=NULL; //??
		}else{
			$this->cache[$cachegroup][$key]=$val;
		}
	}
	
	/**
	 * Get the value of cache indexed by group and key
	 *
	 * @param String $cachegroup
	 * @param String  $key
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return mixed cached Value
	 */
	function getCache($cachegroup, $key){
		if(!$this->cache)$this->initCache();
		
		if(isset($this->cache[$cachegroup][$key])){
			return $this->cache[$cachegroup][$key];
		}
		
		return false;
	}
	
	
	/**
	 * initialize cache
	 *
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return void
	 */
	function initCache(){
		$ret='';
		try{
			$ret=@unserialize(@file_get_contents($this->path.'cache_GETAPI.txt'));
		}catch(Exception $e){
			$this->cache=array();
		}
		$this->cache=$ret;
	}
	
	/**
	 * Save Cache
	 *
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return void
	 */
	function saveCache(){
		if(is_array($this->cache)&&count($this->cache)>0){
			file_put_contents($this->path.'cache_GETAPI.txt',serialize($this->cache));
		}
	}
	
	/**
	 * trim...
	 *
	 * @param  $value 
	 * @author	G�nther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function cleanval($value){
		return trim($value);
	}
	
}

?>
