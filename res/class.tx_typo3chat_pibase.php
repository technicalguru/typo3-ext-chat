<?php

require_once(t3lib_extMgm::extPath('rsextbase').'res/class.tx_rsextbase_pibase.php');
require_once(t3lib_extMgm::extPath('typo3chat').'res/class.tx_typo3chat_database.php');

class tx_typo3chat_pibase extends tx_rsextbase_pibase {

	var $extKey        = 'typo3chat';
	
	/**
	 * Always call this function before starting.
	 * @param array $config configuration
	 */
	function init($config) {
		parent::init($config);
	
	}
	
	/**
	 * Creates the database object.
	 */
	function createDatabaseObject() {
		$this->db = t3lib_div::makeInstance('tx_typo3chat_database');
		$this->db->init($this);
	}
	
	
	/**
	 * Renders the openchat link.
	 * @param string $content
	 * @param array $conf
	 */
	function openchat($content, $conf) {
		$content = '';
		
		// Get user data
		$user = $this->cObj->data;
		
		// Check conditions (online and not frontend user!)
		if (!$user['_is_online']) return '';
		if ($user['uid'] == $GLOBALS['TSFE']->fe_user->user['uid']) return '';
		
		// Prepare the cObject
		$cObj = t3lib_div::makeInstance("tslib_cObj");
		$cObj->setCurrentVal($GLOBALS["TSFE"]->id);
		$cObj->data = $user;
		
		// We need to render:
		// <a href="javascript:void(0)" onclick="javascript:chatWith('UID','USERNAME')">DISPLAY</a>
		
		// Pre-Display part
		$rc = "<a href=\"javascript:void(0)\" onclick=\"javascript:chatWith('$user[uid]','$user[username]')\">";
		
		// Display part
		$rc .= $cObj->cObjGetSingle($conf['display'], $conf['display.']);
		
		// Post display part
		$rc .= "</a>";
		
		return $rc;
	}
}

?>