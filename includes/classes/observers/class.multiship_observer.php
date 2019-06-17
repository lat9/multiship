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
                    'NOTIFY_HEADER_END_CHECKOUT_PROCESS',
                    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING',
                    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT',
                    
                    /* /includes/modules[/YOUR_TEMPLATE]/checkout_process.php */
                    'NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS',
                    
                    /* /includes/modules/order_total/ot_shipping.php (zc156b+) */
                    'NOTIFY_OT_SHIPPING_TAX_CALCS',
                )
            );
        }
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9) 
    {
        switch ($eventID) {
            // -----
            // These two notifications work in concert with the jscript_checkout_shipping_multiship.php script's
            // processing.  If Multi-Ship is enabled, that jQuery processing adds an additional field to the to-be-posted
            // form and submits the form on any change of the shipping selection. If the checkout_shipping page's 
            // header finds the selection OK, it records the selection in the session and re-directs to checkout_payment.
            //
            // If, on entry to the checkout_shipping page, the jQuery-added variable is set, a session
            // variable is set to be interrogated on the checkout_payment page.  If that variable is set on
            // entry to checkout_payment, this processing redirects back to checkout_shipping to allow the
            // Multiple Ship-to addresses' processing to perform any shipping-cost recalculations based on that
            // shipping-selection change.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
                unset($_SESSION['multiship_shipping_changed']);
                if (!empty($_POST['multiship_changed'])) {
                    $_SESSION['multiship_shipping_changed'] = true;
                }
                break;
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
                if (isset($_SESSION['multiship_shipping_changed'])) {
                    unset($_SESSION['multiship_shipping_changed']);
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
                }
                $_SESSION['multiship']->fixupSessionShippingCost();
                break;
                
            // -----
            // Issued by /includes/modules/order_total/ot_shipping.php just prior to the shipping tax calculations.
            // If the order has multiple ship-to addresses, the session-based class will provide an update to those
            // calculations, based on possibly multiple shipping-tax rates.
            //
            // Parameters:
            //
            // $p2 ... (r/w) A reference to the boolean flag, set to true if the shipping-tax calculations should be overridden.
            // $p3 ... (r/w) A reference to the possibly-updated $shipping_tax value.
            // $p4 ... (r/w) A reference to the possibly-updated $shipping_tax_description
            //
            case 'NOTIFY_OT_SHIPPING_TAX_CALCS':
                $p2 = $_SESSION['multiship']->updateShippingTaxInfo($p3, $p4);
                break;
                
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
