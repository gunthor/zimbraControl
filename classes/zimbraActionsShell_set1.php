<?PHP
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

require_once 'zimbraActionsShell_common.php';

/**
 * zimbraControl - Toolkit to control Zimbra
 * 
 * Shell Actions
 * 
 * @package    zimbraControl
 * @author     G�nter Homolka 2010 <g.homolka@belisk.com>
 * @copyright  2010 The Authors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    1.0.0
 * @link       http://zimbraControl.belisk.com
 * @since      File available since Release 1.0.0
 * @see	       zimbraGETapi
 * @todo       Tests that need to be made:
 *              - 
 */
class zimbraActionsShell_set1 extends zimbraActionsShell_common{

	/**
	 * Exectues CMD
	 *
	 * @param  $cmd
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return output of Shell
	 */
	public function doCMD($cmd){

		if(!$this->isinitialized){
			$this->init();
			$this->isinitialized=true;
		}

		// CMD LOG Aus/einschalten
		//$this->docmdlog(true/false);

		list($err,$ret)=$this->do_cmd($cmd['cmd']);
		echo "<pre>";
		echo "--------";
		print_r($ret);
		echo "--------";
		print_r($this->errlog);
		echo "--------";
		print_r($this->cmdlog);
		echo "--------";
		echo "</pre>";

	}
}