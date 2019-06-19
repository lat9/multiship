<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$tpl_page_body = (!empty($is_multiship_order)) ? '/tpl_account_history_info_multiship.php' : '/tpl_account_history_info_default.php';
require $template->get_template_dir($tpl_page_body, DIR_WS_TEMPLATE, $current_page_base, 'templates') . $tpl_page_body;
