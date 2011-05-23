/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * EditElement plugin for htmlArea RTE
 */
HTMLArea.AnnotateElement = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		/*
		 * Setting up some properties from PageTSConfig
		 */
		this.buttonsConfiguration = this.editorConfiguration.buttons;
		this.useAttribute = {};
		this.useAttribute.lang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useLangAttribute) ? this.buttonsConfiguration.language.useLangAttribute : true;
		this.useAttribute.xmlLang = (this.buttonsConfiguration.language && this.buttonsConfiguration.language.useXmlLangAttribute) ? this.buttonsConfiguration.language.useXmlLangAttribute : false;
		if (!this.useAttribute.lang && !this.useAttribute.xmlLang) {
			this.useAttribute.lang = true;
		}

			// Importing list of allowed attributes
		if (this.getPluginInstance("TextStyle")) {
			this.allowedAttributes = this.getPluginInstance("TextStyle").allowedAttributes;
		}
		if (!this.allowedAttributes && this.getPluginInstance("InlineElements")) {
			this.allowedAttributes = this.getPluginInstance("InlineElements").allowedAttributes;
		}
		if (!this.allowedAttributes && this.getPluginInstance("BlockElements")) {
			this.allowedAttributes = this.getPluginInstance("BlockElements").allowedAttributes;
		}
		if (!this.allowedAttributes) {
			this.allowedAttributes = new Array("id", "title", "lang", "xml:lang", "dir", "class");
			if (Ext.isIE) {
				this.allowedAttributes.push("className");
			}
		}
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '1.0',
			developer	: 'Jochen Rau (based on the work of Stanilas Rolland)',
			developerUrl	: 'http://typoplanet.de',
			copyrightOwner	: 'Stanislas Rolland, Jochen Rau',
			sponsor		: 'SJBR & qedStudio (London)',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the buttons
		 */
		buttonId = 'ShowAnnotatedTerms';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId + '-Tooltip'),
			iconCls		: 'htmlarea-action-show-annotated-terms',
			action		: 'onButtonPress'
		};
		this.registerButton(buttonConfiguration);
		/*
		 * Registering the dropdown list
		 */
		var buttonId = 'TermSelector';
		if (this.buttonsConfiguration[buttonId.toLowerCase()] && this.buttonsConfiguration[buttonId.toLowerCase()].dataUrl) {
			var dropDownConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				storeUrl	: this.buttonsConfiguration[buttonId.toLowerCase()].dataUrl,
				action		: 'onChange'
			};
			if (this.buttonsConfiguration.language) {
				dropDownConfiguration.width = this.buttonsConfiguration.language.width ? parseInt(this.buttonsConfiguration.language.width, 10) : 200;
				if (this.buttonsConfiguration.language.listWidth) {
					dropDownConfiguration.listWidth = parseInt(this.buttonsConfiguration.language.listWidth, 10);
				}
				if (this.buttonsConfiguration.language.maxHeight) {
					dropDownConfiguration.maxHeight = parseInt(this.buttonsConfiguration.language.maxHeight, 10);
				}
			}
			this.registerDropDown(dropDownConfiguration);
		}
		return true;
	},
	/*
	 * This function gets called when the editor is generated
	 */
	onGenerate: function () {
			// Add rules to the stylesheet for language mark highlighting
			// Model: body.htmlarea-show-tagged-terms *[lang=en]:before { content: "en: "; }
			// Works in IE8, but not in earlier versions of IE
		var select = this.getButton('Language');
		if (select) {
			var styleSheet = this.editor._doc.styleSheets[0];
			select.getStore().each(function (option) {
				var selector = 'body.htmlarea-show-tagged-terms *[' + 'lang="' + option.get('value') + '"]:before';
				var style = 'content: "' + option.get('value') + ': ";';
				var rule = selector + ' { ' + style + ' }';
				if (!Ext.isIE) {
					try {
						styleSheet.insertRule(rule, styleSheet.cssRules.length);
					} catch (e) {
						this.appendToLog('onGenerate', 'Error inserting css rule: ' + rule + ' Error text: ' + e, 'warn');
					}
				} else {
					styleSheet.addRule(selector, style);
				}
				return true;
			}, this);
				// Monitor the combo's store being loaded
			select.mon(select.getStore(), 'load', function () { this.updateValue(select); }, this);
		}
	},
	/*
	 * This function gets called when a button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.toggleLanguageMarks();
		return false;
	},

	/*
	 * Toggles the display of language marks
	 *
	 * @param	boolean		forceLanguageMarks: if set, language marks are displayed whatever the current state
	 *
	 * @return	void
	 */
	toggleLanguageMarks : function (forceLanguageMarks) {
		var body = this.editor._doc.body;
		if (!HTMLArea.DOM.hasClass(body, 'htmlarea-show-tagged-terms')) {
			HTMLArea.DOM.addClass(body,'htmlarea-show-tagged-terms');
		} else if (!forceLanguageMarks) {
			HTMLArea.DOM.removeClass(body,'htmlarea-show-tagged-terms');
		}
	},

	/*
	 * This function gets called when some language was selected in the drop-down list
	 */
	onChange : function (editor, combo, record, index) {
		this.applyLanguageMark(combo.getValue());
	},

	/*
	 * This function applies the langauge mark to the selection
	 */
	applyLanguageMark : function (language) {
		var selection = this.editor._getSelection();
		var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
		var range = this.editor._createRange(selection);
		var parent = this.editor.getParentElement(selection, range);
		var selectionEmpty = this.editor._selectionEmpty(selection);
		var endPointsInSameBlock = this.editor.endPointsInSameBlock();
		var fullNodeSelected = false;
		if (!selectionEmpty) {
			if (endPointsInSameBlock) {
				var ancestors = this.editor.getAllAncestors();
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i])
						&& ((!Ext.isIE && ancestors[i].textContent === range.toString()) || (Ext.isIE && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text))));
					if (fullNodeSelected) {
						parent = ancestors[i];
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && statusBarSelection.textContent === range.toString()) {
					fullNodeSelected = true;
					parent = statusBarSelection;
				}
			}
		}
		if (selectionEmpty || fullNodeSelected) {
				// Selection is empty or parent is selected in the status bar
			if (parent) {
					// Set language attributes
				this.setLanguageAttributes(parent, language);
			}
		} else if (endPointsInSameBlock) {
				// The selection is not empty, nor full element
			if (language != "none") {
					// Add tag with lang attribute(s)
				var newElement = this.editor._doc.createElement("acronym");
				this.setLanguageAttributes(newElement, language);
				this.editor.wrapWithInlineElement(newElement, selection, range);
				if (!Ext.isIE) {
					range.detach();
				}
			}
		} else {
			this.setLanguageAttributeOnBlockElements(language);
		}
	},

	/*
	 * This function gets the language attribute on the given element
	 *
	 * @param	object		element: the element from which to retrieve the attribute value
	 *
	 * @return	string		value of the lang attribute, or of the xml:lang attribute
	 */
	getLanguageAttribute : function (element) {
		var xmllang = "none";
		try {
				// IE7 complains about xml:lang
			xmllang = element.getAttribute("xml:lang") ? element.getAttribute("xml:lang") : "none";
		} catch(e) { }
		return element.getAttribute("lang") ? element.getAttribute("lang") : xmllang;
	},

	/*
	 * This function sets the language attributes on the given element
	 *
	 * @param	object		element: the element on which to set the value of the lang and/or xml:lang attribute
	 * @param	string		language: value of the lang attributes, or "none", in which case, the attribute(s) is(are) removed
	 *
	 * @return	void
	 */
	setLanguageAttributes : function (element, language) {
		if (language == "none") {
				// Remove language mark, if any
			element.removeAttribute("title");
				// Remove the span tag if it has no more attribute
			if ((element.nodeName.toLowerCase() == "acronym") && !HTMLArea.hasAllowedAttributes(element, this.allowedAttributes)) {
				this.editor.removeMarkup(element);
			}
		} else {
			element.setAttribute("title", language);
		}
	},

	/*
	 * This function gets the language attributes from blocks sibling of the block containing the start container of the selection
	 *
	 * @return	string		value of the lang attribute, or of the xml:lang attribute, or "none", if all blocks sibling do not have the same attribute value as the block containing the start container
	 */
	getLanguageAttributeFromBlockElements : function() {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		var startAncestors = this.editor.getBlockAncestors(endBlocks.start);
		var endAncestors = this.editor.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		var language = this.getLanguageAttribute(startAncestors[index]);
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.isBlockElement(block)) {
				if (this.getLanguageAttribute(block) != language || this.getLanguageAttribute(block) == "none") {
					language = "none";
					break;
				}
			}
			if (block == endAncestors[index]) {
				break;
			}
		}
		return language;
	},

	/*
	 * This function sets the language attributes on blocks sibling of the block containing the start container of the selection
	 */
	setLanguageAttributeOnBlockElements : function(language) {
		var selection = this.editor._getSelection();
		var endBlocks = this.editor.getEndBlocks(selection);
		var startAncestors = this.editor.getBlockAncestors(endBlocks.start);
		var endAncestors = this.editor.getBlockAncestors(endBlocks.end);
		var index = 0;
		while (index < startAncestors.length && index < endAncestors.length && startAncestors[index] === endAncestors[index]) {
			++index;
		}
		if (endBlocks.start === endBlocks.end) {
			--index;
		}
		for (var block = startAncestors[index]; block; block = block.nextSibling) {
			if (HTMLArea.isBlockElement(block)) {
				this.setLanguageAttributes(block, language);
			}
			if (block == endAncestors[index]) {
				break;
			}
		}
	},

	/*
	 * This function gets called when the toolbar is updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			var selection = this.editor._getSelection();
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var range = this.editor._createRange(selection);
			var parent = this.editor.getParentElement(selection);
			switch (button.itemId) {
				case 'ShowTaggedTerms':
					button.setInactive(!HTMLArea.DOM.hasClass(this.editor._doc.body, 'htmlarea-show-tagged-terms'));
					break;
				case 'TermSelector':
						// Updating the language drop-down
					var fullNodeSelected = false;
					var language = this.getLanguageAttribute(parent);
					if (!selectionEmpty) {
						if (endPointsInSameBlock) {
							for (var i = 0; i < ancestors.length; ++i) {
								fullNodeSelected = (statusBarSelection === ancestors[i])
									&& ((!Ext.isIE && ancestors[i].textContent === range.toString()) || (Ext.isIE && ((selection.type !== "Control" && ancestors[i].innerText === range.text) || (selection.type === "Control" && ancestors[i].innerText === range.item(0).text))));
								if (fullNodeSelected) {
									parent = ancestors[i];
									break;
								}
							}
								// Working around bug in Safari selectNodeContents
							if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && statusBarSelection.textContent === range.toString()) {
								fullNodeSelected = true;
								parent = statusBarSelection;
							}
							language = this.getLanguageAttribute(parent);
						} else {
							language = this.getLanguageAttributeFromBlockElements();
						}
					}
					this.updateValue(button, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock);
					break;
				default:
					break;
			}
		}
	},

	/*
	* This function updates the language drop-down list
	*/
	updateValue : function (select, language, selectionEmpty, fullNodeSelected, endPointsInSameBlock) {
		var store = select.getStore();
		store.removeAt(0);
		if ((store.findExact('value', language) != -1) && (selectionEmpty || fullNodeSelected || !endPointsInSameBlock)) {
			select.setValue(language);
			store.insert(0, new store.recordType({
				text: this.localize('Remove language mark'),
				value: 'none'
			}));
		} else {
			store.insert(0, new store.recordType({
				text: this.localize('No language mark'),
				value: 'none'
			}));
			select.setValue('none');
		}
		select.setDisabled(!(store.getCount()>1) || (selectionEmpty && this.editor.getParentElement().nodeName.toLowerCase() === 'body'));
	}
});
