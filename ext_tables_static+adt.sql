DROP TABLE IF EXISTS tx_commerce_attribute_correlationtypes;
CREATE TABLE tx_commerce_attribute_correlationtypes (
	uid int(11) NOT NULL auto_increment,
	title varchar(80) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid)
);
#
# @see tx_commeerce_element_alib.php
# new elements should be defined there as Constant too
#
INSERT INTO tx_commerce_attribute_correlationtypes VALUES ('1', 'selector');
INSERT INTO tx_commerce_attribute_correlationtypes VALUES ('2', 'shall');
INSERT INTO tx_commerce_attribute_correlationtypes VALUES ('3', 'can');
INSERT INTO tx_commerce_attribute_correlationtypes VALUES ('4', 'product');
INSERT INTO tx_commerce_attribute_correlationtypes VALUES ('5', 'filter_attributes');

#
# Table structure for table 'tx_commerce_address_types'
#
CREATE TABLE tx_commerce_address_types (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(3) DEFAULT '0' NOT NULL,
	hidden tinyint(3) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,
	title varchar(80) DEFAULT '' NOT NULL,
	name varchar(80) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
#
# new types of addresses can be added here for future needs
#
INSERT INTO tx_commerce_address_types (uid, pid, title, name) VALUES ('1', '0', 'billing address', 'billing');
INSERT INTO tx_commerce_address_types (uid, pid, title, name) VALUES ('2', '0', 'delivery address', 'delivery');

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

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY lang (sys_language_uid),
	KEY parlang (l18n_parent)
);
#
# new types of articles can be added here for future needs
#
INSERT INTO tx_commerce_article_types (uid, pid, title) VALUES ('1', '0', 'article');
INSERT INTO tx_commerce_article_types (uid, pid, title) VALUES ('2', '0', 'payment');
INSERT INTO tx_commerce_article_types (uid, pid, title) VALUES ('3', '0', 'delivery');

#
# Add _fe_commerce if not exists
#
INSERT INTO be_users (pid, username, password, tstamp, crdate) VALUES ('0', '_fe_commerce', MD5(RAND()), UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()));