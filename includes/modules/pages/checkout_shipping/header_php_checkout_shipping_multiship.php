<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_SHIPPING_MULTISHIP');

// -----
// No multiship session variable? Nothing to do.
//
if (!isset($_SESSION['multiship'])) {
    return;
}

// -----
// Initialize the Multiple Ship-to address 'environment', checking to see
// if the order 'qualifies' to be offered multiple ship-to addresses.
//
$_SESSION['multiship']->checkoutInitialize();

// -----
// Set the flags for the template's use.
//
$offer_multiple_shipping = $_SESSION['multiship']->canOffer();
$multiple_shipping_active = $_SESSION['multiship']->isSelected();
if ($multiple_shipping_active) {
    $offer_multiple_shipping = false;
    $multiple_shipping_address_count = $_SESSION['multiship']->numShippingAddresses();
    $multiship_shipping = explode('_', $_SESSION['multiship']->getShippingId());
    if (isset($quotes) && is_array($quotes)) {
        for ($i = 0, $n = count($quotes); $i < $n ; $i++) {
            if ($quotes[$i]['id'] == $multiship_shipping[0]) {
                for ($j = 0, $m = count($quotes[$i]['methods']); $j < $m; $j++) {
                    if ($quotes[$i]['methods'][$j]['id'] == $multiship_shipping[1]) {
                        $quotes[$i]['methods'][$j]['cost'] = $_SESSION['multiship']->getMultiShipShippingCost();
                    }
                }
            }
        }
    }
}

$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_SHIPPING_MULTISHIP');
