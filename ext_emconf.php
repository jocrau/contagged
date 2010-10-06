<?php

########################################################################
# Extension Manager/Repository config file for ext "contagged".
#
# Auto generated 06-02-2010 10:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Content parser and tagger (Glossary)',
	'description' => 'This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\').',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.6.4',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_contagged/rte/',
	'modify_tables' => 'tt_content,pages',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Jochen Rau',
	'author_email' => 'jochen.rau@typoplanet.de',
	'author_company' => 'typoplanet',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:24:{s:9:"ChangeLog";s:4:"01e3";s:10:"README.txt";s:4:"83a1";s:22:"class.tx_contagged.php";s:4:"73ec";s:21:"ext_conf_template.txt";s:4:"d179";s:12:"ext_icon.gif";s:4:"50a3";s:17:"ext_localconf.php";s:4:"0162";s:14:"ext_tables.php";s:4:"36c3";s:14:"ext_tables.sql";s:4:"0afa";s:27:"icon_tx_contagged_terms.gif";s:4:"50a3";s:30:"icon_tx_contagged_terms__h.gif";s:4:"930e";s:16:"locallang_db.xml";s:4:"0305";s:7:"tca.php";s:4:"1d36";s:23:"doc/cooluri_example.xml";s:4:"e0a4";s:14:"doc/manual.sxw";s:4:"8abc";s:23:"doc/realurl_example.php";s:4:"bf01";s:23:"javascript/contagged.js";s:4:"d4f6";s:41:"model/class.tx_contagged_model_mapper.php";s:4:"e3e2";s:40:"model/class.tx_contagged_model_terms.php";s:4:"8890";s:30:"pi1/class.tx_contagged_pi1.php";s:4:"2e70";s:18:"pi1/contagged.tmpl";s:4:"86b9";s:17:"pi1/locallang.xml";s:4:"bcfe";s:20:"static/constants.txt";s:4:"afcb";s:16:"static/setup.txt";s:4:"7ac9";s:25:"static/examples/setup.txt";s:4:"4e66";}',
	'suggests' => array(
	),
);

?>