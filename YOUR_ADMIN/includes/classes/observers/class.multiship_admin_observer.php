<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
  die('Illegal Access');
}

class multiship_observer extends base 
{
    public function __construct () 
    {
        $this->attach(
            $this, 
            array(
                //-Issued by /includes/classes/order.php
                'NOTIFY_ORDER_AFTER_QUERY',
                
                //-Issued by /admin/includes/classes/order.php (pre-zc156) and on admin for zc156 (albeit deprecated)
                'ORDER_QUERY_ADMIN_COMPLETE',
                
                //-Issued by /admin/orders.php
                'NOTIFY_ADMIN_ORDERS_MENU_LEGEND',
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',
                'NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START',       //-Added by multiship!
                'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN',
                'NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS',      //-Added by multiship!
                
                //-Issued by /admin/includes/functions/general.php::zen_remove_order
                'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER'
            )
        );
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5) 
    {
        global $db;
        $this->eventID = $eventID;
        $order_query_admin = false;

        switch ($eventID) {
            // -----
            // Enabling zc155/zc156 interoperability, the zc155 admin order-class issues **only** this
            // event while the zc156 version brings in the storefront version of the class which issues
            // this event (deprecated) _after_ issuing the event that follows.
            //
            // If the NOTIFY_ORDER_AFTER_QUERY event has been processed, there's nothing to do here.  If
            // it hasn't (i.e. zc155), then this clause sets a flag to let the zc156 event "know" that
            // the orders_id has been gathered.
            //
            case 'ORDER_QUERY_ADMIN_COMPLETE':
                if (isset($this->processed_order)) {
                    break;
                }
                $order_query_admin = true;
                if (empty($p1['orders_id'])) {
                    $this->logError('Invalid notification parameters: ' . json_encode($p1));
                }
                $orders_id = (int)$p1['orders_id'];
            case 'NOTIFY_ORDER_AFTER_QUERY':            //-Fall through from above processing
                if (!$order_query_admin) {
                    if (empty($p2)) {
                        $this->logError('Invalid notification parameters: ' . json_encode($p2));
                    }
                    $orders_id = (int)$p2;
                }
                $this->processed_order = true;
        
                $multiship_orders = $db->Execute(
                    "SELECT orders_multiship_id, delivery_name as name, delivery_company as company, delivery_street_address as street_address, delivery_suburb as suburb, 
                            delivery_city as city, delivery_postcode as postcode, delivery_state as state, delivery_country as country, delivery_address_format_id as address_format_id, 
                            orders_status, content_type 
                       FROM " . TABLE_ORDERS_MULTISHIP . " 
                      WHERE orders_id = $orders_id"
                );
                $class->info['is_multiship_order'] = !$multiship_orders->EOF;
                $class->multiship_info = array();
                while (!$multiship_orders->EOF) {
                    $multiship_id = $multiship_orders->fields['orders_multiship_id'];
                    unset($multiship_orders->fields['orders_multiship_id']);
                    $class->multiship_info[$multiship_id]['info'] = $multiship_orders->fields;
          
                    $multiship_totals = $db->Execute(
                        "SELECT title, text, value, class 
                           FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " 
                          WHERE orders_multiship_id = $multiship_id 
                       ORDER BY sort_order"
                    );
                    $class->multiship_info[$multiship_id]['totals'] = array();
                    while (!$multiship_totals->EOF) {
                        $class->multiship_info[$multiship_id]['totals'][] = $multiship_totals->fields;
                        $multiship_totals->MoveNext();
                    }
                    unset ($multiship_totals);
          
                    $multiship_orders->MoveNext();
                }
                unset ($multiship_orders);
        
                $orders_products = $db->Execute(
                    "SELECT orders_multiship_id 
                       FROM " . TABLE_ORDERS_PRODUCTS . " 
                      WHERE orders_id = $orders_id 
                   ORDER BY orders_products_id"
                );
                if ($orders_products->RecordCount() != count($class->products)) {
                    $this->logError('orders_products count mismatch, current: ' . $orders_products->RecordCount() . ', in order: ' . count($class->products));
                }
                $i = 0;
                while (!$orders_products->EOF) {
                    $class->products[$i]['orders_multiship_id'] = $orders_products->fields['orders_multiship_id'];
                    $i++;
                    $orders_products->MoveNext();
                }
                unset($orders_products);
                break;
      
            case 'NOTIFIER_ADMIN_ZEN_REMOVE_ORDER':
                if (!isset($p2) || ((int)$p2) <= 0) {
                    $this->logError("Missing or invalid orders_id in notification params array ($p2).");
                }
                $orders_id = (int)$p2;
                $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP . " WHERE orders_id = $orders_id");
                $db->Execute("DELETE FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . " WHERE orders_id = $orders_id");
                break;

            case 'NOTIFY_ADMIN_ORDERS_MENU_LEGEND':
                $p2 .= ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_blue.gif', TEXT_MULTISHIP_ORDER, 10, 10) . ' ' . TEXT_MULTISHIP_ORDER;
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE':
                if ($this->isMultiShipOrder($p2['orders_id'])) {
                    $p3 .= zen_image(DIR_WS_IMAGES . 'icon_status_blue.gif', TEXT_MULTISHIP_ORDER, 10, 10) . '&nbsp;';
                }
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_UPDATE_ORDER_START':
                $this->updateMultiShipOrders((int)$p1);
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN':
                if ($this->isMultiShipOrder($p1)) {
                }
                break;
                
            case 'NOTIFY_ADMIN_ORDERS_EXTRA_STATUS_INPUTS':
                $this->addMultiShipStatusFields($p1, $p2);
                break;
      
            default:
                break;
        }
        $this->eventID = '';
    }
  
