<?php
/***************************************************************
	*  Copyright notice
	*
	*  (c) 2007 Jochen Rau <j.rau@web.de>
	*  All rights reserved
	*
	*  This script is part of the TYPO3 project. The TYPO3 project is
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

require_once (PATH_tslib . 'class.tslib_pibase.php');
require_once (t3lib_extMgm::extPath('contagged') . 'model/class.tx_contagged_model_terms.php');
require_once (t3lib_extMgm::extPath('contagged') . 'model/class.tx_contagged_model_mapper.php');

/**
 * contagged list plugin
 *
 * @author	Jochen Rau <j.rau@web.de>
 * @package	TYPO3
 * @subpackage	tx_contagged_pi1
 */
class tx_contagged_pi1 extends tslib_pibase {
	var $prefixId = 'tx_contagged_pi1'; // same as class name
	var $scriptRelPath = 'pi1/class.tx_contagged_pi1.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $templateFile = 'EXT:contagged/pi1/contagged.tmpl';
	var $conf; // the TypoScript configuration array
	var $templateCode; // template file
	var $local_cObj;
	var $typolinkConf;
	var $backPid; // pid of the last visited page (from piVars)
	var $indexChar; // char of the given index the user has clicked on (from piVars)
	var $termKey; // local key for each term (not related to the uid in the database)

