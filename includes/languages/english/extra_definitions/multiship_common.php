<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
define('SHIP_TO_MULTIPLE_ADDRESSES', 'Ship to multiple addresses');
define('SHIPPING_TO_MULTIPLE_ADDRESSES', 'Shipping to multiple addresses, see below.');

define('SHIP_TO_MULTIPLE_ADDRESSES_LINK', 'Shipping to multiple addresses?  Click %s to identify which products ship to what address.');
define('SHIP_TO_MULTIPLE_ADDRESSES_ACTIVE', 'Currently shipping to %1$u addresses.  Click %2$s to make any changes.');
    define('SHIP_TO_MULTIPLE_HERE', 'here');    //-Used as the anchor text for the links (%s), inserted above.

define('TEXT_SHIPPING_TO', 'Shipping to: ');

define('TEXT_GRAND_TOTAL', 'Grand Total:');

define('MULTISHIP_MULTIPLE', 'Multiple');
define('MULTISHIP_MULTIPLE_ADDRESSES', 'Multiple Addresses');

define('ICON_MULTISHIP_NOSHIP', 'multiship_noship.png');
define('ICON_MULTISHIP_NOSHIP_ALT', 'Identifies that a selected ship-to address is not compatible with the currently-selected shipping method.');

if (!defined('WARNING_PRODUCT_QUANTITY_ADJUSTED')) {
    define('WARNING_PRODUCT_QUANTITY_ADJUSTED', 'Quantity has been adjusted to what is in stock. ');
}

define('MULTISHIP_PRODUCT_ADD_SHIP_PRIMARY', 'The newly-added product <b>(%u x %s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.');
define('MULTISHIP_PRODUCT_INCREASE_SHIP_PRIMARY', 'Additional quantities of the product <b>(%s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.');
define('MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY', 'All of the product <b>(%s)</b> will ship to your <b>Primary</b> address.  You will have the opportunity to change this during the checkout process.');

define('ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING', 'That address selection is not supported by the currently-selected shipping method.');
define('MULTISHIP_CHOOSE_DIFFERENT_SHIPPING', 'One or more of your additional shipping addresses cannot be used with the currently-selected shipping method. Either change your shipping method or click the link below to make changes to your additional shipping addresses.');
define('MULTISHIP_ICON_NO_SHIP', '<i class="fa fa-exclamation-circle fa-lg"></i>');
