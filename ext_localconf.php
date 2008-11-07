<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_contagged_terms=1
');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_contagged_pi1.php','_pi1','list_type',1);

// $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['contagged'] = array();
// $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['contagged']['objectReference'] = 'EXT:'.$_EXTKEY.'/extensions/contagged/class.tx_rtehtmlarea_contagged.php:&tx_rtehtmlarea_contagged';
// $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['contagged']['addIconsToSkin'] = 1;
// $TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['contagged']['disableInFE'] = 1;

// $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['tx_contagged'] = 'EXT:contagged/class.tx_contagged.php:&tx_contagged->main';
// $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['tx_contagged'] = 'EXT:contagged/class.tx_contagged.php:&tx_contagged->main';
?>