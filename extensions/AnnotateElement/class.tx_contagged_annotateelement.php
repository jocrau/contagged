<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  (c) 2011 Adapted by Jochen Rau <jochen.rau*typoplanet.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Edit Element extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 */
class tx_contagged_annotateelement extends tx_rtehtmlarea_api {

	protected $extensionKey = 'contagged';		// The key of the extension that is extending htmlArea RTE
	protected $prefixId = 'tx_contagged';
	protected $relativePathToResources = '/resources/contagged/'; // This is used to tag terms with RDFa
	protected $pluginName = 'AnnotateElement';			// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/AnnotateElement/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/AnnotateElement/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir
	protected $htmlAreaRTE;					// Reference to the invoking object
	protected $thisConfig;					// Reference to RTE PageTSConfig
	protected $toolbar;					// Reference to RTE toolbar array
	protected $LOCAL_LANG; 					// Frontend language array

	protected $conf; // The generated term configuration
	protected $termsArray; // an array holding the terms data
	
	protected $pluginButtons = 'termselector,showannotatedterms';
	protected $convertToolbarForHtmlAreaArray = array (
		'termselector'			=> 'TermSelector',
		'showannotatedterms'		=> 'ShowAnnotatedTerms',
		);

	public function main($parentObject) {
		if (!t3lib_extMgm::isLoaded('contagged')) {
			$this->pluginButtons = t3lib_div::rmFromList('termselector', $this->pluginButtons);
		} else {
			require_once(t3lib_extMgm::extPath('contagged') . 'model/class.tx_contagged_model_terms.php');
			$this->buildConfiguration();
			$this->loadTerms();
		}
		return parent::main($parentObject);
	}
	
	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.'].buttons.button-id.property = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$button = 'termselector';
		$registerRTEinJavascriptString = '';
		if (!is_array( $this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][$button . '.'])) {
			$registerRTEinJavascriptString .= '
		RTEarea['.$RTEcounter.'].buttons.'. $button .' = new Object();';
		}
		$termsJSArray = array();
		foreach ($this->termsArray as $key => $termArray) {
			$termConf = $this->conf['types.'][$termArray['term_type'] . '.'];
			$termsJSArray[] = array(
				'text' => $termArray['term_main'],
				'value' => $key
			);
		}
		array_unshift($termsJSArray, array('text' => 'Select term', 'value' => 'none'));
		if ($this->htmlAreaRTE->is_FE()) {
			$GLOBALS['TSFE']->csConvObj->convArray($termsJSArray, $this->htmlAreaRTE->OutputCharset, 'utf-8');
		} else {
			$GLOBALS['LANG']->csConvObj->convArray($termsJSArray, $GLOBALS['LANG']->charSet, 'utf-8');
		}
		$termsJSArray = json_encode(array('options' => $termsJSArray));
		$registerRTEinJavascriptString .= '
	RTEarea['.$RTEcounter.'].buttons.'. $button .'.dataUrl = "' . $this->htmlAreaRTE->writeTemporaryFile('', $button, 'js', $termsJSArray) . '";
	RTEarea['.$RTEcounter.'].buttons.'. $button .'.relativePathToResource = "' . $this->relativePathToResources . '";';
		return $registerRTEinJavascriptString;
	}

	protected function buildConfiguration() {
		require_once (PATH_t3lib.'class.t3lib_page.php');
		require_once (PATH_t3lib.'class.t3lib_tstemplate.php');
		require_once (PATH_t3lib.'class.t3lib_tsparser_ext.php');

		$template = t3lib_div::makeInstance('t3lib_TStemplate');
		$template->tt_track = 0;
		$template->init();
		$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
		$rootline = $sysPage->getRootLine($this->getCurrentPageId());
		$rootlineIndex = 0;
		foreach ($rootline as $index => $rootlinePart) {
			if ($rootlinePart['is_siteroot'] == 1) {
				$rootlineIndex = $index;
				break;
			}
		}
		$template->runThroughTemplates($rootline, $rootlineIndex);
		$template->generateConfig();

		if (is_array($template->setup['plugin.'][$this->prefixId.'.'])) {
			$this->conf = $template->setup['plugin.'][$this->prefixId.'.'];
		} else {
			$this->conf = array();
		}
	}

	protected function loadTerms() {
		if (!is_array($this->termsArray)) {
			if (!empty($this->conf)) {
				$model = t3lib_div::makeInstance('tx_contagged_model_terms', $this->conf);
				$this->termsArray = $model->findAllTerms();
			} else {
				$this->termsArray = array();
			}
		}
	}


	protected function getCurrentPageId() {
		$pageId = (integer)t3lib_div::_GP('id');
		if ($pageId > 0) {
			return (int) $pageId;
		}

		preg_match('/(?<=id=)[0-9]+/', urldecode(t3lib_div::_GET('returnUrl')), $matches);
		if (count($matches) > 0) {
			return (int) $matches[0];
		}

		$rootTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
		if (count($rootTemplates) > 0) {
			return (int) $rootTemplates[0]['pid'];
		}

		$rootPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
		if (count($rootPages) > 0) {
			return (int) $rootPages[0]['uid'];
		}

		// take pid 1 as fallback
		return 1;
	}

	/**
	 * Return an updated array of toolbar enabled buttons
	 *
	 * @param	array		$show: array of toolbar elements that will be enabled, unless modified here
	 *
	 * @return 	array		toolbar button array, possibly updated
	 */
	public function applyToolbarConstraints($show) {
		if (!t3lib_extMgm::isLoaded('contagged')) {
			return array_diff($show, array('annotateelement'));
		} else {
			return $show;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/contagged/extensions/AnnotateElement/class.tx_contagged_annotateelement.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/contagged/extensions/AnnotateElement/class.tx_contagged_annotateelement.php']);
}
?>