<?php
/**
 * @package admin
 * @copyright Copyright 2003-2011 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: invoice.php 19136 2011-07-18 16:56:18Z wilt $
*/
// -----
// Modified by lat9 (vinosdefrutastropicales.com) as part of the multiple ship-to addresses plugin
// Copyright 2014-2019, Vinos de Frutas Tropicales
//
require 'includes/application_top.php' ;

require DIR_WS_CLASSES . 'currencies.php';
$currencies = new currencies();

$oID = (int)zen_db_prepare_input($_GET['oID']);

// -----
// Bring in storefront version of the class for zc155 (already baked-in for zc156 and later).
//
if (strpos(PROJECT_VERSION_MINOR, '5.5') === 0) {
    require DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
} else {
    require DIR_WS_CLASSES . 'order.php';
}
$order = new order($oID);

$orders_status_array = array();
$orders_status = $db->Execute(
    "SELECT orders_status_id, orders_status_name
       FROM " . TABLE_ORDERS_STATUS . "
      WHERE language_id = " . (int)$_SESSION['languages_id']
);
while (!$orders_status->EOF) {
    $orders_status_array[$orders_status->fields['orders_status_id']] = $orders_status->fields['orders_status_name'];
    $orders_status->MoveNext();
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<style type="text/css"><!--
.separator-row { background-color: #b4b4b4; }
-->
</style>
<script type="text/javascript" src="includes/menu.js"></script>
<script type="text/javascript" type="text/javascript"><!--
function couponpopupWindow(url) {
  window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=280,screenX=150,screenY=150,top=150,left=150')
}
//--></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">

<!-- body_text //-->
<table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td class="pageHeading"><?php echo nl2br(STORE_NAME_ADDRESS); ?></td>
        <td class="pageHeading" align="right"><?php echo zen_image(DIR_WS_IMAGES . HEADER_LOGO_IMAGE, HEADER_ALT_TEXT); ?></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td colspan="2"><?php echo zen_draw_separator(); ?></td>
      </tr>

<?php
if ($order->billing['name'] != $order->delivery['name'] || $order->billing['street_address'] != $order->delivery['street_address']) {
?>
      <tr>
        <td class="main"><b><?php echo ENTRY_CUSTOMER; ?></b></td>
      </tr>
      <tr>
        <td class="main"><?php echo zen_address_format($order->customer['format_id'], $order->customer, 1, '', '<br>'); ?></td>
      </tr>
<?php 
} 
?>
      <tr>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo ENTRY_SOLD_TO; ?></b></td>
          </tr>
          <tr>
            <td class="main"><?php echo zen_address_format($order->billing['format_id'], $order->billing, 1, '', '<br>'); ?></td>
          </tr>
          <tr>
            <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '5'); ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo $order->customer['telephone']; ?></td>
          </tr>
          <tr>
            <td class="main"><?php echo '<a href="mailto:' . $order->customer['email_address'] . '">' . $order->customer['email_address'] . '</a>'; ?></td>
          </tr>
        </table></td>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main"><b><?php echo ENTRY_SHIP_TO; ?></b></td>
          </tr>
          <tr>
            <td class="main"><?php echo MULTISHIP_MULTIPLE_ADDRESSES; ?></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
  </tr>
  <tr>
    <td class="main"><b><?php echo ENTRY_ORDER_ID . $oID; ?></b></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td class="main"><strong><?php echo ENTRY_DATE_PURCHASED; ?></strong></td>
        <td class="main"><?php echo zen_date_long($order->info['date_purchased']); ?></td>
      </tr>
      <tr>
        <td class="main"><b><?php echo ENTRY_PAYMENT_METHOD; ?></b></td>
        <td class="main"><?php echo $order->info['payment_method']; ?></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
  </tr>
<?php
$currency = $order->info['currency'];
$currency_value = $order->info['currency_value'];
$decimal_places = $currencies->get_decimal_places($currency);
foreach ($order->multiship_info as $multiship_id => $multiship_info) {
    $taxable_check = $db->Execute(
        "SELECT `value` 
           FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . "
          WHERE orders_id = $oID
            AND orders_multiship_id = $multiship_id
            AND class = 'ot_tax' LIMIT 1"
    );
    $no_tax_collected = ($taxable_check->EOF || $taxable_check->fields['value'] == 0);
?>
  <tr class="dataTableHeadingRow">
    <td class="separator-row"><?php echo MULTISHIP_SHIPPED_TO . zen_address_format($multiship_info['info']['address_format_id'], $multiship_info['info'], false, '', ', '); ?></td>
  </tr>
  
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr class="dataTableHeadingRow">
        <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
        <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TAX; ?></td>
        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></td>
        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></td>
        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></td>
        <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></td>
      </tr>
<?php
    foreach ($order->products as $currentProduct) {
        if ($currentProduct['orders_multiship_id'] != $multiship_id) {
            continue;
        }
        $product_price = $currentProduct['final_price'];
        $product_tax = ($no_tax_collected) ? 0 : $currentProduct['tax'];
        $product_qty = $currentProduct['qty'];
        $product_onetime = $currentProduct['onetime_charges'];
      
        if (DISPLAY_PRICE_WITH_TAX_ADMIN == 'true') {
            $priceIncTax = $currencies->format(zen_round(zen_add_tax($product_price, $product_tax), $decimal_places) * $product_qty, true, $currency, $currency_value);
        } else {
            $priceIncTax = $currencies->format(zen_add_tax($product_price, $product_tax) * $product_qty, true, $currency, $currency_value);
        }
?>
      <tr class="dataTableRow">
        <td class="dataTableContent" valign="top" align="right"><?php echo $product_qty; ?>&nbsp;x</td>
        <td class="dataTableContent" valign="top"><?php echo $currentProduct['name']; ?>
<?php
        if (isset($currentProduct['attributes']) && count($currentProduct['attributes']) > 0) {
            foreach ($currentProduct['attributes'] as $currentAttribute) {
?>
          <br /><span class="nobreak"><small>&nbsp;<i> - <?php echo $currentAttribute['option'] . ': ' . nl2br(zen_output_string_protected($currentAttribute['value'])); ?>
<?php
                if ($currentAttribute['price'] != '0') {
                    echo ' (' . $currentAttribute['prefix'] . $currencies->format($currentAttribute['price'] * $product_qty, true, $currency, $currency_value) . ')';
                }
                if ($currentAttribute['product_attribute_is_free'] == '1' and $currentProduct['product_is_free'] == '1') {
                    echo TEXT_INFO_ATTRIBUTE_FREE;
                }
?>
          </i></small></span>
<?php
            }  // END attributes foreach
        }  // current product has attributes
?>
        </td>
        <td class="dataTableContent" valign="top"><?php echo $currentProduct['model']; ?></td>
        <td class="dataTableContent" align="right" valign="top"><?php echo zen_display_tax_value($product_tax); ?>%</td>
        <td class="dataTableContent" align="right" valign="top"><strong><?php echo $currencies->format($product_price, true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format($product_onetime, true, $currency, $currency_value) : ''); ?></strong></td>
        <td class="dataTableContent" align="right" valign="top"><strong><?php echo $currencies->format(zen_add_tax($product_price, $product_tax), true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format(zen_add_tax($product_onetime, $product_tax), true, $currency, $currency_value) : ''); ?></strong></td>
        <td class="dataTableContent" align="right" valign="top"><strong><?php echo $currencies->format(zen_round($product_price, $decimal_places) * $product_qty, true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format($product_onetime, true, $currency, $currency_value) : ''); ?></strong></td>
        <td class="dataTableContent" align="right" valign="top"><strong><?php echo $priceIncTax . ($product_onetime != 0 ? '<br />' . $currencies->format(zen_add_tax($product_onetime, $product_tax), true, $currency, $currency_value) : ''); ?></strong></td>
      </tr>
<?php
    }  // END products foreach
?>
      <tr>
        <td align="right" colspan="8"><table border="0" cellspacing="0" cellpadding="2">
<?php
    foreach ($multiship_info['totals'] as $currentTotal) {
?>
          <tr>
            <td align="right" class="<?php echo str_replace('_', '-', $currentTotal['class']); ?>-Text"><?php echo $currentTotal['title']; ?></td>
            <td align="right" class="<?php echo str_replace('_', '-', $currentTotal['class']); ?>-Amount"><?php echo $currencies->format($currentTotal['value'], false); ?></td>
          </tr>
<?php
    }  // END totals foreach
?>
        </table></td>
      </tr>
    </table></td>
  </tr>
<?php
}  // END multiship address foreach
?>
  <tr class="separator-row">
    <td style="border-top: 1px solid #414141;"><div style="width: 100%"><div style="width: 70%; float: left;">&nbsp;</div><div style="width: 29%; float: right;"><strong><?php echo MULTISHIP_GRAND_TOTALS; ?></strong></div></div></td>
  </tr>
  
  <tr>
    <td align="right"><table border="0" cellspacing="0" cellpadding="2">
<?php
foreach ($order->totals as $currentTotal) {
?>
      <tr>
        <td align="right" class="<?php echo str_replace('_', '-', $currentTotal['class']); ?>-Text"><?php echo $currentTotal['title']; ?></td>
        <td align="right" class="<?php echo str_replace('_', '-', $currentTotal['class']); ?>-Amount"><?php echo $currencies->format($currentTotal['value'], false); ?></td>
      </tr>
<?php
}  // END totals foreach
?>
    </table></td>
  </tr>
<?php
if (ORDER_COMMENTS_INVOICE > 0) {
?>
  <tr>
    <td class="main"><table border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></td>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></td>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
      </tr>
<?php
    $limit = (ORDER_COMMENTS_INVOICE == 1) ? ' LIMIT 1' : '';
    $orders_history = $db->Execute(
        "SELECT orders_status_id, date_added, comments
           FROM " . TABLE_ORDERS_STATUS_HISTORY . "
          WHERE orders_id = $oID AND customer_notified >= 0
       ORDER BY date_added$limit"
    );

    if ($orders_history->RecordCount() > 0) {
        while (!$orders_history->EOF) {
?>
      <tr>
        <td class="smallText" align="center" valign="top"><?php echo zen_datetime_short($orders_history->fields['date_added']); ?></td>
        <td class="smallText" valign="top"><?php echo $orders_status_array[$orders_history->fields['orders_status_id']]; ?></td>
        <td class="smallText" valign="top"><?php echo ($orders_history->fields['comments'] == '' ? TEXT_NONE : nl2br(zen_db_output($orders_history->fields['comments']))) . '&nbsp;'; ?></td>
      </tr>
<?php
            $orders_history->MoveNext();

        }
    } else {
?>
      <tr>
        <td class="smallText" colspan="5"><?php echo TEXT_NO_ORDER_HISTORY; ?></td>
      </tr>
<?php
    }
?>
    </table></td>
  </tr>
<?php
} // order comments
?>
</table>
<!-- body_text_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>