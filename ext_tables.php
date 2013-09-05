<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('tt_content');

/***********************************************************************************************
 * DATABASE EXTENSIONS
 ***********************************************************************************************/
$TCA['tx_typo3chat_messages'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:jrt/locallang_db.xml:tx_typo3chat_messages',
		'label'     => 'uid',
		'crdate'    => 'sent_date',
		'default_sortby' => 'ORDER BY sent_date DESC',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_typo3chat_messages.gif',
	),
);

/***********************************************************************************************
 * PLUGINS
 ***********************************************************************************************/

$i=1;
while (file_exists(t3lib_extMgm::extPath($_EXTKEY).'pi'.$i.'/class.tx_'.$_EXTKEY.'_pi'.$i.'.php')) {
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi'.$i]='layout,select_key';
	$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi'.$i]='pi_flexform';
	t3lib_extMgm::addPlugin(array('LLL:EXT:'.$_EXTKEY.'/pi'.$i.'/locallang.xml:tt_content.list_type', $_EXTKEY.'_pi'.$i),'list_type');
	t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi'.$i, 'FILE:EXT:'.$_EXTKEY.'/pi'.$i.'/flexform.xml');
	$i++;
}

/***********************************************************************************************
 * TYPOSCRIPT SETUP
 ***********************************************************************************************/
t3lib_extMgm::addStaticFile($_EXTKEY,'static/','Typo3Chat');

?>