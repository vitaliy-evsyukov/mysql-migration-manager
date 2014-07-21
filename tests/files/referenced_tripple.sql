-- {
-- 	"name" : "workaround_jJIxK_create",
-- 	"action_type" : "create_workaround"
-- }
DELIMITER ;;

CREATE PROCEDURE `workaround_jJIxK`
(
    given_table    VARCHAR(64),
    given_index    VARCHAR(64),
    index_stmt     TEXT,
    index_action   VARCHAR(10)
)
BEGIN

    DECLARE IndexIsThere INTEGER;

    SELECT COUNT(1) INTO IndexIsThere
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_schema = DATABASE()
    AND   table_name   = given_table
    AND   index_name   = given_index;

    IF (IndexIsThere >= 1 AND index_action = 'drop') OR (IndexIsThere = 0 AND index_action = 'create') THEN
        SET @sqlstmt = index_stmt;
        PREPARE st FROM @sqlstmt;
        EXECUTE st;
        DEALLOCATE PREPARE st;
    END IF;

END ;;

DELIMITER ;
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "change_fk_before_`a`",
-- 	"referenced_tables" : [
-- 		"`a`"
-- 	]
-- }
CALL `workaround_jJIxK` ('b', 'eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop', 'ALTER TABLE `b` ADD INDEX eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop (`a_id`);', 'create');
ALTER TABLE `b` DROP FOREIGN KEY `b_ibfk_1`; # was CONSTRAINT `b_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `a` (`id`)
ALTER TABLE `b` CHANGE COLUMN `a_id` `a_id` smallint(6) NOT NULL; # was int(11) NOT NULL
-- {
-- 	"name" : "`c`",
-- 	"action_type" : "change_fk_before_`b`",
-- 	"referenced_tables" : [
-- 		"`b`"
-- 	]
-- }
CALL `workaround_jJIxK` ('c', 'eqfk_temp_a952f29b884f11d57656504a7f2e4f89_drop', 'ALTER TABLE `c` ADD INDEX eqfk_temp_a952f29b884f11d57656504a7f2e4f89_drop (`b_id`);', 'create');
ALTER TABLE `c` DROP FOREIGN KEY `c_ibfk_1`; # was CONSTRAINT `c_ibfk_1` FOREIGN KEY (`b_id`) REFERENCES `b` (`id`)
ALTER TABLE `c` CHANGE COLUMN `b_id` `b_id` smallint(6) NOT NULL; # was int(11) NOT NULL
-- {
-- 	"name" : "`a`",
-- 	"action_type" : "change_column"
-- }
ALTER TABLE `a` CHANGE COLUMN `id` `id` smallint(6) NOT NULL AUTO_INCREMENT; # was int(11) NOT NULL AUTO_INCREMENT
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "change_column"
-- }
ALTER TABLE `b` CHANGE COLUMN `id` `id` smallint(6) NOT NULL AUTO_INCREMENT; # was int(11) NOT NULL AUTO_INCREMENT
-- {
-- 	"name" : "`workaround_Pgxpn`",
-- 	"action_type" : "drop_routine"
-- }
DROP PROCEDURE IF EXISTS `workaround_Pgxpn`;
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "change_fk_after_`a`",
-- 	"referenced_tables" : [
-- 		"`a`"
-- 	]
-- }
CALL `workaround_jJIxK` ('b', 'a_id', 'ALTER TABLE `b` ADD INDEX `a_id` (`a_id`);', 'create');
ALTER TABLE `b` ADD CONSTRAINT `b_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `a` (`id`);
-- {
-- 	"name" : "`c`",
-- 	"action_type" : "change_fk_after_`b`",
-- 	"referenced_tables" : [
-- 		"`b`"
-- 	]
-- }
CALL `workaround_jJIxK` ('c', 'b_id', 'ALTER TABLE `c` ADD INDEX `b_id` (`b_id`);', 'create');
ALTER TABLE `c` ADD CONSTRAINT `c_ibfk_1` FOREIGN KEY (`b_id`) REFERENCES `b` (`id`);
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "drop_temporary_index"
-- }
CALL `workaround_jJIxK` ('b', 'eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop', 'ALTER TABLE `b` DROP INDEX eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop;', 'drop');
-- {
-- 	"name" : "`c`",
-- 	"action_type" : "drop_temporary_index"
-- }
CALL `workaround_jJIxK` ('c', 'eqfk_temp_a952f29b884f11d57656504a7f2e4f89_drop', 'ALTER TABLE `c` DROP INDEX eqfk_temp_a952f29b884f11d57656504a7f2e4f89_drop;', 'drop');
-- {
-- 	"name" : "workaround_jJIxK_drop",
-- 	"action_type" : "drop_workaround"
-- }
DROP PROCEDURE `workaround_jJIxK`;
