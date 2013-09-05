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

require_once(t3lib_extMgm::extPath('rsextbase').'res/class.tx_rsextbase_ajaxbase.php');


/**
 * Plugin 'Ajax Calls' for the 'typo3chat' extension.
 *
 * @author      Administrator <typo3@ralph-schuster.eu>
 * @package     TYPO3
 * @subpackage  tx_rsextbase
 */
class tx_typo3chat_ajax_pi1 extends tx_rsextbase_ajaxbase {
	
	var $openChatBoxes = array();
	var $chatHistory   = array();
	var $chatStatus    = array();
		
	function main($jsonRequest, &$dispatcher) {
		if (!$GLOBALS['TSFE']->fe_user) return '';
		
		// Wait for any other request
		//$this->pi->acquire_lock('chat'+$GLOBALS['TSFE']->fe_user->user['uid']);
		
		$this->initChatSession();
		$rc = parent::main($jsonRequest, $dispatcher);
		$this->saveChatSession();
		
		// Release the lock
		//$this->pi->release_lock('chat'+$GLOBALS['TSFE']->fe_user->user['uid']);
		//print_r($this->chatHistory);
		return $rc;
	}
	
	function initChatSession() {
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$this->chatHistory   = $GLOBALS["TSFE"]->fe_user->getKey("ses", "typo3chat_chatHistory");
		$this->openChatBoxes = $GLOBALS["TSFE"]->fe_user->getKey("ses", "typo3chat_openChatBoxes");
		$this->chatStatus    = $GLOBALS["TSFE"]->fe_user->getKey("ses", "typo3chat_chatStatus");
	}
	
