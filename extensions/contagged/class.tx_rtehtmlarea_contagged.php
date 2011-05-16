<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Fabien Udriot <fabien.udriot(arobas)ecodev.ch>
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
 * Ecodocument plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * TYPO3 SVN ID: $Id: class.tx_rtehtmlarea_contagged.php 2985 2008-01-31 11:37:57Z ingmars $
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_contagged extends tx_rtehtmlareaapi {

	protected $extensionKey = 'contagged'; // The key of the extension that is extending htmlArea RTE	
	protected $pluginName = 'contagged'; // The name of the plugin registered by the extension
	protected $relativePathToSkin = 'extensions/contagged/skin/htmlarea.css';// Path to the skin (css) file relative to the extension dir.
	
	protected $pluginButtons = 'contagged';
	protected $convertToolbarForHtmlAreaArray = array (
		'contagged'	=> 'contagged', #must be the same in the javascript var buttonId = Dummyplugin
		);
	
	
	 /**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		
		$registerRTEinJavascriptString = '';
		return $registerRTEinJavascriptString;
	}
	

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea_contagged/extensions/contagged/class.tx_rtehtmlarea_contagged.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/rtehtmlarea_contagged/contagged/class.tx_rtehtmlarea_contagged.php']);
}

?>