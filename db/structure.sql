BEGIN TRANSACTION;
CREATE TABLE `status` ( id INTEGER PRIMARY KEY AUTOINCREMENT , `name` TEXT);

INSERT INTO `status` (name) VALUES (YES);
INSERT INTO `status` (name) VALUES (NO);

CREATE TABLE `response` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`event_id` INTEGER,`player_id` INTEGER,`status_id` INTEGER  , `time` INTEGER, 'spotorder' INTEGER DEFAULT 0, `lastupdate` INTEGER, FOREIGN KEY(`event_id`)
						 REFERENCES `event`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL, FOREIGN KEY(`player_id`)
						 REFERENCES `player`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL, FOREIGN KEY(`status_id`)
						 REFERENCES `status`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL );
CREATE TABLE `comment` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`author` TEXT,`message` TEXT,`time` INTEGER,`event_id` INTEGER  , FOREIGN KEY(`event_id`)
						 REFERENCES `event`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL );
CREATE TABLE `link` ( id INTEGER PRIMARY KEY AUTOINCREMENT , `name` TEXT, `link` TEXT, `description` TEXT, `date` INTEGER);
CREATE TABLE `payment` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`value` NUMERIC,`description` TEXT,`date` INTEGER,`player_id` INTEGER  , FOREIGN KEY(`player_id`)
						 REFERENCES `player`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL );
CREATE TABLE `player` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`name` TEXT,`mail` TEXT,`sex` TEXT,`number` TEXT,`receive_mail` TEXT , `action` TEXT, `phone` TEXT, `iban` TEXT);
CREATE TABLE `tag` ( id INTEGER PRIMARY KEY AUTOINCREMENT , `title` TEXT);
CREATE TABLE `player_tag` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`player_id` INTEGER,`tag_id` INTEGER  , FOREIGN KEY(`tag_id`)
						 REFERENCES `tag`(`id`)
						 ON DELETE CASCADE ON UPDATE CASCADE, FOREIGN KEY(`player_id`)
						 REFERENCES `player`(`id`)
						 ON DELETE CASCADE ON UPDATE CASCADE );
CREATE TABLE `subscriber` ( id INTEGER PRIMARY KEY AUTOINCREMENT , `mail` TEXT, `joined` INTEGER);
CREATE TABLE `pickup` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`name` TEXT,`sex` TEXT,`time` INTEGER,`event_id` INTEGER  , FOREIGN KEY(`event_id`)
						 REFERENCES `event`(`id`)
						 ON DELETE SET NULL ON UPDATE SET NULL );
CREATE TABLE `event` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  ,`name` TEXT,`location` TEXT,`date` TEXT,`teamfee` TEXT,`playersfee` TEXT,`boys_min` TEXT,`boys_max` TEXT,`girls_min` TEXT,`girls_max` TEXT,`status` TEXT,`description` TEXT,`archived` INTEGER,`billed` INTEGER,`days` TEXT   );
CREATE INDEX index_foreignkey_response_status ON `response` (status_id);
CREATE INDEX index_foreignkey_response_player ON `response` (player_id);
CREATE INDEX index_foreignkey_response_event ON `response` (event_id);
CREATE INDEX index_foreignkey_comment_event ON `comment` (event_id) ;
CREATE INDEX index_foreignkey_payment_player ON `payment` (player_id) ;
CREATE INDEX index_foreignkey_player_tag_player ON `player_tag` (player_id) ;
CREATE INDEX index_foreignkey_player_tag_tag ON `player_tag` (tag_id) ;
CREATE UNIQUE INDEX UQ_player_tagplayer_id__tag_id ON `player_tag` (`player_id`,`tag_id`);
CREATE INDEX index_foreignkey_pickup_event ON `pickup` (event_id) ;
COMMIT;
