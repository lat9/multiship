<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

class multiship_observer extends base {

  function multiship_observer() {
    $this->attach($this, array( /* order.php class */ 
                                'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER', 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM', 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM', 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND2', 'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS', 'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN',
                                /* page header_php.php's */ 
                                'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION', 'NOTIFY_HEADER_END_CHECKOUT_PROCESS'));
  }
  
  function update(&$class, $eventID, $p1a, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9) {
    switch ($eventID) {
      // -----
      // Issued by /includes/classes/order.php just after writing the overall order's information to the orders table.
      //
      case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER': {
        $_SESSION['multiship']->_createOrderHeader($p1a);
        break;
      }
      case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM': {
        $_SESSION['multiship']->_createOrderFixupTotal($p1a);
        break;
      }
      case 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM': {
        $_SESSION['multiship']->_createOrderAddProducts($p1a);
        break;
      }
      case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM': {
        $_SESSION['multiship']->_createOrderAddAttributes($p1a);
        break;
      }
      case 'NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION': {
        $_SESSION['multiship']->_prepare();
        break;
      }
      case 'NOTIFY_HEADER_END_CHECKOUT_PROCESS': {
        $_SESSION['multiship']->_cleanup();
        break;
      }
      case 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND2': {
        $_SESSION['multiship']->_fixupOrderEmail($class, $p1a, $p2, $p3);
        break;
      }
      case 'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS': {
        $_SESSION['multiship']->_saveEmailHeader($p2, $p3);
        break;
      }
      case 'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN': {
        $_SESSION['multiship']->_insertAttributesText($class);
        break;
      }
      default: {
        break;
      }
    }
    
  }
}