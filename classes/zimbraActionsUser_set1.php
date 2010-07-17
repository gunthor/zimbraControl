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

	/**
	 * get New Emails of folder from user
	 * cmd[uid]=uid, [folder]=folder, defautl:inbox, [limit]=limit
	 *
	 * @param  $cmd 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function getEmailsFromFolder($user, $foldername,$limit){
	
	
		if(!$this->check($cmd['folder'],0)){
		    $cmd['folder']='inbox';
		}
		

			
		// Get All Emails in Folder Meldesystem...
		$soap='
<query>in:"'.$cmd['folder'].'"</query>
<limit>'.$limit.'</limit>
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
}
