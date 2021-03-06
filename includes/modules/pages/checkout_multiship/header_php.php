<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_MULTISHIP');
require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

// -----
// If there's nothing (left) in the customer's cart, redirect them back to the shopping_cart page.
// If there's only one item left in the cart (or the multiship class isn't set), then this cart is not 
// a multiship candidate; redirect the customer back to the checkout_shipping page.
//
$cart_contents = $_SESSION['cart']->count_contents();
if ($cart_contents <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
} elseif ($cart_contents == 1 || !isset($_SESSION['multiship'])) {
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING));
}

// if the customer is not logged on, redirect them to the login page
if (empty($_SESSION['customer_id'])) {
    $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_MULTISHIP));
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
} else {
    // validate customer
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
}

// if no shipping method has been selected, redirect the customer to the shipping method selection page
if (!isset($_SESSION['shipping'])) {
    $zco_notifier->notify('CHECKOUT_MULTISHIP_SHIPPING_NOT_SELECTED');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}
if (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] == 'free_free' && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') && $_SESSION['cart']->get_content_type() != 'virtual' && $_SESSION['cart']->show_total() < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
    $zco_notifier->notify('CHECKOUT_MULTISHIP_FREE_SHIPPING_MISMATCH');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// Addressing a corner-case scenario.  If a customer has entered some multiple ship-to addresses
// and subsequently changed the shipping-method so that one or more of those previously-entered
// addresses are no longer valid, we need a way to let the 'checkout_shipping' processing "know"
// that it should ignore those invalid addresses, from a 'multiship' standpoint, so that the 
// customer can make their change/correction.
//
if (isset($_GET['address_correction'])) {
    $ms_cart = $_SESSION['multiship']->getCart();
    foreach ($ms_cart as $address_id => $info) {
        if (isset($info['address-error'])) {
            $_SESSION['multiship_new_shipping'] = true;
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
        }
    }
}

// -----
// If the page's form has been posted, see what the customer wants to do.  The form can be posted either by
//
// - Changing one of the ship-to address selections (an onchange submission)
// - Clicking the "Update" button after (possibly) changing one or more of the item quantities.  The default
//   quantity for each item is '1', so if an item's quantity has been set to 0, the cart's quantity is reduced
//   by 1.  If the item's quantity is set to a positive, non-1 number, then the cart's quantity is increased
//   by that amount (less the '1').
//
if (isset($_POST['securityToken'])) {
    // -----
    // If the update button was pressed, then one or more of the item quantities might have changed.
    //
    if (isset($_POST['update_x'])) {
        for ($i = 0, $n = count($_POST['qty']); $i < $n; $i++) {
            $qty = (int)$_POST['qty'][$i];
            if ($qty != 1) {
                $prid = $_POST['prid'][$i];
                $current_qty = $_SESSION['cart']->get_quantity($prid);
            
                $attributes = (isset($_SESSION['cart']->contents[$prid]['attributes'])) ? $_SESSION['cart']->contents[$prid]['attributes'] : array ();
                foreach ($attributes as $option => $value) {
                    if ($value == 0) {
                        unset($attributes[$option]);
                        $attributes[TEXT_PREFIX . $option] = $_SESSION['cart']->contents[$prid]['attributes_values'][$option];
                    }
                }

                $current_qty = ($qty <= 0) ? $current_qty : $current_qty + $qty;       
                $_SESSION['cart']->update_quantity($prid, $current_qty-1, $attributes);
            
                if ($qty <= 0) {
                    unset($_POST['prid'][$i], $_POST['qty'][$i], $_POST['address'][$i]);
                } else {
                    $sendto = $_POST['address'][$i];
                    for ($j = 0, $m = $qty - 1; $j < $m; $j++) {
                        $_POST['prid'][] = $prid;
                        $_POST['qty'][] = 1;
                        $_POST['address'][] = $sendto;
                    }
                }
            }
        }
    }
    
    // -----
    // Check to see that the ship-to addresses currently selected are 'compatible' with
    // the currently-selected shipping method.
    //
    if (!$_SESSION['multiship']->addressValidation($_POST['address'])) {
        $messageStack->add('multiship', ERROR_ADDRESS_NOT_VALID_FOR_SHIPPING, 'error');
    } else {
        // -----
        // Record the customer's multiship selection in the session variable.
        //
        $_SESSION['multiship']->setMultiship($_POST['address'], $_POST['prid']);
    }
}

$multiship_selected = $_SESSION['multiship']->isSelected();

