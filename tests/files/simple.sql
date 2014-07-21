-- {
-- 	"name" : "`a`",
-- 	"action_type" : "add_column"
-- }
ALTER TABLE `a` ADD COLUMN `c` char(10) DEFAULT NULL;
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "change_column"
-- }
ALTER TABLE `b` CHANGE COLUMN `id` `id` int(11) NOT NULL AUTO_INCREMENT; # was smallint(6) NOT NULL AUTO_INCREMENT
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "add_column"
-- }
ALTER TABLE `b` ADD COLUMN `c` char(20) DEFAULT NULL;
