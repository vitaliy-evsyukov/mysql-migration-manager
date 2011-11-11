CREATE  TABLE IF NOT EXISTS `sotm_category_x_category_tying` (
`category_tying_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор набора сопустующих категорий',
`master_category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор основной категории',
`slave_category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор подчиненной категории',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`category_tying_id`, `master_category_id`, `slave_category_id`),
INDEX `fk_sotm_category_x_category_tying__master_category_id` (`master_category_id` ASC),
INDEX `fk_sotm_category_x_category_tying__slave_category_id` (`slave_category_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
CONSTRAINT `fk_sotm_category_x_category_tying__category_tying`
FOREIGN KEY (`category_tying_id` )
REFERENCES `sotm_category_tying` (`category_tying_id` ),
CONSTRAINT `fk_sotm_category_x_category_tying__master_category_id`
FOREIGN KEY (`master_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_x_category_tying__slave_category_id`
FOREIGN KEY (`slave_category_id` )
REFERENCES `sotm_category` (`category_id` ))
ENGINE = InnoDB;