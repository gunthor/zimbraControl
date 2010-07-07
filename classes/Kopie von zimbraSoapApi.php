<?php
/***************************************************************
*  Copyright notice
*
*  (c) Günter Homolka 2010 (g.homolka@belisk.com)
*  All rights reserved
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once 'zimbraConfig.php';

/**
 * SOAP Interface to zimbra
 *
 * @author	Günther Homolka <g.homolka@belisk.com> 
 * @package zimbraControl
 * @subpackage zimbraconfig
 * @see	t3lib_tsparser.php, t3lib_matchcondition.php
 */
// todo: error handling.
class zimbraSoapApi{

	var $_timestamp;
	
	var $client_user;
	var $client_admin;
	
	var $cacheconfig=array();
	
	// stored in file: config[admin/user][username][sessid]=sessid
	// stored in file: -""-                        [authtoken]=authtoken
	// stored in file: -""-                        [endtime]=endtime
		
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
		$this->path=dirname(__FILE__).'/';
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
	
	/**
	 *
	 *
	 * @param  $text 
	 * @param  $client 
	 * @param  $exception 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function soaperr($text,$client, $exception=''){
		
		
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
	


	/**
	 *
	 *
	 * @param  $forcenew 
	 * @param  false 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function admin_login($forcenew=false){
		// wenn nicht erzwungen und Wenn AUTH und SESSID gespeichert und die jetzt jünger als endzeit ist... 
		// => diese nehmen => return
		if(!$forcenew && isset($this->cacheconfig['admin'][$this->admin_username])){
			
			$data=$this->cacheconfig['admin'][$this->admin_username];
			if(     isset($data['sessid']) && isset($data['authtoken']) && isset($data['endtime']) 
			    && mktime()<$data['endtime']
			){
				$this->admin_sessid=$data['sessid'];
				$this->admin_authtoken=$data['authtoken'];
				return;
			}
		// Ansonsten einloggen
		}
		
		$header=new SoapHeader('urn:zimbra','context');
			
		$params=array(
			new SoapParam($this->admin_username,'name'),
			new SoapParam($this->admin_password,'password')
		);

		$options=array('uri'=>'urn:zimbraAdmin');
		
		$result=$this->dosoap('AuthRequest',$header,$params,$options,true);
		
		$this->admin_sessid=$result['sessionId'];
		$this->admin_authtoken=$result['authToken'];
		$this->admin_endtime=mktime()+$result['lifetime']/1000; // Kommt in ms an interessanter weise...?
		$this->setcachvalue();
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
						
		
		// Bei Fehlern...                  
		if(isset($response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code'])){
			
			$err=$response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code'];
			
			// Wenn AUTH fehlgeschlagen, aufhören...
			if($err=='account.AUTH_FAILED'){
				echo "AUTH FAILED";
				return false;
			
			// Wenn Fehler und newlogin nicht getan, sondern auf vergangene Session vertraut => nochmal probieren
			// mit erzwungenem einloggen
			}else if($err=='service.AUTH_REQUIRED'){
				if(!$forcenewlogin){
					return $this->dosoap_admin($action,$soap,true);				
				}else{
					echo "AUTH FAILED (ENDLESS)";
					return false;
				}
			}
		}
			
		
		return $this->xml2array($response);
	}
	
	/**
	 *
	 *
	 * @param  $forcenew 
	 * @param  false 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function user_login($forcenew=false){
		
		// wenn nicht erzwungen und Wenn AUTH und SESSID gespeichert und die jetzt jünger als endzeit ist... 
		// => diese nehmen => return
		if(!$forcenew && isset($this->cacheconfig['user'][$this->user_username])){
		
			$data=$this->cacheconfig['user'][$this->user_username];
			
			if(    isset($data['sessid']) && isset($data['authtoken']) && isset($data['endtime']) 
			    && mktime()<$data['endtime']
			  ){
				$this->user_sessid=$data['sessid'];
				$this->user_authtoken=$data['authtoken'];
				return;
			}
		// Ansonsten einloggen
		}
		
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
		
		$this->user_sessid=$result['sessionId'];
		$this->user_authtoken=$result['authToken'];
		$this->user_endtime=mktime()+$result['lifetime']/1000; // Kommt in ms an interessanter weise...?
			
		$this->setcachvalue();
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
		
		
		// Bei Fehlern...
		if(isset($response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code'])){
			
			$err=$response_a['soap:Body']['soap:Fault']['soap:detail']['Error']['Code'];
			
			// Wenn AUTH fehlgeschlagen, aufhören...
			if($err=='account.AUTH_FAILED'){
				echo "AUTH FAILED";
				return false;
			
			// Wenn Fehler und newlogin nicht getan, sondern auf vergangene Session vertraut => nochmal probieren
			// mit erzwungenem einloggen
			}else if($err=='service.AUTH_REQUIRED'){
				if(!$forcenewlogin){
					return $this->dosoap_user($urn,$action,$soap,true);				
				}else{
					echo "AUTH FAILED (ENDLESS)";
					return false;
				}
			}
		}
	
		return $this->xml2array($response);
	}
	
	/**
	 *
	 *
	 * @param  $action 
	 * @param  $header 
	 * @param  $params 
	 * @param  $options 
	 * @param  $loginaction 
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
		
		if(zimbraConfig::debug){
			echo "<pre>\n\n=============== SOAP ANFANG =======================\n";
			
			echo "\n\nREQUEST:m\n";
			print_r($this->client->__getLastRequest());
			print_r( $this->xml2array($this->client->__getLastRequest()));
			
			echo "\n\nRESPONSEm:\n";
			print_r($this->client->__getLastResponse());
			print_r( $this->xml2array($this->client->__getLastResponse()));
			echo "\n====================SOAP ENDE ==================\n\n<pre>";
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
	 *
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function builduserclient(){
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
	 *
	 *
	 * @param  $username 
	 * @param  $action 
	 * @param  $urn 
	 * @param  $soap 
	 * @param  false 
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
		
		return $a;
		
	}
	
	/**
	 *
	 *
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function buildadminclient(){
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
	
	/**
	 *
	 *
	 * @param  $username 
	 * @param  $password 
	 * @param  $action 
	 * @param  $soap 
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
		
		return $a;
	}
	
	function to_utf8($val){
		
		return utf8_encode($val);
	}
	
	function from_utf8($val){
		
		return utf8_decode($val);
	}
	
	// donnotaskey: Ausschalten: bei <a n="zimbramailirgendwas">Value</a> => array[zimbramailirgendwas]=Value
	/**
	 *
	 *
	 * @param  $contents 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function xml2array($contents){
		
		if(isset($this->options['parse']) && $this->options['parse']===false){
			return $contents;
		}
		
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
		
		
		$status = $unserializer->unserialize($contents, false);   

		if(PEAR::isError($status)){
			echo 'Error: ' . $status->getMessage();
		}else{
			$d = $unserializer->getUnserializedData();
			return $d;
		}
	}
	
	
	
	
	
	// Uploadsection...
	
	
	/**
	 *
	 *
	 * @param  $username 
	 * @param  $files 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
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
	 * get File from Web
	 *
	 * @param  $filetoupload 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	private function getUploadFile($filetoupload){
		
		$urlext=array('http');
		
		$pathtofile=$filetoupload;
		
		foreach($urlext as $prot){
			if(strpos($filetoupload,$prot.'://')!==false){
				
				// Ordner, damit der Filename erhalten bleibt!
				$pathtofile_dir=$this->path.'/'.$this->_tmpdir.'/'.mktime().'_'.rand();
				
				mkdir($pathtofile_dir,0777);
				
				// Filenamen holen..
				$fileinfo=pathinfo($filetoupload);
	
				$pathtofile=$pathtofile_dir.'/'.$fileinfo['basename'];
				
				// Datei von Internet temporär speichern, wenn vorhanden
				$fp=@fopen($filetoupload,'r');
				if($fp!==false){
					$bits=file_get_contents($filetoupload);
					file_put_contents($pathtofile,$bits);
					
					// zum löschen markieren im array
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
	 * Cleant TMP directory
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
