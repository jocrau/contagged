<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addUserTSConfig('
	options.saveDocNew.tx_contagged_terms=1
');

t3lib_extMgm::addPageTSConfig('
RTE.default {
	proc.allowTags := addToList(exclude)
	proc.entryHTMLparser_db = 0
	proc.entryHTMLparser_db.allowTags := addToList(exclude)
	proc.entryHTMLparser_db.tags.span.allowedAttribs := addToList(property, rel, resource, rev, typeof, content, about, datatype)
	FE.proc.allowTags := addToList(exclude)
	FE.proc.entryHTMLparser_db.allowTags := addToList(exclude)
}');

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_contagged_pi1.php', '_pi1', 'list_type', 1);

$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['AnnotateElement'] = array();
$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['AnnotateElement']['objectReference'] = 'EXT:'.$_EXTKEY.'/extensions/AnnotateElement/class.tx_contagged_annotateelement.php:&tx_contagged_annotateelement';
$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['AnnotateElement']['addIconsToSkin'] = 1;
$TYPO3_CONF_VARS['EXTCONF']['rtehtmlarea']['plugins']['AnnotateElement']['disableInFE'] = 0;

?>