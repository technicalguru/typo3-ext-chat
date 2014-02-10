<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_typo3chat_messages'] = array (
	'ctrl' => $TCA['tx_typo3chat_messages']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sender,recipient,message,sent_date,received'
	),
	'feInterface' => $TCA['tx_typo3chat_messages']['feInterface'],
	'columns' => array (
		// SENDER
		'sender' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages.sender',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'fe_users',
				'foreign_table_where' => 'fe_users.deleted=0 AND fe_users.disable=0 ORDER BY fe_users.username',
			),
		),
		// RECIPIENT
		'recipient' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages.recipient',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'fe_users',
				'foreign_table_where' => 'fe_users.deleted=0 AND fe_users.disable=0 ORDER BY fe_users.username',
			),
		),
		// MESSAGE
		'message' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages.message',
			'config' => array (
				'type' => 'input',
				'size' => '48',
				'eval' => 'required,trim',
			)
		),
		// SENT DATE
		'sent_date' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages.sent_date',
			'config' => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'required,trim,datetime',
				'default'  => '0',
				'checkbox' => '0',
			),
		),
		// RECEIVED
		'received' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages.received',
			'config' => array (
				'type'    => 'check',
				'default' => '0',
			),
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sender,recipient,message,sent_date,received')
	),
	'palettes' => array (
	)
);

?>
