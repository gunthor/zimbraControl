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
 * Function Set 3 for User actions
 * New Mail Functions
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
class zimbraActionsUser_set3 extends zimbraActionsUser_common{

	/**
	 * Make new Message
	 *
	 * @param Boolean ressource Email is an ressource (location, mietbares ding..)
	 * @author	Günther Homolka <g.homolka@belisk.com>
	 * @return
	 */
	function newmsg($cmd, $params){

		// Todo: UTF8
		$reihe=array('user','uploadfiles');

		$a=array();

		// Reihenwerte in assoz array speichern..
		foreach($reihe as $key){
			$val=array_shift($params);
			if($val==''){
				continue;
			}
			$a[$key]=$this->cleanval($val);
		}
		p($a);
		$useremail=$this->getemail($a['user']);


		// Array mit id's kommt zurück, gleiche Reihenfolge wie übermittelte
		$uploadfiles=explode('#',$a['uploadfiles']);
		$uploadids=$this->z->doUpload($useremail,$uploadfiles);


		p($uploadids);


		$html=<<<EOF
 <html><head><style type='text/css'>p { margin: 0; }</style></head><body><div style='font-family: Times New Roman; font-size: 12pt; color: #000000'><img src="cid:DWT244"><span style="font-weight: bold; color: rgb(255, 0, 0);">Made with zimbraGETApi<br><br><table style="border: 1px solid rgb(0, 0, 0); width: 90%; text-align: left; vertical-align: middle; border-collapse: collapse;" align="center" cellpadding="3" cellspacing="0"><tbody><tr><td style="width: 50%; border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">a<br></td><td style="width: 50%; border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">b<br></td></tr><tr><td style="border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">c<br></td><td style="border-left: 1px solid rgb(0, 0, 0); border-top: 1px solid rgb(0, 0, 0);">d<br></td></tr></tbody></table>;-)<br><br></span></div></body></html>

EOF;

		$txt=<<<EOF
Made with zimbraGETApi ;-)

EOF;
		$html=str_replace('<','&lt;',$html);
		$txt=str_replace('<','&lt;',$txt);

		// UTF 8 Decode, &bnsp, &auml; usw:      [soap:Text] => parse error: Error on line 81 of document  : The entity "nbsp" was referenced, but not declared. Nested exception: The entity "nbsp" was referenced, but not declared.

		$html=utf8_encode($html);
		$txt=utf8_encode($txt);
/*
		include('sonderzeichen.php');
		$html=str_replace($fromsz,$tosz,$html);
		$txt=str_replace($fromsz,$tosz,$txt);*/
/*


/*
		// a=> email adresse
		// p=> personal-name
		//

		$soap='
<m>
<e t="t" a="g.homolka@belisk.com" p="Guenther Homolka1" add="1"/>
<e t="f" a="mail1@mail1.com" p="Guenther Homolka3"/>
<su>Test Email</su>

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

	<mp ci="DWT244">
		<attach aid="'.$uploadids[0].'"></attach>
	</mp>
</mp>
<attach aid="'.$uploadids[1].'">
</attach>
</m>
';
*/
		$soap='
<m>
<e t="c" a="g.homolka@belisk.com" p="Guenther Homolka Inode" add="1"/>
<e t="f" a="mail1@mail1.com" p="Guenther Homolka Absender"/>
<su>Test Email</su>

<mp ct="multipart/alternative">
	<mp ct="text/plain">
		<content>
'.$txt.'
		</content>
	</mp>
	<mp ct="multipart/related">
		<mp ct="text/html">

			<content>
	'.$html.'
			</content>
		</mp>

		<mp ci="DWT244">
			<attach aid="'.$uploadids[0].'"></attach>
		</mp>
	</mp>
</mp>
<attach aid="'.$uploadids[1].','.$uploadids[2].'"></attach>
</m>
';


		$soap=array('SendMsgRequest','zimbraMail',$soap);

		$ret=$this->dousersoap($useremail,$soap);

		// ID:
		//$ret['soap:Body']['SendMsgResponse']['m']['id']

		p($ret);
	}
}
