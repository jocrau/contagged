<?php

########################################################################
# Extension Manager/Repository config file for ext: "contagged"
#
# Auto generated 20-03-2009 12:03
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Content parser and tagger (Glossary)',
	'description' => 'This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\').',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.0.3',
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
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"783f";s:10:"README.txt";s:4:"72b7";s:22:"class.tx_contagged.php";s:4:"0326";s:21:"ext_conf_template.txt";s:4:"3d40";s:12:"ext_icon.gif";s:4:"50a3";s:17:"ext_localconf.php";s:4:"f640";s:14:"ext_tables.php";s:4:"7a50";s:14:"ext_tables.sql";s:4:"0afa";s:27:"icon_tx_contagged_terms.gif";s:4:"50a3";s:16:"locallang_db.xml";s:4:"0305";s:7:"tca.php";s:4:"7ed7";s:23:"doc/cooluri_example.xml";s:4:"3b41";s:14:"doc/manual.sxw";s:4:"0774";s:23:"doc/realurl_example.php";s:4:"7ef3";s:55:"extensions/contagged/class.tx_rtehtmlarea_contagged.php";s:4:"6e77";s:34:"extensions/contagged/locallang.xml";s:4:"9489";s:38:"extensions/contagged/skin/htmlarea.css";s:4:"5e97";s:46:"extensions/contagged/skin/images/contagged.gif";s:4:"50a3";s:46:"extensions/contagged/skin/images/contagged.png";s:4:"63d3";s:39:"htmlarea/plugins/contagged/contagged.js";s:4:"41da";s:40:"htmlarea/plugins/contagged/locallang.xml";s:4:"e333";s:16:"js/selecttext.js";s:4:"1179";s:41:"model/class.tx_contagged_model_mapper.php";s:4:"7092";s:40:"model/class.tx_contagged_model_terms.php";s:4:"b375";s:30:"pi1/class.tx_contagged_pi1.php";s:4:"4d12";s:18:"pi1/contagged.tmpl";s:4:"55bf";s:17:"pi1/locallang.xml";s:4:"3830";s:20:"static/constants.txt";s:4:"a199";s:16:"static/setup.txt";s:4:"3980";s:20:"static/css/setup.txt";s:4:"bfc8";s:25:"static/examples/setup.txt";s:4:"7887";}',
	'suggests' => array(
	),
);

?>