<?PHP
/***************************************************************
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

require_once 'zimbraActions.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Common Functions for User SOAP Actions
 * 
 * @package    zimbraControl
 * @author     Günter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraActions
 * @todo       documentate it
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraActionsUser_common extends zimbraActions{
	/**
	 * Get Folder Id of folder from user
	 *
	 * @param String $uid user@domain.com
	 * @param String $folderpath (e.g. inbox/dir1)
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return folderid, or false if not successfull
	 */
	public function getFolderid($uid,$folderpath){

		$cachegroup='folders_'.$cmd['uid'];
		$cname='f_'.$cmd['folder'];

		// getfolderid
		$efolderid=$this->getCache($cachegroup,$cname);
		if($efolderid===false){
			$soap=array('GetFolderRequest','zimbraMail','<folder path="'.$folderpath.'"/>');
			$ret=$this->dousersoap($cmd['uid'],$soap);

			$efolderid=c($ret['soap:Body']['GetFolderResponse']['folder']['id']);

			if(!$efolderid || !is_numeric($efolderid)){
				return false;
			}

			$this->setCache($cachegroup,$cname,$efolderid);
		}
		return $efolderid;
	}


	/**
	 * Do Headers to readable form...
	 *
	 * @param array $from headers from..
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return array readable Headers
	 */
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





	/**
	 *
	 *
	 * @param String $externID
	 * @param String $zimbraID
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return void
	 */
	function getIdFromExternalId($externID){
		$t=-1;
		try{
			$ret=@unserialize(@file_get_contents($this->path.'oracle_zimbra_id.txt'));
			if(isset($ret[$oracleid]) && $ret[$oracleid]!=''){
				$t=$ret[$externID];
			}
		}catch(Exception $e){
		}

		return false;
	}

	/**
	 *
	 *
	 * @param String $externID
	 * @param String $zimbraID
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return void
	 */
	function setExternalIdMap($externID,$zimbraID){
		try{
			$ret=@unserialize(@file_get_contents($this->path.'oracle_zimbra_id.txt'));
			$ret[$externID]=$zimbraID;

			file_put_contents($this->path.'oracle_zimbra_id.txt',serialize($ret));

		}catch(Exception $e){
			errlog("Couldn't save: OracleId: $oraclid => ZimbraId: $zimbraid");
		}

	}

	/**
	 *
	 *
	 * @param  $zmailid
	 * @param int $format 0: ddmmYYYY_hhii,  1: yyyymmddThhiiss
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
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
}
