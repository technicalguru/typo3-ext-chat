<?php

require_once(t3lib_extMgm::extPath('rsextbase').'res/class.tx_rsextbase_database.php');


class tx_typo3chat_database extends tx_rsextbase_database {

	/**
	 * Returns all new messages for this user.
	 */
	function getNewMessages() {
		$user = $this->getUser();
		$where = "(recipient = $user[uid]) AND (received = 0)";
		return $this->getRecords('tx_typo3chat_messages', $where, 'uid ASC');
	}
	
	/**
	 * Marks all messages for this user as received.
	 */
	function setAllReceived($messages) {
		$time = time();
		if (is_array($messages) && (count($messages) > 0)) {
			$user = $this->getUser();
			$where = "(recipient = $user[uid]) AND received = 0 AND uid IN (";
			foreach ($messages AS $message) {
				$where .= $message['uid'].',';
			}
			$where .= '0)';
			$this->updateRecordsWhere('tx_typo3chat_messages', $where, array('received' => 1, 'tstamp' => $time));
		}
	}
	
	/**
	 * Inserts a message.
	 * @param string $recipient
	 * @param string $message
	 */
	function addMessage($recipient, $message) {
		$time = time();
		$sender = $this->getUser();
		$values = array (
			'sender' => $sender['uid'],
			'recipient' => $recipient,
			'message' => mysql_real_escape_string($message),
			'sent_date' => strftime('%Y-%m-%d %H:%M:%S', $time),
			'received' => 0,
			'crdate' => $time,
			'tstamp' => $time,
		);
		return $this->createRecord('tx_typo3chat_messages', $values);
	}
	
	function cleanOldMessages() {
		$expiry = time() - 3600*48;
		$where = "received=1 AND tstamp<$expiry";
		$this->deleteRecords('tx_typo3chat_messages', $where);
	}
}

?>