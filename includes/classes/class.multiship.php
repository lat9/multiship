<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

class multiship extends base 
{
    var $address2multiship;
    var $selected;
    var $offer;

    // -----
    // Class constructor.  This class is created via auto_load as $_SESSION['multicart'].
    //
    public function __construct() 
    {
        $this->selected = false;
        $this->offer = false;
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
    function set_multiship ($address_array, $prid_array) 
    {
    $this->selected = false;
    $multiship_values = array();
    $address = false;
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
        $multiship_values[$address]['has_physical'] |= $this->cart_item_is_physical ($prid);
        
      } else {
        $multiship_values[$address] = array();
        $multiship_values[$address]['has_physical'] = $this->cart_item_is_physical ($prid);
        
      }
      
      if (isset($multiship_values[$address][$prid])) {
        $multiship_values[$address][$prid]++;
         
      } else {
        $multiship_values[$address][$prid] = 1;
        
      }
      
    }  // END foreach inspecting each address/prid pair
    
    if ($this->selected) {
      if ($_SESSION['cart']->get_content_type() == 'mixed') {
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
          unset ($virtual_products['has_physical'], $multiship_values[$virtual_product_address_id]);
          if (isset($multiship_values[$_SESSION['customer_default_address_id']])) {
            $physical_product_address_id = $_SESSION['customer_default_address_id'];
          }
          if (sizeof($multiship_values) == 1) {
            $_SESSION['sendto'] = $physical_product_address_id;
            $this->selected = false;
            unset($this->cart);
            
          } else {
            $multiship_values[$physical_product_address_id] = array_merge ($multiship_values[$physical_product_address_id], $virtual_products);
            $this->cart = $multiship_values;
            
          }
          
        } else {
          $this->cart = $multiship_values;
          
        }
        
      } else {
        $this->cart = $multiship_values;

      }
      
    } else {
      unset ($this->cart);
      
    }
    
    return $this->selected;
  }
  
    // -----
    // Returns a binary flag that indicates whether or not the customer has selected multiple
    // shipping addresses for the current order.
    //
    public function is_selected() 
    {
        return $this->selected;
    }
  
    // -----
    // Returns a binary flag that indicates whether the customer can be offered multiple shipping
    // addresses for this order, as set by the _prepare function's processing during the
    // checkout_confirmation page.
    //
    public function can_offer() 
    {
        return $this->offer;
    }
  
    // -----
    // Returns an array that contains the details of the multiple shipping addresses.  The
    // primary index for the array is the address_id to which the array of products is to
    // be sent.
    //
    public function get_details() 
    {
        return (isset($this->details)) ? $this->details : array();
    }

    // -----
    // Returns an array of order_total values that have been summed for all the sub-orders.
    //
    function get_totals() 
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
    // Returns the current multiship "cart" contents.
    //
    public function get_cart() 
    {
        return (isset($this->cart)) ? $this->cart : array();
    }
  
    // -----
    // Returns the image to be associated with an unshippable address.
    //
    public function get_noship_image($address_id = '') 
    {
        global $template, $current_page_base;
        return ($address_id === '' || (isset($this->noship_address_id) && $address_id === $this->noship_address_id)) ? zen_image($template->get_template_dir(ICON_MULTISHIP_NOSHIP, DIR_WS_TEMPLATE, $current_page_base, 'images/icons') . '/' . ICON_MULTISHIP_NOSHIP, ICON_MULTISHIP_NOSHIP_ALT) : '';
    }
    
    // -----
    // Returns a boolean flag to indicate whether or not an item presently in the cart is a physical item.
    //
    function cart_item_is_physical($prid) 
    {
        global $db;
        $is_physical = false;
        if (isset($_SESSION['cart']->contents[$prid])) {
            $pID = (int)zen_get_prid ($prid);
            $virtual_check = $db->Execute(
                "SELECT products_virtual 
                   FROM " . TABLE_PRODUCTS . " 
                  WHERE products_id = $pID 
                  LIMIT 1"
            );
            $is_physical = ($virtual_check->fields['products_virtual'] == 0);
            if ($is_physical && isset($_SESSION['cart']->contents[$prid]['attributes']) && is_array($_SESSION['cart']->contents[$prid]['attributes'])) {
                foreach ($_SESSION['cart']->contents[$prid]['attributes'] as $option_id => $value_id) {
                    $download_count = $db->Execute(
                        "SELECT count(*) as total 
                           FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa, " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                          WHERE pa.products_id = $pID
                            AND pa.options_values_id = $value_id
                            AND pa.products_attributes_id = pad.products_attributes_id"
                    );
                    if ($download_count->fields['total'] > 0) {
                        $is_physical = false;
                        break;
                    }
                }  // END per-attribute foreach
            }
        }
        return $is_physical;
    }
  
    // --------------------------------------------------------------------------
    //                     I N T E R N A L   F U N C T I O N S
    //---------------------------------------------------------------------------

    // -----
    // Called by the multiship_observer class, upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER
    // (issed by the order class).
    //
    protected function _createOrderHeader($order_info_array) 
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
                zen_db_perform (TABLE_ORDERS_MULTISHIP, $sql);
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
                    zen_db_perform (TABLE_ORDERS_MULTISHIP_TOTAL, $sql);
                }
            }
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM
    // (issued by the order class).  This gives us an opportunity to modify the overall order's total for the current
    // order-total class.
    //
    protected function _createOrderFixupTotal ($orders_totals_array) 
    {
        global $db, $currencies;
        if (is_array($this->totals) && isset($this->totals[$orders_totals_array['class']])) {
            $this->_debugLog('_createOrderFixupTotal: start', array ('in' => $orders_totals_array, 'totals' => $this->totals));
            $currentTotal = $this->totals[$orders_totals_array['class']];
            $insert_id = $db->Insert_ID();
            $db->Execute(
                "UPDATE " . TABLE_ORDERS_TOTAL . " 
                    SET text = '" . $currencies->format($currentTotal) . "', 
                        value = " . $currentTotal . " 
                  WHERE orders_total_id = $insert_id");
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM
    // (issued by the order class).  This is called once for each product in the current session's cart.
    //
    protected function _createOrderAddProducts($orders_products_array) {
        global $db, $currencies;
        $this->_debugLog('_createOrderAddProducts: start', array ('this' => $this));

        // -----
        // If the current order has multiple shipping addresses ...
        //
        if ($this->selected) {
            $orders_products_id = $orders_products_array['orders_products_id'];
            unset($orders_products_array['orders_products_id']);
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
                            $this->_debugLog('_createOrderAddProducts: product quantity went negative.', array ( 'sql' => $orders_products_array, 'details' => $this->details ), true);
                        }
                        if (!$initial_modification) {
                            $initial_modification = true;
                            $db->Execute(
                                "UPDATE " . TABLE_ORDERS_PRODUCTS . " 
                                    SET products_quantity = " . $product_qty . ",
                                        products_tax = " . $currentProduct['tax'] . ",
                                        orders_multiship_id = " . $this->address2multiship[$address_id] . "
                                  WHERE orders_products_id = $orders_products_id"
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
        $this->_debugLog('_createOrderAddProducts: end', array ('this' => $this));
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM
    // (issued by the order class).
    //
    protected function _createOrderAddAttributes($products_attributes_array) 
    {
        if ($this->selected) {
            foreach ($this->orders_multiship_ids as $orders_products_id) {
                $products_attributes_array['orders_products_id'] = $orders_products_id;
                zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $products_attributes_array);
            }
        }
    }
  
    protected function _insertAttributesText($order) 
    {
        if ($this->selected) {
            foreach ($this->details as $address_id => &$currentInfo) {
                if ($currentInfo['need_attributes']) {
                    $currentInfo['products_ordered_text'] .= $order->products_ordered_attributes . "\n";
                    $currentInfo['products_ordered_html'] = sprintf($currentInfo['products_ordered_html'], nl2br($order->products_ordered_attributes));
                    $currentInfo['need_attributes'] = false;
                }
            }
            unset ($currentInfo);
        }
    }
  
    // -----
    // Called by the multiship_observer upon receipt of NOTIFY_ORDER_EMAIL_BEFORE_PRODUCTS (issued by the order class, after the
    // email lead-in information is built).  Save this information for use in the modification of the email for a multiple ship-to
    // order's processing.
    //
    protected function _saveEmailHeader($text_email, $html_email) 
    {
        $this->text_email = $text_email;
    }
  
    // -----
    // Called by the multiship_observer upon receipt of the NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND2 (issued by the order class, 
    // just before sending the order confirmation email).  If the current order has multiple shipping addresses, this provides the
    // fix-ups required for the text and HTML email contents.
    //
    function _fixupOrderEmail($order, $parmArray, &$email_order, &$html_email) 
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
    // Called by the _prepare function, below, to count the number of PHYSICAL products present in the
    // current session's cart.
    //
    protected function _cart_physical_items() 
    {
        $num_physical_items = 0;
        foreach ($_SESSION['cart']->contents as $prid => $current_product) {     
            if ($this->cart_item_is_physical($prid)) {
                $num_physical_items += $current_product['qty'];
            }
        }  // END per-product foreach
        return $num_physical_items;
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION.
    // If the order currently contains multiple ship-to addresses, then the quantities might have changed
    // and, thus, the shopping cart's cartID value.  Need the cart's value to match that in the session
    // or the customer will be redirected back to the checkout_shipping page.
    //
    protected function _fixCartID() 
    {
        if ($this->selected) {
            $_SESSION['cartID'] = $_SESSION['cart']->cartID;
        }
    }
  
    // -----
    // Called by the multiship_observer class upon receipt of NOTIFY_HEADER_END_CHECKOUT_CONFIRMATION
    // (issued by the header_php.php file for the checkout_confirmation page).
    //
    protected function _prepare() 
    {
        global $order, ${$_SESSION['payment']|, $shipping_modules, $currencies, $total_weight, $total_count;
        $this->_debugLog('_prepare: start', $this);

        // -----
        // Set the binary flag that indicates whether or not the multiple-shipping selection should be offered to the customer.
        // This selection is offered if all of the following are true:
        //
        // 1) The current payment method supports multiple shipping addresses and the shop-owner has enabled multiple shipping addresses.
        // 2) There is more than one "physical" item in the customer's cart.
        // 3) The current customer is not checking out via COWOA.
        // 4) The current customer is not checking out via a PayPal Express Checkout guest account.
        //
        $this->offer = ( method_exists(${$_SESSION['payment']}, 'multiple_shipping_addresses') && ${$_SESSION['payment']}->multiple_shipping_addresses() && 
                         $this->_cart_physical_items() > 1 &&
                         !(isset($_SESSION['COWOA']) && $_SESSION['COWOA'] == 1) && 
                         !isset($_SESSION['customer_guest_id']) );

        if (!$this->offer) {
            $this->_cleanup();
        }

        // -----
        // If the customer has selected multiple shipping addresses on the checkout_multiship page ...
        //
        if ($this->selected) {
            // -----
            // Make sure that the currently-selected shipping method is "sane".  The 'id' element must be set and it must contain an
            // underscore, separating the shipping method's class name from the currently-selected method.
            //
            $shipping_error = false;
            if (!isset($_SESSION['shipping']['id'])) {
                $shipping_error = true;
            } else {
                $shipping_info = explode ('_', $_SESSION['shipping']['id']);
                if (count($shipping_info) != 2) {
                    $shipping_error = true;
                }
            }
            if ($shipping_error) {
                $this->_debugLog('_prepare: Redirecting to checkout_shipping, invalid shipping method.', $_SESSION['shipping']);
                zen_redirect(zen_href_link(FILENAME_SHIPPING, '', 'SSL'));
            }
          
            // -----
            // Save the current contents of both the shopping cart and the default sendto-address.  These values will be
            // manipulated to provide the shipping costs on a per-shipto address basis.
            //
            $saved_cart_contents = $_SESSION['cart']->contents;
            $saved_sendto = $_SESSION['sendto'];
            $saved_shipping_cost = $_SESSION['shipping']['cost'];
          
            // -----
            // Loop through each of the ship-to addresses that were previously gathered, populating the shopping-cart object
            // with the products to be sent to that address and setting the session's sendto address ID to that value.  This
            // allows the currently-selected shipping module to be used to calculate this sub-cart's shipping costs.
            //
            $this->totals = array();
            $multiship_info = array();
            $multiship_grand_total = 0;
            foreach ($this->cart as $address_id => $products) {
                $multiship_info[$address_id] = array();
                $multiship_info[$address_id]['address'] = zen_address_label($_SESSION['customer_id'], $address_id, false, '', ', ');
                $_SESSION['cart']->contents = array();
                foreach ($products as $prid => $qty) {
                    if ($prid == 'has_physical') {
                        continue;
                    }
                    $_SESSION['cart']->contents[$prid] = array( 'qty' => $qty );
                    if (isset($saved_cart_contents[$prid]['attributes'])) {
                        $_SESSION['cart']->contents[$prid]['attributes'] = $saved_cart_contents[$prid]['attributes'];
                    }
                    if (isset ($saved_cart_contents[$prid]['attributes_values'])) {
                        $_SESSION['cart']->contents[$prid]['attributes_values'] = $saved_cart_contents[$prid]['attributes_values'];
                    }
                }
            
                // -----
                // Get the shipping quote for this ship-to address.  If the quote comes back empty, then this shipping method is
                // not supported for the selected products and/or address.  Redirect back to the multiship page.
                //
                $_SESSION['sendto'] = $address_id;
                $total_weight = $_SESSION['cart']->show_weight();
                $total_count = $_SESSION['cart']->count_contents();
            
                // -----
                // Let the order class do the "heavy lifting" in the pulling in of the product list and delivery address 
                // information for the current shipping address.
                //
                $order = new order;
                $multiship_info[$address_id]['products'] = $order->products;
                $multiship_info[$address_id]['delivery'] = $order->delivery;
                $multiship_info[$address_id]['content_type'] = $order->content_type;
                $multiship_info[$address_id]['info'] = $order->info;
            
                // -----
                // Pull in the httpClient class for those shipping methods (like UPS) that require it!
                //
                require_once DIR_WS_CLASSES . 'http_client.php'; 
            
                $shipping_quote = $shipping_modules->quote($shipping_info[1], $shipping_info[0]);
                $this->_debugLog("_prepare: quote received for $address_id", array('weight' => $total_weight, 'info' => $shipping_info, 'quote' => $shipping_quote));
                if (!is_array($shipping_quote) || count($shipping_quote) == 0) {
                    $this->_debugLog("_prepare: no shipping quote for $address_id, redirecting the checkout_multiship");
                    $this->noship_address_id = $address_id;
                    $_SESSION['cart']->contents = $saved_cart_contents;
                    $_SESSION['sendto'] = $saved_sendto;
                    $_SESSION['shipping']['cost'] = $saved_shipping_cost;
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL'));
                }
                
                $shipping_cost = $shipping_quote[0]['methods'][0]['cost'];
                $multiship_info[$address_id]['info']['shipping_cost'] = $shipping_cost;
                $_SESSION['shipping']['cost'] = $shipping_cost;
                $shipping_class = $shipping_info[0];
                global $$shipping_class;
                $this->shipping_method = $$shipping_class->title;
                $shipping_method = $shipping_quote[0]['module'] . ' (' . $shipping_quote[0]['methods'][0]['title'] . ')';
                $multiship_info[$address_id]['info']['shipping_method'] = $shipping_method;

                // -----
                // Now that the shipping cost for this sub-order has been calculated, let the order class do the "heavy lifting"
                // in the pulling in of the product list and delivery address information for the current shipping address.
                //
                $order = new order;
                $multiship_info[$address_id]['products'] = $order->products;
                $multiship_info[$address_id]['delivery'] = $order->delivery;
                $multiship_info[$address_id]['content_type'] = $order->content_type;
                $multiship_info[$address_id]['info'] = $order->info;

                $order_total_modules = new order_total;
                $order_total_modules->collect_posts();
                $order_total_modules->pre_confirmation_check();
                if (MODULE_ORDER_TOTAL_INSTALLED) {
                    $multiship_info[$address_id]['totals'] = $order_total_modules->process();
                    foreach ($multiship_info[$address_id]['totals'] as &$currentTotal) {
                        $code = $currentTotal['code'];
                        if ($code == 'ot_shipping') {
                            $currentTotal['value'] = $shipping_quote[0]['methods'][0]['cost'];
                            $currentTotal['text'] = $currencies->format($currentTotal['value'], true, $order->info['currency'], $order->info['currency_value']);
                            $currentTotal['title'] = $shipping_method;
                        }
                        if (!isset($this->totals[$code])) {
                            $this->totals[$code] = 0;
                        }
                        $this->totals[$code] += $currentTotal['value'];
                    }
                    unset ($currentTotal);
                }
            }

            $this->details = $multiship_info;
          
            $_SESSION['cart']->contents = $saved_cart_contents;
            $_SESSION['sendto'] = $saved_sendto;
            $_SESSION['shipping']['cost'] = $saved_shipping_cost;
        }  // Customer has chosen multiple ship-to addresses
        $this->_debugLog('_prepare: end', $this);
    }
  
    // -----
    // Called at the start of the shopping_cart class' "remove" processing.  Removes all references to the
    // specified prid from the multiship session data.  Since this action is invoked from the shopping_cart
    // page and the customer will need to re-enter the checkout_confirmation page to continue, just clear
    // out any multiship details that were previously calculated since the confirmation page's re-entry
    // will result in a recalculation anyway.
    //
    protected function _removeProduct($prid) 
    {
        // -----
        // First, remove all multiship class elements associated with the ship-to details.
        //
        unset($this->details, $this->totals, $this->text_email);

        // -----
        // Next, go throught the multiship "cart" contents, removing all references to the product.  If, after
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
            $this->_cleanup();
        }
    }
  
    // -----
    // Called at the start of the shopping_cart function update_product to update the quantity (either up or down)
    // for a product that's presently in the cart.  This processing happens either from the shopping_cart or
    // checkout_multiship page; in either case, the customer must (eventually) return to the checkout_confirmation page
    // prior to checkout completion and the multiship shipping calculations will be performed there ... so remove all ship-to
    // details as part of the processing.
    //
    protected function _updateProduct($prid, $new_quantity, $attributes) 
    {
        global $messageStack;

        if (!empty($new_quantity)) {
            unset ($this->details, $this->totals, $this->text_email);
      
            // -----    
            // If the update request did not happen on the checkout_multiship page and the to-be-updated
            // quantity is less than that currently in the cart, remove all references to this product
            // from the multiship 'cart'; shipping for all remaining instances of the product will
            // default to the customer's current shipping address.
            //
            if (isset($_GET['main_page']) && $_GET['main_page'] != FILENAME_CHECKOUT_MULTISHIP) {
                $products_name = zen_get_products_name ($prid);
                if ($new_quantity < $_SESSION['cart']->get_quantity ($prid)) {
                    $this->_removeProduct ($prid);
                    $messageStack->add_session('header', sprintf (MULTISHIP_PRODUCT_DECREASE_SHIP_PRIMARY, $products_name), 'caution');
                } elseif ($new_quantity > $_SESSION['cart']->get_quantity ($prid)) {
                    $this->cart[$_SESSION['customer_default_address_id']][$prid] += ($new_quantity - $_SESSION['cart']->get_quantity ($prid));
                    $messageStack->add_session('header', sprintf (MULTISHIP_PRODUCT_INCREASE_SHIP_PRIMARY, $products_name), 'caution');
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
    protected function _checkAddProductMessage($prid, $qty, $attributes) 
    {
        global $messageStack;
        $uprid = zen_get_uprid ($prid, $attributes);
        if ($this->selected && $qty != 0 && !$_SESSION['cart']->in_cart($uprid)) {
            $products_name = zen_get_products_name($prid);
            $messageStack->add_session('header', sprintf (MULTISHIP_PRODUCT_ADD_SHIP_PRIMARY, $qty, $products_name), 'caution');
          
            if (!isset($this->cart[$_SESSION['customer_default_address_id']])) {
                $this->cart[$_SESSION['customer_default_address_id']] = array ();
            }

            if (!isset ($this->cart[$_SESSION['customer_default_address_id']][$uprid])) {
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
    protected function _cleanup() 
    {
        $this->selected = false;
        $this->offer = false;
        $this->address2multiship = array();
        unset($this->details, $this->cart, $this->totals, $this->shipping_method, $this->orders_multiship_ids, $this->text_email, $this->noship_address_id);
    }
  
    // -----
    // Provides a common notifier-trace log, optionally issuing an error if a fatal, non-recoverable interface
    // error is detected.
    //
    function _debugLog($message, $extras = array(), $die = false) 
    {
        $this->notify($message, $extras);

        if ($die) {
            trigger_error ($message . (zen_not_null($extras)) ? print_r($extras, true) : '', E_USER_ERROR);
            die();
        }
    }
}