<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

class multiship_observer extends base 
{
    public function __construct() 
    {
        if (!empty($_SESSION['multiship']) && $_SESSION['multiship']->isEnabled()) {
            $this->attach(
                $this, array(
                    /* order.php class */ 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', 
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM', 
                    'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND', 
                    'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS', 
                    'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN',
                    
                    /* shopping_cart.php class */
                    'NOTIFIER_CART_REMOVE_START', 
                    'NOTIFIER_CART_UPDATE_QUANTITY_START', 
                    'NOTIFIER_CART_ADD_CART_START',
                    
                    /* page header_php.php's */ 
                    'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION', 
                    'NOTIFY_HEADER_END_CHECKOUT_PROCESS',
                    
                    /* /includes/modules[/YOUR_TEMPLATE]/checkout_process.php */
                    'NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS',
                )
            );
        }
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9) 
    {
        switch ($eventID) {
            // -----
            // Issued by /includes/classes/order.php just after writing the overall order's information to the orders table.
            //
            // Parameters:
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create the order's header.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER':
                $_SESSION['multiship']->createOrderHeader($p1);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each total for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that total's record.
            // - $p2 ... (r/w) A reference to the just-created order_totals record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM':
                $_SESSION['multiship']->createOrderFixupTotal($p1, $p2);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each product for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that product's record.
            // - $p2 ... (r/w) A reference to the just-created orders_products record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM':
                $_SESSION['multiship']->createOrderAddProducts($p1, $p2);
                break;
                
            // -----
            // Issued by /includes/classes/order.php, after the creation of each product-attribute addition for the order.
            //
            // Parameters:
            //
            // - $p1 ... (r/o) An associative array containing the $sql_data_array used to create that attribute's record.
            // - $p2 ... (r/w) A reference to the just-created orders_products_attributes record's id.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM':
                $_SESSION['multiship']->createOrderAddAttributes($p1);
                break;
                
            case 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION':
                $_SESSION['multiship']->_fixCartID();
                break;

            case 'NOTIFY_HEADER_END_CHECKOUT_PROCESS':
                $_SESSION['multiship']->sessionCleanup();
                break;
                
            case 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND':
                $_SESSION['multiship']->fixupOrderEmail($class, $p1, $p2, $p3);
                break;
                
            case 'NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS':
                $_SESSION['multiship']->saveEmailHeader($p2, $p3);
                break;
                
            case 'NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN':
                $_SESSION['multiship']->insertAttributesText($class);
                break;
                
            case 'NOTIFIER_CART_REMOVE_START':
                $_SESSION['multiship']->removeProduct($p2);
                break;
                
            case 'NOTIFIER_CART_UPDATE_QUANTITY_START':
                $_SESSION['multiship']->updateProduct($p2, $p3, $p4);
                break;
                
            case 'NOTIFIER_CART_ADD_CART_START':
                $_SESSION['multiship']->_checkAddProductMessage ($p2, $p3, $p4);
                break;
                
            case 'NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS':
                $_SESSION['multiship']->adjustOrdersBaseTotals();
                break;
                
            default:
                break;
        }
    }
}
