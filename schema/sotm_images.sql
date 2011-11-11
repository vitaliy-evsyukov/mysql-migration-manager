CREATE  TABLE IF NOT EXISTS `sotm_images` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`server_path` VARCHAR(255)  ,
`file_type` ENUM('gif','jpeg','png') NOT NULL,
`alt_text` VARCHAR(255)  ,
`is_custom` TINYINT(1) NOT NULL DEFAULT '0',
`status` SMALLINT(11) NOT NULL DEFAULT '1',
`is_visible` INT(11) NOT NULL DEFAULT '1',
`edited` TINYINT(1),
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
`last_modified` DATETIME  ,
`sotm_id` INT(11),
`is_white_bg` TINYINT(3) UNSIGNED   COMMENT 'Белый задник',
PRIMARY KEY (`id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `status` (`status` ASC),
INDEX `is_deleted` (`is_deleted` ASC),
INDEX `indx_last_modified` (`last_modified` ASC) )
ENGINE = InnoDB;