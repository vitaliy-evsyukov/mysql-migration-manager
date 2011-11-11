CREATE  TABLE IF NOT EXISTS `sotm_block_type` (
`block_type` VARCHAR(32) NOT NULL,
`title` VARCHAR(255)  ,
`use_additional_info` TINYINT(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`block_type`) )
ENGINE = InnoDB;