// -----
// Build up address list input to create product-by-product drop-down address selection.
//
$addresses = $db->Execute(
    "SELECT address_book_id 
       FROM " . TABLE_ADDRESS_BOOK . " 
      WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
      ORDER BY address_book_id ASC"
);
if ($addresses->EOF) {
    zen_redirect(zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'));
}
$multishipAddresses = array();
while (!$addresses->EOF) {
    $multishipAddresses[] = array( 
        'id' => $addresses->fields['address_book_id'],
        'text' => str_replace("\n", ', ', zen_address_label($_SESSION['customer_id'], $addresses->fields['address_book_id']))
    );
    $addresses->MoveNext();
}

// -----
// Build up the products' list, one entry for each item currently in the cart, so each entry is associated with a quantity of 1
// for the specified product.
//
$products = $_SESSION['cart']->get_products();
$products_onetime_charges = false;
for ($i = 0, $productsArray = array(), $n = count($products); $i < $n; $i++) {
    $currentProduct = array( 
        'id' => $products[$i]['id'],
        'name' => $products[$i]['name'],
        'price' => $currencies->format($products[$i]['final_price']),
     );
    if ($products[$i]['onetime_charges'] != 0) {
        $products_onetime_charges = true;
        $currentProduct['price'] .= '<span class="onetime_charge">' . ONETIME_CHARGE_INDICATOR . '</span>';
    
    }
  
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
        $options_order_by = (PRODUCTS_OPTIONS_SORT_ORDER == '0') ? ' ORDER BY LPAD(po.products_options_sort_order,11,"0")' : ' ORDER BY po.products_options_name';
        $currentProduct['attributes'] = array();
        foreach ($products[$i]['attributes'] as $option => $value) {
            $attributes = 
                "SELECT po.products_options_name, pov.products_options_values_name
                   FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        INNER JOIN " . TABLE_PRODUCTS_OPTIONS . " po
                            ON po.products_options_id = pa.options_id
                           AND po.language_id = :languageID
                        INNER JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                            ON pov.products_options_values_id = pa.options_values_id
                           AND pov.language_id = :languageID
                     WHERE pa.products_id = :productsID
                     AND pa.options_id = :optionsID
                     AND pa.options_values_id = :optionsValuesID$options_order_by";

            $attributes = $db->bindVars($attributes, ':productsID', $products[$i]['id'], 'integer');
            $attributes = $db->bindVars($attributes, ':optionsID', $option, 'integer');
            $attributes = $db->bindVars($attributes, ':optionsValuesID', $value, 'integer');
            $attributes = $db->bindVars($attributes, ':languageID', $_SESSION['languages_id'], 'integer');
            $attributes_values = $db->Execute($attributes);

            if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
                $attr_value = htmlspecialchars($products[$i]['attributes_values'][$option], ENT_COMPAT, CHARSET, TRUE);
            } else {
                $attr_value = $attributes_values->fields['products_options_values_name'];
            }

            $currentProduct['attributes'][] = array( 
                'name' => $attributes_values->fields['products_options_name'], 
                'value' => $attr_value
            );
            $zco_notifier->notify("CHECKOUT_MULTISHIP_ATTRIBUTES ($option => $value):", $attributes, $attributes_values, $currentProduct, $attr_value);
        }
    }
    for ($j = 0; $j < $products[$i]['quantity']; $j++) {
        $productsArray[] = $currentProduct;
    }
}

// -----
// Now, add the ship-to addresses to be associated with each product, keeping in mind that an instance of the
// product could have been either added or removed during prior processing.
//
$multiship_details = $_SESSION['multiship']->getCart();
$invalid_address_present = false;
for ($i = 0, $n = count($productsArray); $i < $n; $i++) {
    $productsArray[$i]['sendto'] = $_SESSION['sendto'];
    $prid = $productsArray[$i]['id'];
    $productsArray[$i]['is_physical'] = $_SESSION['multiship']->cartItemIsPhysical($prid);
    foreach ($multiship_details as $address_id => &$currentProducts) {
        if (isset($currentProducts['address-error'])) {
            $invalid_address_present = true;
        }
        if (isset($currentProducts[$prid]) && $currentProducts[$prid] > 0) {
            $productsArray[$i]['sendto'] = $address_id;
            $currentProducts[$prid]--;
            break;
        }
    }
    unset($currentProducts);
}

$checkout_shipping_link = ($invalid_address_present) ? zen_href_link(FILENAME_CHECKOUT_MULTISHIP, 'address_correction', 'SSL') : zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL');

if ($invalid_address_present) {
    $messageStack->add('multiship', sprintf(ERROR_ADDRESS_INVALID_FOR_SHIPPING_METHOD, MULTISHIP_ICON_NO_SHIP), 'error');
}

$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2);

//$flag_disable_right = $flag_disable_left = true;

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_MULTISHIP');
