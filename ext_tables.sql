#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
	tx_commerce_mountpoints tinytext
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_commerce_mountpoints tinytext
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_commerce_user_state_id blob NOT NULL
	tx_commerce_tt_address_id blob NOT NULL
	first_name varchar(50) DEFAULT '' NOT NULL,
	last_name varchar(50) DEFAULT '' NOT NULL,
	static_info_country char(3) DEFAULT '' NOT NULL
);

#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_commerce_foldereditorder tinyint(4) unsigned DEFAULT '0' NOT NULL,
	tx_graytree_foldername varchar(30) DEFAULT '' NOT NULL,

	KEY tx_commerce_foldereditorder (tx_commerce_foldereditorder),
	KEY tx_gray_folder (tx_graytree_foldername),
);

#
# Table structure for table 'tt_address'
#
CREATE TABLE tt_address (
	surname varchar(80) default '',
	tx_commerce_default_values int(11) DEFAULT '0' NOT NULL,
	tx_commerce_fe_user_id int(11) DEFAULT '0' NOT NULL,
	tx_commerce_address_type_id int(11) DEFAULT '0' NOT NULL,
	tx_commerce_is_main_address tinyint(4) DEFAULT '0' NOT NULL,
);

#
# Table structure for table 'tx_commerce_articles'
#
CREATE TABLE tx_commerce_articles (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	navtitle varchar(80) DEFAULT '' NOT NULL,
	images blob NOT NULL,
	ordernumber varchar(80) DEFAULT '' NOT NULL,
	eancode varchar(20) DEFAULT '' NOT NULL,
	description_extra text NOT NULL,
	plain_text text NOT NULL,
	prices mediumtext NOT NULL,
	tax double(4,2) DEFAULT '0.00' NOT NULL,
	article_type_uid int(11) DEFAULT '0' NOT NULL,
	supplier_uid int(11) DEFAULT '0' NOT NULL,
	uid_product int(11) DEFAULT '0' NOT NULL,
	article_attributes int(11) DEFAULT '0' NOT NULL,
	attribute_hash varchar(32) DEFAULT '' NOT NULL,
	attributesedit mediumtext NOT NULL,
	classname varchar(255) DEFAULT '' NOT NULL,
	relatedpage int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY uproduct (uid_product)
);

#
# Table structure for table 'tx_commerce_articles_article_attributes_mm'
#
CREATE TABLE tx_commerce_articles_article_attributes_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	value_char varchar(255) DEFAULT '',
	uid_valuelist int(11) DEFAULT '0' NOT NULL,
	uid_product int(11) DEFAULT '0' NOT NULL,
	default_value double(12,2) default '0.00',

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_commerce_article_prices'
#
CREATE TABLE tx_commerce_article_prices (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	uid_article int(11) DEFAULT '0' NOT NULL,
	price_net int(11) DEFAULT '0',
	price_gross int(11) DEFAULT '0',
	purchase_price int(11) DEFAULT '0',
	price_scale_amount_start int(11) DEFAULT '1',
	price_scale_amount_end int(11) DEFAULT '1',

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY uarticle (uid_article)
);

