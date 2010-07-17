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

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Config zimbra Environment
 * 
 * Todo for enable SOAP-functions:
 * extension=php_soap.dll
 * extension=php_openssl.dll
 * 
 * Todo for enable SHELL-functions:
 * extension=php_ssh2.dll
 * in doc/dlls you will find some version of this dll.
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
 * @todo       
 * @todo       Tests that need to be made:
 *              - 
 */
//include 'zimbraConfig2.php';

class zimbraConfig{
	#############################################################
	# Main
	#############################################################
	
	#DefaultEmail Domain
	const defaultEmailDomain='';
	const adminAccount_user='';
	const adminAccount_password='';
	
	#############################################################
	# SOAP
	#############################################################
		
	# Admin Soap Domain
	const _adminlocation='https://domain.com:7071/service/admin/soap/';
	# User Soap Domain
	const _userlocation='http://domain.com/service/soap/';
	# Upload zimbra Location
	const _uploadlocation='http://domain.com/service/upload';
	
	# PreAuth Key
	# @see http://wiki.zimbra.com/wiki/Preauth
	const _preAuthKey='';
	#Exiration of preauthkey
	const _preauth_expiration=0;

	#############################################################
	# SHELL
	#############################################################
	const _ssh_server='domain.com';
	const _ssh_port='22';
	
	const _ssh_user='root';
	const _ssh_domain='mailserver'; #domain on linux machine...
	const _ssh_pass='password';
	
	#Zibra User (sudo su ...)
	const _shellZimbraUser='zimbra';

	#############################################################
	# Diverses
	#############################################################
	#Debug
	const debug=false;
}

?>
