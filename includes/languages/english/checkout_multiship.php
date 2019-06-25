<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

define('NAVBAR_TITLE_1', 'Checkout');
define('NAVBAR_TITLE_2', 'Choose Multiple Shipping Addresses');

define('HEADING_TITLE', 'Choose Shipping Address for Each Item');
define('TEXT_RETURN_TO_SHIPPING', 'All finished? Click %s to resume the checkout process.');

define('TEXT_CURRENT_SHIPPING_METHOD', 'Your current shipping method: ');
define('TEXT_SHIPPING_METHOD_CHANGE', 'Click %s to change your shipping method.');

define('HEADING_ITEM', 'Item');
define('HEADING_PRICE', 'Price');
define('HEADING_QTY', 'Qty.');
define('HEADING_SENDTO', 'Send To:');

define('TEXT_OPTION_DIVIDER', ': ');
define('ONETIME_CHARGE_INDICATOR', '*');
define('TEXT_ONETIME_CHARGES_APPLY', 'One-time charges apply.');
define('TEXT_NEED_ANOTHER_ADDRESS', 'Need another address? ');
define('TEXT_ENTER_NEW_ADDRESS', 'Enter a new shipping address.');
define('TEXT_DELETE_ITEM', 'If you changed any quantities, click ');

define('TEXT_MULTISHIP_INSTRUCTIONS', 'You can delete an item by changing its quantity to 0 and clicking the "Update" button. To send a single item to multiple people, change the item\'s quantity to equal the number of people you\'re sending it to and then click the "Update" button.<br /><br /><strong>Notes:</strong><ul><li>If an icon appears next to a shipping address, that address is not supported by the currently-selected shipping method.</li><li>Any products that you have in your cart that don\'t require shipping (like gift certificates or downloadable products) are not displayed here.</li></ul>'); 

define('TEXT_QUANTITIES_CHANGED', 'One or more product quantities have been changed, but not yet updated.  If you leave this page, those changes will not be saved.  To save those quantity changes, stay on the page and click the update button.');

define('ERROR_ADDRESS_INVALID_FOR_SHIPPING_METHOD', 'One or more of the shipping addresses you previously chose cannot be used for the currently-selected shipping method; see the selections below marked with %1$s.<br /><br />Either modify the marked shipping addresses or click the link below to change the shipping method for your order.');
