<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages('tx_contagged_terms');
t3lib_extMgm::addToInsertRecords('tx_contagged_terms');

// add contagged to the "insert plugin" content element
t3lib_extMgm::addPlugin(array('LLL:EXT:contagged/locallang_db.php:tx_contagged_terms.plugin', $_EXTKEY.'_pi1'),'list_type');

// initialize static extension templates
t3lib_extMgm::addStaticFile($_EXTKEY,'static/', 'Content parser');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/examples/', 'Experimental Setup');

$TCA["tx_contagged_terms"] = array (
	"ctrl" => array (
		'title'     => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms',		
		'label'     => 'term_replace',
		'label_alt' => 'term_main, term_alt',
		'label_alt_force' => TRUE,
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'versioningWS' => TRUE, 
		'origUid' => 't3_origuid',
		'languageField'            => 'sys_language_uid',	
		'transOrigPointerField'    => 'l18n_parent',	
		'transOrigDiffSourceField' => 'l18n_diffsource',	
		'sortby' => 'sorting',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',	
			'starttime' => 'starttime',	
			'endtime' => 'endtime',	
			'fe_group' => 'fe_group',
		),
		'useColumnsForDefaultValues' => 'term_type',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_contagged_terms.gif',
	),
	"feInterface" => array (
		"fe_admin_fieldList" => "sys_language_uid, l18n_parent, l18n_diffsource, hidden, starttime, endtime, fe_group, term_main, term_alt, term_type, term_lang, term_replace, desc_short, desc_long, image, dam_images, imagecaption, imagealt, imagetitle, related, link, exclude",
	)
);

// Add a field  "exclude this page from parsing" to the table "pages" and "tt_content"
$tempColumns = Array (
    "tx_contagged_dont_parse" => Array (
        "exclude" => 1,
        "label" => "LLL:EXT:contagged/locallang_db.xml:pages.tx_contagged_dont_parse",
        "config" => Array (
            "type" => "check",
        )
    ),
);

t3lib_div::loadTCA("pages");
t3lib_extMgm::addTCAcolumns("pages",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("pages","tx_contagged_dont_parse;;;;1-1-1");

t3lib_div::loadTCA("tt_content");
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("tt_content","tx_contagged_dont_parse;;;;1-1-1");

?>