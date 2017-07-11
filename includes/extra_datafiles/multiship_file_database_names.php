<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('TABLE_ORDERS_MULTISHIP', DB_PREFIX . 'orders_multiship');
define('TABLE_ORDERS_MULTISHIP_TOTAL', DB_PREFIX . 'orders_multiship_total');

define('FILENAME_CHECKOUT_MULTISHIP', 'checkout_multiship');