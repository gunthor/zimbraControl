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


require_once 'zimbraLog.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * error Handling
 * error:
 *	
 *	$err=new zerror('error!',$exception,critical,config);
 *	return array($err,$obj);
 * 	
 *	l->errlog($err)
 *	
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
 * @todo       improve error handling
 * @todo       Tests that need to be made:
 *              - 
 */
class zerror{
	var $errmsg;
	var $line;
	var $file;
	var $time;
	var $params;
	var $id;
	
	const critical=true;
	const notcritical=false;
	
	public static function err($errormsg,$params,$critical=zerror::notcritical,$config=array()){
		zerror::error($errormsg,$params,$critical,$config);
	}
	
	public function error($errormsg,$params,$critical=zerror::notcritical,$config=array()){
		zlog::log($errormsg,$params);
		
		if($critical==zerror::critical){
			exit;
		}
	}
}



?>
