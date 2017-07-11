<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
//
if (count($accountHistory) > 0) {
    foreach ($accountHistory as &$currentOrder) {
        $check = $db->Execute(
            "SELECT o.order_total FROM " . TABLE_ORDERS_MULTISHIP . " om
                INNER JOIN " . TABLE_ORDERS . " o
                    ON o.orders_id = om.orders_id
              WHERE om.orders_id = " . $currentOrder['orders_id'] . "
              LIMIT 1"
        );
        if (!$check->EOF) {
            $currentOrder['order_name'] = MULTISHIP_MULTIPLE_ADDRESSES;
            $currentOrder['order_total'] = $check->fields['order_total'];
        }
    }
}