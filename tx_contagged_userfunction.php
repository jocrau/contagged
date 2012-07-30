<?php

function user_addTermTypes(&$params,&$pObj) {
	global $BE_USER;

	$template = t3lib_div::makeInstance('t3lib_TStemplate');
	$template->tt_track = 0;
	$template->init();
	$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
	$rootline = $sysPage->getRootLine(getCurrentPageId());
	$rootlineIndex = 0;
	foreach ($rootline as $index => $rootlinePart) {
		if ($rootlinePart['is_siteroot'] == 1) {
			$rootlineIndex = $index;
			break;
		}
	}
	$template->runThroughTemplates($rootline, $rootlineIndex);
	$template->generateConfig();
	$conf = $template->setup['plugin.']['tx_contagged.'];

	// make localized labels
	$LOCAL_LANG_ARRAY = array();
	if (!empty($conf['types.'])) {
		foreach ($conf['types.'] as $typeName => $typeConfigArray ) {
			unset($LOCAL_LANG_ARRAY);
			if ( !$typeConfigArray['hideSelection']>0 && !$typeConfigArray['dataSource'] ) {
				if (is_array($typeConfigArray['label.'])) {
					foreach ($typeConfigArray['label.'] as $langKey => $labelText) {
						$LOCAL_LANG_ARRAY[$langKey]['label'] = $labelText;
					}
				}
				$LOCAL_LANG_ARRAY['default']['label'] = $typeConfigArray['label'] ? $typeConfigArray['label'] : $typeConfigArray['label.']['default'];
				$params['items'][]= array( $GLOBALS['LANG']->getLLL('label',$LOCAL_LANG_ARRAY), substr($typeName,0,-1) );
			}
		}
	}
}

function getCurrentPageId() {
	$pageId = (integer)t3lib_div::_GP('id');
	if ($pageId > 0) {
		return $pageId;
	}

	preg_match('/(?<=id=)[0-9]a/', urldecode(t3lib_div::_GET('returnUrl')), $matches);
	if (count($matches) > 0) {
		return $matches[0];
	}

	$rootTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
	if (count($rootTemplates) > 0) {
		return $rootTemplates[0]['pid'];
	}

	$rootPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
	if (count($rootPages) > 0) {
		return $rootPages[0]['uid'];
	}

	// take pid 1 as fallback
	return 1;
}

?>