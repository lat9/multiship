<?php
/**
 * @package admin
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: packingslip.php 15788 2010-04-02 10:44:40Z drbyte $
*/
// -----
// Modified by lat9 (vinosdefrutastropicales.com) as part of the multiple ship-to addresses plugin
// Copyright 2014-2017, Vinos de Frutas Tropicales
//
require 'includes/application_top.php';

require DIR_WS_CLASSES . 'currencies.php';
$currencies = new currencies();

$oID = (int)zen_db_prepare_input($_GET['oID']);
include DIR_WS_CLASSES . 'order.php';
$order = new order($oID);

// prepare order-status pulldown list
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
<script language="javascript" src="includes/menu.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF">
<!-- body_text //-->
<?php
$billing_name = $order->billing['name'];
$billing_street_address = $order->billing['street_address'];
foreach ($order->multiship_info as $multiship_id => $multiship_info) {
?>
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
    if ($billing_name != $multiship_info['info']['name'] || $billing_street_address != $multiship_info['info']['street_address']) {
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
            <td class="main"><?php echo zen_address_format($multiship_info['info']['address_format_id'], $multiship_info['info'], 1, '', '<br />'); ?></td>
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
  <tr>
    <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr class="dataTableHeadingRow">
        <td class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
        <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></td>
      </tr>
<?php
    foreach ($order->products as $currentProduct) {
        if ($currentProduct['orders_multiship_id'] != $multiship_id) {
        continue;
        }
?>
      <tr class="dataTableRow">
        <td class="dataTableContent" valign="top" align="right"><?php echo $currentProduct['qty']; ?>&nbsp;x</td>
        <td class="dataTableContent" valign="top"><?php echo $currentProduct['name']; ?>
<?php

        if (isset($currentProduct['attributes']) && (sizeof($currentProduct['attributes']) > 0)) {
            foreach ($currentProduct['attributes'] as $currentAttribute) {
?>
          <br /><span class="nobreak"><small>&nbsp;<i> - <?php echo $currentAttribute['option'] . ': ' . nl2br(zen_output_string_protected($currentAttribute['value'])); ?></i></small></span>
<?php
            }
?>
        </td>
        <td class="dataTableContent" valign="top"><?php echo $currentProduct['model']; ?></td>
<?php
        }
?>
      </tr>
<?php
    }
?>
    </table></td>
  </tr>
<?php
    if (ORDER_COMMENTS_PACKING_SLIP > 0) {
?>
  <tr>
    <td class="main"><table border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_DATE_ADDED; ?></strong></td>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_STATUS; ?></strong></td>
        <td class="smallText" align="center"><strong><?php echo TABLE_HEADING_COMMENTS; ?></strong></td>
      </tr>
<?php
        $limit = (ORDER_COMMENTS_PACKING_SLIP == 1) ? ' LIMIT 1' : '';
        $orders_history = $db->Execute(
            "SELECT orders_status_id, date_added, comments
               FROM " . TABLE_ORDERS_STATUS_HISTORY . "
              WHERE orders_id = $oID AND customer_notified >= 0
           ORDER BY date_added$limit"
        );

        if (!$orders_history->EOF) {
            while (!$orders_history->EOF) {
?>
      <tr>
        <td class="smallText" align="center" valign="top"><?php echo zen_datetime_short($orders_history->fields['date_added']); ?></td>
        <td class="smallText" valign="top"><?php echo $orders_status_array[$orders_history->fields['orders_status_id']]; ?></td>
        <td class="smallText" valign="top"><?php echo ($orders_history->fields['comments'] == '' ? TEXT_NONE : nl2br(zen_db_output($orders_history->fields['comments']))); ?>&nbsp;</td>
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
<br style="page-break-before: always;" />
<?php
}  // END loop processing each sub-order
?>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>