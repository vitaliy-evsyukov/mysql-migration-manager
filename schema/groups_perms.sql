CREATE  TABLE IF NOT EXISTS `groups_perms` (
`group_id` INT(11) UNSIGNED NOT NULL ,
`module` VARCHAR(50) NOT NULL ,
`perm` VARCHAR(50) NOT NULL ,
`value` VARCHAR(255) NOT NULL ,
PRIMARY KEY (`group_id`, `module`, `perm`) ,
CONSTRAINT `FK_groups_perms_managers_groups_group_id`
FOREIGN KEY (`group_id` )
REFERENCES `managers_groups` (`group_id` ))
ENGINE = InnoDB;