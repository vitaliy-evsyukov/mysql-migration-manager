-- {
-- 	"name" : "workaround_85f8v_create",
-- 	"action_type" : "create_workaround"
-- }
DELIMITER ;;

CREATE PROCEDURE `workaround_85f8v`
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
CALL `workaround_85f8v` ('b', 'eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop', 'ALTER TABLE `b` ADD INDEX eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop (`a_id`);', 'create');
ALTER TABLE `b` DROP FOREIGN KEY `b_ibfk_1`; # was CONSTRAINT `b_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `a` (`id`)
ALTER TABLE `b` CHANGE COLUMN `a_id` `a_id` smallint(6) NOT NULL; # was int(11) NOT NULL
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
-- 	"name" : "`b`",
-- 	"action_type" : "change_fk_after_`a`",
-- 	"referenced_tables" : [
-- 		"`a`"
-- 	]
-- }
CALL `workaround_85f8v` ('b', 'a_id', 'ALTER TABLE `b` ADD INDEX `a_id` (`a_id`);', 'create');
ALTER TABLE `b` ADD CONSTRAINT `b_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `a` (`id`);
-- {
-- 	"name" : "`b`",
-- 	"action_type" : "drop_temporary_index"
-- }
CALL `workaround_85f8v` ('b', 'eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop', 'ALTER TABLE `b` DROP INDEX eqfk_temp_40078f3dfbce1933b03798206753cfc7_drop;', 'drop');
-- {
-- 	"name" : "workaround_85f8v_drop",
-- 	"action_type" : "drop_workaround"
-- }
DROP PROCEDURE `workaround_85f8v`;
