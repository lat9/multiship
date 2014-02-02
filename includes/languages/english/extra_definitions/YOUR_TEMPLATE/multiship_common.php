<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

define('SHIP_TO_MULTIPLE_ADDRESSES', 'Ship to multiple addresses');
define('SHIPPING_TO_MULTIPLE_ADDRESSES', 'Shipping to multiple addresses, see below.');

define('TEXT_SHIPPING_TO', 'Shipping to: ');

define('TEXT_GRAND_TOTAL', 'Grand Total:');

define('MULTISHIP_MULTIPLE', 'Multiple');
define('MULTISHIP_MULTIPLE_ADDRESSES', 'Multiple Addresses');

define('ICON_MULTISHIP_NOSHIP', 'multiship_noship.png');
define('ICON_MULTISHIP_NOSHIP_ALT', 'Identifies that a selected ship-to address is not compatible with the currently-selected shipping method.');

if (!defined('WARNING_PRODUCT_QUANTITY_ADJUSTED')) {
  define('WARNING_PRODUCT_QUANTITY_ADJUSTED', 'Quantity has been adjusted to what is in stock. ');
}