	function saveChatSession() {
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "typo3chat_chatHistory",   $this->chatHistory);
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "typo3chat_openChatBoxes", $this->openChatBoxes);
		$GLOBALS["TSFE"]->fe_user->setKey("ses", "typo3chat_chatStatus",    $this->chatStatus);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
	}
	
	/**
	 * Registers the methods.
	 * @see tx_rsextbase_ajaxbase::registerMethods()
	 */
	function registerMethods() {
		$this->registerMethod('chatheartbeat',    'chatHeartbeat');
		$this->registerMethod('sendchat',         'sendChat');
		$this->registerMethod('closechat',        'closeChat');
		$this->registerMethod('startchatsession', 'startChatSession');
		$this->registerMethod('chathistory',      'chatHistory');
	}
	
	/**
	 * Respond to a heartbeat.
	 */
	function chatHeartbeat($request) {
		$items = '';
		$chatBoxes = array();

		// Collect all new messages and update history
		$messages = $this->pi->db->getNewMessages();
		foreach ($messages AS $message) {
			$sender = $message['sender'];
			$senderRecord = $this->pi->db->getUser($sender);
			
			// Load the history with this sender (if box was not open yet)
			if (!isset($this->openChatBoxes[$sender]) && isset($this->chatHistory[$sender])) {
				$items = $this->chatHistory[$sender];
			}

			$item = $this->constructItem(0, $sender, $senderRecord['username'], $message['message'], $message['crdate']);
			$items .= "$item,";
			
			// Initialize chat history if required
			if (!isset($this->chatHistory[$sender])) {
				$this->chatHistory[$sender] = '';
			}

			// Initialize chat status if required
			if (!isset($this->chatStatus[$sender])) {
				$this->chatStatus[$sender] = 1; // user is online
			}

			// Add message to chat history
			$this->chatHistory[$sender] .= "$item,";

			// Unset chat box of sender
			$this->openChatBoxes[$sender] = $message['sent_date'];
		}

		// Make some checks
		if (!empty($this->openChatBoxes)) {
			foreach ($this->openChatBoxes as $sender => $time) {
				$senderRecord = $this->pi->db->getUser($sender);
						
				// Check if user logged in/out meanwhile
				if ($this->chatStatus[$sender] != $senderRecord['_is_online']) {
					if ($this->chatStatus[$sender]) {
						// User went offline
						$this->chatStatus[$sender] = 0;
						$item = $this->constructItem(2, $sender, $senderRecord['username'], "User logged off", time());
						$items .= "$item,";
					} else {
						// User logged in again
						$this->chatStatus[$sender] = 1;
						$item = $this->constructItem(2, $sender, $senderRecord['username'], "User logged in", time());
						$items .= "$item,";
					}
				}
			}
		}

		$this->pi->db->setAllReceived($messages);
		
		// Prepare response
		if ($items != '') {
			$items = substr($items, 0, -1);
		}
		
		// Respond
		return "{ \"items\": [$items] }";
	}

	/**
	 * Return the chat box history for this sender.
	 * @param string $sender
	 */
	function chatBoxSession($sender) {
		$items = '';

		if (isset($this->chatHistory[$sender])) {
			$items = $this->chatHistory[$sender];
		}
		return $items;
	}

	/**
	 * Start chat by transmitting all chat messages
	 */
	function startChatSession() {
		$user = $this->pi->db->getUser();
		
		if ($user['uid']) {
			$this->pi->db->cleanOldMessages();
			$items = '';
			if (!empty($this->openChatBoxes)) {
				foreach ($this->openChatBoxes as $sender => $void) {
					$items .= $this->chatBoxSession($sender);
				}
			}
		} else {
			return "{ \"username\": \"\" }";
		}
		
		// Prepare response
		if ($items != '') {
			$items = substr($items, 0, -1);
		}

		// Respond
		return "{ \"username\": \"$user[username]\", \"items\": [$items] }";
	}

	/**
	 * User sent a message. Add it to database
	 */
	function sendChat($request) {
		$from = $this->pi->db->getUser();
		$from = $from['username'];
		
		$to = $request['params']['to'];
		$toRecord = $this->pi->db->getUser($to);
		
		$message = $request['params']['message'];

		$this->openChatBoxes[$to] = date('Y-m-d H:i:s', time());

		if (!isset($this->chatHistory[$to])) {
			$this->chatHistory[$to] = '';
		}

		if (!isset($this->chatStatus[$to])) {
			$this->chatStatus[$to] = 1;
		}
		
		$item = $this->constructItem(1, $to, $toRecord[username], $message, time());
		$this->chatHistory[$to] .= "$item,";


		// Add to database now
		$this->pi->db->addMessage($to, $message);

		// Respond
		return "1";
	}

	/**
	 * Close the chat.
	 */
	function closeChat($request) {
		unset($this->openChatBoxes[$request['params']['chatbox']]);

		// Respond
		return "1";
	}

	/**
	 * Send history of chat with given participant.
	 */
	function chatHistory($request) {
		$partnerId = $request['params']['to'];
		$user = $this->pi->db->getUser();
		//$partner = $this->pi->db->getUser($partnerId);
		
		// Prepare response
		$items = '';
		if (isset($this->chatHistory[$partnerId])) {
			$items = $this->chatHistory[$partnerId];
		}
		if ($items != '') {
			$items = substr($items, 0, -1);
		}
		
		// Respond
		return "{ \"items\": [$items] }";
	}
	
	/**
	 * Constructs a single item for JSON return
	 * @param unknown_type $type
	 * @param unknown_type $senderId
	 * @param unknown_type $senderName
	 * @param unknown_type $message
	 * @param unknown_type $timestamp
	 */
	function constructItem($type, $senderId, $senderName, $message, $timestamp = 0) {
		if (is_array($type)) {
			// Construction from database record!
			$record = $type;
			$type = 0;
			$senderId = $record['sender'];
			if ($senderId == $GLOBALS['TSFE']->fe_user->user['uid']) {
				$senderId = $record['recipient'];
				$type = 1;
			}
			$sender = $this->pi->db->getUser($senderId);
			$senderName = $sender['username'];
			$message = $record['message'];
			$timestamp = $record['crdate'];
		}
		
		// Sanitize message
		$message = $this->sanitize($message);
		
		// Format the timestamp if required
		if (preg_match('/^\\d+$/', $timestamp)) settype($timestamp, 'int');
		if (is_int($timestamp)) {
			if ($timestamp == 0) $timestamp = time();
			if ($timestamp < time() - (24*3600)) $timestamp = strftime($this->pi->config['datetimeFormat'], $timestamp);
			else $timestamp = strftime($this->pi->config['timeFormat'], $timestamp);
		}
		$item = "{ \"s\": \"$type\", \"f\": \"$senderId\", \"n\": \"$senderName\", \"m\": \"$message\", \"t\": \"$timestamp\" }";
		
		return $item;
	}
	
	/**
	 * Sanitizes the text for transmission.
	 * @param string $text
	 */
	function sanitize($text) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = str_replace("\n\r","\n", $text);
		$text = str_replace("\r\n","\n", $text);
		$text = str_replace("\n","<br>", $text);
		return $text;
	}
}

?>