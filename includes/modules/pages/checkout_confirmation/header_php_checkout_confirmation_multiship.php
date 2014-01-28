<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION_MULTISHIP');

// -----
// Set the flags for the template's use.
//
$offer_multiple_shipping = $_SESSION['multiship']->can_offer();
$multiple_shipping_active = $_SESSION['multiship']->is_selected();
if ($multiple_shipping_active) {
  $offer_multiple_shipping = false;
  $multiship_info = $_SESSION['multiship']->get_details();
  $multiship_totals = $_SESSION['multiship']->get_totals();
  $multiship_grand_total = 0;
  if (is_array($multiship_totals) && isset($multiship_totals['ot_total'])) {
    $multiship_grand_total = $multiship_totals['ot_total'];
  }
  $editShippingButtonLink = zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL');
}

$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION_MULTISHIP');