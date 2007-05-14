<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


$TCA["tx_contagged_terms"] = array (
	"ctrl" => $TCA["tx_contagged_terms"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group term_main, term_alt, term_type, term_lang, replacement, desc_short, desc_long, link, exclude"
	),
	"feInterface" => $TCA["tx_contagged_terms"]["feInterface"],
	"columns" => array (
		't3ver_label' => array (		
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_contagged_terms',
				'foreign_table_where' => 'AND tx_contagged_terms.pid=###CURRENT_PID### AND tx_contagged_terms.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
			"term_main" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_main",
				"config" => Array (
					"type" => "input",
					"size" => "30",
					"eval" => "required",
				)
			),
			"term_alt" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_alt",
				"config" => Array (
					"type" => "input",
					"size" => "30",
				)
			),
			"term_type" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_type",
				"config" => Array (
					"type" => "select",
					"itemsProcFunc" => "user_addItemsToTCA",
					"size" => 1,
					"maxitems" => 1,
				)
			),
			"term_lang" => Array (		
				"exclude" => 1,		
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang",		
				"config" => Array (
					"type" => "select",
					"items" => Array (
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.0", ""),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.1", "en"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.2", "fr"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.3", "de"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.4", "it"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.5", "es"),
					),
					"size" => 1,	
					"maxitems" => 1,
				)
			),
			"term_replace" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_replace",
				"config" => Array (
					"type" => "input",
					"size" => "30",
				)
			),
			"desc_short" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.desc_short",
				"config" => Array (
					"type" => "input",
					"size" => "30",
				)
			),
			"desc_long" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.desc_long",
				"config" => Array (
					"type" => "text",
					"cols" => "30",
					"rows" => "5",
					"wizards" => Array(
						"_PADDING" => 2,
						"RTE" => array(
							"notNewRecords" => 1,
							"RTEonly" => 1,
							"type" => "script",
							"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
							"icon" => "wizard_rte2.gif",
							"script" => "wizard_rte.php",
						),
					),
				)
			),
			"link" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.link",
				"config" => Array (
					"type"     => "input",
					"size"     => "15",
					"max"      => "255",
					"checkbox" => "",
					"eval"     => "trim",
					"wizards"  => array(
						"_PADDING" => 2,
						"link"     => array(
							"type"         => "popup",
							"title"        => "Link",
							"icon"         => "link_popup.gif",
							"script"       => "browse_links.php?mode=wizard",
							"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
						)
					)
				)
			),
			"exclude" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.exclude",
				"config" => Array (
					"type" => "check",
				)
			),
		),
		"types" => array (
			"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, term_main, term_alt, term_type, term_lang, term_replace, desc_short, desc_long;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_contagged/rte/], link, exclude")
		),
		"palettes" => array (
			"1" => array("showitem" => "starttime, endtime, fe_group")
		)
	);
	
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_tstemplate.php');
require_once (PATH_t3lib.'class.t3lib_tsparser_ext.php');

function user_addItemsToTCA(&$params,&$pObj) {

	// get extension configuration
	$extConfArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contagged']);
	if ( intval($extConfArray['mainConfigStorageUid']) > 0 ) {
		$mainConfigStorageUid = intval($extConfArray['mainConfigStorageUid']);
	} else {
		// TODO parse static setup
	}

		
	$sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
	// FIXME: pageUid is static; make it configurable
	$rootLine = $sysPageObj->getRootLine($mainConfigStorageUid);
	$TSObj = t3lib_div::makeInstance('t3lib_tsparser_ext');
	$TSObj->tt_track = 0;
	$TSObj->init();
	$TSObj->runThroughTemplates($rootLine);
	$TSObj->generateConfig();
	$conf = $TSObj->setup['plugin.']['tx_contagged.'];
	
	// 
	if ($conf['types.']) {
		foreach ($conf['types.'] as $typeName => $typeConfigArray ) {
			$params['items'][]= Array( $typeConfigArray['label'], substr($typeName,0,-1) );
		}
	}

}
?>