#
# Table structure for table 'tx_commerce_article_types'
#
CREATE TABLE tx_commerce_article_types (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(3) DEFAULT '0' NOT NULL,
	title varchar(255) DEFAULT '' NOT NULL,

	KEY lang (sys_language_uid),
	KEY parlang (l18n_parent),
	KEY parent (pid),
	PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_commerce_attributes'
#
CREATE TABLE tx_commerce_attributes (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	has_valuelist tinyint(3) DEFAULT '0' NOT NULL,
	multiple tinyint(3) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	internal_title varchar(160) DEFAULT '' NOT NULL,
	unit varchar(80) DEFAULT '' NOT NULL,
	valueformat varchar(80) DEFAULT '' NOT NULL,
	valuelist varchar(255) DEFAULT '' NOT NULL
	icon blob NOT NULL, 
	iconmode tinyint(3) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_attribute_correlationtypes'
#
CREATE TABLE tx_commerce_attribute_correlationtypes (
	uid int(11) NOT NULL auto_increment,
	title varchar(80) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
);

#
# Table structure for table 'tx_commerce_attribute_values'
#
CREATE TABLE tx_commerce_attribute_values (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	value varchar(255) DEFAULT '' NOT NULL,
	attributes_uid blob NOT NULL,
	icon blob NOT NULL,
	showvalue tinyint(4) DEFAULT '0' NOT NULL,

	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_baskets'
#
CREATE TABLE tx_commerce_baskets (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	pos int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sid char(128) DEFAULT '' NOT NULL,
	finished_time int(11) DEFAULT '0' NOT NULL,
	article_id int(11) NOT NULL DEFAULT '0',
	price_id int(11) NOT NULL DEFAULT '0',
	price_gross int(11) DEFAULT '0' NOT NULL,
	price_net int(11) DEFAULT '0' NOT NULL,
	quantity int(11) DEFAULT '0' NOT NULL,
	readonly int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY sid (sid),
	KEY finished_time (finished_time)
);

#
# Table structure for table 'tx_commerce_categories'
#
CREATE TABLE tx_commerce_categories (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	perms_userid int(11) DEFAULT '0' NOT NULL,
	perms_groupid int(11) DEFAULT '0' NOT NULL,
	perms_user int(11) DEFAULT '0' NOT NULL,
	perms_group int(11) DEFAULT '0' NOT NULL,
	perms_everybody int(11) DEFAULT '0' NOT NULL,
	editlock int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	extendToSubpages tinyint(3) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(255) DEFAULT '' NOT NULL,
	description text NOT NULL,
	images blob NOT NULL,
	teaser text NOT NULL,
	teaserimages blob NOT NULL,
	navtitle varchar(80) DEFAULT '' NOT NULL,
	keywords text NOT NULL,
	attributes mediumtext NOT NULL,
	parent_category varchar(255) DEFAULT '' NOT NULL,
	uname varchar(80) DEFAULT '' NOT NULL,
	ts_config text NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_categories_attributes_mm'
#
CREATE TABLE tx_commerce_categories_attributes_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	uid_correlationtype int(11) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY uid_correlationtype (uid_correlationtype)
);

#
# Table structure for table 'tx_commerce_categories_parent_category_mm'
#
CREATE TABLE tx_commerce_categories_parent_category_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames char(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	is_reference smallint(3) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_commerce_manufacturer'
#
CREATE TABLE tx_commerce_manufacturer (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	street varchar(80) DEFAULT '' NOT NULL,
	number varchar(80) DEFAULT '' NOT NULL,
	zip varchar(80) DEFAULT '' NOT NULL,
	city varchar(80) DEFAULT '' NOT NULL,
	country int(11) DEFAULT '0' NOT NULL,
	phone varchar(80) DEFAULT '' NOT NULL,
	fax varchar(80) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	internet varchar(80) DEFAULT '' NOT NULL,
	contactperson varchar(80) DEFAULT '' NOT NULL,
	logo blob NOT NULL,

	PRIMARY KEY (uid),
);

#
# Table structure for table 'tx_commerce_moveordermails'
#
CREATE TABLE tx_commerce_moveordermails (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	mailkind int(11) unsigned DEFAULT '0' NOT NULL,
	mailtemplate blob NOT NULL,
	sendername varchar(255) DEFAULT '' NOT NULL,
	senderemail varchar(255) DEFAULT '' NOT NULL,
	otherreceiver varchar(255) DEFAULT '' NOT NULL,
	BCC varchar(255) DEFAULT '' NOT NULL,
	htmltemplate blob NOT NULL,
	mailcharset varchar(255) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_newclients'
#
CREATE TABLE tx_commerce_newclients (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	year int(11) DEFAULT '0' NOT NULL,
	month int(11) DEFAULT '0' NOT NULL,
	day int(11) DEFAULT '0' NOT NULL,
	dow int(11) DEFAULT '0' NOT NULL,
	hour int(11) DEFAULT '0' NOT NULL,
	registration int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_orders'
#
CREATE TABLE tx_commerce_orders (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	newpid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	cust_deliveryaddress int(11) NOT NULL DEFAULT '0',
	order_type_uid int(11) NOT NULL DEFAULT '0',
	order_id varchar(80) NOT NULL DEFAULT '',
	cust_fe_user int(11) NOT NULL DEFAULT '0',
	cust_invoice int(11) NOT NULL DEFAULT '0',
	paymenttype varchar(80) DEFAULT '' NOT NULL,
	sum_price_net int(11) DEFAULT '0' NOT NULL,
	sum_price_gross int(11) DEFAULT '0' NOT NULL,
	payment_ref_id varchar(50) DEFAULT '',
	cu_iso_3_uid int(11) DEFAULT '0' NOT NULL,
	order_sys_language_uid int(11) DEFAULT '0' NOT NULL,
	pricefromnet tinyint(4) DEFAULT '0' NOT NULL,
	comment text,
	internalcomment text,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY orderid (order_id)
);

#
# Table structure for table 'tx_commerce_order_articles'
#
CREATE TABLE tx_commerce_order_articles (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	order_id varchar(80) NOT NULL DEFAULT '',
	order_uid int(11) NOT NULL DEFAULT '0',
	article_type_uid int(11) NOT NULL DEFAULT '0',
	article_uid int(11) NOT NULL DEFAULT '0',
	article_number varchar(80) DEFAULT '' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	price_net int(10) DEFAULT '0' NOT NULL,
	price_gross int(10) DEFAULT '0' NOT NULL,
	tax double(4,2) DEFAULT '0.00' NOT NULL,
	amount int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY orderid (order_id)
);

#
# Table structure for table 'tx_commerce_order_types'
#
CREATE TABLE tx_commerce_order_types (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	icon blob NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_products'
#
CREATE TABLE tx_commerce_products (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	starttime int(11) DEFAULT '0' NOT NULL,
	endtime int(11) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	subtitle varchar(80) DEFAULT '' NOT NULL,
	navtitle varchar(80) DEFAULT '' NOT NULL,
	keywords text NOT NULL,
	description text NOT NULL,
	teaser text NOT NULL,
	teaserimages blob NOT NULL,
	images blob NOT NULL,
	categories varchar(255) DEFAULT '' NOT NULL,
	manufacturer_uid int(11) DEFAULT '0' NOT NULL,
	attributes mediumtext NOT NULL,
	articles mediumtext NOT NULL,
	attributesedit mediumtext NOT NULL,
	uname varchar(80) DEFAULT '' NOT NULL,
	relatedpage int(11) DEFAULT '0' NOT NULL,
	relatedproducts blob NOT NULL,

	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_products_attributes_mm'
#
CREATE TABLE tx_commerce_products_attributes_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,
	uid_correlationtype int(11) DEFAULT '0' NOT NULL,
	uid_valuelist int(11) DEFAULT '0' NOT NULL,
	default_value varchar(255) DEFAULT '' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign),
	KEY uid_correlationtype (uid_correlationtype)
);

#
# Table structure for table 'tx_commerce_products_categories_mm'
#
CREATE TABLE tx_commerce_products_categories_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames char(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_commerce_products_related_mm'
# 
#
CREATE TABLE tx_commerce_products_related_mm (
	uid_local int(11) DEFAULT '0' NOT NULL,
	uid_foreign int(11) DEFAULT '0' NOT NULL,
	tablenames varchar(30) DEFAULT '' NOT NULL,
	sorting int(11) DEFAULT '0' NOT NULL,

	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_commerce_salesfigures'
#
CREATE TABLE tx_commerce_salesfigures (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	year int(11) DEFAULT '0' NOT NULL,
	month int(11) DEFAULT '0' NOT NULL,
	day int(11) DEFAULT '0' NOT NULL,
	dow int(11) DEFAULT '0' NOT NULL,
	hour int(11) DEFAULT '0' NOT NULL,
	pricegross int(11) DEFAULT '0' NOT NULL,
	pricenet int(11) DEFAULT '0' NOT NULL,
	amount int(11) DEFAULT '0' NOT NULL,
	orders int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_supplier'
#
CREATE TABLE tx_commerce_supplier (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	street varchar(80) DEFAULT '' NOT NULL,
	number varchar(80) DEFAULT '' NOT NULL,
	zip varchar(80) DEFAULT '' NOT NULL,
	city varchar(80) DEFAULT '' NOT NULL,
	country int(11) DEFAULT '0' NOT NULL,
	phone varchar(80) DEFAULT '' NOT NULL,
	fax varchar(80) DEFAULT '' NOT NULL,
	email varchar(80) DEFAULT '' NOT NULL,
	internet varchar(80) DEFAULT '' NOT NULL,
	contactperson varchar(80) DEFAULT '' NOT NULL,
	logo blob NOT NULL,

	PRIMARY KEY (uid),
);

#
# Table structure for table 'tx_commerce_tracking'
#
CREATE TABLE tx_commerce_tracking (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_oid int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_id int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	orders_uid blob NOT NULL,
	trackingcodes_uid blob NOT NULL,
	msg varchar(80) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_trackingcodes'
#
CREATE TABLE tx_commerce_trackingcodes (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_oid int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_id int(11) unsigned DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	description text NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);

#
# Table structure for table 'tx_commerce_user_states'
#
CREATE TABLE tx_commerce_user_states (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	icon blob NOT NULL,

	PRIMARY KEY (uid),
	KEY lang (sys_language_uid),
	KEY langpar (l18n_parent),
	KEY parent (pid)
);
