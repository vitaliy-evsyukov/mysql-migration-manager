CREATE  TABLE IF NOT EXISTS `sotm_category_service_info` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`attach_analog_mail` TINYINT(1) NOT NULL,
`feedback_form_id` INT(11) UNSIGNED   COMMENT 'Идентификатор формы с отзывами',
`show_vendor_filter` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"отображать фильр вендора\"',
`default_order` ENUM('price','name','order') NOT NULL DEFAULT 'order' COMMENT 'Сортировка по полю по умолчанию',
`default_sort` ENUM('asc','desc') NOT NULL DEFAULT 'asc' COMMENT 'Порядок сортировки',
PRIMARY KEY (`category_id`),
INDEX `fk_sotm_category_service_info__feedback_form_id` (`feedback_form_id` ASC),
CONSTRAINT `fk_sotm_category_service_info_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_service_info__feedback_form_id`
FOREIGN KEY (`feedback_form_id` )
REFERENCES `crm_form` (`form_id` )
ON DELETE SET NULL)
ENGINE = InnoDB;