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

require_once 'zimbraActionsUser_common.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Function Set 1 for User actions
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
 * @todo       rewrite it to common way
 * @todo       Tests that need to be made:
 *              - 
 */
 class zimbraActionsUser_set1 extends zimbraActionsUser_common{

	//
	/*
	|  Sonstige funktionen
	*/
	// To Test, Todo
	function douserfixargs($cmd,$params){
		if($cmd=='SF'){
			echo ";;";
			$email=$this->getemail($params[0]);
			$soap=array('GetFolderRequest','zimbraMail','<folder path="inbox/erledigt"/>');
			$ret=$this->dousersoap($email,$soap);
				
			p($ret);
		}
	}
	
	/*
	|    USER Funktionalität
	|
	*/
	// To Test, Todo
	

	/**
	 * Get Folder Id
	 *
	 * @param  $uid 
	 * @param  $foldername 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return folderid, or -1 if not successfull
	 */
	public function getFolderid($uid,$foldername){
	
		$cachegroup='folders_'.$cmd['uid'];
		$cname='f_'.$cmd['folder'];
		
		// getfolderid
		$efolderid=$this->getCache($cachegroup,$cname);
		if($efolderid===false){
			$soap=array('GetFolderRequest','zimbraMail','<folder path="'.$cmd['folder'].'"/>');
			$ret=$this->dousersoap($cmd['uid'],$soap);
			if(!isset($ret)){
				return -1;
			}
			$efolderid=$ret['soap:Body']['GetFolderResponse']['folder']['id'];
			if(!is_numeric($efolderid)){
				return -1;
			}
				
			$this->setCache($cachegroup,$cname,$efolderid);
		}	
		return $efolderid;
	}
	
	/**
	 *
	 *
	 * @param  $zmailid 
	 * @param  $zfolderid 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function moveMailTo($zmailid,$zfolderid){
					// Mail verschieben...
				$soap='
<action>
<id>'.$zmailid.'</id>
<op>move</op>
<l>'.$zfolderid.'</l>
</action>
';
				$soap=array('ConvActionRequest','zimbraMail',$soap);
				return $this->dousersoap($email,$soap);
	}
	
	
	
	
	/**
	 * get New Emails of folder folder
	 * cmd[uid]=uid, [folder]=folder, defautl:inbox, [limit]=limit
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function getEmailsFromFolder($cmd){
	
	
		if(!$this->check($cmd['folder'],0))$cmd['folder']='inbox';
		

			
		// Get All Emails in Folder Meldesystem...
		$soap='
<query>in:"'.$cmd['folder'].'"</query>
<limit>2</limit>
';
		$soap=array('SearchRequest','zimbraMail',$soap);
		$ret=$this->dousersoap($email,$soap);
		
		// Nur eine Nachricht da => dann kein "richtiges" array...
		if(isset($ret['soap:Body']['SearchResponse']['c']['d'])){
			$ret['soap:Body']['SearchResponse']['c']=array($ret['soap:Body']['SearchResponse']['c']);
		}
		
		$meldungen=&$ret['soap:Body']['SearchResponse']['c'];
		
		return $meldungen;
	}
	
	/**
	 *
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function getDetailedEmailsFromFolder($cmd){
		
		$meldungen=$this->getEmailsFromFolder($cmd);
		
		$mailstorage=array();
		
		// Jede Nachricht durchgehen...
		foreach($meldungen as $m){
			// Mail als ein ganzer holen....
			$soap='
<query>in:"inbox"</query>
<cid>'.$m['id'].'</cid>
<fetch>all</fetch>
<read>1</read>
<html>1</html>
';
			$soap=array('SearchConvRequest','zimbraMail',$soap);
			$ret=$this->dousersoap($email,$soap);
				
			// Wenn ein Nachrichtenarray (conversation) da ist: nur den letzen "Stand" nehmen.
			if(!isset($ret['soap:Body']['SearchConvResponse']['m']['d'])){
				$ret['soap:Body']['SearchConvResponse']['m']=$ret['soap:Body']['SearchConvResponse']['m'][0];
			}
				
			// Array damit ausfüllen...
			$m1=&$ret['soap:Body']['SearchConvResponse']['m'];
				
			$m2=array(
				'id'=>$m1['cid'],
				'tmstamp'=>$m1['d']/1000,
				'date'=>date('d.m.Y H:i:s',$m1['d']/1000),
				'subject'=>$m1['su'],
				'preview'=>$m1['fr'],
				'persons'=>$this->doReceiptHeaders($m1['e']),
				'html_text'=>$m1['mp']['ct'],
			);
			// Content fetchen...
			// Todo: do it right...
			if(is_array($m1['mp']['mp'])){
					
				foreach($m1['mp']['mp'] as $val){
						
					if($val['ct']=='text/plain'){
						
						$m2['content']['txt']=$val['content'];
					}else if($val['ct']=='text/html'){
						$m2['content']['html']=$val['content'];
					}
				}
			}else{
				$m2['content']['txt']=$m1['mp']['content'];
			}
			$mailstorage[]=$m2;
		}
		
		return $mailstorage;
	}

	
	// Für above...
	private function doReceiptHeaders($from){
		$ret=array();
		
		foreach($from as $f){
			$name=$f['p'].'( '.$f['d'].' )';
			$email=$f['a'];
			
			if(strpos($f['t'],'t')){
				$ret['to'][]=array('name'=>$name,'email'=>$email);
			}
			
			if(strpos($f['t'],'f')){
				$ret['from'][]=array('name'=>$name,'email'=>$email);
			}
			
			if(strpos($f['t'],'c')){
				$ret['cc'][]=array('name'=>$name,'email'=>$email);
			}	
		}
		return $ret;
	}
	
	/*
	function doDatabaseRequest($values){
		echo "doDatabaseRequest";
		p($values);
		ob_start();
		print_r($values);
		$log1=ob_get_clean();
		dolog('test.log',$log1,false);
	
	}
	*/
	
	
	
	
	
	/*
	|    Neuer Kalendereintrag.
	|  ende haut auch nicht hin...
	| Kalenderfunktionen...
	*/
	// To Test, Todo
	function cal($cmd,$params){
		if($cmd=='SCAL'){

			//SC,oracleid,organisator,starttime,endtime,location,subject,umailid1:umailid2:umailid3,Res1:Res2:Res3,kurztext...

			$reihe=array('oracleid','organisator','starttime','endtime','location','subject','attendees','ressources','locations','kurztext');
			
			$a=array();
			
			// Reihenwerte in assoz array speichern..		
			foreach($reihe as $key){
				$val=array_shift($params);
				if($val==''){
					continue;
				}
				$a[$key]=$this->cleanval($val);
			}
			p($a);
			$user=$this->getemail($a['organisator']);
			
			$msg=array(array('',''),array('',''));
			$soapcomp='';
			$soapm='';
			
			$soap='';
			
			$allday=0;
			if($a['endtime']=='-1'){
				$allday=1;
				$a['endtime']=$a['starttime'];
			}
			
			$a['starttime']=$this->TimeToStamp($a['starttime'],0);
			$a['endtime']=$this->TimeToStamp($a['endtime'],0);
			$a['organisator']=$this->getemail($a['organisator']);
			
			$soap.='<comp status="CONF" fb="B" class="PRI" transp="O" allDay="'.$allday.'" name="'.$a['subject'].'" loc="'.$a['location'].'">';
			
			$soap.='<s tz="(GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna" d="'.date('Ymd\THis',$a['starttime']).'"/>';

			
			$soap.='<e tz="(GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna" d="'.date('Ymd\THis',$a['endtime']).'"/>';
				
			$a['organisator_name']=$this->getcached_name($user,$a['organisator']);
			$soap.='<or a="'.$a['organisator'].'" d="'.$a['organisator_name'].'"/>';
			
			$ressources=explode(':',$a['ressources']);
			foreach($ressources as $ressource){
				$name=$this->getcached_name($user,$ressource,1);
				$email=$this->getemail($ressource);
				
				$soap.='<at role="NON" ptst="NE" cutype="RES" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
				$soapm.='<e a="'.$email.'" p="'.$name.'" t="t"/>';
				
				$msg[0][0].=$name.' '.$email."\n";
				$msg[1][0].=$name.' '.$email."<br>\n";
				
			}
			
			$locations=explode(':',$a['locations']);
			foreach($locations as $location){
				$name=$this->getcached_name($user,$location,1);
				$email=$this->getemail($location);
				
				$soap.='<at role="NON" ptst="NE" cutype="ROO" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
				$soapm.='<e a="'.$email.'" p="'.$name.'" t="t"/>';
				
				$msg[0][0].=$name.' '.$email."\n";
				$msg[1][0].=$name.' '.$email."<br>\n";
				
			}
			
			$attendees=explode(':',$a['attendees']);
			foreach($attendees as $attendee){
				$name=$this->getcached_name($user,$attendee);
				$email=$this->getemail($attendee);
				
				$soap.='<at role="REQ" ptst="NE" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
				$soapm.='<e a="'.$email.'" p="'.$name.'" t="t" add="1"/>';
				
				$msg[0][1].=$name.' '.$email."\n";
				$msg[1][1].=$name.' '.$email."<br>\n";
			}
			
			$soap.='
<alarm action="DISPLAY">
<trigger>
<rel m="10" related="START" neg="1"/>
</trigger>
</alarm>
';
		
		
			$soap.='</comp></inv>'.$soapm;
			
			$soap.='<su>'.$a['subject'].'</su>';
			$soap.=$this->doCalText('Neue Sitzungsanfrage:',$a,$msg);
		
			
			//$soapm.='<attach aid="7f695385-20f0-4c8f-b73c-b40da8801a23:dda9a456-f989-46b5-92ab-90bdc1b265c0"/>';
						
			echo "======= ENDE VON DIESER SCHLANGE 1 ==============";
			$zimbra_calid=$this->getzimbraIdByOracleId($a['oracleid']);
			echo "======= ENDE VON DIESER SCHLANGE 2 ==============";
			echo "ZIMBRACALID: $zimbra_calid";
			
			// Keine Vorhanden....
			if($zimbra_calid==-1){
				
				$soap='<m><inv>'.$soap.'</m>';
				
				$soap=array('CreateAppointmentRequest','zimbraMail',$soap);
				
				$ret=$this->dousersoap($user,$soap);
				/*
				if(isset($ret['soap:Header']['context']['notify']['created']['appt']['uid'])){
					$zimbra_calid=$ret['soap:Header']['context']['notify']['created']['appt']['uid'];
					$this->setzimbraIdByOracleId($a['oracleid'],$zimbra_calid);
				}*/
				if(isset($ret['soap:Body']['CreateAppointmentResponse']['invId'])){
					$zimbra_calid=$ret['soap:Body']['CreateAppointmentResponse']['invId'];
					$this->setzimbraIdByOracleId($a['oracleid'],$zimbra_calid);
				}
			}else{
				$soap='<id>'.$zimbra_calid.'</id><m><inv>'.$soap.'</m>';
				$soap=array('ModifyAppointmentRequest','zimbraMail',$soap);
				
				$ret=$this->dousersoap($user,$soap);

			}
		
		// Kalendereintrag löschen...
		}else if($cmd=='DCAL'){	
			// $_GET['cmd'][1]='DCAL,947-946,mail1';
	
			// DCAL,oracleid,organisator
			$oracleid=$params[0];
			$useremail=$this->getemail($params[1]);
			
			//$zimbra_calid=getzimbraIdByOracleId($oracleid);
			$zimbra_calid=$params[0];
			
			
			if($zimbra_calid==-1){
				return;
			}else{
				$soap=array('GetMsgRequest','zimbraMail','<m><id>'.$zimbra_calid.'</id></m>');
				$ret=$this->dousersoap($useremail,$soap);
				
				$m1=&$ret['soap:Body']['GetMsgResponse']['m']['inv']['comp'];
				
			
				$msg=array(array('',''),array('',''));
				$soape='';
				
				foreach($m1['at'] as $empf){
					$email=$this->getemail($empf['a']);
					
					$soape.='<e a="'.$email.'" t="t"/>';
					
					// Ressource...
					if(isset($empf['cutype']) && $empf['cutype']=='RES'){

						$msg[0][0].=$empf['d'].' '.$email."\n";
						$msg[1][0].=$empf['d'].' '.$email."<br>\n";
					
					// Andere...
					}else{
						$msg[0][0].=$empf['d'].' '.$email."\n";
						$msg[1][0].=$empf['d'].' '.$email."<br>\n";
					}
				}
				
				$params=array(
'organisator'=>$m1['or']['a'],
'starttime'=>$this->TimeToStamp($m1['s']['d'],1),
'endtime'=>$this->TimeToStamp($m1['e']['d'],1),
'location'=>$m1['loc'],
'kurztext'=>$m1['fr'],
'subject'=>'su??',
'organisator_name'=>$m1['or']['d']		
);				
				$soap='';
				$soap.='<id>'.$zimbra_calid.'</id><comp>0</comp>';
				$soap.='<m>';
				$soap.='<su>Abgebrochen: Test</su>';
				
				$soap.=$this->doCalText('Der folgende Termin wurde abgesagt:',$params,$msg);
				$soap.='</m>';
				
				$soap=array('CancelAppointmentRequest','zimbraMail',$soap);
				
				$ret=$this->dousersoap($useremail,$soap);

			}


		}
	}
	
	function getcached_name($user,$email,$ressource=false){
		
		$usermail=$this->getemail($user);
		$email=$this->getemail($email);
		
		$cachegroup='cal_names';
		$cname=$email;
		
		$cache=$this->getCache($cachegroup,$cname);
		if($cache===false){
			$cache=NULL;
				
			//Ressource
			if($ressource){
				
				$soap=array('SearchCalendarResourcesRequest','zimbraAccount','<attrs>displayName</attrs><searchFilter><cond attr="mail" op="has" value="'.$email.'" /></searchFilter>');
				//"attr":"displayName","op":"has","value":"anne"
				$ret=$this->dousersoap($usermail,$soap,true);
				
				if(isset($ret['soap:Body']['SearchCalendarResourcesResponse']['calresource']['displayName']['_content'])){
					$cache=$ret['soap:Body']['SearchCalendarResourcesResponse']['calresource']['displayName']['_content'];	
				}
			// Normaler Name
			}else{
				$userdetails=$this->getAccountbyName($email);
				
				if(isset($userdetails['displayName']['_content'])){
					$cache=$userdetails['displayName']['_content'];	
				}

			}
			//":{"_jsns":"urn:zimbraAccount","attrs":",mail,zimbraCalResLocationDisplayName,zimbraCalResContactEmail,description","searchFilter":{"conds":{"cond":[{"attr":"zimbraCalResType","op":"eq","value":"Equipment"}]}}}}}
			
			$this->setCache($cachegroup,$cname,$cache);
		}
		$name=$cache;

		echo "cache: $name.";
		return $name;
	}
	
	// -1: Keine Vorhanden
	function getzimbraIdByOracleId($oracleid){
		$t=-1;
		try{
			$ret=@unserialize(@file_get_contents($this->path.'oracle_zimbra_id.txt'));
			if(isset($ret[$oracleid]) && $ret[$oracleid]!=''){
				$t=$ret[$oracleid];
			}
		}catch(Exception $e){
		}
		
		return $t;
	}
	
	function setzimbraIdByOracleId($oracleid,$zimbraid){
		try{
			$ret=@unserialize(@file_get_contents($this->path.'oracle_zimbra_id.txt'));
			$ret[$oracleid]=$zimbraid;
			
			file_put_contents($this->path.'oracle_zimbra_id.txt',serialize($ret));
			
		}catch(Exception $e){
			errlog("Couldn't save: OracleId: $oraclid => ZimbraId: $zimbraid");
		}
	
	}
	
	// 0: ddmmYYYY_hhii
	// 1: yyyymmddThhiiss
	function TimeToStamp($time,$format=0){
		
		//ddmmYYYY_hhii
		if($format==0){
			$d=substr($time,0,2);
			$m=substr($time,2,2);
			$y=substr($time,4,4);
			$h=substr($time,9,2);
			$i=substr($time,11,2);
		//20100213T142000
		}else if($format==1){
			$y=substr($time,0,4);
			$m=substr($time,4,2);
			$d=substr($time,6,2);
			$h=substr($time,9,2);
			$i=substr($time,11,2);
		}
		
		$t=mktime($h,$i,0,$m,$d,$y);
		echo date('d.m.Y H:i:s',$t);
		return $t;
	}
	
	function doCalText($text,$params,$msg){
		
		// Messages generieren...
		$txt='

'.$text.'

Betreff: '.$params['subject'].' 
Organisator: '.$params['organisator_name'].' '.$params['organisator'].' 

Ort: '.$params['location'].'
Ressourcen:'.$msg[0][0].'
Uhrzeit: '.date('d.m.Y H:i',$params['starttime']).' - '.date('d.m.Y H:i',$params['endtime']).'
 
Eingeladene Teilnehmer: '.$msg[0][1].'

*~*~*~*~*~*~*~*~*~*

Notiz
'.$params['kurztext'].'

';

		$html='
'.$text.'

Betreff: '.$params['subject'].' 
Organisator: '.$params['organisator_name'].' '.$params['organisator'].' 

Ort: '.$params['location'].'
Ressourcen:'.$msg[1][0].'
Uhrzeit: '.date('d.m.Y H:i',$params['starttime']).' - '.date('d.m.Y H:i',$params['endtime']).'
 
Eingeladene Teilnehmer: '.$msg[1][1].'

*~*~*~*~*~*~*~*~*~*

Notiz
'.$params['kurztext'].'

';
	
		$replace=array(
		'txt'=>array(array('<','>'),array('','')),
		'html'=>array(array('<','>'),array('',''))
		);
			
		$txt=str_replace($replace['txt'][0],$replace['txt'][1],$txt);
		$html=str_replace($replace['html'][0],$replace['html'][1],$html);
		
		$soap='
<mp ct="multipart/alternative">
<mp ct="text/plain">
<content>
'.$txt.'
</content>
</mp>

<mp ct="text/html">
<content>
'.$html.'
</content>
</mp>
</mp>
';
		return $soap;

	}
	
	
	
	
	
	
	// Funktioniert soweit!  ;-)
	// cmdarray: mail1,uploadfiles
	function newmsg($cmd, $params){
	
		// Todo: UTF8
		$reihe=array('user','uploadfiles');
			
		$a=array();
			
		// Reihenwerte in assoz array speichern..		
		foreach($reihe as $key){
			$val=array_shift($params);
			if($val==''){
				continue;
			}
			$a[$key]=$this->cleanval($val);
		}
		p($a);
		$useremail=$this->getemail($a['user']);
		
		
		// Array mit id's kommt zurück, gleiche Reihenfolge wie übermittelte
		$uploadfiles=explode('#',$a['uploadfiles']);
		$uploadids=$this->z->doUpload($useremail,$uploadfiles);
		
		
		p($uploadids);
		
	
		$html=<<<EOF
 <html><head><style type='text/css'>p { margin: 0; }</style></head><body><div style='font-family: Times New Roman; font-size: 12pt; color: #000000'><img src="cid:DWT244"><span style="font-weight: bold; color: rgb(255, 0, 0);">Made with zimbraGETApi<br><br><table style="border: 1px solid rgb(0, 0, 0); width: 90%; text-align: left; vertical-align: middle; border-collapse: collapse;" align="center" cellpadding="3" cellspacing="0"><tbody><tr><td style="width: 50%; border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">a<br></td><td style="width: 50%; border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">b<br></td></tr><tr><td style="border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">c<br></td><td style="border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">d<br></td></tr></tbody></table>;-)<br><br></span></div></body></html>
 		
EOF;

		$txt=<<<EOF
Made with zimbraGETApi ;-)
	
