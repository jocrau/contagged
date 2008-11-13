#
# Table structure for table 'tx_contagged_terms'
#
CREATE TABLE tx_contagged_terms (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	term_main tinytext NOT NULL,
	term_alt tinytext NOT NULL,
	term_type tinytext NOT NULL,
	term_lang char(2) DEFAULT '' NOT NULL,
	term_replace tinytext NOT NULL,
	desc_short tinytext NOT NULL,
	desc_long text NOT NULL,
	image text NOT NULL,
	dam_images int(11) DEFAULT '0' NOT NULL,
	imagecaption text NOT NULL,
	imagealt text NOT NULL,
	imagetitle text NOT NULL,
	related int(11) DEFAULT '0' NOT NULL,
	link tinytext NOT NULL,
	exclude tinyint(3) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_contagged_related_mm'
#
CREATE TABLE tx_contagged_related_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	tablenames tinytext NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_contagged_dont_parse tinyint(3) DEFAULT '0' NOT NULL,
	tx_contagged_keywords text NOT NULL,
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	tx_contagged_dont_parse tinyint(3) DEFAULT '0' NOT NULL,
);