    public function isMultiShipOrder($order_id) 
    {
        $check = $GLOBALS['db']->Execute(
            "SELECT orders_multiship_id 
               FROM " . TABLE_ORDERS_MULTISHIP . " 
              WHERE orders_id = " . (int)$order_id . " 
              LIMIT 1"
        );
        return !$check->EOF;
    }
    
    protected function updateMultiShipOrders($oID)
    {
        if (isset($_POST['multiship_status']) && is_array($_POST['multiship_status']) && is_array($_POST['multiship_current_status'])) {
            foreach ($_POST['multiship_status'] as $multiship_id => $multiship_status) {
                $multiship_id = (int)$multiship_id;
                $multiship_status = (int)$multiship_status;
                $current_status = (isset($_POST['multiship_current_status'][$multiship_id])) ? (int)$_POST['multiship_current_status'][$multiship_id] : false;
                if ($current_status !== false && $multiship_status != $current_status) {
                    if ($GLOBALS['comments'] != '') {
                        $GLOBALS['comments'] .= "\n";
                    }
                    $GLOBALS['comments'] .= sprintf(MULTISHIP_SUBORDER_STATUS_CHANGED, zen_db_prepare_input($_POST['multiship_shipping_name'][$multiship_id]), $GLOBALS['orders_status_array'][$current_status], $GLOBALS['orders_status_array'][$multiship_status]);
              
                    $GLOBALS['db']->Execute(
                        "UPDATE " . TABLE_ORDERS_MULTISHIP . "
                            SET orders_status = $multiship_status,
                                last_modified = now()
                          WHERE orders_multiship_id = $multiship_id
                            AND orders_id = $oID
                          LIMIT 1"
                    );
                }
            }
        }
    }
    
    protected function addMultiShipStatusFields($order, &$extra_status_fields)
    {
        if (!empty($order->info['is_multiship_order'])) {
            foreach ($order->multiship_info as $multiship_id => $multiship_info) {
                $hidden_fields = zen_draw_hidden_field("multiship_current_status[$multiship_id]", $multiship_info['info']['orders_status']);
                $hidden_fields .= zen_draw_hidden_field("multiship_shipping_name[$multiship_id]", $multiship_info['info']['name']);
                $extra_status_fields[] = array(
                    'label' => array(
                        'text' => sprintf(MULTISHIP_SUBORDER_STATUS, '<em>' . $multiship_info['info']['name'] . '</em>'),
                        'parms' =>  'style="font-weight: 700;"'
                    ),
                    'input' => zen_draw_pull_down_menu("multiship_status[$multiship_id]", $GLOBALS['orders_statuses'], $multiship_info['info']['orders_status'], 'class="form-control"') . $hidden_fields
                );
            }
        }
    }
  
    protected function logError($message) 
    {
        $event_info = ($this->eventID != '') ? '' : (' (' . $this->eventID . ')');
        trigger_error($event_info . ': ' . $message, E_USER_ERROR);
        exit();
    }
}