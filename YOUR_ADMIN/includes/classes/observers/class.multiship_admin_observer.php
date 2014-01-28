<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
  die('Illegal Access');
}

class multiship_observer extends base {

  function multiship_observer() {
    $this->attach($this, array('NOTIFY_ADMIN_ORDER_CLASS_END_QUERY', 'NOTIFY_FUNCTION_GENERAL_REMOVE_ORDER'));

  }
  
  function update(&$class, $eventID, $p1a) {
    global $db;
    $this->eventID = $eventID;

    switch ($eventID) {
      case 'NOTIFY_ADMIN_ORDER_CLASS_END_QUERY': {
        if (!is_array($p1a) || !array_key_exists('orders_id', $p1a)) {
          $this->_logError('Missing orders_id in notification params array (' . print_r($p1a, true) . ')');

        }
        $orders_id = (int)$p1a['orders_id'];
        
        $multiship_orders = $db->Execute("SELECT orders_multiship_id, delivery_name as name, delivery_company as company, delivery_street_address as street_address, delivery_suburb as suburb, delivery_city as city, delivery_postcode as postcode, delivery_state as state, delivery_country as country, delivery_address_format_id as address_format_id, orders_status, content_type FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = $orders_id");
        $class->info['is_multiship_order'] = !$multiship_orders->EOF;
        $class->multiship_info = array();
        while (!$multiship_orders->EOF) {
          $multiship_id = $multiship_orders->fields['orders_multiship_id'];
          unset ($multiship_orders->fields['orders_multiship_id']);
          $class->multiship_info[$multiship_id]['info'] = $multiship_orders->fields;
          
          $multiship_totals = $db->Execute("SELECT title, text, value, class FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " WHERE orders_multiship_id = $multiship_id ORDER BY sort_order");
          $class->multiship_info[$multiship_id]['totals'] = array();
          while (!$multiship_totals->EOF) {
             $class->multiship_info[$multiship_id]['totals'][] = $multiship_totals->fields;
            
            $multiship_totals->MoveNext();
          }
          unset ($multiship_totals);
          
          $multiship_orders->MoveNext();
        }
        unset ($multiship_orders);
        
        $orders_products = $db->Execute("SELECT orders_multiship_id FROM " . TABLE_ORDERS_PRODUCTS . " WHERE orders_id = $orders_id ORDER BY orders_products_id");
        if ($orders_products->RecordCount() != sizeof ($class->products)) {
          $this->_logError('orders_products count mismatch, current: ' . $orders_products->RecordCount() . ', in order: ' . sizeof($class->products));
          
        }
        $i = 0;
        while (!$orders_products->EOF) {
          $class->products[$i]['orders_multiship_id'] = $orders_products->fields['orders_multiship_id'];
          $i++;
          $orders_products->MoveNext();
        }
        unset ($orders_products);
        
        break;
      }
      
      case 'NOTIFY_FUNCTION_GENERAL_REMOVE_ORDER': {
        if (!is_array($p1a) || !array_key_exists('orders_id', $p1a)) {
          $this->_logError('Missing orders_id in notification params array (' . print_r($p1a, true) . ')');

        }
        $orders_id = (int)$p1a['orders_id'];
        $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = $orders_id");
        $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " WHERE orders_id = $orders_id");
        break;
      }
      
      default: {
        break;
      }
    }
    
    $this->eventID = '';
    
  }
  
  function is_multiship_order($order_id) {
    global $db;
    $check = $db->Execute("SELECT orders_multiship_id FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = " . (int)$order_id . " LIMIT 1");
    return !$check->EOF;
  }
  
  function _logError ($message) {
    $event_info = ($this->eventID != '') ? '' : (' (' . $this->eventID . ')');
    error_log(__FILE__ . $event_info . ': ' . $message);
    die();
  }
  
}