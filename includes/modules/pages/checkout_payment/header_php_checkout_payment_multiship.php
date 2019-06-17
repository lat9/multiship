<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_PAYMENT_MULTISHIP');

// -----
// No multiship session variable? Nothing to do.
//
if (!isset($_SESSION['multiship'])) {
    return;
}

// -----
// Set the flags for the template's use.
//
$multiple_shipping_active = $_SESSION['multiship']->isSelected();
if ($multiple_shipping_active) {
    $multiship_totals = $_SESSION['multiship']->getTotals();
    $multiship_grand_total = 0;
    if (is_array($multiship_totals) && isset($multiship_totals['ot_total'])) {
        $multiship_grand_total = $multiship_totals['ot_total'];
    }
    if (MODULE_MULTISHIP_PAYMENT_METHODS != '') {
        $multiship_unsupported_payments = explode(',', str_replace(' ', '', MODULE_MULTISHIP_PAYMENT_METHODS));
        foreach ($multiship_unsupported_payments as $multiship_payment2remove) {
            if (isset(${$multiship_payment2remove}) && is_object(${$multiship_payment2remove})) {
                ${$multiship_payment2remove}->enabled = false;
            }
        }
    }
}

$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_PAYMENT_MULTISHIP');
