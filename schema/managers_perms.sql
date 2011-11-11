CREATE  TABLE IF NOT EXISTS `managers_perms` (
`man_id` INT(11) UNSIGNED NOT NULL ,
`module` VARCHAR(50) NOT NULL ,
`perm` VARCHAR(50) NOT NULL ,
`value` VARCHAR(255) NOT NULL ,
PRIMARY KEY (`man_id`, `module`, `perm`),
CONSTRAINT `fk_managers_perms__man_id`
FOREIGN KEY (`man_id`)
REFERENCES `managers` (`man_id`))
ENGINE = InnoDB;