EOF;
		$html=str_replace('<','&lt;',$html);
		$txt=str_replace('<','&lt;',$txt);
		
		// UTF 8 Decode, &bnsp, &auml; usw:      [soap:Text] => parse error: Error on line 81 of document  : The entity "nbsp" was referenced, but not declared. Nested exception: The entity "nbsp" was referenced, but not declared.

		$html=utf8_encode($html);
		$txt=utf8_encode($txt);
/*
		include('sonderzeichen.php');
		$html=str_replace($fromsz,$tosz,$html);
		$txt=str_replace($fromsz,$tosz,$txt);*/
/*		


/*		
		// a=> email adresse
		// p=> personal-name
		//

		$soap='
<m>
<e t="t" a="g.homolka@belisk.com" p="Guenther Homolka1" add="1"/>
<e t="f" a="mail1@mail1.com" p="Guenther Homolka3"/>
<su>Test Email</su>

<mp ct="multipart/alternative">
	<mp ct="text/plain">
		<content>
'.$txt.'
		</content>
	</mp>
	<mp ct="text/html">

		<content>
'.$html.'
		</content>
	</mp>
	
	<mp ci="DWT244">
		<attach aid="'.$uploadids[0].'"></attach>
	</mp>
</mp>
<attach aid="'.$uploadids[1].'">
</attach>
</m>
';
*/
		$soap='
<m>
<e t="c" a="g.homolka@belisk.com" p="Guenther Homolka Inode" add="1"/>
<e t="f" a="mail1@mail1.com" p="Guenther Homolka Absender"/>
<su>Test Email</su>

<mp ct="multipart/alternative">
	<mp ct="text/plain">
		<content>
'.$txt.'
		</content>
	</mp>
	<mp ct="multipart/related">
		<mp ct="text/html">
	
			<content>
	'.$html.'
			</content>
		</mp>
		
		<mp ci="DWT244">
			<attach aid="'.$uploadids[0].'"></attach>
		</mp>
	</mp>
</mp>
<attach aid="'.$uploadids[1].','.$uploadids[2].'"></attach>
</m>
';


		$soap=array('SendMsgRequest','zimbraMail',$soap);
				
		$ret=$this->dousersoap($useremail,$soap);
		
		// ID:
		//$ret['soap:Body']['SendMsgResponse']['m']['id']
                            
		p($ret);
	}

}
