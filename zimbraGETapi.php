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
 * Admin/User Actions via SOAP and Shell
 * GETAPI: Control Zimbra via GET/POST CMDs
 *
 * @package    zimbraControl
 * @author     Günter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	 
 * @todo      documentate it
 * @todo      Tests that need to be made:
 *            - 
 */

//error_reporting(0);						// Error Reporting komplett abschalten
//error_reporting(E_ERROR | E_WARNING | E_PARSE);		// Nur einfache Fehler melden
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);	// E_NOTICE ist sinnvoll um uninitialisierte oder falsch geschriebene Variablen zu entdecken
//error_reporting(E_ALL ^ E_NOTICE);				// Melde alle Fehler außer E_NOTICE (Dies ist der Vorgabewert in php.ini)
//error_reporting(E_ALL);					// Melde alle PHP Fehler
error_reporting(E_ALL);

#------------------------------------------ START  -------------------------------------------
/*
if($_SERVER['REMOTE_ADDR']!='10.0.0.2' && $_SERVER['REMOTE_ADDR']!='127.0.0.1'){
	die("false IP adress: ".$_SERVER['REMOTE_ADDR']);
}
*/

// Startzeit
$starttime=time();

require_once 'classes/zimbraError.php';
require_once 'classes/CMDParser.php';

/*
Delimiter are "," and ":". to use them in values escape them with "#". Escape "#" also with "#"
e.g. [valuea => valuea][valu,,ea => valu#,#,ea][valu,:#ea => valu#,#:##ea]
*/

//$_GET['cmd']='SU,36476,g.#,_###,homolka@rk3002.com,,Günt#:_###:he:r,Homolka,g.homolka@belisk.com,,,,,3002,Purkersdorf';

$_GET['cmd']='SHELL,zmprov gaa';


if(!isset($_GET['cmd'])){
	zlog::log(zerror::err('no command diven',null,zerror::critical));
	exit;
}

if(!is_array($_GET['cmd'])){
	$_GET['cmd']=array($_GET['cmd']);
}


$included=array();



$cmdcoder=new CMDParser();

foreach($_GET['cmd'] as $command){
	list($err,$cmd)=$cmdcoder->decode($command);
	print_r($cmd);
	if($err){
		echo $err;
	}else{
		if(!isset($included[$cmd['class']])){
			require_once 'classes/'.$cmd['class'].'.php';
			$included[$cmd['class']]=new $cmd['class']();
		}
		list($ret,$return)=call_user_func_array(array($included[$cmd['class']], $cmd['action']), $cmd['params']);
	}
}


exit;


function p($var){
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

?>
