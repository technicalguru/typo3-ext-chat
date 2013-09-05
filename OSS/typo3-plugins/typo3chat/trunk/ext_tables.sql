
#
# Table structure for table 'tx_typo3chat_messages'
#
CREATE TABLE tx_typo3chat_messages (
  uid int(11) not null auto_increment,
  crdate int(11) default 0 not null,
  tstamp int(11) default 0 not null,
  sender int(11) default 0 not null,
  recipient int(11) default 0 not null,
  message text not null,
  sent_date datetime default '0000-00-00 00:00:00' not null,
  received tinyint(4) unsigned default 0 not null,
  
  PRIMARY KEY (uid)
);
