<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// TODO RTE configuration
// t3lib_extMgm::addPageTSConfig('
// 	RTE.config.tx_contagged_terms.desc_long {
// 		proc.exitHTMLparser_db=1
// 		proc.exitHTMLparser_db {
// 			keepNonMatchedTags=1
// 		}
// 	}
// ');

// TODO Remove lines below; just for testing
// t3lib_extMgm::addPageTSConfig('
// 	TCEFORM.tx_contagged_terms.term_type.itemsProcFunc.test = 1
// ');

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_contagged_terms=1
');

// $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']['tx_contagged'] = 'EXT:contagged/class.tx_contagged.php:&tx_contagged->main';

?>