<?php

$compare = array(
    'desc' =>
        array(
            'a' =>
                array(
                    0 =>
                        array(
                            'type' => 'add_column',
                            'sql'  => 'ALTER TABLE `a` ADD COLUMN `c` char(10) DEFAULT NULL;',
                        ),
                ),
            'b' =>
                array(
                    0 =>
                        array(
                            'type' => 'change_column',
                            'sql'  => 'ALTER TABLE `b` CHANGE COLUMN `id` `id` int(11) NOT NULL AUTO_INCREMENT; # was smallint(6) NOT NULL AUTO_INCREMENT',
                        ),
                    1 =>
                        array(
                            'type' => 'add_column',
                            'sql'  => 'ALTER TABLE `b` ADD COLUMN `c` char(20) DEFAULT NULL;',
                        ),
                ),
        ),
);