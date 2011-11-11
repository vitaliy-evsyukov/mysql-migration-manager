CREATE  TABLE IF NOT EXISTS `crm_internal_num` (
`internal_num` INT(11) UNSIGNED NOT NULL COMMENT 'Добавочный номер',
`is_group` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак: Групповой номер',
`is_backcall` TINYINT(1) NOT NULL DEFAULT '1',
`title` VARCHAR(255) NOT NULL COMMENT 'Название',
`comments` VARCHAR(1024)   COMMENT 'Описание',
`transfer_gorod` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
`file_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
PRIMARY KEY (`internal_num`),
INDEX `is_backcall` (`is_backcall` ASC) )
ENGINE = InnoDB;