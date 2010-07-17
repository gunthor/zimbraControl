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

require_once 'zimbraActions.php';



/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Common Functions for Admin SOAP Actions
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
class zimbraActionsAdmin_common extends zimbraActions{

	/**
	 * generate a uid from raw uid
	 * ("user1" => "user1@domain.com",
	 *  "user1@domain2.com" => "user1@domain2.com")
	 *
	 * @param String $name
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return String zimbrauid
	 */
	function  makeUid($uid){
	    if(!strpos($uid,'@')){
		return $uid.'@'.zimbraConfig::defaultEmailDomain;
	    }
	    return $uid;
	}
	/**
	 * Get the Account ID by account Name name
	 *
	 * @param String $name
	 * @author	Günther Homolka <g.homolka@belisk.com> 
	 * @return String AccountId, -1 @ error
	 */
	function getAccountIdbyName($name){
		$ret=$this->getAccountbyName($name);
		return c($ret['id']);
	}

	/**
	 * Get the account by Name
	 *
	 * @param String $name
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return array Account Details, -1 @ Error
	 */
	function getAccountbyName($name){
		$soap=array('GetAccountRequest','<account by="name">'.$name.'</account>');
	
		$ret=$this->doadminsoap($soap);
		$a=c($ret['soap:Body']['GetAccountResponse']['account']);
		echo "-$a-";
		return c($ret['soap:Body']['GetAccountResponse']['account']);
	}

}

?>
