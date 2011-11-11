CREATE  TABLE IF NOT EXISTS `sotm_seo_status` (
`seo_status_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор SEO статуса',
`title` VARCHAR(255) NOT NULL COMMENT 'Название статуса',
`rating_auto` INT(11) NOT NULL DEFAULT '0' COMMENT 'Автоматическая оценка',
PRIMARY KEY (`seo_status_id`) )
ENGINE = InnoDB;