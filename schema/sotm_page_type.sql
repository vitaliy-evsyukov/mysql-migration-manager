CREATE  TABLE IF NOT EXISTS `sotm_page_type` (
`page_type` VARCHAR(32) NOT NULL,
`title` VARCHAR(255)  ,
`is_ajax` SMALLINT(6) NULL DEFAULT '0',
`cache_time` INT(11) NOT NULL DEFAULT '3600',
PRIMARY KEY (`page_type`) )
ENGINE = InnoDB;