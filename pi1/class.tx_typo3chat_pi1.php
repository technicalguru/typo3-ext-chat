<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Administrator <typo3@ralph-schuster.eu>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('typo3chat').'res/class.tx_typo3chat_pibase.php');

/**
 * Plugin 'Typo3Chat Plugin' for the 'typo3chat' extension.
 *
 * @author	Administrator <typo3@ralph-schuster.eu>
 * @package	TYPO3
 * @subpackage	tx_typo3chat
 */
class tx_typo3chat_pi1 extends tx_typo3chat_pibase {
	
	var $relPath       = 'pi1';
	var $prefixId      = 'tx_typo3chat_pi1';
	var $scriptRelPath = 'pi1/class.tx_typo3chat_pi1.php';
	
	/**
	 * Returns the HTML content.
	 */
	function getPluginContent() {
		// We need a login!
		if (!$this->db->getUser()) return '';
		
		// We need <a href="javascript:void(0)" onclick="javascript:chatWith('username')">Chat With Jane Doe</a>
		$content = 'Not a valid usage of this plugin';
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Creates the JSON request object from GET/POST vars.
	 * @return JSON request object or 0 if JSON request was invalid.
	 */
	function getJsonRequest() {
		// we need to construct the request differently
		// method=typo3chat::pi1::<action>
		// params=??
		// id=0
		$method = t3lib_div::_GP('action');
		$className = 'tx_'.$this->extKey.'_'.$this->relPath;
		$classPath = t3lib_extMgm::extPath($this->extKey).$this->relPath.'/class.'.$className.'.php';
		
		// Extract all required params
		$chatbox = t3lib_div::_GP('chatbox');
		$to      = t3lib_div::_GP('to');
		$message = t3lib_div::_GP('message');
		
		// Construct parameter array
		$params = array();
		if ($chatbox) $params['chatbox'] = $chatbox;
		if ($to)      $params['to']      = $to;
		if ($message) $params['message'] = $message;
		
		// Construct the request array
		$rc = array(
			'classPath' => $classPath,
			'className' => $className,
			'method'    => $method,
			'params'    => $params,
			'id'        => 0,
		);
		print_r($rc);
		exit;
		return $rc;
	}
	
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/typo3chat/pi1/class.tx_typo3chat_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/typo3chat/pi1/class.tx_typo3chat_pi1.php']);
}

?>
