/***************************************************************
*  Copyright notice
*
*  (c) 2008 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Character Map Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id: $
 */
contagged = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Jochen Rau",
			developerUrl	: "http://www.typoplanet.de/",
			copyrightOwner	: "Jochen Rau",
			sponsor		: "",
			sponsorUrl	: "",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "contagged";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize("contagged"),
			action		: "onButtonPress",
			textMode	: true,
			dialog		: true
		};
		this.registerButton(buttonConfiguration);

		return true;
	 },

	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function(editor, id) {

		if (this.editor.hasSelectedText()) {
			var term = this.editor.getSelectedHTML();
			vHWin=window.open('http://localhost:8888/t3dev/typo3/alt_doc.php?edit[tx_contagged_terms][2]=edit&columnsOnly=term_main%2Cterm_alt%2Cterm_type%2Cterm_lang%2Cterm_replace%2Cdesc_short%2Cdesc_long%2Clink%2Cexclude&noView=0&returnUrl=close.html','FEquickEditWindow','width=540,height=400,status=0,menubar=0,scrollbars=1,resizable=1');vHWin.focus();return false;
		}
		
		alert(term);
	}
});

