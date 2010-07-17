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
 * Function Set 2 for User actions
 * Calendar Functions
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
class zimbraActionsUser_set2 extends zimbraActionsUser_common{

	/**
	 * Creates/Modify new Calendar Event to User
	 * Mapped with externalID
	 *
	 * @param
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
	function SetCalenderEvent($cmd,$params){
		//SC,oracleid,organisator,starttime,endtime,location,subject,umailid1:umailid2:umailid3,Res1:Res2:Res3,kurztext...
		$timezone='(GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna';
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

		$soap.='<s tz="'.$timezone.'" d="'.date('Ymd\THis',$a['starttime']).'"/>';


		$soap.='<e tz="'.$timezone.'" d="'.date('Ymd\THis',$a['endtime']).'"/>';

		$a['organisator_name']=$this->getcached_name($user,$a['organisator']);
		$soap.='<or a="'.$a['organisator'].'" d="'.$a['organisator_name'].'"/>';

		$ressources=explode(':',$a['ressources']);
		foreach($ressources as $ressource){
			$name=$this->getcached_name($user,$ressource,1);
			$email=$this->getemail($ressource);

			$soap.='<at role="NON" ptst="NE" cutype="RES" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
			$soapm.='<e a="'.$email.'" p="'.$name.'" t="t"/>';

			// TXT Message
			$msg[0][0].=$name.' '.$email."\n";
			// HTML Message
			$msg[1][0].=$name.' '.$email."<br>\n";

		}

		$locations=explode(':',$a['locations']);
		foreach($locations as $location){
			$name=$this->getcached_name($user,$location,1);
			$email=$this->getemail($location);

			$soap.='<at role="NON" ptst="NE" cutype="ROO" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
			$soapm.='<e a="'.$email.'" p="'.$name.'" t="t"/>';

			// TXT Message
			$msg[0][0].=$name.' '.$email."\n";
			// HTML Message
			$msg[1][0].=$name.' '.$email."<br>\n";

		}

		$attendees=explode(':',$a['attendees']);
		foreach($attendees as $attendee){
			$name=$this->getcached_name($user,$attendee);
			$email=$this->getemail($attendee);
			$soap.='<at role="REQ" ptst="NE" rsvp="0" a="'.$email.'" d="'.$name.'"/>';
			$soapm.='<e a="'.$email.'" p="'.$name.'" t="t" add="1"/>';

			// TXT Message
			$msg[0][1].=$name.' '.$email."\n";
			// HTML Message
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

		// Create New Cal Entry
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
		// Modify Cal Entry
		}else{
			$soap='<id>'.$zimbra_calid.'</id><m><inv>'.$soap.'</m>';
			$soap=array('ModifyAppointmentRequest','zimbraMail',$soap);
			$ret=$this->dousersoap($user,$soap);
		}
	}

	/**
	 * Delete Calendar Event From User
	 * Mapped with externalID
	 *
	 * @param
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
	function DeleteCalenderEvent($cmd,$params){
		// $_GET['cmd'][1]='DCAL,947-946,mail1';

		// DCAL,oracleid,organisator
		$oracleid=$params[0];
		$useremail=$this->getemail($params[1]);

		//$zimbra_calid=getzimbraIdByOracleId($oracleid);
		$zimbra_calid=$params[0];

		//if($zimbra_calid==-1)return;

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




	/**
	 * Get the Account Name cached..
	 *
	 * @param Boolean ressource Email is an ressource (location, mietbares ding..)
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
	function doCalText($text,$params,$msg){

		// TXT MESSAGE
		//---------------------------------------------------
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

		// HTML MESSAGE
		//---------------------------------------------------
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
		// Replace critical parts...
		$replace=array(
		    'txt'=>array(array('<','>'),array('','')),
		    'html'=>array(array('<','>'),array('',''))
		);

		$txt=str_replace($replace['txt'][0],$replace['txt'][1],$txt);
		$html=str_replace($replace['html'][0],$replace['html'][1],$html);

		// Generate Soap
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

	/**
	 * Get the Account Name cached..
	 *
	 * @param Boolean ressource Email is an ressource (location, mietbares ding..)
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
	function getAccountNameCached($user,$email,$ressource=false){

		$usermail=$this->getemail($user);
		$email=$this->getemail($email);

		$cachegroup='cal_names';
		$cname=$email;

		$cache=$this->getCache($cachegroup,$cname);
		if(!$cache){

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
		return $cache;
	}
	
}
