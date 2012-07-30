<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');


$TCA["tx_contagged_terms"] = array (
	"ctrl" => $TCA["tx_contagged_terms"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "sys_language_uid,l18n_parent,l18n_diffsource,hidden,starttime,endtime,fe_group term_main, term_alt, term_type, term_lang, replacement, desc_short, desc_long, reference, pronunciation, image, dam_images,imagecaption, imagealt, imagetitle, multimedia, related, link, exclude"
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
					"type" => "text",
					"cols" => "30",
					"rows" => "5",
				)
			),
			"term_type" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_type",
				"config" => Array (
					"type" => "select",
					"itemsProcFunc" => "user_addTermTypes",
					"size" => 1,
					"maxitems" => 1,
					"disableNoMatchingValueElement" => 1,
				)
			),
			"term_lang" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang",
				"config" => Array (
					"type" => "select",
					// TODO Make selectable languages configurable.
					"items" => Array (
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.0", ""),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.1", "en"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.2", "fr"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.3", "de"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.4", "it"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.5", "es"),
						Array("LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.term_lang.I.6", "un"),
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
				)
			),
			"reference" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.reference",
				"config" => Array (
					"type" => "text",
					"cols" => "30",
					"rows" => "2",
				)
			),
			"pronunciation" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.pronunciation",
				"config" => Array (
					"type" => "input",
					"size" => "30",
				)
			),
			'multimedia' => Array (
				'label' => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.multimedia',
				'config' => Array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => 'swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv,mp3,mp4,m4v',
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => 'uploads/media',
					'size' => '2',
					'maxitems' => '1',
					'minitems' => '0'
				)
			),
			'related' => Array (
				'exclude' => 1,
				'l10n_mode' => 'exclude',
				'label' => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.related',
				'config' => Array (
					'type' => 'group',
					'internal_type' => 'db',
					'allowed' => '*',
					'MM' => 'tx_contagged_related_mm',
					'show_thumbs' => 1,
					'size' => 3,
					'autoSizeMax' => 20,
					'maxitems' => 9999,
					'minitems' => 0,
				)
			),
			"link" => Array (
				"exclude" => 1,
				"label" => "LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.link",
				"config" => Array (
					"type"     => "input",
					"size"     => "28",
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
			"0" => array("showitem" => "sys_language_uid;;;;1-1-1, l18n_parent, l18n_diffsource, hidden;;1, term_main, term_alt, term_type, term_lang, term_replace, desc_short, desc_long;;;richtext[*]:rte_transform[mode=ts_css|imgpath=uploads/tx_contagged/rte/], reference, pronunciation, image, dam_images, imagecaption, imagealt, imagetitle, multimedia, related, link, exclude")
		),
		"palettes" => array (
			"1" => array("showitem" => "starttime, endtime, fe_group"),
			"2" => array("showitem" => "")
		)
	);

$extConfArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contagged']);
if ( $extConfArray['getImagesFromDAM'] > 0 && t3lib_extMgm::isLoaded('dam') ) {
	$TCA["tx_contagged_terms"]['columns'] = array_merge_recursive($TCA["tx_contagged_terms"]['columns'],array(
		'dam_images' => txdam_getMediaTCA('image_field', 'dam_images')
		)
	);
} else {
	$TCA["tx_contagged_terms"]['columns'] = array_merge_recursive($TCA["tx_contagged_terms"]['columns'], array(
			'image' => Array (
				'exclude' => 1,
				'l10n_mode' => $l10n_mode_image,
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.images',
				'config' => Array (
					'type' => 'group',
					'internal_type' => 'file',
					'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
					'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
					'uploadfolder' => 'uploads/pics',
					'show_thumbs' => '1',
					'size' => 3,
					'autoSizeMax' => 15,
					'maxitems' => '99',
					'minitems' => '0'
				)
			),
			'imagecaption' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.imagecaption',
				'l10n_mode' => $l10n_mode,
				'config' => Array (
					'type' => 'text',
					'cols' => '30',
					'rows' => '3'
				)
			),
			'imagealt' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.imagealt',
				'l10n_mode' => $l10n_mode,
				'config' => Array (
					'type' => 'text',
					'cols' => '20',
					'rows' => '3'
				)
			),
			'imagetitle' => Array (
				'exclude' => 1,
				'label' => 'LLL:EXT:contagged/locallang_db.xml:tx_contagged_terms.imagetitle',
				'l10n_mode' => $l10n_mode,
				'config' => Array (
					'type' => 'text',
					'cols' => '20',
					'rows' => '3'
				)
			),
		)
	);
}

require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_tstemplate.php');
require_once (PATH_t3lib.'class.t3lib_tsparser_ext.php');
require_once(t3lib_extMgM::extPath('contagged').'tx_contagged_userfunction.php');

?>