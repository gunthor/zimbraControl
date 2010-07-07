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

require_once 'zimbraActionsAdmin_common.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Function Set 1 for Admin actions
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
 * @todo       documentate it
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraActionsAdmin_set1 extends zimbraActionsAdmin_common{
	

	/**
	 * Set new User Details, if they're given
	 * if user doesn't exist, it will be added.
	 *
	 * @param array $user uid,[password],[fname],[sname],[email],{email_alias},[cos],[title],[phone],[street],[postal],[location]
	 * @param boolean $set		if true  set, else remove
	 * @author Günther Homolka <g.homolka@belisk.com> 
	 * @return errs
	 */
	public function setRemoveUser($user,$set=1){
		
		$zuid=$this->getAccountIdbyName($user['uid']);
		
		// to write less...
		$f=$user;
		
		// Set
		if($set){
			
			// change entries if necessary...
			//$f['']=($f['']);
			
			$soap='';
			if($this->check($f['password'],0))		$soap.='<password>'.$f['password'].'</password>'; // zimbra needs a password.. :(
			if($this->check($f['displayname'],0))	$soap.='<a n="displayName">'.$f['displayname'].'</a>';
			if($this->check($f['fname'],0))			$soap.='<a n="gn">'.$f['fname'].'</a>';
			if($this->check($f['sname'],0))			$soap.='<a n="sn">'.$f['sname'].'</a>';
			if($this->check($f['title'],0))			$soap.='<a n="initials">'.$f['title'].'</a>';
			
			/* #Not needed now
			if($this->check($f['phone'],4))		$soap.='<a n="telephoneNumber">'.$f['phone'].'</a>';
			if($this->check($f['street'],1))		$soap.='<a n="street">'.$f['street'].'</a>';
			if($this->check($f['postal'],2,array(0)))		$soap.='<a n="postalCode">'.$f['postal'].'</a>';
			if($this->check($f['location'],2))		$soap.='<a n="l">'.$f['location'].'</a>';
			if($this->check($f['st'],0))	$soap.='<a n="st">'.$f['st'].'</a>';
			if($this->check($f['staat'],0))		$soap.='<a n="co">'.$f['staat'].'</a>';
			*/
			
			// <-start specific
			
			if($this->check($f['forwarding_email'],0)){
				
				// Wenn als forwarding email_adresse die domain (@domain.com) angegeben ist, dann diese löschen, nicht erlaubt.
				if(strpos($f['forwarding_email'],$this->domain)){
					$f['forwarding_email']='';
				}
				$soap.='<a n="zimbraPrefMailForwardingAddress">'.$f['forwarding_email'].'</a>';    // zimbraMailForwardingAddress = hidden for user...
			}
			
			// end specific->
			
			// Create
			if($zuid==-1){
				$what='CreateAccountRequest';
				
				if(!$this->check($f['password']))	$soap.='<password>'.dorandompassword().'</password>'; // zimbra needs a password.. :(
				
				// <-start specific
				
				// generate email alias 
				if(!$this->checkmail($f['email_alias']) && $f['vname']!='' && $f['nname']!=''){
					$f['email_alias'][]=substr($f['vname'],0,1).'.'.$f['nname'].'@'.$this->domain;
				}
				// default class of service
				if($f['cos']==''){
					$cos=$this->getCosIdbyName($f['defaultuser']);
					if($cos!=-1)			$soap.='<a n="zimbraCOSId">'.$cos.'</a>';		
				}
				// end specific ->
				
				$soap='<name>'.$f['email'].'</name>'.$soap;

			// Modify
			}else{
				$what='ModifyAccountRequest';
				
				if($soap==''){
					$soap='<id>'.$zuid.'</id>'.$soap;	
				}
			}
			
			// Execute soap
			if($soap!=''){
				$soap=array($what,$soap);
				$ret=$this->doadminsoap($soap);
				         
				$zuid=$ret['soap:Body']['ModifyAccountResponse']['account']['id'];
			}
			
			// Manage Aliase
			if($f['email_alias']!=''){
				$this->doAccountAliase($zuid,$f['email_alias'],'user','uid');
			}
		
		// Delete
		}else{
			
			// Nothing to delete
			if($zuid==-1){
				return array(-1,'User "'.$user['uid'].'" doesn t exist');
				
			// Delete
			}else{
				$soap=array('RemoveAccountRequest','<id>'.$zuid.'</id>');
				$ret=$this->doadminsoap($soap,1);
			}
		}
	}
	

	/**
	 * Check Helper for setRemoveUser
	 *
	 * @param  $value 
	 * @param  $mode 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @see    setRemoveUser
	 * @return  boolean passed/not passed compared to mode
	 */
	private function check(&$value,$mode){
		if($mode!=0 && ($value=='' || $value==null ))return false;
		if($value=='-'){
			$value='';
			return true;
		}
		switch($mode){
			case 0: if($value=='')return false;
			case 1: if($value=='')return false;
			case 2: if($value=='')return false;
			case 3: if($value=='')return false;
			default: return false;
		}
		
	}
	
	/**
	 * Check Helper for setRemoveUser
	 *
	 * @param  $value 
	 * @param  $mode 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @see    setRemoveUser
	 * @return  boolean passed/not passed compared to mode
	 */
	private function dorandompassword(){
		srand(microtime(1));
		return md5(rand());
	}

	/**
	 * Set/Remoove Distribution
	 *
	 *
	 * @param array $distribution	Distribution $distribution[did]=email, distribution[alias][n]=alias
	 * @param boolean $set		if true,  set, else remove
	 * @author Günther Homolka <g.homolka@belisk.com> 
	 * @return errs
	 */
	public function setRemoveDistribution($distribution,$set=1){
		
		$zdid=$this->getDistributionIdbyName($distribution['did']);
		
		// Set
		if($set){
			
			// Create
			if($zdid==-1){
				$soap=array('CreateDistributionListRequest','<name>'.$email.'</name>');
				
				$ret=$this->doadminsoap($soap);

				if(isset($ret['soap:Body']['CreateDistributionListResponse']['dl']['id'])){
					$id=$ret['soap:Body']['CreateDistributionListResponse']['dl']['id'];
					
					$this->doAccountAliase($did,$distribution['alias'],'dist');
				}
			// Modify
			}else{
				// what to modify??
				//$soap='<a n="...">...</a>';
				//$soap.='<a n="...">...</a>';
				
				$soap=array('ModifyDistributionListRequest','<id>'.$zdid.'</id>'.$soap);
				
				$ret=$this->doadminsoap($soap);
				
				$this->accountDoAlias($did,$distribution['alias'],'dist');
			}
		
		// Delete
		}else{
			
			// Nothing to delete
			if($zdid==-1){
				return array(-1,'DistList "'.$distribution['did'].'" doesn t exist');
				
			// Delete
			}else{
				$soap=array('DeleteDistributionListRequest','<id>'.$zimbra_uid.'</id>');
				$ret=$this->doadminsoap($soap,1);
			}
		}
	}
	
	
	/**
	 * Add/Remove Alias of Distlist, User
	 *
	 * @param  $id 
	 * @param  $alias 
	 * @param  $distlist 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	public function doAccountAliase($id,$alias,$distlist='user',$idby='name'){
		
		// User Alias
		if($distlist=='user'){
			if($idby=='name'){
				$zid=$this->getAccountIdbyName($id);
			}else{
				$zid=$id;
			}
			
			$what='AccountAlias';
		
		// Distribution Alias
		}else if($distlist=='ist'){
			if($idby=='name'){
				$zid=$this->getDistributionIdbyName($id);
			}else{
				$zid=$id;
			}
			
			$what='DistributionListAlias';
		}
	
		if($zid==-1){
			return array(-1,'Error "'.$id.'" not found');	
		}
		
		foreach($alias as $alias2) {
			$a=substr($alias2,0,1);
			$alias=str_replace(array('+','-'),'',substr($alias2,1)); // Remove all + and -, only first is used.
			
			$soap='<id>'.$zid.'</id><alias>'.$alias.'</alias>';
			
			if($a=='-'){
				$soap=array('Remove'.$what.'Request',$soap);
			}else{
				if($a!='+')$alias=$a.$alias;
				$soap=array('Add'.$what.'Request',$soap);
			}
			
			$this->doadminsoap($soap,1);
		}
	}
	
	/**
	 * Set Class of Service to Name
	 *
	 * @param array $user given user (user[uid] =email necessary)
	 * @param  $cosname 
	 * @author Günther Homolka <g.homolka@belisk.com> 
	 * @return array errs
	 */
	function setClassOfService($user,$cosname){
		
		$uid=$this->getZimbraIdbyName($user[uid]);
		if(!$uid)return $this->lasterror;
		
		$cosid=$this->getCosIdbyName($params[1]);
		if(!$cosid)return $this->lasterror;
		
		$soap=array('ModifyAccountRequest','<id>'.$uid.'</id><a n="zimbraCOSId">'.$cosid.'</a>');

		return $this->doadminsoap($soap,1);		
	}


	/**
	 *
	 *
	 * @param  $uid 
	 * @param  $dids 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function UserChangeDistributionlists($uid,$dids){
		
		$zuid=$this->getAccountIdbyName($user['uid']);
		
		if($zuid==-1){
				return array(-1,'Error "'.$uid.'" not found');
		}
		
		foreach($dids as $did1){
				
			$a=substr(0,1,$did1);
			$did=str_replace(array('+','-'),'',substr(1,$did1)); // Remove all + and -, only first is used.
			
			$dist=$this->getDistributionbyName($verteilerlistid);
				
			if($dist['zid']==-1){
				$err[]=array(-1,'Error "'.$did.'" not found');
				continue;
			}
			
			$found=in_array($email,$member);
			
			$soap='<id>'.$dist['zid'].'</id><dlm>'.$uid.'</dlm>';
			
			// Action...
			if($a=='-'){
				if(!$found)continue; // If already not in list continue
				$soap=array('RemoveDistributionListMemberRequest',$soap);
			}else{
				if($found)continue; // if already in list continue
				if($a!='+')$alias=$a.$alias;
				$soap=array('AddDistributionListMemberRequest',$soap);
			}
			
			$ret=$this->doadminsoap($soap);
		}
	}
	
	
	/**
	 *
	 *
	 * @param  $did 
	 * @param  $uids 
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return 
	 */
	function DistributionlistChangeUser($did,$uids){
		
		$dist=$this->getDistributionbyName($verteilerlistid);
				
		if($dist['zid']==-1){
			return array(-1,'Error "'.$did.'" not found');
		}
			
		$soap_r=''; // remove uids
		$soap_a=''; // add uids
			
		foreach($uids as $uid){
				
			$a=substr(0,1,$did1);
			$did=str_replace(array('+','-'),'',substr(1,$did1)); // Remove all + and -, only first is used.
			$found=in_array($uid,$member);
			
			// Action...
			if($a=='-'){
				if(!$found)continue; // If already not in list continue
				$soap_r.='<dlm>'.$param.'</dlm>';
			}else{
				if($found)continue; // if already in list continue
				if($a!='+')$alias=$a.$alias;
				$soap_a.='<dlm>'.$param.'</dlm>';
			}
		}
		
		if($soap_a!=''){
			$soap=array('AddDistributionListMemberRequest','<id>'.$verteilerlistid.'</id>'.$soap_a);
			$ret=$this->doadminsoap($soap);
		}
		
		if($soap_r!=''){
			$soap=array('RemoveDistributionListMemberRequest','<id>'.$verteilerlistid.'</id>'.$soap_r);
			$ret=$this->doadminsoap($soap);	
		}
		
	}
	
	
	// ready
	function getCosIdbyName($cosname){
		
		if($cosname!=''){
			$cachegroup='cosids';
			$cname=$cosname;
			
			$cache=$this->getCache2($cachegroup,$cname);
			if($cache===false){
				
				$soap=array('GetCosRequest','<cos by="name">'.$cosname.'</cos>');
				$ret=$this->doadminsoap($soap);
				
				$cache=NULL;
				if(isset($ret['soap:Body']['GetCosResponse']['cos']['id'])){
					$cache=$ret['soap:Body']['GetCosResponse']['cos']['id'];	
				}
				$this->setCache2($cachegroup,$cname,$cache);
			}
			$cosid=$cache;
			
			return $cosid;
		}
	}		
	
	// ready
	function getAccountIdbyName($name){
		$ret=$this->getAccountbyName($name);
		
		if(isset($ret['id'])){
			return $ret['id'];
		}else{
			return -1;
		}

	}
	// ready
	function getAccountbyName($name){
		$soap=array('GetAccountRequest','<account by="name">'.$name.'</account>');
		$ret=$this->doadminsoap($soap);
		
		if(isset($ret['soap:Body']['GetAccountResponse']['account'])){
			return $ret['soap:Body']['GetAccountResponse']['account'];
		}else{
			return -1;
		}
	}
	
	function getDistributionIdbyName($name,$getlist=0){
		
		$soap='<dl by="name">'.$name.'</dl>'; 
		$found=-1;
		if($getlist==0){
			$soap.='<limit>1</limit>';
		}
		$soap=array('GetDistributionListRequest',$soap);
		
		
		$ret=$this->doadminsoap($soap);
		
		if(isset($ret['soap:Body']['soap:Fault']['soap:faultstring'])){
			
			echo "FEHLER!!";
			
			return $ret['soap:Body']['soap:Fault']['soap:faultstring'];
		}else{
			$id=$ret['soap:Body']['GetDistributionListResponse']['dl']['id'];
			
			if($getlist!=0){
				return array($id,$ret['soap:Body']['GetDistributionListResponse']['dl']['dlm']);
			}
			
			return $id;
		}
	}
}

?>
