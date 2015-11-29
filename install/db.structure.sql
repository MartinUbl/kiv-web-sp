SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `contribution_rating`;
CREATE TABLE `contribution_rating` (

  `contributions_id` int(11) NOT NULL,

  `users_id` int(11) NOT NULL,

  `originality` smallint(6) NOT NULL default '0',

  `topic` smallint(6) NOT NULL default '0',

  `structure` smallint(6) NOT NULL,

  `language` smallint(6) NOT NULL,

  `recommendation` smallint(6) NOT NULL,

  `notes` text,
  `rating_date` datetime NOT NULL,

  CONSTRAINT contrib_rating_1 FOREIGN KEY (contributions_id)
    REFERENCES contributions(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT contrib_rating_2 FOREIGN KEY (users_id)
    REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY  (`contributions_id`,`users_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `contribution_rating_assignment`;
CREATE TABLE `contribution_rating_assignment` (

  `contributions_id` int(11) NOT NULL,

  `users_id` int(11) NOT NULL,

  CONSTRAINT contrib_rating_assign_1 FOREIGN KEY (contributions_id)
    REFERENCES contributions(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT contrib_rating_assign_2 FOREIGN KEY (users_id)
    REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY  (`contributions_id`,`users_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `contributions`;
CREATE TABLE `contributions` (

  `id` int(11) NOT NULL auto_increment,

  `users_id` int(11) NOT NULL,

  `name` varchar(64) NOT NULL,

  `authors` varchar(255) NOT NULL,

  `abstract` text NOT NULL,

  `filename` varchar(128) NOT NULL,

  `status` varchar(16) NOT NULL default 'new',

  `create_date` datetime NOT NULL,

  `submission_date` datetime default NULL,

  CONSTRAINT contrib_1 FOREIGN KEY (users_id)
    REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  PRIMARY KEY  (`id`)

) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (

  `id` int(11) NOT NULL auto_increment,

  `username` varchar(32) NOT NULL,

  `password_hash` varchar(128) NOT NULL,

  `first_name` varchar(64) default NULL,

  `last_name` varchar(64) default NULL,

  `email` varchar(64) NOT NULL,

  `role` varchar(16) NOT NULL default 'author',

  PRIMARY KEY  (`id`)

) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users_deleted`;
CREATE TABLE `users_deleted` (
  `id` int(11) NOT NULL,

  `username` varchar(32) NOT NULL,

  `password_hash` varchar(128) NOT NULL,

  `first_name` varchar(64) default NULL,

  `last_name` varchar(64) default NULL,

  `email` varchar(64) NOT NULL,

  `role` varchar(16) NOT NULL default 'author',

  `delete_date` datetime NOT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET foreign_key_checks = 1;
