<pre>
<?php

require_once('php-sql-parser.php');
$sql = array(
'report_max_product_reliz_date' => <<<'EOT'
select `pr`.`model` AS `model`,max(`pr`.`date`) AS `max_date` from `products_reliz` `pr` group by `pr`.`model`;
EOT
,
   'report_different_firm_id' => <<<'EOT'
select `s`.`model` AS `model`,`s`.`max_date` AS `max_date`,`pr`.`firm_id` AS `firm_id`,`p`.`reliz_firm_id` AS `reliz_firm_id` from ((`report_max_product_reliz_date` `s` left join `products_reliz` `pr` on(((`pr`.`model` = `s`.`model`) and (`pr`.`date` = `s`.`max_date`)))) left join `products` `p` on((`pr`.`model` = `p`.`products_model`))) where ((`pr`.`firm_id` <> `p`.`reliz_firm_id`) and (`pr`.`firm_id` > 0)) group by `s`.`model` order by `s`.`model`;
EOT
,
   'sotm_underground' => <<<'EOT'
select `gmaps_mstations`.`id` AS `underground_id`,`gmaps_mstations`.`title` AS `title`,`gmaps_mstations`.`category` AS `city_id`,`gmaps_mstations`.`last_modified` AS `last_modified` from `gmaps_mstations`;
EOT
,
   'log_callcenter_dnd' => <<<'EOT'
select `default_sm_log`.`log_callcenter_dnd`.`id` AS `id`,`default_sm_log`.`log_callcenter_dnd`.`queue_id` AS `queue_id`,`default_sm_log`.`log_callcenter_dnd`.`man_id` AS `man_id`,`default_sm_log`.`log_callcenter_dnd`.`reason` AS `reason`,`default_sm_log`.`log_callcenter_dnd`.`date_start` AS `date_start`,`default_sm_log`.`log_callcenter_dnd`.`date_finish` AS `date_finish` from `default_sm_log`.`log_callcenter_dnd`;
EOT
,
   'report_different_product_status' => <<<'EOT'
select `pxid`.`product_id` AS `product_id`,`sp`.`product_status` AS `product_status`,`get_product_status_by_id1c`(`pxid`.`id1c`) AS `new_status` from (`sotm_products` `sp` join `sotm_product_x_id1c` `pxid`) where ((`sp`.`product_status` <> 'off') and (`sp`.`product_status` <> 'deleted') and (`sp`.`products_id` = `pxid`.`product_id`)) having (`sp`.`product_status` <> `new_status`);
EOT
,
   'callback_num_status' => <<<'EOT'
select `m`.`man_id` AS `man_id`,`mm`.`dobav` AS `dobav`,(case when (`ct`.`id` is not null) then 'busy' when (isnull(`ct`.`id`) and isnull(`mm`.`man_id`)) then 'online' when (isnull(`ct`.`id`) and (`mm`.`status` = 'online')) then 'online' else 'offline' end) AS `num_status` from ((`crm_asterisk_managers` `m` join `crm_asterisk_queues` `q`) join (`managers` `mm` left join `cdr_talks` `ct` on((`mm`.`dobav` = `ct`.`dobav`)))) where ((`q`.`alias` = 'callback') and (`m`.`queue_id` = `q`.`id`) and (`m`.`man_id` = `mm`.`man_id`) and (`mm`.`dobav` is not null));
EOT
,
   'report_profit_calcs' => <<<'EOT'
select `od`.`order_id` AS `order_id`,`o`.`platform` AS `platform`,`od`.`profit` AS `profit`,ceiling(((coalesce(`od`.`total_sum`,0) - coalesce(`od`.`zak_sum`,0)) - coalesce(`od`.`delivery_cost`,0))) AS `calc_profit_on_dop`,ceiling(((coalesce(`ot`.`value`,0) - coalesce(`od`.`zak_sum`,0)) - coalesce(`od`.`delivery_cost`,0))) AS `calc_profit_on_totals`,`od`.`total_sum` AS `total_sum`,`od`.`zak_sum` AS `zak_sum`,`od`.`delivery_cost` AS `delivery_cost`,`ot`.`value` AS `ot_total` from ((`orders_dop` `od` join `orders` `o`) left join `orders_total` `ot` on(((`ot`.`orders_id` = `od`.`order_id`) and (`ot`.`class` = 'ot_total')))) where (`od`.`order_id` = `o`.`orders_id`) order by `od`.`order_id` desc;
EOT
);

print_r($sql);

foreach ($sql as $query) {
$query = str_replace('`', '', $query);
echo "$query\n";

$start = microtime(true);
$parser = new PHPSQLParser($query);

print_r($parser->parsed);
echo "parse time simplest query:" .( microtime(true) - $start) . "\n";

}


exit;