	/**
	 * main method of the contagged list plugin
	 *
	 * @param	string		$content: The content of the cObj
	 * @param	array		$conf: The configuration
	 * @return	string			a single or list view of terms
	 */
	function main($content) {
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
		$this->pi_loadLL();
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_contagged.'];
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']?$this->conf['templateFile']:$this->templateFile);
		$this->typolinkConf = $this->conf['typolink.'];
		$this->typolinkConf['parameter.']['current'] = 1;
		$this->typolinkConf['additionalParams'] = $this->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
		unset($this->typolinkConf['additionalParams.']);
		$this->backPid = (int)$this->piVars['backPid'] ? (int)$this->piVars['backPid'] : NULL;
		$this->indexChar = $this->piVars['index'] ? urldecode($this->piVars['index']) : NULL;
		if ( !is_null($this->piVars['key']) ) {
			$this->termKey = (int)$this->piVars['key'];
		}
		$this->sword = $this->piVars['sword'] ? urldecode($this->piVars['sword']) : NULL;

		// get an array of all type configurations
		$this->typesArray = $this->conf['types.'];

		$this->mapper = new tx_contagged_model_mapper($this);

		// get the model (an associated array of terms)
		$this->model = new tx_contagged_model_terms($this);
		$this->termsArray = $this->model->getTermsArray();

		if ( is_null($this->termKey) && is_null($this->sword) ) {
			$renderFunction = 'renderList';
		} elseif ( is_null($this->termKey) && !is_null($this->sword) ) {
			$renderFunction = 'renderListBySword';
		} elseif ( !is_null($this->termKey) ) {
			$renderFunction = 'renderSingleItemByKey';
		}

		// TODO hook "newRenderFunctionName"

		if(method_exists($this, $renderFunction)) {
			$content .= $this->$renderFunction();
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderList() {
		$subparts = $this->getSubparts('LIST');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		foreach ( $this->termsArray as $termKey => $termArray ) {
			if ( $termArray['exclude']!=1 && $this->conf['types.'][$termArray['term_type'].'.']['dontListTerms']!=1 && in_array($GLOBALS['TSFE']->id,$termArray['listPages']) ) {
				if ( $this->indexChar==NULL || $termArray['indexChar']==$this->indexChar ) {
					$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
					$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
				}
			}
		}
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderListBySword() {
		$subparts = $this->getSubparts('LIST');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		foreach ( $this->termsArray as $termKey => $termArray ) {
			if ( $termArray['exclude']!=1 && $this->conf['types.'][$termArray['term_type'].'.']['dontListTerms']!=1 ) {
				if ( $this->indexChar==NULL || $termArray['indexChar']==$this->indexChar ) {
					$fieldsToSearch = t3lib_div::trimExplode(',',$this->conf['fieldsToSearch'] );
					foreach ($fieldsToSearch as $field) {
						$swordMatched = preg_match('/'.preg_quote($this->sword,'/').'/Uui',$termArray[$field]) ? TRUE : FALSE;
					}
					if ( $swordMatched ) {
						$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
						$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
					}
				}
			}
		}
		// TODO Display warning if result is empty
		// if (!$content) {
		// 	$subpartArray['###LIST###'] = "No items.";
		// }
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);			

		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function renderSingleItemByKey() {
		$subparts = $this->getSubparts('SINGLE');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		$this->renderSingleItem($this->termKey,$markerArray,$wrappedSubpartArray);
		$subpartArray['###LIST###'] = $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);

		return $content;
	}

	// TODO hook "newRenderFunction"

	function getSubparts($templateName='LIST') {
		$subparts['template_list'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_' . $templateName . '###');
		$subparts['item'] = $this->cObj->getSubpart($subparts['template_list'],'###ITEM###');

		return $subparts;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$markerArray: ...
	 * @param	[type]		$wrappedSubpartArray: ...
	 * @return	[type]		...
	 */
	function renderLinks(&$markerArray,&$wrappedSubpartArray) {
		// make "back to..." link
		if ($this->backPid) {
			if($this->conf['addBackLinkDescription']>0) {
				$backPage = $this->pi_getRecord('pages', $this->backPid);
				$markerArray['###BACK_TO###'] = $this->pi_getLL('backToPage') . " \"" . $backPage['title'] . "\"";
			} else {
				$markerArray['###BACK_TO###'] = $this->pi_getLL('back');
			}
		} else {
			$markerArray['###BACK_TO###'] = '';
		}
		unset($typolinkConf);
		$typolinkConf['parameter'] = $this->backPid;
		$wrappedSubpartArray['###LINK_BACK_TO###'] = $this->local_cObj->typolinkWrap($typolinkConf);

		// make "link to all entries"
	    $markerArray['###INDEX_ALL###'] = $this->pi_linkTP($this->pi_getLL('all'));

		// make "to list ..." link
		unset($typolinkConf);
		$markerArray['###TO_LIST###'] = $this->pi_getLL('toList');
		$typolinkConf = $this->typolinkConf;
		$typolinkConf['parameter.']['wrap'] = "|,".$GLOBALS['TSFE']->type;
		$wrappedSubpartArray['###LINK_TO_LIST###'] = $this->local_cObj->typolinkWrap($typolinkConf);		
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$termKey: ...
	 * @param	[type]		$markerArray: ...
	 * @param	[type]		$wrappedSubpartArray: ...
	 * @return	[type]		...
	 */
	function renderSingleItem ($termKey,&$markerArray,&$wrappedSubpartArray) {
		$termArray = $this->termsArray[$termKey];
		$typeConfigArray = $this->conf['types.'][$termArray['term_type'] . '.'];

		// TODO Add a search box
		// $markerArray['###SEARCHBOX###'] = $this->pi_list_searchBox();
		$markerArray['###SEARCHBOX###'] = '';

		$markerArray['###TERM_TYPE###'] = $typeConfigArray['label'];
		$markerArray['###TERM###'] = $this->cObj->editIcons($termArray['term'],'tx_contagged_terms:term_main,term_alt,term_type,term_lang,term_replace,desc_short,desc_long,link,exclude',$editIconsConf,'tx_contagged_terms:'.$termArray['uid']);
		$markerArray['###TERM_MAIN###'] = $termArray['term_main'];
		$markerArray['###TERM_ALT###'] = $termArray['term_alt']?implode(', ',$termArray['term_alt']):$this->pi_getLL('na');
		$markerArray['###TERM_REPLACE###'] = $termArray['term_replace']?$termArray['term_replace']:$this->pi_getLL('na');
		$markerArray['###DESC_SHORT###'] = $termArray['desc_short']?$termArray['desc_short']:$this->pi_getLL('na');
		$markerArray['###DESC_LONG###'] = $termArray['desc_long']?$termArray['desc_long']:$this->pi_getLL('na');
		$markerArray['###TERM_LANG###'] = $this->pi_getLL('lang.'.$termArray['term_lang'])?$this->pi_getLL('lang.'.$termArray['term_lang']):$this->pi_getLL('na');
		// TODO Support for tx_categories
		$markerArray['###TERM_CATEGORY###'] = $termArray['term_category']?$termArray['term_category']:$this->pi_getLL('na');

		$labelWrap['noTrimWrap'] = $typeConfigArray['labelWrap1']?$typeConfigArray['labelWrap1']:$this->conf['labelWrap1'];
		$markerArray['###TERM_TYPE_LABEL###'] = $markerArray['###TERM_TYPE###']?$this->local_cObj->stdWrap($this->pi_getLL('term_type'),$labelWrap):'';
		$markerArray['###TERM_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term'),$labelWrap);
		$markerArray['###TERM_MAIN_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term_main'),$labelWrap);
		$markerArray['###TERM_ALT_LABEL###'] = $markerArray['###TERM_ALT###']?$this->local_cObj->stdWrap($this->pi_getLL('term_alt'),$labelWrap):'';
		$markerArray['###TERM_REPLACE_LABEL###'] = $markerArray['###TERM_REPLACE###']?$this->local_cObj->stdWrap($this->pi_getLL('term_replace'),$labelWrap):'';
		$markerArray['###DESC_SHORT_LABEL###'] = $markerArray['###DESC_SHORT###']?$this->local_cObj->stdWrap($this->pi_getLL('desc_short'),$labelWrap):'';
		$markerArray['###DESC_LONG_LABEL###'] = $markerArray['###DESC_LONG###']?$this->local_cObj->stdWrap($this->pi_getLL('desc_long'),$labelWrap):'';
		$markerArray['###TERM_LANG_LABEL###'] = $markerArray['###TERM_LANG###']?$this->local_cObj->stdWrap($this->pi_getLL('term_lang'),$labelWrap):'';

		// make "more..." link
		$markerArray['###DETAILS###'] = $this->pi_getLL('details');
		unset($typolinkConf);
		$typolinkConf = $this->typolinkConf;
		$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[key]=' . $termKey;
		$typolinkConf['parameter.']['wrap'] = "|,".$GLOBALS['TSFE']->type;
		$wrappedSubpartArray['###LINK_DETAILS###'] = $this->local_cObj->typolinkWrap($typolinkConf);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$markerArray: ...
	 * @return	[type]		...
	 */
	function renderIndex (&$markerArray) {
		$subparts = array();
		$subparts['template_index'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_INDEX###');
		$subparts['item'] = $this->cObj->getSubpart($subparts['template_index'],'###ITEM###');

		$indexArray = $this->getIndexArray();

		// wrap index chars and add a class attribute if there is a selected index char.
		foreach ($indexArray as $indexChar => $link) {
			$cssClass = '';
			if ($this->piVars['index']==$indexChar) {
				$cssClass = " class='tx-contagged-act'";
			}
			if ($link) {
				$markerArray['###SINGLE_CHAR###'] = '<span' . $cssClass . '>' . $link . '</span>';
			} elseif ($this->conf['showOnlyMatchedIndexChars']==0) {
				$markerArray['###SINGLE_CHAR###'] = '<span' . $cssClass . '>' . $indexChar . '</span>';
			} else {
				$markerArray['###SINGLE_CHAR###'] = '';
			}
			$subpartArray['###INDEX_CONTENT###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'], $markerArray);
		}

		// // make "link to all entries"
		// unset($typolinkConf);
		// $typolinkConf = $this->typolinkConf;
		// $allLink = $this->local_cObj->typolink($this->pi_getLL('all'), $typolinkConf);
		// $markerArray['###INDEX_ALL###'] = $allLink;

		$markerArray['###INDEX###'] = $this->cObj->substituteMarkerArrayCached($subparts['template_index'], $markerArray, $subpartArray);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getIndexArray() {
		// Get localized index chars.
		foreach (t3lib_div::trimExplode(',', $this->pi_getLL('indexChars')) as $key => $value) {
			$subCharArray = t3lib_div::trimExplode('|', $value);
			$indexArray[$subCharArray[0]] = NULL;
	        foreach($subCharArray as $subChar) {
	            $reverseIndexArray[$subChar] = $subCharArray[0];
	        }
		}

		// The configuered subchars like Ö will be linked as O (see documentation and file "locallang.xml").
		unset($typolinkConf);
		$typolinkConf = $this->typolinkConf;
		foreach ($this->termsArray as $termKey => $termArray) {
			if ( $termArray['exclude']!=1 && $this->conf['types.'][$termArray['term_type'].'.']['dontListTerms']!=1 && in_array($GLOBALS['TSFE']->id,$termArray['listPages']) ) {
				$sortField = $this->model->getSortField($termArray);
				// debug($sortField);
				foreach ($reverseIndexArray as $subChar => $indexChar) {
					// debug(preg_quote($subChar),$termArray['term']);
					// debug(preg_match('/^'.preg_quote($subChar).'/ui',$termArray['term']));
					if ( preg_match('/^'.preg_quote($subChar).'/ui',$termArray[$sortField])>0 ) {
						$typolinkConf['additionalParams'] = '&' . $this->prefixId . '[index]=' . $indexChar;
						$indexArray[$indexChar] = $this->local_cObj->typolink($indexChar, $typolinkConf);
						$this->termsArray[$termKey]['indexChar'] = $indexChar;
					}
				}
				// If the term matches no given index char, crate one if desired and add it to the index
				if ( $this->termsArray[$termKey]['indexChar']=='' && $this->conf['autoAddIndexChars']==1 ) {					
					// get the first char of the term (UTF8)
					// TODO: Make the RegEx configurable to make ZIP-Codes possible
					preg_match('/^./u',$termArray[$sortField],$match);
					$newIndexChar = $match[0];
					$indexArray[$newIndexChar] = NULL;
					$typolinkConf['additionalParams'] = '&' . $this->prefixId . '[index]=' . urlencode($newIndexChar);
					$indexArray[$newIndexChar] = $this->local_cObj->typolink($newIndexChar, $typolinkConf);
					$this->termsArray[$termKey]['indexChar'] = $newIndexChar;
				}
			}
		}

		// TODO Sorting of the index (UTF8)
		ksort($indexArray,SORT_LOCALE_STRING);

		return $indexArray;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']);
}
?>