<?php
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

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * define CMDs
 * 
 * @package    zimbraControl
 * @author     G�nter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraActions
 * @see        zimbraActionsAdmin_common
 * @todo       improve command syntax
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraCMD{
	/**
	 * List of CMDs
	 * 
	 * cmd divided by , 
	 * [..] optional
	 * {..} many, divided by :
	 *
         * ,: escaped by #
         *
	 * [#,=>,][##=>#][#:=>:]
	 * e.g. [:: => #:#:][#, => ###,]
	 * 
	 * @access private
	 * @var    array
	 */
	var $conf=array(
		#############################################################
		# Admin  Possibilities
		#############################################################
		'SU'=>array( // Set User √
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'setRemoveUser',
			'params'=>array('uid,{email_alias},[password],[fname],[sname],[forwarding_email],[cos],[title],[phone],[street],[postal],[location]',1),
		),
		'UCA'=>array( // User Change Alias [nothing/+/-] for add/add/remove √
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'doAccountAliase',
			'params'=>array('uid,{email_aliase}','user'),
		),
		'RU'=>array( // Remove User √
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'setRemoveUser',
			'params'=>array('uid',0),
		),
		'USC'=>array( // User setClassOfService √
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'setClassOfService',
			'params'=>array('uid,cosname'),
		),


		'SD'=>array( // Set DistributionList
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'setRemoveDistribution',
			'params'=>array('did,{email_alias}',1)
		),
		'DCA'=>array( //DistributionList Change Aliase
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'doAccountAliase',
			'params'=>array('uid,{email_aliase}','user')
		),
		'RD'=>array( // Remove DistributionList
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'setRemoveDistribution',
			'params'=>array('did',0)
		),
		
		
		'UCD'=>array( // UserChangeDistributionlists
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'UserChangeDistributionlists',
			'params'=>array('uid,{dids}')
		),
		'DCU'=>array( // DistributionlistChangeUser
			'class'=>'zimbraActionsAdmin_set1',
			'action'=>'DistributionlistChangeUser',
			'params'=>array('did,{uids}')
		),
		
		
		#############################################################
		# User Possibilities
		#############################################################
		'SC'=>array( // Set Calendar Entry
			'class'=>'zimbraActionsUser_set1',
			'action'=>'setRemoveCalendarEntry',
			'params'=>array('extid,{dids}',	1)
		),
		'RC'=>array( // Remove Calendar Entry
			'class'=>'zimbraActionsUser_set1',
			'action'=>'setRemoveCalendarEntry',
			'params'=>array('extid,{dids}',	1)
		),
		
		'SM'=>array( // Sendmessage
			'class'=>'zimbraActionsUser_set1',
			'action'=>'sendMessage',
			'params'=>array('',array('POST'=>array('messages'=>array(array('uids'=>array('uids'),'message'=>'message','attachements'=>'attachements'))))),
		),
		
		'GNM'=>array( // Get new Mails
			'class'=>'zimbraActionsUser_set1',
			'action'=>'getNewMails',
			'params'=>array('uid')
		),
		
		
		#############################################################
		# SHELL
		#############################################################
		'SHELL'=>array( // do shell cmd  √
			'class'=>'zimbraShell',
			'action'=>'doCMD',
			'params'=>array('cmd')
		),
	);
}
?>	
