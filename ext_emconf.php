<?php

########################################################################
# Extension Manager/Repository config file for ext: "contagged"
#
# Auto generated 23-09-2008 10:42
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Content parser and tagger',
	'description' => 'This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\').',
	'category' => 'fe',
	'shy' => 0,
	'version' => '0.1.3',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_contagged/rte/',
	'modify_tables' => 'tt_content,pages',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Jochen Rau',
	'author_email' => 'j.rau@web.de',
	'author_company' => '',
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
	'_md5_values_when_last_written' => 'a:23:{s:9:"ChangeLog";s:4:"a31d";s:10:"README.txt";s:4:"72b7";s:22:"class.tx_contagged.php";s:4:"8994";s:21:"ext_conf_template.txt";s:4:"0086";s:12:"ext_icon.gif";s:4:"50a3";s:17:"ext_localconf.php";s:4:"d45d";s:14:"ext_tables.php";s:4:"022c";s:14:"ext_tables.sql";s:4:"3600";s:27:"icon_tx_contagged_terms.gif";s:4:"50a3";s:16:"locallang_db.xml";s:4:"750b";s:7:"tca.php";s:4:"b9b2";s:14:"doc/manual.sxw";s:4:"0774";s:23:"doc/realurl_example.php";s:4:"7ef3";s:16:"js/selecttext.js";s:4:"1179";s:41:"model/class.tx_contagged_model_mapper.php";s:4:"8ead";s:40:"model/class.tx_contagged_model_terms.php";s:4:"a8ed";s:30:"pi1/class.tx_contagged_pi1.php";s:4:"6370";s:18:"pi1/contagged.tmpl";s:4:"4d1f";s:17:"pi1/locallang.xml";s:4:"1a34";s:20:"static/constants.txt";s:4:"973c";s:16:"static/setup.txt";s:4:"97ff";s:20:"static/css/setup.txt";s:4:"fd93";s:25:"static/examples/setup.txt";s:4:"b39c";}',
	'suggests' => array(
	),
);

?>