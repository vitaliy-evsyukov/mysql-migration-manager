CREATE  TABLE IF NOT EXISTS `sotm_blocks` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`type` VARCHAR(32) NOT NULL,
`is_active` TINYINT(1) NOT NULL DEFAULT '0',
`for_pagetype` VARCHAR(32)  ,
`for_pageid` INT(11) UNSIGNED  ,
`name` VARCHAR(255)  ,
`title` VARCHAR(255)  ,
`content` VARCHAR(2048)  ,
`cache_time` INT(11) NULL DEFAULT '0',
`comments` VARCHAR(512)  ,
PRIMARY KEY (`id`),
UNIQUE INDEX `uk_sotm_blocks` (`type` ASC, `is_active` ASC, `for_pagetype` ASC, `name` ASC, `for_pageid` ASC),
INDEX `fk_sotm_blocks__page_type` (`for_pagetype` ASC),
INDEX `fk_sotm_blocks__product_id` (`for_pageid` ASC),
CONSTRAINT `fk_sotm_blocks__block_type`
FOREIGN KEY (`type` )
REFERENCES `sotm_block_type` (`block_type` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_blocks__page_type`
FOREIGN KEY (`for_pagetype` )
REFERENCES `sotm_page_type` (`page_type` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_blocks__product_id`
FOREIGN KEY (`for_pageid` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;