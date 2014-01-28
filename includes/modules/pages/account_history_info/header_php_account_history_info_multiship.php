<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
//
$order_id = (int)$_GET['order_id'];
$products_sql = "SELECT products_prid, orders_multiship_id FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = :ordersID ORDER BY orders_products_id";
$products_sql = $db->bindVars($products_sql, ':ordersID', $order_id, 'integer');
$products_info = $db->Execute($products_sql);

$product_count = 0;
while (!$products_info->EOF) {
  $order->products[$product_count]['products_prid'] = $products_info->fields['products_prid'];
  $order->products[$product_count]['orders_multiship_id'] = $products_info->fields['orders_multiship_id'];
  $product_count++;
  $products_info->MoveNext();
}
unset($products_info, $products_sql);

$multiship_sql = "SELECT orders_multiship_id, orders_id, delivery_name AS name, delivery_company AS company, delivery_street_address AS street_address, delivery_suburb as suburb, delivery_city as city, delivery_postcode as postcode, delivery_country as country, delivery_address_format_id as format_id, orders_status, content_type FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = :ordersID";
$multiship_sql = $db->bindVars($multiship_sql, ':ordersID', $order_id, 'integer');
$multiship = $db->Execute($multiship_sql);
  
$is_multiship_order = !$multiship->EOF;
$multiship_info = array();
while (!$multiship->EOF) {
  $orders_multiship_id = $multiship->fields['orders_multiship_id'];
  $currentInfo = array();
  $currentInfo['delivery'] = $multiship->fields;
  $currentInfo['products'] = $order->products;
  $currentInfo['address'] = zen_address_format($currentInfo['delivery']['format_id'], $currentInfo['delivery'], false, '', ', ');
  
  $totals = $db->Execute("SELECT * FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " WHERE orders_multiship_id = $orders_multiship_id ORDER BY sort_order");
  $currentInfo['totals'] = array();
  while (!$totals->EOF) {
    $currentInfo['totals'][] = $totals->fields;
    $totals->MoveNext();
  }
  unset ($totals);
  
  for ($i = 0; $i < $product_count; $i++) {
    if ($currentInfo['products'][$i]['orders_multiship_id'] != $orders_multiship_id) {
      unset($currentInfo['products'][$i]);
      
    }
  }
  
  $multiship_info[$orders_multiship_id] = $currentInfo;
  $multiship->MoveNext();
}

unset($currentInfo, $multiship, $multiship_sql);

$multiship_grand_total = $order->info['total'];