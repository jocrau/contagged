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
	var $prefixId = 'tx_contagged'; // same as class name
	var $scriptRelPath = 'pi1/class.tx_contagged_pi1.php'; // path to this script relative to the extension dir
	var $extKey = 'contagged'; // the extension key
	var $templateFile = 'EXT:contagged/pi1/contagged.tmpl';
	var $conf; // the TypoScript configuration array
	var $templateCode; // template file
	var $local_cObj;
	var $typolinkConf;
	var $backPid; // pid of the last visited page (from piVars)
	var $indexChar; // char of the given index the user has clicked on (from piVars)
	

	/**
	 * main method of the contagged list plugin
	 *
	 * @param	string		$content: The content of the cObj
	 * @param	array		$conf: The configuration
	 * @return	string			a single or list view of terms
	 */
	function main($content, $conf) {
		$this->local_cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->local_cObj->setCurrentVal($GLOBALS['TSFE']->id);
		$this->pi_loadLL();
		$this->conf = t3lib_div::array_merge_recursive_overrule($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_contagged.'], $conf);
		$this->templateCode = $this->cObj->fileResource($this->conf['templateFile']?$this->conf['templateFile']:$this->templateFile);
		$this->typolinkConf = $this->conf['typolink.'];
		$this->typolinkConf['parameter.']['current'] = 1;
		$this->typolinkConf['additionalParams'] = $this->cObj->stdWrap($typolinkConf['additionalParams'], $typolinkConf['additionalParams.']);
		unset($this->typolinkConf['additionalParams.']);
		$this->backPid = $this->piVars['backPid'] ? intval($this->piVars['backPid']) : NULL;
		$this->pointer = $this->piVars['pointer'] ? intval($this->piVars['pointer']) : NULL;
		$this->indexChar = $this->piVars['index'] ? urldecode($this->piVars['index']) : NULL; // TODO The length should be configurable
		if ( !is_null($this->piVars['key']) ) {
			$termKey = intval($this->piVars['key']);
		}		
		$sword = $this->piVars['sword'] ? htmlspecialchars(urldecode($this->piVars['sword'])) : NULL;

		// get an array of all type configurations
		$this->typesArray = $this->conf['types.'];

		// get the model (an associated array of terms)
		$this->mapper = new tx_contagged_model_mapper($this);
		$this->model = new tx_contagged_model_terms($this);
		$this->termsArray = $this->model->findAllTermsToListOnPage();

		if ((strtolower($this->conf['layout']) == 'minilist') || (strtolower($this->cObj->data['select_key']) == 'minilist')) {
			$content .= $this->renderMiniList();
		} elseif ( is_null($termKey) && is_null($sword) ) {
			$content .= $this->renderList();
		} elseif ( is_null($termKey) && !is_null($sword) ) {
			$content .= $this->renderListBySword($sword);
		} elseif ( !is_null($termKey) ) {
			$content .= $this->renderSingleItemByKey($termKey);
		}

		// TODO hook "newRenderFunctionName"

		$content = $this->removeUnfilledMarker($content);
		
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Renders the list of terms
	 *
	 * @return	$string	The list as HTML
	 */
	function renderList() {
		$subparts = $this->getSubparts('LIST');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		$this->renderSearchBox($markerArray);
		$indexedTerms = array();
		foreach ( $this->termsArray as $termKey => $termArray ) {
			if ( $this->indexChar==NULL || $termArray['indexChar']==$this->indexChar ) {
				$indexedTerms[$termKey] = $termArray;
			}
		}
		if ( $this->conf['pagebrowser.']['enable'] > 0 ) {
			$this->renderPageBrowser($markerArray, count($indexedTerms));
			$terms = array_slice($indexedTerms, ($this->pointer * $this->internal['results_at_a_time']), $this->internal['results_at_a_time'], TRUE);
		} else {
			$terms = $indexedTerms;
		}
		foreach ( $terms as $termKey => $termArray ) {
			$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
			$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
		}
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);

		return $content;
	}

	/**
	 * Renders the mini list of terms
	 *
	 * @return	$string	The list as HTML
	 */
	function renderMiniList() {
		$subparts = $this->getSubparts('MINILIST');
		$terms = $this->termsArray;
		foreach ( $terms as $termKey => $termArray ) {
			$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
			$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
		}
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);

		return $content;
	}

	function renderListBySword($sword) {
		$swordMatched = FALSE;
		$subparts = $this->getSubparts('LIST');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		$this->renderSearchBox($markerArray);
		foreach ( $this->termsArray as $termKey => $termArray ) {
			$fieldsToSearch = t3lib_div::trimExplode(',', $this->conf['searchbox.']['fieldsToSearch'] );
			foreach ($fieldsToSearch as $field) {						
				if (is_array($termArray[$field])) {
					foreach ($termArray[$field] as $subField) {
						if (preg_match('/' . preg_quote($sword,'/') . '/Uis', strip_tags($termArray[$field][$subField])) > 0) {
							$swordMatched = TRUE;
							break;
						}
					}
				} else {
					if (preg_match('/' . preg_quote($sword,'/') . '/Uis', strip_tags($termArray[$field])) > 0) {
						$swordMatched = TRUE;
						break;
					}
				}
			}
			if ( $swordMatched ) {
				$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
				$subpartArray['###LIST###'] .= $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
				$swordMatched = FALSE;
			}
		}
		if ($subpartArray['###LIST###'] == '') {
			$subpartArray['###LIST###'] = $this->pi_getLL('no_matches');			
		}

		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);			

		return $content;
	}

	function renderSingleItemByKey($termKey) {
		$subparts = $this->getSubparts('SINGLE');
		$this->renderLinks($markerArray,$wrappedSubpartArray);
		$this->renderIndex($markerArray);
		$this->renderSingleItem($termKey,$markerArray,$wrappedSubpartArray);
		$subpartArray['###LIST###'] = $this->cObj->substituteMarkerArrayCached($subparts['item'],$markerArray,$subpartArray,$wrappedSubpartArray);
		$content = $this->cObj->substituteMarkerArrayCached($subparts['template_list'],$markerArray,$subpartArray,$wrappedSubpartArray);

		return $content;
	}

	// TODO hook "newRenderFunction"

	function getSubparts($templateName = 'LIST') {
		$subparts['template_list'] = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_' . $templateName . '###');
		$subparts['item'] = $this->cObj->getSubpart($subparts['template_list'], '###ITEM###');

		return $subparts;
	}

	function renderLinks(&$markerArray, &$wrappedSubpartArray) {
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

	function renderSingleItem ($termKey,&$markerArray,&$wrappedSubpartArray) {
		$termArray = $this->termsArray[$termKey];
		$typeConfigArray = $this->conf['types.'][$termArray['term_type'] . '.'];

		$markerArray['###TERM_TYPE###'] = $typeConfigArray['label'];
		$markerArray['###TERM###'] = $this->cObj->editIcons($termArray['term'],'tx_contagged_terms:term_main,term_alt,term_type,term_lang,term_replace,desc_short,desc_long,image,dam_images,imagecaption,imagealt,imagetitle,related,link,exclude',$editIconsConf,'tx_contagged_terms:'.$termArray['uid']);
		$markerArray['###TERM_MAIN###'] = $termArray['term_main'];
		$markerArray['###TERM_ALT###'] = $termArray['term_alt']?implode(', ',$termArray['term_alt']):$this->pi_getLL('na');
		$markerArray['###TERM_REPLACE###'] = $termArray['term_replace']?$termArray['term_replace']:$this->pi_getLL('na');
		$markerArray['###DESC_SHORT###'] = $termArray['desc_short']?$termArray['desc_short']:$this->pi_getLL('na');
		$markerArray['###DESC_LONG###'] = $termArray['desc_long']?$termArray['desc_long']:$this->pi_getLL('na');
		$markerArray['###IMAGES###'] = $this->renderImages($termArray);
		$markerArray['###RELATED###'] = $this->renderRelated($termArray);
		$markerArray['###TERM_LANG###'] = $this->pi_getLL('lang.'.$termArray['term_lang'])?$this->pi_getLL('lang.'.$termArray['term_lang']):$this->pi_getLL('na');

		$labelWrap['wrap'] = $typeConfigArray['labelWrap1']?$typeConfigArray['labelWrap1']:$this->conf['labelWrap1'];
		$markerArray['###TERM_TYPE_LABEL###'] = $markerArray['###TERM_TYPE###']?$this->local_cObj->stdWrap($this->pi_getLL('term_type'),$labelWrap):'';
		$markerArray['###TERM_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term'),$labelWrap);
		$markerArray['###TERM_MAIN_LABEL###'] = $this->local_cObj->stdWrap($this->pi_getLL('term_main'),$labelWrap);
		$markerArray['###TERM_ALT_LABEL###'] = $markerArray['###TERM_ALT###']?$this->local_cObj->stdWrap($this->pi_getLL('term_alt'),$labelWrap):'';
		$markerArray['###TERM_REPLACE_LABEL###'] = $markerArray['###TERM_REPLACE###']?$this->local_cObj->stdWrap($this->pi_getLL('term_replace'),$labelWrap):'';
		$markerArray['###DESC_SHORT_LABEL###'] = $markerArray['###DESC_SHORT###']?$this->local_cObj->stdWrap($this->pi_getLL('desc_short'),$labelWrap):'';
		$markerArray['###DESC_LONG_LABEL###'] = $markerArray['###DESC_LONG###']?$this->local_cObj->stdWrap($this->pi_getLL('desc_long'),$labelWrap):'';
		$markerArray['###RELATED_LABEL###'] = $markerArray['###RELATED###']?$this->local_cObj->stdWrap($this->pi_getLL('related'),$labelWrap):'';
		$markerArray['###IMAGES_LABEL###'] = $markerArray['###IMAGES###']?$this->local_cObj->stdWrap($this->pi_getLL('images'),$labelWrap):'';
		$markerArray['###TERM_LANG_LABEL###'] = $markerArray['###TERM_LANG###']?$this->local_cObj->stdWrap($this->pi_getLL('term_lang'),$labelWrap):'';

		// make "more..." link
		$markerArray['###DETAILS###'] = $this->pi_getLL('details');
		unset($typolinkConf);
		$typolinkConf = $this->typolinkConf;
		$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[key]=' . $termKey;
		$typolinkConf['parameter'] = $termArray['listPages'][0];
		$this->typolinkConf['parameter.']['current'] = 0;
		$typolinkConf['parameter.']['wrap'] = "|,".$GLOBALS['TSFE']->type;
		$wrappedSubpartArray['###LINK_DETAILS###'] = $this->local_cObj->typolinkWrap($typolinkConf);
	}
	
	function renderRelated($term) {
		$relatedCode = '';
		if (is_array($term['related'])) {
			foreach ($term['related'] as $termReference) {
				$result = $this->model->findTermByUid($termReference['sourceName'], $termReference['uid']);
				$key = key($result);
				if (array_key_exists($key, $this->termsArray)) {
					$relatedTerm = current($result);
					$typolinkConf = $this->typolinkConf;
					$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[key]=' . $key;
					$typolinkConf['parameter.']['wrap'] = "|,".$GLOBALS['TSFE']->type;
					$relatedCode .= $this->local_cObj->stdWrap($this->local_cObj->typoLink($relatedTerm['term'], $typolinkConf), $this->conf['related.']['single.']['stdWrap.']);					
				}
			}
			return $this->local_cObj->stdWrap(trim($relatedCode), $this->conf['related.']['stdWrap.']);
		} else {
			return NULL;
		}
	}
	
	function renderImages($termArray) {
		$images = array();
		$imagesCaption = array();
		$imagesAltText = array();
		$imagesTitleText = array();
		$imagesCode = '';
		$imagesConf = $this->conf['images.']['single.'];
		$extConfArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['contagged']);
		if ( $extConfArray['getImagesFromDAM'] > 0 && t3lib_extMgm::isLoaded('dam') ) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_dam.file_path, tx_dam.file_name, tx_dam.alt_text, tx_dam.caption, tx_dam.title',
				'tx_dam', 'tx_dam_mm_ref', 'tx_contagged_terms',
				'AND tx_dam_mm_ref.tablenames = "tx_contagged_terms" AND tx_dam_mm_ref.ident="dam_images" ' .
				'AND tx_dam_mm_ref.uid_foreign = "' . $termArray['uid'] . '"', '', 'tx_dam_mm_ref.sorting_foreign ASC'
				);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$images[] = $row['file_path'] . $row['file_name'];
				$imagesCaption[] = str_replace(array(chr(10),chr(13)), ' ', $row['caption'] . ' ');
				$imagesAltText[] = str_replace(array(chr(10),chr(13)), ' ', $row['alt_text'] . ' ');
				$imagesTitleText[] = str_replace(array(chr(10),chr(13)), ' ', $row['title'] . ' ');
			}
		} else {
			$images = t3lib_div::trimExplode(',', $termArray['image'], 1);
			foreach ($images as $image) {
				$imagesWithPath[] = 'uploads/pics/' . $image;
			}
			$images = $imagesWithPath;
			$imagesCaption = t3lib_div::trimExplode(chr(10), $termArray['imagecaption']);
			$imagesAltText = t3lib_div::trimExplode(chr(10), $termArray['imagealt']);
			$imagesTitleText = t3lib_div::trimExplode(chr(10), $termArray['imagetitle']);
		}
		// debug($images, 'images');
		// debug($imagesCaption, 'imagesCaption');
		// debug($imagesAltText, 'imagesAltText');
		// debug($imagesTitleText, 'imagesTitleText');
		
		if (!empty($images)) {
			foreach ($images as $key => $image) {
				$imagesConf['image.']['file'] = $image;
				$imagesConf['image.']['altText'] = $imagesAltText[$key];
				$imagesConf['image.']['titleText'] = $imagesTitleText[$key];
				$caption = $imagesCaption[$key] != '' ? $this->local_cObj->stdWrap($imagesCaption[$key], $this->conf['images.']['caption.']['stdWrap.']) : '';
				$imagesCode .= $this->local_cObj->IMAGE($imagesConf['image.']);
				$imagesCode .= $caption;
			}
			return $this->local_cObj->stdWrap(trim($imagesCode), $this->conf['images.']['stdWrap.']);			
		} else {
			return NULL;
		}
		
	}

	function renderIndex(&$markerArray) {
		if ($this->conf['index.']['enable'] > 0) {
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
			$markerArray['###INDEX###'] = $this->cObj->substituteMarkerArrayCached($subparts['template_index'], $markerArray, $subpartArray);
		} else {
			$markerArray['###INDEX###'] = '';
		}
	}

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
					if ( preg_match('/^'.preg_quote($subChar).'/' . $this->conf['modifier'],$termArray[$sortField])>0 ) {
						$typolinkConf['additionalParams'] = '&' . $this->prefixId . '[index]=' . $indexChar;
						$indexArray[$indexChar] = $this->local_cObj->typolink($indexChar, $typolinkConf);
						$this->termsArray[$termKey]['indexChar'] = $indexChar;
					}
				}
				// If the term matches no given index char, crate one if desired and add it to the index
				if ( ($this->termsArray[$termKey]['indexChar'] == '') && ($this->conf['index.']['autoAddIndexChars'] == 1) ) {					
					// get the first char of the term (UTF8)
					// TODO: Make the RegEx configurable to make ZIP-Codes possible
					preg_match('/^./' . $this->conf['modifier'],$termArray[$sortField],$match);
					$newIndexChar = $match[0];
					$indexArray[$newIndexChar] = NULL;
					$typolinkConf['additionalParams'] .= '&' . $this->prefixId . '[index]=' . urlencode($newIndexChar);
					$indexArray[$newIndexChar] = $this->local_cObj->typolink($newIndexChar, $typolinkConf);
					$this->termsArray[$termKey]['indexChar'] = $newIndexChar;
				}
			}
		}

		// TODO Sorting of the index (UTF8)
		ksort($indexArray,SORT_LOCALE_STRING);

		return $indexArray;
	}
	
	function renderPageBrowser(&$markerArray, $resultCount) {
		// setup the page browser
		$this->internal['res_count'] = $resultCount;
		$this->internal['results_at_a_time'] = $this->conf['pagebrowser.']['results_at_a_time'] ? intval($this->conf['pagebrowser.']['results_at_a_time']) : 20;
		$this->internal['maxPages'] = $this->conf['pagebrowser.']['maxPages'] ? intval($this->conf['pagebrowser.']['maxPages']) : 3;
		$this->internal['dontLinkActivePage'] = $this->conf['pagebrowser.']['dontLinkActivePage'] ? (boolean)$this->conf['pagebrowser.']['dontLinkActivePage'] : FALSE;
		$this->internal['showFirstLast'] = $this->conf['pagebrowser.']['showFirstLast'] ? (boolean)$this->conf['pagebrowser.']['showFirstLast'] : FALSE;
		$this->internal['pagefloat'] = 'center';
		if ( ($this->internal['res_count'] > $this->internal['results_at_a_time']) && ($this->conf['pagebrowser.']['enable'] > 0)) {
			$showResultCount = $this->conf['pagebrowser.']['showResultCount'] ? (boolean)$this->conf['pagebrowser.']['showResultCount'] : FALSE;
			$markerArray['###PAGEBROWSER###'] = $this->pi_list_browseresults($showResultCount);
		} else {
			$markerArray['###PAGEBROWSER###'] = '';			
		}
	}
	
	function renderSearchBox(&$markerArray) {
		if ($this->conf['searchbox.']['enable'] > 0) {
			$markerArray['###SEARCHBOX###'] = $this->pi_list_searchBox();
		} else {
			$markerArray['###SEARCHBOX###'] = '';
		}
	}
	
	protected function removeUnfilledMarker($content) {
		return preg_replace('/###.*?###/', '', $content);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/contagged/pi1/class.tx_contagged_pi1.php']);
}
?>