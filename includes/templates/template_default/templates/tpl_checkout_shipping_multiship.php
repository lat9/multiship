<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// -----
// If we're to OFFER multiple ship-to addresses, display the customer's default address,
// as usual, with a link to the page where the multiple addresses can be specified.
//
$anchor_text = '<a href="' . zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL') . '">' . SHIP_TO_MULTIPLE_HERE . '</a>';
if ($offer_multiple_shipping) {
?>
<div id="checkoutShipto" class="floatingBox back">
<?php 
if ($displayAddressEdit) { 
?>
    <div class="buttonRow forward"><?php echo '<a href="' . $editShippingButtonLink . '">' . zen_image_button(BUTTON_IMAGE_CHANGE_ADDRESS, BUTTON_CHANGE_ADDRESS_ALT) . '</a>'; ?></div>
<?php 
} 
?>
    <address class=""><?php echo zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], true, ' ', '<br />'); ?></address>
</div>
<div class="floatingBox important forward"><?php echo TEXT_CHOOSE_SHIPPING_DESTINATION; ?></div>

<div class="clearBoth"><?php echo sprintf(SHIP_TO_MULTIPLE_ADDRESSES_LINK, $anchor_text); ?></div>
<?php
} elseif ($multiple_shipping_active) {
?>
<h3><?php echo SHIPPING_TO_MULTIPLE_ADDRESSES; ?></h3>
<div><?php echo sprintf(SHIP_TO_MULTIPLE_ADDRESSES_ACTIVE, $multiple_shipping_address_count, $anchor_text); ?></div>
<?php
}
