<?php
/**
 * Page Template
 *
 * Loaded automatically by index.php?main_page=account_edit.<br />
 * Displays information related to a single specific order
 *
 * @package templateSystem
 * @copyright Copyright 2003-2011 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_account_history_info_default.php 19103 2011-07-13 18:10:46Z wilt $
 */
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
?>
<div class="centerColumn" id="accountHistInfo">

  <div class="forward"><?php echo HEADING_ORDER_DATE . ' ' . zen_date_long($order->info['date_purchased']); ?></div>
  <br class="clearBoth" />
  <h2 id="orderHistoryInfoHeader"><?php echo HEADING_TITLE . ORDER_HEADING_DIVIDER . sprintf(HEADING_ORDER_NUMBER, $_GET['order_id']); ?></h2>
  
  <div id="myAccountShipInfo" class="floatingBox back">
<?php
if ($order->delivery != false) {
?>
    <h3><?php echo HEADING_DELIVERY_ADDRESS; ?></h3>
    <address><?php echo MULTISHIP_MULTIPLE_ADDRESSES; ?></address>
<?php
}
?>

<?php
if (zen_not_null($order->info['shipping_method'])) {
?>
    <h4><?php echo HEADING_SHIPPING_METHOD; ?></h4>
    <div><?php echo $order->info['shipping_method']; ?></div>
<?php } else { // temporary just remove these 4 lines ?>
    <div>WARNING: Missing Shipping Information</div>
<?php
}
?>
  </div>

  <div id="myAccountPaymentInfo" class="floatingBox forward">
    <h3><?php echo HEADING_BILLING_ADDRESS; ?></h3>
    <address><?php echo zen_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br />'); ?></address>

    <h4><?php echo HEADING_PAYMENT_METHOD; ?></h4>
    <div><?php echo $order->info['payment_method']; ?></div>
  </div>
  <br class="clearBoth" />
<?php
require($template->get_template_dir('tpl_modules_multiship.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_modules_multiship.php');

/**
 * Used to display any downloads associated with the cutomers account
 */
if (DOWNLOAD_ENABLED == 'true') {
    require($template->get_template_dir('tpl_modules_downloads.php',DIR_WS_TEMPLATE, $current_page_base,'templates'). '/tpl_modules_downloads.php');
}

/**
 * Used to loop thru and display order status information
 */
if (count($statusArray)) {
?>

<table border="0" width="100%" cellspacing="0" cellpadding="0" id="myAccountOrdersStatus" summary="Table contains the date, order status and any comments regarding the order">
<caption><h2 id="orderHistoryStatus"><?php echo HEADING_ORDER_HISTORY; ?></h2></caption>
    <tr class="tableHeading">
        <th scope="col" id="myAccountStatusDate"><?php echo TABLE_HEADING_STATUS_DATE; ?></th>
        <th scope="col" id="myAccountStatus"><?php echo TABLE_HEADING_STATUS_ORDER_STATUS; ?></th>
        <th scope="col" id="myAccountStatusComments"><?php echo TABLE_HEADING_STATUS_COMMENTS; ?></th>
       </tr>
<?php
    foreach ($statusArray as $statuses) {
?>
    <tr>
        <td><?php echo zen_date_short($statuses['date_added']); ?></td>
        <td><?php echo $statuses['orders_status_name']; ?></td>
        <td><?php echo (empty($statuses['comments']) ? '&nbsp;' : nl2br(zen_output_string_protected($statuses['comments']))); ?></td> 
     </tr>
<?php
    }
?>
</table>
<?php 
} 
?>
<hr />
</div>
