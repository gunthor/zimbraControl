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
 * Checks if value exists
 *
 * @param mixed var
 * @author	Günther Homolka <g.homolka@belisk.com>
 * @return value, or false if it's not existing
 */
function c(&$var){
    if(isset($var))return $var;
    return false;
}


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
 * @todo       error Handling
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraSoapApi{

	var $_timestamp;
	
	var $client_user;
	var $client_admin;
	
	var $cacheconfig=array();
	
	// stored in file: cacheconfig[admin/user][username][sessid]=sessid
	// stored in file: -""-                             [authtoken]=authtoken
	// stored in file: -""-                              [endtime]=endtime
		
	var $admin_username;
	var $user_username;
	
	var $admin_sessid;
	var $admin_authtoken;
	var $admin_password; // Not stored in file!
	var $admin_endtime;
	
	var $user_sessid;
	var $user_authtoken;
	var $user_endtime;
	
	var $path;
	var $_cachefile='cache/cache_zimbraSoap.txt';
	var $_tmpdir='tmp';
	
	var $options=array();
	var $defaultopions=array();
	
	
	var $_adminlocation=zimbraConfig::_adminlocation;
	var $_userlocation=zimbraConfig::_userlocation;
	var $_uploadlocation=zimbraConfig::_uploadlocation;
	var $_preAuthKey=zimbraConfig::_preAuthKey;
	#Exiration of preauthkey
	var $_preauth_expiration=zimbraConfig::_preauth_expiration;
	

	
	/**
	 * Execute an admin SOAP Request..
	 *
	 * @param  $username 
	 * @param  $password 
	 * @param  $action 
	 * @param  $soap
	 * @param  $options additonal options
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function doadmin($username,$password,$action,$soap,$options=false){
		$this->buildadminclient();
		
		if(count($options)>0){
			$this->options=$options;
		}else{
			$this->options=$this->defaultopions;
		}
		
		$this->admin_username=$username;
		$this->admin_password=$password;
		
		$a=$this->dosoap_admin($action,$soap);
		
		// Error loggen...
		if(isset($ret['soap:Body']['soap:Fault']['soap:faultstring'])){
		    zlog::soapErrlog($ret);
		}

		return $a;
	}
	
	/**
	 * Execute an User SOAP Request.
	 *
	 * @param  $username 
	 * @param  $action 
	 * @param  $urn 
	 * @param  $soap 
	 * @param  $options additonal options
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function douser($username,$action,$urn,$soap,$options=array()){
		
		$this->user_username=$username;
		$this->builduserclient();
		
		if(count($options)>0){
			$this->options=$options;
		}else{
			$this->options=$this->defaultopions;
		}
		
		$a=$this->dosoap_user($action,$urn,$soap);

		// Error loggen...
		if(isset($ret['soap:Body']['soap:Fault']['soap:faultstring'])){
		    zlog::soaperrlog($ret);
		}

		return $a;
		
	}
	
	/**
	 * Constructor
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function __construct(){
		if(file_exists($this->path.$this->_cachefile)){
		
			$vars=file_get_contents($this->path.$this->_cachefile);
			$vars=unserialize($vars);
			
			if(is_array($vars)){
				$this->cacheconfig=$vars;
			}
		}
		$this->_timestamp=time().'000';
		$this->path=dirname(__FILE__).'/../';
	}
	
	/**
	 *
	 *
	 * @param  $action 
	 * @param  $soap 
	 * @param  $forcenewlogin 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function dosoap_admin($action,$soap,$forcenewlogin=false){
		
		$this->admin_login($forcenewlogin);
		
		$soap_header='
<context xmlns="urn:zimbra">
<authToken>'.$this->admin_authtoken.'</authToken>
<sessionId id="'.$this->admin_sessid.'">'.$this->admin_sessid.'</sessionId> 
</context>';
		$header=new SoapHeader(
			'urn:zimbra',
			'context',
			new SoapVar($soap_header,XSD_ANYXML)
		);
		
		$params=array(
			new SoapVar($soap,XSD_ANYXML)
		);
		
		$options=array('uri'=>'urn:zimbraAdmin');

		$response=$this->dosoap($action,$header,$params,$options);
		$response_a=$this->xml2array($response);
						
		$err=c($response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code']);

		// Bei Fehlern...
		if($err){
			// Wenn AUTH fehlgeschlagen, aufhören...
			if($err=='account.AUTH_FAILED'){
				zlog::hardError('ADMIN AUTH FAILED!',array($action,$header,$params,$options));
				exit;
				
			// Wenn Fehler und newlogin nicht getan, sondern auf vergangene Session vertraut => nochmal probieren
			// mit erzwungenem einloggen
			}else if($err=='service.AUTH_REQUIRED'){
				if(!$forcenewlogin){
					return $this->dosoap_admin($action,$soap,true);				
				}else{
					zlog::hardError('ADMIN AUTH FAILED! (endless)',array($action,$header,$params,$options));
					exit;
				}
			}
		}
		
		return $response_a;
	}
	
	/**
	 * 
	 *
	 * @param  $action 
	 * @param  $urn 
	 * @param  $soap 
	 * @param  $forcenewlogin 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function dosoap_user($action,$urn,$soap,$forcenewlogin=false){
		
		$this->user_login($forcenewlogin);
		
		$soap_header='
<context xmlns="urn:zimbra">
<authToken>'.$this->user_authtoken. '</authToken>
<sessionId id="'.$this->user_sessid.'">'.$this->user_sessid.'</sessionId> 
</context>';

		$header=new SoapHeader(
			'urn:zimbra',
			'context',
			new SoapVar($soap_header,XSD_ANYXML)
		);
			
		$params=array(
			new SoapVar($soap,XSD_ANYXML)
		);
		
		$options=array('uri'=>'urn:'.$urn);
		
		$response=$this->dosoap($action,$header,$params,$options);
		$response_a=$this->xml2array($response);
		

		$err=c($response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code']);

		// Bei Fehlern...
		if($err){
			// Wenn AUTH fehlgeschlagen, aufhören...
			if($err=='account.AUTH_FAILED'){
				zlog::soapErrLog('AUTH FAILED: ');
				return false;

			// Wenn Fehler und newlogin nicht getan, sondern auf vergangene Session vertraut => nochmal probieren
			// mit erzwungenem einloggen
			}else if($err=='service.AUTH_REQUIRED'){
				if(!$forcenewlogin){
					return $this->dosoap_user($urn,$action,$soap,true);
				}else{
					zlog::soapErrLog('AUTH FAILED (endless)');
					return false;
				}
			}
		}
	
		return $response_a;
	}	
	
	/**
	 * Login an adminsitrator account
	 *
	 * @param  $forcenew if false, the last auth key will be taken, if one exists and not expired...
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function admin_login($forcenew=false){
		// if not forcenewlogin and a  AUTH and SESSID exists and they are not expired... 
		// => take them and return...
		if(!$forcenew && isset($this->cacheconfig['admin'][$this->admin_username])){
			
			$data=$this->cacheconfig['admin'][$this->admin_username];
			if(     isset($data['sessid']) && isset($data['authtoken']) && isset($data['endtime']) 
			    && mktime()<$data['endtime']
			){
				$this->admin_sessid=$data['sessid'];
				$this->admin_authtoken=$data['authtoken'];
				return;
			}
		//otherwise login admin account
		}
		
		$header=new SoapHeader('urn:zimbra','context');
			
		$params=array(
			new SoapParam($this->admin_username,'name'),
			new SoapParam($this->admin_password,'password')
		);

		$options=array('uri'=>'urn:zimbraAdmin');
		
		$result=$this->dosoap('AuthRequest',$header,$params,$options,true);
		
		// IF AUTH FAILED
		if(!$result){
			zlog::hardError('ADMIN AUTH FAILED!',array($header,$params,$options));
			exit;
		}

		// Store result to cache
		$this->admin_sessid=$result['sessionId'];
		$this->admin_authtoken=$result['authToken'];
		$this->admin_endtime=mktime()+$result['lifetime']/1000; //time comes in ms...
		$this->setcachvalue();
	}

	/**
	 *
	 * Login an User account
	 *
	 * @param  $forcenew if false, the last auth key will be taken, if one exists and not expired...
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function user_login($forcenew=false){
		
		// if not forcenewlogin and a  AUTH and SESSID exists and they are not expired... 
		// => take them and return...
		if(!$forcenew && isset($this->cacheconfig['user'][$this->user_username])){
		
			$data=$this->cacheconfig['user'][$this->user_username];
			
			if(    isset($data['sessid']) && isset($data['authtoken']) && isset($data['endtime']) 
			    && mktime()<$data['endtime']
			  ){
				$this->user_sessid=$data['sessid'];
				$this->user_authtoken=$data['authtoken'];
				return;
			}
		// otherwise login User
		}
		
		// PreAuth
		$by_value='name';
	
		$preauth_uncrypt=$this->user_username.'|'.$by_value.'|'.$this->_preauth_expiration.'|'.$this->_timestamp; 
		$preauth_crypt=hash_hmac('sha1',$preauth_uncrypt,$this->_preAuthKey);
				
		$soap='
<account by="name">'.$this->user_username.'</account> 
<preauth timestamp="'.$this->_timestamp.'" expires="'.$this->_preauth_expiration.'">'.$preauth_crypt.'</preauth>
';
		
		$header=new SoapHeader('urn:zimbra','context');
		$params=array(new SoapVar($soap,XSD_ANYXML));
		
		$options=array('uri'=>'urn:zimbraAccount');
		
		$result=$this->dosoap('AuthRequest',$header,$params,$options,true);

		// IF AUTH FAILED
		if(!$result){
			zlog::soapErrLog('AUTH FAILED: ');
			return false;
		}
		
		// Store result to cache
		$this->user_sessid=$result['sessionId'];
		$this->user_authtoken=$result['authToken'];
		$this->user_endtime=mktime()+$result['lifetime']/1000; // Kommt in ms an interessanter weise...?
			
		$this->setcachvalue();
	}
	

	
	/**
	 * Do an Soap, User or Admin, stored in $this->client
	 *
	 * @param  $action SOAP Action
	 * @param  $header SOAP Header
	 * @param  $params SOAP PARAMS
	 * @param  $options SOAP options
	 * @param  $loginaction we need to do another return..
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function dosoap($action,$header,$params,$options,$loginaction=false){
		        	
		try{
			$result = $this->client->__soapCall(
				$action, 
				$params, 
				$options,
				$header
			);

		}catch (SoapFault $exception) {
		}

		// DEbug information...
		if(zimbraConfig::debug){
			$t=array(array('<','>'),array('&lt;','&gt;'));
			ob_start();
			echo "<pre>\n\n=============== SOAP ANFANG =======================\n";
			
			echo "\n\nREQUEST:m\n";
			print_r(str_replace($t[0],$t[1],$this->client->__getLastRequest()));
			print_r( $this->xml2array($this->client->__getLastRequest()));
			
			echo "\n\nRESPONSEm:\n";
			print_r(str_replace($t[0],$t[1],$this->client->__getLastResponse()));
			print_r( $this->xml2array($this->client->__getLastResponse()));
			echo "\n====================SOAP ENDE ==================\n\n<pre>";
			$f=ob_get_clean();
			zlog::debugLog('SOAP DEBUG',$f);

		}
		
		// Bei Login kommt hier der authtoken, sessid usw. zurück.
		if($loginaction){
			if(isset($result)){
				return $result;
			}else{
				return false;
			}
		}else{
			return $this->client->__getLastResponse();
		}
	}
	
	/**
	 * LOG SOAP ERROR
	 *
	 * @param  $text 
	 * @param  $client 
	 * @param  $exception 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	/*
	private function soaperr($text,$client, $exception=''){
		
		
		ob_start();
		echo "<pre>\n\n=============== SOAP ERROR ANFANG =======================\n";
		echo "$text \n";
		
		echo "\n\nRESPONSE:m\n";
		print_r($this->client->__getLastRequest());
		print_r( $this->xml2array($this->client->__getLastRequest()));
		
		echo "\n\nRESPONSEm:\n";
		print_r($this->client->__getLastResponse());
		print_r( $this->xml2array($this->client->__getLastResponse()));
		echo "\n====================SOAP ERROR ENDE ==================\n\n<pre>";
		
		$out=ob_get_clean();
		$out=$out."\n=======================".date('d.m.Y H:i')."==============\n";
		
		zlog::errlog($out);

	}
	*/
	
	/**
	 * Build SOAP UserClinet
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function builduserclient(){
		if($this->client_user==null){
			$this->client_user=new SoapClient(null,
				array(
					'location' => $this->_userlocation,
					'uri' => "urn:zimbra",
					'trace' => 1,
					'exceptions' => 1,
					'soap_version' => SOAP_1_1,
					'style' => SOAP_RPC,
					'use' => SOAP_LITERAL
				)
			);
		}
		$this->client=&$this->client_user;
	}
	
	/**
	 * Build SOAP Admin Client
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function buildadminclient(){
		if($this->client_admin==null){
			$this->client_admin = new SoapClient(null,
				array(
					'location' => $this->_adminlocation,
					'uri' => "urn:zimbraAdmin",
					'trace' => 1,
					'exceptions' => 1,
					'soap_version' => SOAP_1_1,
					'style' => SOAP_RPC,
					'use' => SOAP_LITERAL
				)
			);
		}
		$this->client=&$this->client_admin;
	}
	
	// donnotaskey: Ausschalten: bei <a n="zimbramailirgendwas">Value</a> => array[zimbramailirgendwas]=Value
	/**
	 * Make an Array out of XML
	 *
	 * @param  $contents 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function xml2array($contents){
		
		if(isset($this->options['parse']) && $this->options['parse']===false){
			return $contents;
		}
		$errrep=error_reporting(0);
		// XML Parser herrichten...
		ini_set('include_path', $this->path.'PEAR');
		set_include_path($this->path.'PEAR');
		
		require_once 'XML/Unserializer.php';
		$options=array(
			XML_UNSERIALIZER_OPTION_ATTRIBUTES_PARSE    => true,
			//XML_UNSERIALIZER_OPTION_ATTRIBUTES_ARRAYKEY => false
		);
		
		//user options...
		if(!isset($this->options['donnotaskey']) || $this->options['donnotaskey']!=1){
			$options['keyAttribute']='n';	
		}
	
		//  be careful to always use the ampersand in front of the new operator 				
		$unserializer = new XML_Unserializer(
			$options
		);
		
		$d=false;
		
		$status = $unserializer->unserialize($contents, false);   

		if(PEAR::isError($status)){
			echo 'Error: ' . $status->getMessage();
		}else{
			$d = $unserializer->getUnserializedData();
		}
		error_reporting($errrep);
		
		return $d;
	}
	
	/**
	 * Set Cache Values
	 * called everytime a cache value get updated...
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function setcachvalue(){
		
		if($this->admin_username!=''){
			$a=&$this->cacheconfig['admin'][$this->admin_username];
			$a=array(
				'authtoken'=>$this->admin_authtoken,
				'sessid'=>$this->admin_sessid,
				'endtime'=>$this->admin_endtime
			);
			//echo "dateY: ".date('d.m.Y H:i',$a['endtime']);
		}
		
		if($this->user_username!=''){
			$a=&$this->cacheconfig['user'][$this->user_username];
			$a=array(
				'authtoken'=>$this->user_authtoken,
				'sessid'=>$this->user_sessid,
				'endtime'=>$this->user_endtime
			);
		}
		
		$vars=serialize($this->cacheconfig);
		
		file_put_contents($this->path.$this->_cachefile,$vars);
	}	
	
	function to_utf8($val){
		
		return utf8_encode($val);
	}
	
	function from_utf8($val){
		
		return utf8_decode($val);
	}
	
	#############################################################
	# Upload Section
	#############################################################
	
	
	/**
	 * Do an Upload with given user..
	 *
	 * @param  $username 
	 * @param  $files can be Server files, also 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return array with upload ids in same order as files
	 */
	public function doUpload($username, $files){
		// Authentifizieren...
		// einmal nur als user...
		// Wegen autthoken...
		
		//echo "Test".$this->user_authtoken."test";
		$retval=false;
		
		// Files uploaden...
		if(!is_array($files)){
			$files=array($files);
		}
		
		// Post array vorbereiten...
		$post=array();
		
		$i=0;	
		foreach($files as $filetoupload){
			$filepath=$this->getUploadFile($filetoupload);
			if($filepath!==false){
				$post['file'.$i]='@'.$filepath;
				$i++;
			}
		}
		
		// Wenn noch nicht eingellogged => einloggen
		if(strlen($this->user_authtoken)<3){
			$this->user_username=$username;
			$this->builduserclient();
			$this->user_login(true); // with forcenew...
		}
		
		$post['requestId']='client_token'; // Hier kann man irgendwas einfügen...

		// Wenn Files zum uploaden da...
		if($i>0){
			
			$this->builduserclient();
			$this->user_username=$username;
			$this->user_login();
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
			curl_setopt($ch, CURLOPT_URL, $this->_uploadlocation.'?fmt=raw' );
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_COOKIE,'ZM_AUTH_TOKEN='.$this->user_authtoken);
			
			// Vorfühlen
			$response = curl_exec($ch);
			if(strpos($response,'HTTP ERROR: 401')!==false){
				echo "AUTH FAILED1";
				// Neu Login erzwingen...
				$this->user_login(true);
				curl_setopt($ch, CURLOPT_COOKIE,'ZM_AUTH_TOKEN='.$this->user_authtoken);
				$response = curl_exec($ch);
			}
			
			// Wenn leerer Upload geklappt hat
			if(strpos($response,"204,'null'")!==false){
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
				$response = curl_exec($ch);
				
				$response=preg_match('/200,\'client_token\',\'(.*)\'/msU',$response,$out);
				$retval=explode(',',$out[1]);
			
			// Wenn nicht...
			}else{
				echo "Fehler: AUTH";
				p($response);
			}
			
			//p($out);
			$this->cleantmp_filedir();

		}else{
			echo "No files to Upload";
		}
		
		return $retval;
	}
	
	
	// [i][0]=ordner
	// [i][1]=file (Ohne path!)
	/**
	 *
	 */
	var $tmp_filedir=array();

	/**
	 * get File, maybe from Web
	 *
	 * @param  $filetoupload 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function getUploadFile($filetoupload){
		
		$urlext=array('http');
		
		$pathtofile=$filetoupload;
		
		foreach($urlext as $prot){
			
			// if it's an Webfile...
			if(strpos($filetoupload,$prot.'://')!==false){
				
				// Ordner, damit der Filename erhalten bleibt!
				$pathtofile_dir=$this->path.'/'.$this->_tmpdir.'/'.mktime().'_'.rand();
				
				mkdir($pathtofile_dir,0777);
				
				// Filenamen holen..
				$fileinfo=pathinfo($filetoupload);
	
				$pathtofile=$pathtofile_dir.'/'.$fileinfo['basename'];
				
				// save file temporaly on server, for uploading
				// Check, if it exists..
				$fp=@fopen($filetoupload,'r');
				if($fp!==false){
					// filegetcontent is binary safe.
					$bits=file_get_contents($filetoupload);
					file_put_contents($pathtofile,$bits);
					
					// mark it for delete
					$this->tmp_filedir[]=array($pathtofile_dir,$fileinfo['basename']);
				
				}
				if($fp!==false)fclose($fp);
	
				break;
			}
		}
		
		// Wenn datei nicht lokal exisitiert...
		if(!file_exists($pathtofile)){
			echo "File not found: $filetoupload<br>\n";
			return false;
		}
		
		return $pathtofile;
	}
	
	/**
	 * Clean marked files and dirs...
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function cleantmp_filedir(){
		if(isset($this->tmp_filedir)){
			foreach($this->tmp_filedir as $d){
				unlink($d[0].'/'.$d[1]);
				rmdir($d[0]);
			}
		}
	}
	
}
?>
