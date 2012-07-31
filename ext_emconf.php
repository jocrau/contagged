<?php

/***************************************************************
* Extension Manager/Repository config file for ext "contagged".
*
* Auto generated 31-07-2012 15:10
*
* Manual updates:
* Only the data in the array - everything else is removed by next
* writing. "version" and "dependencies" must not be touched!
***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Content parser and tagger (Glossary)',
	'description' => 'This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\').',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.7.0',
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
	'_md5_values_when_last_written' => 'a:25:{s:9:"ChangeLog";s:4:"ea2d";s:22:"class.tx_contagged.php";s:4:"e3df";s:21:"ext_conf_template.txt";s:4:"d179";s:12:"ext_icon.gif";s:4:"50a3";s:17:"ext_localconf.php";s:4:"0f7c";s:14:"ext_tables.php";s:4:"55a2";s:14:"ext_tables.sql";s:4:"0cf0";s:27:"icon_tx_contagged_terms.gif";s:4:"50a3";s:30:"icon_tx_contagged_terms__h.gif";s:4:"930e";s:16:"locallang_db.xml";s:4:"f99a";s:10:"README.txt";s:4:"83a1";s:7:"tca.php";s:4:"5b26";s:29:"tx_contagged_userfunction.php";s:4:"a573";s:23:"doc/cooluri_example.xml";s:4:"dc56";s:14:"doc/manual.sxw";s:4:"8abc";s:23:"doc/realurl_example.php";s:4:"8d84";s:23:"javascript/contagged.js";s:4:"d4f6";s:41:"model/class.tx_contagged_model_mapper.php";s:4:"6cb8";s:40:"model/class.tx_contagged_model_terms.php";s:4:"20e0";s:30:"pi1/class.tx_contagged_pi1.php";s:4:"2a63";s:18:"pi1/contagged.tmpl";s:4:"cd71";s:17:"pi1/locallang.xml";s:4:"0aa2";s:20:"static/constants.txt";s:4:"86c2";s:16:"static/setup.txt";s:4:"bc39";s:25:"static/examples/setup.txt";s:4:"d815";}',
	'suggests' => array(
	),
);

?>