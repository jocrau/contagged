<?php

########################################################################
# Extension Manager/Repository config file for ext: "contagged"
#
# Auto generated 06-10-2007 00:53
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Content parser and tagger',
	'description' => 'This extension parses your content to tag, replace and link specific terms. It is useful to auto-generate a glossary - but not only. See \'ChangeLog\' and WiKi (\'http://wiki.typo3.org/index.php/Contagged\'). Needs at least PHP 4.4.0',
	'category' => 'fe',
	'shy' => 0,
	'version' => '0.0.13',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'uploadfolder' => 1,
	'createDirs' => 'uploads/tx_contagged/rte/',
	'modify_tables' => '',
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
	'_md5_values_when_last_written' => 'a:29:{s:9:"ChangeLog";s:4:"c780";s:10:"README.txt";s:4:"821e";s:22:"class.tx_contagged.php";s:4:"c03f";s:21:"ext_conf_template.txt";s:4:"f53f";s:12:"ext_icon.gif";s:4:"50a3";s:17:"ext_localconf.php";s:4:"a6a7";s:14:"ext_tables.php";s:4:"3a59";s:14:"ext_tables.sql";s:4:"3600";s:27:"icon_tx_contagged_terms.gif";s:4:"50a3";s:16:"locallang_db.xml";s:4:"9602";s:7:"tca.php";s:4:"dffd";s:27:"configuration/css/setup.txt";s:4:"f0f0";s:30:"configuration/ts/constants.txt";s:4:"9507";s:26:"configuration/ts/setup.txt";s:4:"a67c";s:49:"controller/class.tx_contagged_controller_list.php";s:4:"e29c";s:24:"controller/locallang.xml";s:4:"eab9";s:14:"doc/manual.sxw";s:4:"676c";s:19:"doc/wizard_form.dat";s:4:"8bf3";s:20:"doc/wizard_form.html";s:4:"c95f";s:41:"model/class.tx_contagged_model_mapper.php";s:4:"5534";s:40:"model/class.tx_contagged_model_terms.php";s:4:"60ef";s:30:"pi1/class.tx_contagged_pi1.php";s:4:"8f28";s:18:"pi1/contagged.tmpl";s:4:"a534";s:17:"pi1/locallang.xml";s:4:"4a6d";s:20:"static/constants.txt";s:4:"3696";s:16:"static/setup.txt";s:4:"5ff0";s:20:"static/css/setup.txt";s:4:"f0f0";s:37:"view/class.tx_contagged_view_list.php";s:4:"d51a";s:19:"view/contagged.tmpl";s:4:"f38e";}',
	'suggests' => array(
	),
);

?>