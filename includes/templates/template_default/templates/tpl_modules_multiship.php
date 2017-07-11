<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
if (isset($multiship_info) && is_array($multiship_info)) {
    foreach ($multiship_info as $address_id => $currentInfo) {
?>
  <div class="multishipOrder"><?php echo TEXT_SHIPPING_TO . $currentInfo['address']; ?>
    <table border="0" width="100%" cellspacing="0" cellpadding="0" class="cartContentsDisplay">
      <tr class="cartTableHeading">
        <th scope="col" id="ccQuantityHeading" width="30"><?php echo TABLE_HEADING_QUANTITY; ?></th>
        <th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
<?php
        // If there are tax groups, display the tax columns for price breakdown
        $show_tax_group = false;
        if (count($currentInfo['info']['tax_groups']) > 1) {
            $show_tax_group = true;
?>
        <th scope="col" id="ccTaxHeading"><?php echo HEADING_TAX; ?></th>
<?php
        }
?>  
        <th scope="col" id="ccTotalHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
      </tr>
<?php 
        foreach ($currentInfo['products'] as $currentProduct) {
?>
      <tr class="<?php echo $currentProduct['rowClass']; ?>">
        <td  class="cartQuantity"><?php echo $currentProduct['qty']; ?>&nbsp;x</td>
        <td class="cartProductDisplay"><?php echo $currentProduct['name']; ?>
<?php 
            // if there are attributes, loop thru them and display one per line
            if (isset($currentProduct['attributes']) && count($currentProduct['attributes']) > 0 ) {
?>
          <ul class="cartAttribsList">
<?php
                for ($j = 0, $m = count($currentProduct['attributes']); $j < $m; $j++) {
?>
            <li><?php echo $currentProduct['attributes'][$j]['option'] . ': ' . nl2br(zen_output_string_protected($currentProduct['attributes'][$j]['value'])); ?></li>
<?php
                }
?>
          </ul>
<?php
            } // endif attribute-info
?>
        </td>
<?php 
            if ($show_tax_group)  { 
?>
        <td class="cartTotalDisplay"><?php echo zen_display_tax_value($currentProduct['tax']); ?>%</td>
<?php
            }  // endif tax info display  
?>
        <td class="cartTotalDisplay">
<?php 
            echo $currencies->display_price($currentProduct['final_price'], $currentProduct['tax'], $currentProduct['qty']);
            if ($currentProduct['onetime_charges'] != 0 ) {
                echo '<br /> ' . $currencies->display_price($currentProduct['onetime_charges'], $currentProduct['tax'], 1);
            }
?>
        </td>
      </tr>
<?php  
        }  // end for loopthru all products 
?>
    </table>
    <hr />
<?php
        if (MODULE_ORDER_TOTAL_INSTALLED) {
?>
    <div class="orderTotals">
<?php
            foreach ($currentInfo['totals'] as $currentTotal) { 
?>
      <div class="<?php echo $currentTotal['code']; ?>">
        <div class="totalBox larger forward"><?php echo $currentTotal['text']; ?></div>
        <div class="lineTitle larger forward"><?php echo $currentTotal['title']; ?></div>
      </div>
      <br class="clearBoth" />
<?php
            }
?>
    </div>
<?php
        }
?>
  </div>
<?php
    }  // END foreach loop
?>
  <hr />
  <div class="orderTotals grandTotal">
    <div class="totalBox larger forward"><?php echo $currencies->format($multiship_grand_total); ?></div>
    <div class="lineTitle larger forward"><?php echo TEXT_GRAND_TOTAL; ?></div>
  </div>
  <br class="clearBoth" />
<?php
}
