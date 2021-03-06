<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

class multiship extends base 
{
    public $address2multiship,
              $payment_methods,
              $selected,
              $enabled,
              $can_offer,
              $debug,
              $logfile;

    // -----
    // Class constructor.  This class is created via auto_load as $_SESSION['multicart'].
    //
    public function __construct() 
    {
        $this->selected = false;
        $this->can_offer = false;
        $this->enabled = $this->isEnabled();
    }
    
    // -----
    // Called by the plugin's observer-class on each page-load to check to see that the plugin's
    // processing is still to be enabled and to initialize various elements (since this class
    // is session-based).  Also called by the class constructor.
    //
    public function isEnabled()
    {
        // -----
        // The plugin is initially enabled via configuration setting.
        //
        $this->enabled = (defined('MODULE_MULTISHIP_ENABLE') && MODULE_MULTISHIP_ENABLE == 'true');
        
        // -----
        // If enabled via configuration, need check a couple of additional conditions before
        // multiple ship-to addresses can be enabled for the current order.
        //
        if ($this->enabled) {
            $payment_methods = str_replace(' ', '', MODULE_MULTISHIP_PAYMENT_METHODS);
            $this->payment_methods = ($payment_methods != '') ? explode(',', $payment_methods) : false;
            $this->debug = (MODULE_MULTISHIP_DEBUG == 'true');
            $this->logfile = DIR_FS_LOGS . '/multiship_' . date('Ymd') . '.log';
            
            // -----
            // If a customer's currently logged-in, and they're part of a 'group-pricing'
            // group, multiple ship-to addresses are disabled.  The ot_group_pricing
            // processing performs its calculations assuming that the order's current
            // ship-to *address* is the basis of any tax-calculations and if there are
            // multiple *different* tax rates for the ship-to addresses, those calculations
            // will not be correct.
            //
            if (!empty($_SESSION['customer_id'])) {
                $group = $GLOBALS['db']->Execute(
                    "SELECT customers_group_pricing 
                       FROM " . TABLE_CUSTOMERS . " 
                      WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
                      LIMIT 1");
                if ($group->fields['customers_group_pricing'] != 0) {
                    $this->debugLog("isEnabled, setting disabled since the currently logged-in customer is part of a group-pricing group.");
                    $this->enabled = false;
                }
            }
            
            // -----
            // If a coupon was applied to the order (during the checkout_payment page's
            // processing) and the customer "comes back" to the checkout_shipping step,
            // multiple ship-to addresses can no longer be offered for reasons similar to the
            // disablement for group pricing.
            //
            if (!empty($_SESSION['cc_id'])) {
                $this->debugLog("isEnabled, setting disabled since there is a coupon currently applied to the order.");
                $this->enabled = false;
            }
        }
        
        // -----
        // If the plugin is not currently enabled (which could occur if the configuration and/or
        // customer's group-pricing setup were changed since login), clean up the multiship
        // session information.
        //
        if (!$this->enabled) {
            $this->sessionCleanup();
        }
        return $this->enabled;
    }
  
    // -----
    // Called by the header_php.php processing for the "checkout_multiship" page.  The two arrays are
    // (presumed to be) of equal size and a one-to-one correlation between an address_id for the
    // current customer and the Zen Cart "prid" (which defines both the product_id and its associated
    // attributes.
    //
    // Build up an array, indexed by the address_id, that contains the "prid" items to be shipped to that 
    // address_id. If more than one address_id is found in the array of addresses, then the customer has chosen
    // to send their order to more than one shipping address ... and multiship is selected.  The address/prid
    // array is saved within the class data for processing on the checkout_confirmation page.
    //
    // Note that the processing above is slightly modified when there is a mixture of physical and virtual
    // products in the customer's cart.  This function/process won't be called if the entire cart is virtual
    // since there's no shipping required!  If the cart is "mixed", then need to make sure that the 
    // current shipping arrangement doesn't have all virtual products going to an address separate from
    // the physical products.  If so, move the virtual products to be associated with one of the physical
    // ship-to addresses.
    //
    // The function returns a binary flag that indicates whether or not multiple shipping addresses have
    // been selected.
    // 
    public function setMultiship($address_array, $prid_array) 
    {
        $this->selected = false;
        $multiship_values = array();
        $address = false;
        $this->debugLog('setMultiship: ' . json_encode($address_array) . ', ' . json_encode($prid_array));
        foreach ($address_array as $i => $currentAddress) {
            if ($address === false) {
                $address = $currentAddress;
            }
            if ($address != $currentAddress) {
                $this->selected = true;
                $address = $currentAddress;
            }
          
            $prid = $prid_array[$i];
          
            if (isset($multiship_values[$address])) {
                $multiship_values[$address]['has_physical'] |= $this->cartItemIsPhysical($prid);
            } else {
                $multiship_values[$address] = array();
                $multiship_values[$address]['has_physical'] = $this->cartItemIsPhysical($prid);
            }
          
            if (isset($multiship_values[$address][$prid])) {
                $multiship_values[$address][$prid]++;
            } else {
                $multiship_values[$address][$prid] = 1;
            }
          
        }  // END foreach inspecting each address/prid pair

        if (!$this->selected) {
            $this->sessionCleanup();
        } else {
            if ($_SESSION['cart']->get_content_type() != 'mixed') {
                $this->cart = $multiship_values;
            } else {
                $num_physical_addresses = 0;
                foreach ($multiship_values as $address_id => $productInfo) {
                    if ($productInfo['has_physical']) {
                        $num_physical_addresses++;
                        if (!isset($physical_product_address_id)) {
                            $physical_product_address_id = $address_id;
                        }
                    } else {
                        $virtual_product_address_id = $address_id;
                    }
                }
                if (isset($virtual_product_address_id) && $num_physical_addresses == 1) {
                    $virtual_products = $multiship_values[$virtual_product_address_id];
                    unset($virtual_products['has_physical'], $multiship_values[$virtual_product_address_id]);
                    if (isset($multiship_values[$_SESSION['customer_default_address_id']])) {
                        $physical_product_address_id = $_SESSION['customer_default_address_id'];
                    }
                    if (count($multiship_values) == 1) {
                        $_SESSION['sendto'] = $physical_product_address_id;
                        $this->selected = false;
                        $this->sessionCleanup();
                    } else {
                        $multiship_values[$physical_product_address_id] = array_merge($multiship_values[$physical_product_address_id], $virtual_products);
                        $this->cart = $multiship_values;
                    }
                } else {
                    $this->cart = $multiship_values;
                }
            }
        }
        $this->shipping_method_id = ($this->selected) ? $_SESSION['shipping']['id'] : '';
        return $this->selected;
    }
    
    public function addressValidation($addresses)
    {
        global $order;
        $addresses = array_unique($addresses);
        $validated = true;
        if (count($addresses) > 1 || (count($addresses) == 1 && $addresses[0] != $_SESSION['sendto'])) {
            // -----
            // Pull in the httpClient class for those shipping methods (like UPS) that require it!
            //
            require_once DIR_WS_CLASSES . 'http_client.php';
            
            // -----
            // Pull in the order and shipping classes.
            //
            require DIR_WS_CLASSES . 'order.php';
            require DIR_WS_CLASSES . 'shipping.php';
            
            list($module, $method) = explode('_', $_SESSION['shipping']['id']);
            $saved_sendto = $_SESSION['sendto'];
            foreach ($addresses as $address_id) {
                // -----
                // Let the order class do the "heavy lifting" in the pulling in of the product list and delivery address 
                // information for the current shipping address.
                //
                $_SESSION['sendto'] = $address_id;
                $order = new order;
 
                // -----
                // Load the current shipping modules.
                //
                $shipping_modules = new shipping;
                
                $shipping_quote = $shipping_modules->quote($method, $module);
                $this->debugLog("addressValidation: Quote received for $address_id: " . var_export($shipping_quote, true));
                if (!is_array($shipping_quote) || count($shipping_quote) == 0 || isset($shipping_quote[0]['error'])) {
                    $validated = false;
                    $this->debugLog("No shipping quote for $address_id.");
                }
            }
            $_SESSION['sendto'] = $saved_sendto;
        }
        return $validated;
    }
  
    // -----
    // Returns a binary flag that indicates whether or not the customer has selected multiple
    // shipping addresses for the current order.
    //
    public function isSelected() 
    {
        return $this->selected;
    }
  
    // -----
    // Returns a binary flag that indicates whether the customer can be offered multiple shipping
    // addresses for this order, as set by the checkoutInitialization method's processing during the
    // checkout-shipping page.
    //
    public function canOffer() 
    {
        return ($this->enabled && $this->can_offer);
    }
  
    // -----
    // Returns an array that contains the details of the multiple shipping addresses.  The
    // primary index for the array is the address_id to which the array of products is to
    // be sent.
    //
    public function getDetails() 
    {
        return (isset($this->details)) ? $this->details : array();
    }
    
    // -----
    // Returns the count of the number of addresses currently in effect.
    //
    public function numShippingAddresses()
    {
        return (isset($this->cart)) ? count($this->cart) : 0;
    }

    // -----
    // Returns an array of order_total values that have been summed for all the sub-orders.
    //
    function getTotals() 
    {
        return (isset($this->totals)) ? $this->totals : array();
    }
  
    // -----
    // Returns the name of the shipping class that is active for the current order, e.g. "United States Postal Service".
    //
    public function get_shipping_method() 
    {
        return (isset($this->shipping_method)) ? $this->shipping_method : '';
    }
    
    // -----
    // Returns the 'id' (e.g. 'usps_xxxx' or 'flat_flat') of the shipping-method that provided the shipping
    // cost for the current multiship selections.
    //
    public function getShippingId()
    {
        if (empty($this->shipping_method_id)) {
            trigger_error("Sequencing error, shipping_method_id is empty.", E_USER_ERROR);
            exit();
        }
        $this->debugLog("getShippingId, returning {$this->shipping_method_id}.");
        return $this->shipping_method_id;
    }
    
    // -----
    // Returns the summation of the shipping costs for the current multiship selections.
    //
    public function getMultiShipShippingCost()
    {
        if (empty($this->details)) {
            trigger_error("Sequencing error, details is empty.", E_USER_ERROR);
            exit();
        }
        $shipping_cost = 0;
        foreach ($this->details as $addr_id => $ms_info) {
            $shipping_cost += $ms_info['info']['shipping_cost'];
        }
        $this->debugLog("getMultiShipShippingCost, returning $shipping_cost.");
        return $shipping_cost;
    }
    
    // -----
    // Returns the current multiship "cart" contents.
    //
    public function getCart() 
    {
        return (isset($this->cart)) ? $this->cart : array();
    }
  
    // -----
    // Returns the image to be associated with an unshippable address.
    //
    public function getNoShipIcon($address_id = '') 
    {
        return ($address_id === '' || isset($this->cart[$address_id]['address-error'])) ? MULTISHIP_ICON_NO_SHIP : '';
    }
    
    // -----
    // Returns a boolean flag to indicate whether or not an item presently in the cart is a physical item.
    //
    public function cartItemIsPhysical($prid) 
    {
        // -----
        // Initially (for items not in the cart), the product identified is not physical.
        //
        $is_physical = false;
        if (isset($_SESSION['cart']->contents[$prid])) {
            // -----
            // Check to see if the product is marked as a virtual one.
            //
            $pID = (int)zen_get_prid($prid);
            $virtual_check = $GLOBALS['db']->Execute(
                "SELECT products_virtual 
                   FROM " . TABLE_PRODUCTS . " 
                  WHERE products_id = $pID 
                  LIMIT 1"
            );
            $is_physical = ($virtual_check->fields['products_virtual'] == 0);
            
            // -----
            // If the product is not marked as virtual and has attributes, check to see if one of the product's
            // attributes identifies a download-type product.  If so, then the product is not physical.
            //
            if ($is_physical && isset($_SESSION['cart']->contents[$prid]['attributes']) && is_array($_SESSION['cart']->contents[$prid]['attributes'])) {
                foreach ($_SESSION['cart']->contents[$prid]['attributes'] as $option_id => $value_id) {
                    $is_download = $GLOBALS['db']->Execute(
                        "SELECT pa.products_attributes_id
                           FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                INNER JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                    ON pad.products_attributes_id = pa.products_attributes_id
                          WHERE pa.products_id = $pID
                            AND pa.options_id = $option_id
                            AND pa.options_values_id = $value_id
                          LIMIT 1"
                    );
                    if (!$is_download->EOF) {
                        $is_physical = false;
                        break;
                    }
                }  // END per-attribute foreach
            }
        }
        return $is_physical;
    }
    
    // -----
    // Called by the multiship-observer class, upon receipt of NOTIFY_ORDER_CART_FINISHED from the order-class.
    //
    // If the current order has multiple ship-to addresses, we'll sum up the totals and taxes associated
    // with those sub-orders into the order's header/info section for follow-on use by any order-total
    // modules' processing.
    //
    public function updateOrdersTotalsAndTaxes(&$order)
    {
        // -----
        // Quick return if the order doesn't have multiple ship-to addresses, or if the multi-ship
        // totals and details have not yet been set.
        //
        if (!$this->selected || !isset($this->totals) || !isset($this->details) || !empty($this->initialization_active)) {
            return;
        }
        
        $this->debugLog("updateOrdersTotalAndTaxes, on entry: " . json_encode($order->info), true);
        
        // -----
        // Set the order's current total, tax (and tax groups, used by ot_tax for its display) and shipping_cost
        // based on the totals for each multi-ship sub-order.
        //
        $order->info['total'] = $this->totals['ot_total'];
        $order->info['tax'] = $this->totals['ot_tax'];
        $order->info['shipping_cost'] = $this->totals['ot_shipping'];
        
        // -----
        // Loop through each of the sub-orders to update the order's multi-ship tax groups.
        //
        $order->info['tax_groups'] = array();
        foreach ($this->details as $addr_id => $info) {
            foreach ($info['info']['tax_groups'] as $tax_group_name => $tax_group_value) {
                if (!isset($order->info['tax_groups'][$tax_group_name])) {
                    $order->info['tax_groups'][$tax_group_name] = 0;
                }
                $order->info['tax_groups'][$tax_group_name] += $tax_group_value;
            }
        }
        $this->debugLog("updateOrdersTotalAndTaxes, on exit: " . json_encode($order->info));
    }

    // -----
    // Called by the multiship-observer class, upon receipt of NOTIFY_CHECKOUT_PROCESS_AFTER_ORDER_TOTALS_PROCESS
    // from the checkout_process.php module.
    //
    // This gives us the chance to modify the order's base total/tax values to include any additions based on multi-ship
    // shipping charges and associated tax, allowing an external payment method to properly capture the order's total cost.
    //
    public function adjustOrdersBaseTotals()
    {
        // -----
        // If the current order has multiple shipping addresses, update the order's base cost/total and
        // associated tax, allowing payment methods' 'process' method to pick up the possibly updated
        // charges.
        //
        if ($this->selected) {
            $GLOBALS['order']->info['total'] = (isset($this->totals['ot_total'])) ? $this->totals['ot_total'] : 0;
            $GLOBALS['order']->info['tax'] = (isset($this->totals['ot_tax'])) ? $this->totals['ot_tax'] : 0;
        }
    }
    
    // -----
    // Called by the multiship_observer class, upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER
    // (issued by the order class).
    //
    public function createOrderHeader($order_info_array) 
    {
        global $db;
        
        // -----
        // If the current order has multiple shipping addresses ...
        //
        if ($this->selected) {
            // -----
            // First, fix-up the order's total and tax calculations to be the sum of the multiple shipping addresses.
            //
            $orders_id = $order_info_array['orders_id'];
            $order_total = (isset($this->totals['ot_total'])) ? $this->totals['ot_total'] : 0;
            $order_tax = (isset($this->totals['ot_tax'])) ? $this->totals['ot_tax'] : 0;
            $db->Execute(
                "UPDATE " . TABLE_ORDERS . " 
                    SET order_total = $order_total, order_tax = $order_tax, shipping_method = '" . $this->shipping_method . "' 
                WHERE orders_id = $orders_id
                LIMIT 1"
            );
          
            // -----
            // Create the orders_multiship table record for each of the shipping addresses associated with the order.  Save the
            // correlation between the address_id and the table record written so that we can cross-reference the products to their
            // respective shipping addresses.
            //
            $this->address2multiship = array();
            foreach ($this->details as $address_id => $currentInfo) {
                $sql = array( 
                    'orders_id' => $orders_id,
                    'delivery_name' => $currentInfo['delivery']['firstname'] . ' ' . $currentInfo['delivery']['lastname'],
                    'delivery_company' => $currentInfo['delivery']['company'],
                    'delivery_street_address' => $currentInfo['delivery']['street_address'],
                    'delivery_suburb' => $currentInfo['delivery']['suburb'],
                    'delivery_city' => $currentInfo['delivery']['city'],
                    'delivery_postcode' => $currentInfo['delivery']['postcode'],
                    'delivery_state' => $currentInfo['delivery']['state'],
                    'delivery_country' => $currentInfo['delivery']['country']['title'],
                    'delivery_address_format_id' => $currentInfo['delivery']['format_id'],
                    'last_modified' => 'now()',
                    'orders_status' => $order_info_array['orders_status'],
                    'content_type' => $currentInfo['content_type'],
                );
                zen_db_perform(TABLE_ORDERS_MULTISHIP, $sql);
                $multiship_id = $db->Insert_ID();
                $this->address2multiship[$address_id] = $multiship_id;

                // -----
                // Create the order-totals' records for the current shipping address.
                //
                foreach ($currentInfo['totals'] as $currentTotal) {
                    $sql = array(
                        'orders_multiship_id' => $multiship_id,
                        'orders_id' => $orders_id,
                        'title' => $currentTotal['title'],
                        'text' => $currentTotal['text'],
                        'value' => $currentTotal['value'],
                        'class' => $currentTotal['code'],
                        'sort_order' => $currentTotal['sort_order'],
                    );
                    zen_db_perform(TABLE_ORDERS_MULTISHIP_TOTAL, $sql);
                }
            }
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM
    // (issued by the order class).  This gives us an opportunity to modify the overall order's overall total for the current
    // order-total class.
    //
    public function createOrderFixupTotal($orders_totals_array, $insert_id) 
    {
        global $db, $currencies;
        if (is_array($this->totals) && isset($this->totals[$orders_totals_array['class']])) {
            $this->debugLog('createOrderFixupTotal: input: ' . json_encode($orders_totals_array) . 'totals: ' . json_encode($this->totals));
            $currentTotal = $this->totals[$orders_totals_array['class']];
            $db->Execute(
                "UPDATE " . TABLE_ORDERS_TOTAL . " 
                    SET text = '" . $currencies->format($currentTotal) . "', 
                        value = " . $currentTotal . " 
                  WHERE orders_total_id = $insert_id
                  LIMIT 1");
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM
    // (issued by the order class).  This is called once for each product in the current session's cart.
    //
    // Note: A change was introduced in zc155, adding the order-class products' array index; that needs
    //       to be removed prior to database update!
    //
    public function createOrderAddProducts($orders_products_array, $orders_products_id) {
        global $db, $currencies;

        // -----
        // If the current order has multiple shipping addresses ...
        //
        if ($this->selected) {
            unset($orders_products_array['i'], $orders_products_array['orders_products_id']);
            $prid = $orders_products_array['products_prid'];
            $qty = $orders_products_array['products_quantity'];
      
            $initial_modification = false;
            $this->orders_multiship_ids = array();
            foreach ($this->details as $address_id => $currentInfo) {
                if (!isset($this->details[$address_id]['products_ordered_text'])) {
                    $this->details[$address_id]['products_ordered_text'] = '';
                    $this->details[$address_id]['products_ordered_html'] = '';
                }
                $this->details[$address_id]['need_attributes'] = false;
                foreach ($currentInfo['products'] as $currentProduct) {
                    if ($currentProduct['id'] == $prid) {
                        $this->details[$address_id]['need_attributes'] = true;
                        $product_qty = $currentProduct['qty'];
                        $qty -= $product_qty;
                        if ($qty < 0) {
//                            $this->_debugLog('_createOrderAddProducts: product quantity went negative.', array ( 'sql' => $orders_products_array, 'details' => $this->details ), true);
                        }
                        if (!$initial_modification) {
                            $initial_modification = true;
                            $db->Execute(
                                "UPDATE " . TABLE_ORDERS_PRODUCTS . " 
                                    SET products_quantity = " . $product_qty . ",
                                        products_tax = " . $currentProduct['tax'] . ",
                                        orders_multiship_id = " . $this->address2multiship[$address_id] . "
                                  WHERE orders_products_id = $orders_products_id
                                  LIMIT 1"
                            );    
                        } else {
                            $orders_products_array['products_quantity'] = $product_qty;
                            $orders_products_array['orders_multiship_id'] = $this->address2multiship[$address_id];
                            zen_db_perform(TABLE_ORDERS_PRODUCTS, $orders_products_array);
                            $this->orders_multiship_ids[] = $db->Insert_ID();
                        }
            
                        $product_name = $currentProduct['name'];
                        $product_model = $currentProduct['model'] != '' ? (' (' . $currentProduct['model'] . ') ') : '';
                        $product_tax = $currentProduct['tax'];
                        $product_price = $currencies->display_price($currentProduct['final_price'], $product_tax, $product_qty);
                        $product_onetime = $currentProduct['onetime_charges'];
                        $this->details[$address_id]['products_ordered_text'] .= "$product_qty x $product_name$product_model = $product_price";
                        $this->details[$address_id]['products_ordered_html'] .= 
                            "<tr>\n" .
                            "<td class=\"product-details\" align=\"right\" valign=\"top\" width=\"30\">$product_qty&nbsp;x</td>\n" .
                            '<td class="product-details" valign="top">' . nl2br($product_name) . nl2br($product_model) . "\n<small><em>%s</em></small></td>\n" .
                            "<td class=\"product-details-num\" valign=\"top\" align=\"right\">$product_price</td></tr>\n";
                        if ($product_onetime != 0) {
                            $this->details[$address_id]['products_ordered_text'] .= "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price($product_onetime, $product_tax, 1);
                            $this->details[$address_id]['products_ordered_html'] .= '<tr><td class="product-details">' . nl2br(TEXT_ONETIME_CHARGES_EMAIL) . "</td>\n" . '<td>' . $currencies->display_price($product_onetime, $product_tax, 1) . "</td></tr>\n";
                        }
                    }
                }
            }
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM
    // (issued by the order class).
    //
    public function createOrderAddAttributes($products_attributes_array) 
    {
        if ($this->selected) {
            unset($products_attributes_array['orders_products_attributes_id']);
            foreach ($this->orders_multiship_ids as $orders_products_id) {
                $products_attributes_array['orders_products_id'] = $orders_products_id;
                zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $products_attributes_array);
            }
        }
    }
  
    public function insertAttributesText($order) 
    {
        if ($this->selected) {
            foreach ($this->details as $address_id => &$currentInfo) {
                if ($currentInfo['need_attributes']) {
                    $currentInfo['products_ordered_text'] .= $order->products_ordered_attributes . "\n";
                    $currentInfo['products_ordered_html'] = sprintf($currentInfo['products_ordered_html'], nl2br($order->products_ordered_attributes));
                    $currentInfo['need_attributes'] = false;
                }
            }
            unset($currentInfo);
        }
    }
  
    // -----
    // Called by the multiship_observer upon receipt of NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS (issued by the order class, after the
    // email lead-in information is built).  Save this information for use in the modification of the email for a multiple ship-to
    // order's processing.
    //
    public function saveEmailHeader($text_email, $html_email) 
    {
        $this->text_email = $text_email;
    }
  
    // -----
    // Called by the multiship_observer upon receipt of the NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND2 (issued by the order class, 
    // just before sending the order confirmation email).  If the current order has multiple shipping addresses, this provides the
    // fix-ups required for the text and HTML email contents.
    //
    public function fixupOrderEmail($order, $parmArray, &$email_order, &$html_email) 
    {
        global $currencies;

        if ($this->selected) {
            // -----
            // Lead in with the billing address and payment method information for the text emails.
            //
            $email_order = $this->text_email . "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" . EMAIL_SEPARATOR . "\n" . zen_address_label($_SESSION['customer_id'], $_SESSION['billto'], 0, '', "\n") . "\n\n";
            if (is_object($GLOBALS[$_SESSION['payment']])) {
                $cc_num_display = (isset($order->info['cc_number']) && $order->info['cc_number'] != '') ? str_repeat('X', (strlen($order->info['cc_number']) - 8)) . substr($order->info['cc_number'], -4) . "\n\n" : '';
                $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . EMAIL_SEPARATOR . "\n";
                $payment_class = $_SESSION['payment'];
                $email_order .= $GLOBALS[$payment_class]->title . "\n\n";
                $email_order .= (isset($order->info['cc_type']) && $order->info['cc_type'] != '') ? $order->info['cc_type'] . ' ' . $cc_num_display . "\n\n" : '';
                $email_order .= ($GLOBALS[$payment_class]->email_footer) ? $GLOBALS[$payment_class]->email_footer . "\n\n" : '';
            } else {
                $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" . EMAIL_SEPARATOR . "\n" . PAYMENT_METHOD_GV . "\n\n";;
            }
      
            // -----
            // Format a separate section for each ship-to address and its associated products and prices.
            //
            $table_format = '<table border="0" width="100%%" cellspacing="0" cellpadding="2">%s</table>';
            $order_totals_format = '<tr><td class="order-totals-text" align="right" width="100%%">%s</td><td class="order-totals-num" align="right">%s</td></tr>' . "\n";

            $products_html = '';
            foreach ($this->details as $address_id => $currentInfo) {
                $shipping_to = TEXT_SHIPPING_TO . $currentInfo['address'];
                $email_order .= EMAIL_SEPARATOR . "\n" . $shipping_to . "\n" . EMAIL_SEPARATOR . "\n";
                $shipping_to = "<tr><td><div class=\"content-line\">$shipping_to</div></td></tr>";
                $products_html .= '<hr /><table class="order-shipto" border="0" width="100%" cellspacing="0" cellpadding="2">' . "\n" . '<tr><td>' . sprintf ($table_format, $shipping_to) . "</td></tr>\n";

                $email_order .= EMAIL_TEXT_PRODUCTS . "\n" . EMAIL_SEPARATOR . "\n" . $currentInfo['products_ordered_text'] . EMAIL_SEPARATOR . "\n";
                $products_html .= '<tr><td><div class="content-line">' . EMAIL_TEXT_PRODUCTS . '</div>' . sprintf ($table_format, $currentInfo['products_ordered_html']) . "</td></tr>\n";

                //order totals area
                $html_ot = '<tr><td class="order-totals-text" align="right" width="100%">&nbsp;</td>' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">---------</td></tr>' . "\n";
                foreach ($this->details[$address_id]['totals'] as $currentTotal) {
                    $email_order .= strip_tags($currentTotal['title']) . ' ' . strip_tags($currentTotal['text']) . "\n";
                    $html_ot .= '<tr><td class="order-totals-text" align="right" width="100%">' . $currentTotal['title'] . '</td>' . "\n" . '<td class="order-totals-num" align="right">' . $currentTotal['text'] . '</td></tr>' . "\n";
                }
                $email_order .= "\n\n";
                $products_html .= sprintf ($table_format, $html_ot) . "</table>\n";
            }

            $html_email['PRODUCTS_TITLE'] = SHIPPING_TO_MULTIPLE_ADDRESSES;
            $html_email['PRODUCTS_DETAIL'] = $products_html; 

            $grand_total = $currencies->format ($this->totals['ot_total']);
            $html_email['ORDER_TOTALS'] = '<hr />' . sprintf ($table_format, sprintf ($order_totals_format, TEXT_GRAND_TOTAL, $grand_total));
            $email_order .= EMAIL_SEPARATOR . "\n" . TEXT_GRAND_TOTAL . ' ' . $grand_total . "\n\n";

            $html_email['ADDRESS_DELIVERY_DETAIL'] = MULTISHIP_MULTIPLE_ADDRESSES;
        }
    }
 
    // -----
    // Counts (and returns) the number of PHYSICAL products present in the current session's cart.
    //
    protected function cartPhysicalItemsCount() 
    {
        $num_physical_items = 0;
        foreach ($_SESSION['cart']->contents as $prid => $current_product) {     
            if ($this->cartItemIsPhysical($prid)) {
                $num_physical_items += $current_product['qty'];
            }
        } 
        return $num_physical_items;
    }
  
    // -----
    // Called at the end of the checkout-shipping page by an additional header module,
    // allows us to see if multiple ship-to addresses should be offered (or have been
    // previously selected) for the current order.
    //
    public function checkoutInitialize() 
    {
        // -----
        // If the processing is currently disabled (due to configuration setting), nothing else to be done.
        //
        if (!$this->enabled) {
            $this->sessionCleanup();
            return;
        }
        
        // -----
        // Now, see if the multiple-shipping selection should be offered to the customer.  This
        // selection is offered if all of the following are true:
        //
        // 1) There is more than one "physical" item in the customer's cart.
        // 2) The current customer is not checking out via COWOA.
        // 3) The current customer is not checking out via a PayPal Express Checkout guest account.
        //
        $this->can_offer = ($this->cartPhysicalItemsCount() > 1 && empty($_SESSION['COWOA']) && empty($_SESSION['customer_guest_id']) && zen_count_shipping_modules() > 0);
        if (!$this->can_offer) {
            $this->sessionCleanup();
        }

        // -----
        // If multiple shipping-addresses aren't currently selected, make sure that the session-based information
        // from a possibly previous collection of multiple shipping addresses are properly cleaned up.
        //
        if (!$this->selected) {
            $this->sessionCleanup();
            
        // -----
        // Otherwise, the customer has previously selected multiple shipping addresses on the checkout_multiship page ...
        //
        } else {
            // -----
            // Make sure that the currently-selected shipping method is "sane".  The 'id' element must be set and it must contain an
            // underscore, separating the shipping method's class name from the currently-selected method.
            //
            $shipping_error = false;
            if (!isset($_SESSION['shipping']['id'])) {
                $shipping_error = true;
                $session_shipping = false;
            } else {
                $shipping_info = explode ('_', $_SESSION['shipping']['id']);
                $session_shipping = $_SESSION['shipping'];
                if (count($shipping_info) != 2) {
                    $shipping_error = true;
                }
            }
            if ($shipping_error || isset($_SESSION['multiship_new_shipping'])) {
                $this->debugLog('checkoutInitialization: Returning to checkout_shipping, invalid shipping method.' . PHP_EOL . json_encode($session_shipping));
                return;
            }
            $this->shipping_method_id = $_SESSION['shipping']['id'];
          
            // -----
            // Save the current contents of both the shopping cart and the default sendto-address.  These values will be
            // manipulated to provide the shipping costs on a per-ship-to address basis.
            //
            global $total_weight, $total_count;
            $this->saveOrdersBaseValues();
            
            // -----
            // Ditto with the current order.  Save the current instance and declare that variable
            // global so that the order-totals' processing will occur for the multiship recalculation.
            //
            global $order;
            $saved_order = $order;
            
            // -----
            // Set a class variable (used by the updateShippingTaxInfo method) to indicate that we're
            // in the process of gathering the multiple ship-to addresses' information.  That way, the
            // tax-related calculations for the order's sub-orders will use the store's current
            // configuration.
            //
            $this->initialization_active = true;
          
            // -----
            // Loop through each of the ship-to addresses that were previously gathered, populating the shopping-cart object
            // with the products to be sent to that address and setting the session's sendto address ID to that value.  This
            // allows the currently-selected shipping module to be used to calculate this sub-cart's shipping costs.
            //
            $this->totals = array();
            $multiship_info = array();
            $multiship_grand_total = 0;
            $multiship_shipping_total = 0;
            $invalid_address_found = false;
            foreach ($this->cart as $address_id => $products) {
                unset($this->cart[$address_id]['address-error']);
                
                $multiship_info[$address_id] = array();
                $multiship_info[$address_id]['address'] = zen_address_label($_SESSION['customer_id'], $address_id, false, '', ', ');
                $_SESSION['cart']->contents = array();
                foreach ($products as $prid => $qty) {
                    if ($prid == 'has_physical' || $prid == 'address-error') {
                        continue;
                    }
                    $_SESSION['cart']->contents[$prid] = array(
                        'qty' => $qty
                    );
                    if (isset($this->saved_order_info['cart_contents'][$prid]['attributes'])) {
                        $_SESSION['cart']->contents[$prid]['attributes'] = $this->saved_order_info['cart_contents'][$prid]['attributes'];
                    }
                    if (isset($this->saved_order_info['cart_contents'][$prid]['attributes_values'])) {
                        $_SESSION['cart']->contents[$prid]['attributes_values'] = $this->saved_order_info['cart_contents'][$prid]['attributes_values'];
                    }
                }
            
                // -----
                // Get the shipping quote for this ship-to address.  If the quote comes back empty, then this shipping method is
                // not supported for the selected products and/or address.  Redirect back to the multiship page.
                //
                $_SESSION['sendto'] = $address_id;
                $total_weight = $_SESSION['cart']->show_weight();
                $total_count = $_SESSION['cart']->count_contents();
                $_SESSION['shipping']['cost'] = 0;
            
                // -----
                // Let the order class do the "heavy lifting" in the pulling in of the product list and delivery address 
                // information for the current shipping address.
                //
                $order = new order;
                $this->debugLog("Initialize step 1 ($address_id), total_weight = $total_weight, total_count = $total_count, order-info: " . json_encode($order->info), true);
                $multiship_info[$address_id]['products'] = $order->products;
                $multiship_info[$address_id]['delivery'] = $order->delivery;
                $multiship_info[$address_id]['content_type'] = $order->content_type;
                $multiship_info[$address_id]['info'] = $order->info;
            
                // -----
                // Pull in the httpClient class for those shipping methods (like UPS) that require it!
                //
                require_once DIR_WS_CLASSES . 'http_client.php'; 
                
                // -----
                // Load the current shipping modules, bringing in the class if it's not already.
                //
                if (!class_exists('shipping')) {
                    require DIR_WS_CLASSES . 'shipping.php';
                }
                $shipping_modules = new shipping;
                
                $shipping_quote = $shipping_modules->quote($shipping_info[1], $shipping_info[0]);
                $this->debugLog("Quote received for $address_id: " . json_encode($shipping_info) . ', quote: ' . var_export($shipping_quote, true));
                if (!is_array($shipping_quote) || count($shipping_quote) == 0 || isset($shipping_quote[0]['error'])) {
                    $this->debugLog("No shipping quote for $address_id");
                    $this->cart[$address_id]['address-error'] = (isset($shipping_quote[0]['error'])) ? isset($shipping_quote[0]['error']) : ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING;
                    $invalid_address_found = true;
                    continue;
                }
                
                $shipping_cost = $shipping_quote[0]['methods'][0]['cost'];
                $multiship_info[$address_id]['info']['shipping_cost'] = $shipping_cost;
                $multiship_shipping_total += $shipping_cost;
                $_SESSION['shipping']['cost'] = $shipping_cost;
                $shipping_class = $shipping_info[0];
                global ${$shipping_class};
                $this->shipping_method = ${$shipping_class}->title;
                $shipping_method = $shipping_quote[0]['module'] . ' (' . $shipping_quote[0]['methods'][0]['title'] . ')';
                $multiship_info[$address_id]['info']['shipping_method'] = $shipping_method;

                // -----
                // Now that the shipping cost for this sub-order has been calculated, let the order class do the "heavy lifting"
                // in the pulling in of the product list and delivery address information for the current shipping address.
                //
                $order = new order;

                require_once DIR_WS_CLASSES . 'order_total.php';
                $order_total_modules = new order_total;
                $order_total_modules->collect_posts();
                $order_total_modules->pre_confirmation_check();
                
                $this->debugLog("Initialize step 2 ($address_id), order-info: " . json_encode($order->info));
                $multiship_info[$address_id]['products'] = $order->products;
                $multiship_info[$address_id]['delivery'] = $order->delivery;
                $multiship_info[$address_id]['content_type'] = $order->content_type;
                $multiship_info[$address_id]['info'] = $order->info;
                
                if (MODULE_ORDER_TOTAL_INSTALLED) {
                    $multiship_info[$address_id]['totals'] = $order_total_modules->process();
                    if (isset($_SESSION['shipping_tax_description'])) {
                        $multiship_info[$address_id]['info']['shipping_tax_description'] = $_SESSION['shipping_tax_description'];
                    }
                    $multiship_info[$address_id]['info']['shipping_tax'] = $order->info['shipping_tax'];
                    foreach ($multiship_info[$address_id]['totals'] as &$currentTotal) {
                        $code = $currentTotal['code'];
                        $currentTotal['class'] = str_replace('_', '', $code);
                        if ($code == 'ot_shipping') {
                            $currentTotal['value'] = $shipping_quote[0]['methods'][0]['cost'];
                            $currentTotal['text'] = $GLOBALS['currencies']->format($currentTotal['value'], true, $order->info['currency'], $order->info['currency_value']);
                            $currentTotal['title'] = $shipping_method;
                        }
                        if (!isset($this->totals[$code])) {
                            $this->totals[$code] = 0;
                        }
                        $this->totals[$code] += $currentTotal['value'];
                    }
                    unset($currentTotal);
                }
                $this->debugLog(var_export($this->totals, true));
            }

            $this->details = $multiship_info;
            $this->restoreOrdersBaseValues();
            $_SESSION['shipping']['cost'] = $multiship_shipping_total;
            $this->shipping_total = $multiship_shipping_total;
            $order = $saved_order;
        }  // Customer has chosen multiple ship-to addresses
        
        unset($this->initialization_active);
        
        // -----
        // If any of the multiple ship-to addresses aren't valid for the currently-selected shipping method,
        // redirect back to the 'checkout_multiship' page so that the customer can make other
        // selections.
        //
        if (!empty($invalid_address_found)) {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
        }
    }
    protected function saveOrdersBaseValues()
    {
        global $total_weight, $total_count;
        $this->saved_order_info = array(
            'cart_contents' => $_SESSION['cart']->contents,
            'sendto' => $_SESSION['sendto'],
            'shipping_cost' => $_SESSION['shipping']['cost'],
            'total_weight' => $total_weight,
            'total_count' => $total_count,
        );
        if (isset($_SESSION['shipping_tax_description'])) {
            $this->saved_order_info['shipping_tax_description'] = $_SESSION['shipping_tax_description'];
        }
        if (isset($_SESSION['shipping_tax_amount'])) {
            $this->saved_order_info['shipping_tax_amount'] = $_SESSION['shipping_tax_amount'];
        }
    }
    protected function restoreOrdersBaseValues()
    {
        global $total_weight, $total_count;
        $_SESSION['cart']->contents = $this->saved_order_info['cart_contents'];
        $_SESSION['sendto'] = $this->saved_order_info['sendto'];
        $_SESSION['shipping']['cost'] = $this->saved_order_info['shipping_cost'];
        $total_weight = $this->saved_order_info['total_weight'];
        $total_count = $this->saved_order_info['total_count'];
        if (isset($this->saved_order_info['shipping_tax_description'])) {
            $_SESSION['shipping_tax_description'] = $this->saved_order_info['shipping_tax_description'];
        }
        if (isset($this->saved_order_info['shipping_tax_amount'])) {
            $_SESSION['shipping_tax_amount'] = $this->saved_order_info['shipping_tax_amount'];
        }
        unset($this->saved_order_info);
    }
    
    // -----
    // Called at the start of the checkout_payment page to fix-up the session-based
    // shipping cost to reflect any changes based on the multiple ship-to addresses.  That
    // value is used by the order-class on initial creation of the order and stored in
    // the order-object's info['shipping_cost'] field.
    //
    public function fixupSessionShippingCost()
    {
        if (!empty($_SESSION['shipping']) && isset($this->shipping_total)) {
            $_SESSION['shipping']['cost'] = $this->shipping_total;
        }
    }
    
    // -----
    // Called via notification from the ot_shipping module prior to calculating the tax to be
    // applied to the order's shipping.  If multiple ship-to addresses are being used for this
    // order, the updateOrdersTotalsAndTaxes method has already adjusted the order's taxes and total
    // to account for each sub-order's values.
    //
    // Since there's nothing for ot_shipping to do with the shipping taxes, return a tax-rate
    // of 0 to be applied for an "unknown" tax class.
    //
    public function updateShippingTaxInfo(&$shipping_tax, &$shipping_tax_description)
    {
        $this->debugLog("updateShippingTaxInfo, on entry: " . json_encode($GLOBALS['order']->info));
        $shipping_tax_override = ($this->selected && empty($this->initialization_active));
        if ($shipping_tax_override) {
            $shipping_tax = 0;
            $shipping_tax_description = TEXT_UNKNOWN_TAX_RATE;
        }
        return $shipping_tax_override;
    }
  
    // -----
    // Called at the start of the shopping_cart class' "remove" processing.  Removes all references to the
    // specified prid from the multiship session data.  Since this action is invoked from the shopping_cart
    // page and the customer will need to re-enter the checkout_confirmation page to continue, just clear
    // out any multiship details that were previously calculated since the confirmation page's re-entry
    // will result in a recalculation anyway.
    //
    public function removeProduct($prid) 
    {
        // -----
        // First, remove all multiship class elements associated with the ship-to details.
        //
        unset($this->details, $this->totals, $this->text_email);

        // -----
        // Next, go through the multiship "cart" contents, removing all references to the product.  If, after
        // removing the product, an address contains only one reference (the physical/virtual flag) then remove
        // that address as well.
        //
        if (isset($this->cart)) {
            foreach ($this->cart as $address_id => $itemInfo) {
                unset ($this->cart[$address_id][$prid]);
                if (count ($this->cart[$address_id]) == 1) {
                    unset($this->cart[$address_id]);
                }
            }
        }
         
        // -----
        // Finally, if the multiship "cart" is either empty or only has one ship-to address,
        // then multiship is no longer selected.
        //
        if (isset($this->cart) && count($this->cart) < 2) {
            $this->sessionCleanup();
        }
    }
  
    // -----
    // Called at the start of the shopping_cart function update_product to update the quantity (either up or down)
    // for a product that's presently in the cart.  This processing happens either from the shopping_cart or
    // checkout_multiship page; in either case, the customer must (eventually) return to the checkout_shipping page
    // prior to checkout completion and the multiship shipping calculations will be performed there ... so remove all ship-to
    // details as part of the processing.
    //
    public function updateProduct($prid, $new_quantity, $attributes) 
    {
        if ($this->selected && !empty($new_quantity)) {
            unset($this->details, $this->totals, $this->text_email);
      
            // -----    
            // If the update request did not happen on the checkout_multiship page and the to-be-updated
            // quantity is less than that currently in the cart, remove all references to this product
            // from the multiship 'cart'; shipping for all remaining instances of the product will
            // default to the customer's current shipping address.
            //
            if (isset($_GET['main_page']) && $_GET['main_page'] != FILENAME_CHECKOUT_MULTISHIP) {
                $products_name = zen_get_products_name($prid);
                $in_cart_quantity = $_SESSION['cart']->get_quantity($prid);
                if ($new_quantity < $in_cart_quantity) {
                    $this->removeProduct($prid);
                    $GLOBALS['messageStack']->add_session('header', sprintf(MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY, $products_name), 'caution');
                } elseif ($new_quantity > $in_cart_quantity) {
                    $this->cart[$_SESSION['customer_default_address_id']][$prid] += ($new_quantity - $in_cart_quantity);
                    $GLOBALS['messageStack']->add_session('header', sprintf(MULTISHIP_PRODUCT_INCREASE_SHIP_PRIMARY, $products_name), 'caution');
                }
            }
        }
    }
 
    // -----
    // Called at the start of the shopping_cart function add_cart to add a product to the cart.  If
    // multiship addresses have been previously selected, notify the customer that the product addition
    // will be sent to their "Primary" address and that they can make changes during the checkout process and
    // record this change in the multiship session values.
    //
    public function _checkAddProductMessage($prid, $qty, $attributes) 
    {
        global $messageStack;
        $uprid = zen_get_uprid($prid, $attributes);
        if ($this->selected && $qty != 0 && !$_SESSION['cart']->in_cart($uprid)) {
            $products_name = zen_get_products_name($prid);
            $messageStack->add_session('header', sprintf(MULTISHIP_PRODUCT_ADD_SHIP_PRIMARY, $qty, $products_name), 'caution');
          
            if (!isset($this->cart[$_SESSION['customer_default_address_id']])) {
                $this->cart[$_SESSION['customer_default_address_id']] = array ();
            }

            if (!isset($this->cart[$_SESSION['customer_default_address_id']][$uprid])) {
                $this->cart[$_SESSION['customer_default_address_id']][$uprid] = $qty;
                unset($this->details, $this->totals, $this->text_email);
            }
        }
    } 
  
    // -----
    // Resets the class variables to their initial state.  Used internally as well as by the multiship_observer upon
    // receipt of NOTIFY_HEADER_END_CHECKOUT_PROCESS (issued by the header_php.php file for the checkout_process page, just
    // prior to re-directing to the checkout_success page).
    //
    public function sessionCleanup() 
    {
        $this->debugLog('sessionCleanup!');
        $this->selected = false;
        $this->address2multiship = array();
        unset($this->details, $this->cart, $this->totals, $this->shipping_method, $this->orders_multiship_ids, $this->text_email, $this->noship_address_id);
    }
  
    // -----
    // Provides the plugin's debug-log processing.
    //
    protected function debugLog($message, $include_date = false) 
    {
        if ($this->debug) {
            $header = ($include_date === false) ? '' : (PHP_EOL . date('Y-m-d H:i:s: '));
            $header .= '(' . $GLOBALS['current_page_base'] . ') ';
            error_log("$header$message" . PHP_EOL, 3, $this->logfile);
        }
    }
}
