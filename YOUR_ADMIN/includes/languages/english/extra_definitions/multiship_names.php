<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// -----
// Labels for admin-menus.
//
define('BOX_CUSTOMERS_INVOICE_MULTISHIP', 'Invoice - Multiple Ship-to');
define('BOX_CUSTOMERS_PACKINGSLIP_MULTISHIP', 'Packing Slip - Multiple Ship-to');
define('BOX_CONFIG_MULTISHIP', 'Multiple Ship-to Addresses');

// -----
// Constants, used when processing a multi-ship order, e.g. /admin/orders.php.
//
define('MULTISHIP_MULTIPLE_ADDRESSES', 'Shipping to multiple addresses, see below.');
define('TEXT_MULTISHIP_ORDER', 'Order ships to multiple addresses');
define('MULTISHIP_SHIPPED_TO', 'Shipping To: ');
define('MULTISHIP_GRAND_TOTALS', 'Grand Totals');
define('MULTISHIP_OVERALL_STATUS', 'Overall Order Status: ');
define('MULTISHIP_SUBORDER_STATUS', 'Status for sub-order shipping to <strong>%s</strong>: ');

// 1st parameter: sub-order ship name, 2nd parameter: previous order-status, 3rd parameter: current order-status
define('MULTISHIP_SUBORDER_STATUS_CHANGED', '{The status for the sub-order shipping to "%1$s" was changed from %2$s to %3$s.}');

// -----
// Message, used by /admin/init_includes/init_multiship.php if an admin attempts to edit
// an order with multiple ship-to addresses.
//
define('MULTISHIP_ORDER_CANT_EDIT', 'Sorry, but orders with multiple ship-to addresses cannot be edited using <em>Edit Orders</em>.');