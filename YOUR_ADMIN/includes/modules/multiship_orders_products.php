<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.6 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
foreach ($order->multiship_info as $multiship_id => $multiship_info) {
    $taxable_check = $db->Execute(
        "SELECT `value` 
           FROM " . TABLE_ORDERS_MULTISHIP_TOTAL . "
          WHERE orders_id = $oID
            AND orders_multiship_id = $multiship_id
            AND class = 'ot_tax' 
          LIMIT 1"
    );
    $no_tax_collected = ($taxable_check->EOF || $taxable_check->fields['value'] == 0);
?>
    <div class="row"><?php echo MULTISHIP_SHIPPED_TO . zen_address_format($multiship_info['info']['address_format_id'], $multiship_info['info'], false, '', ', '); ?></div>
      
    <div class="row"><table class="table">
        <tr class="dataTableHeadingRow">
            <th class="dataTableHeadingContent" colspan="2"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
            <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS_MODEL; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TAX; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_PRICE_EXCLUDING_TAX; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_PRICE_INCLUDING_TAX; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TOTAL_EXCLUDING_TAX; ?></th>
            <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_TOTAL_INCLUDING_TAX; ?></th>
        </tr>
<?php
    $currency = $order->info['currency'];
    $currency_value = $order->info['currency_value'];
    foreach ($order->products as $currentProduct) {
        if ($currentProduct['orders_multiship_id'] != $multiship_id) {
            continue;
        }
        $product_price = $currentProduct['final_price'];
        $product_tax = ($no_tax_collected) ? 0 : $currentProduct['tax'];
        $product_qty = $currentProduct['qty'];
        $product_onetime = $currentProduct['onetime_charges'];

        if (DISPLAY_PRICE_WITH_TAX_ADMIN == 'true') {
            $priceIncTax = $currencies->format(zen_round(zen_add_tax($product_price, $product_tax), $currencies->get_decimal_places($currency)) * $product_qty, true, $currency, $currency_value);
        } else {
            $priceIncTax = $currencies->format(zen_add_tax($product_price, $product_tax) * $product_qty, true, $currency, $currency_value);
        }
?>
        <tr class="dataTableRow">
            <td class="dataTableContent text-right"><?php echo $product_qty; ?>&nbsp;x</td>
            <td class="dataTableContent"><?php echo $currentProduct['name']; ?>
<?php
        if (isset($currentProduct['attributes']) && count($currentProduct['attributes']) > 0) {
            foreach ($currentProduct['attributes'] as $currentAttribute) {
?>
              <br /><span style="white-space:nowrap;"><small>&nbsp;<i> - <?php echo $currentAttribute['option'] . ': ' . nl2br(zen_output_string_protected($currentAttribute['value'])); ?>
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
            <td class="dataTableContent"><?php echo $currentProduct['model']; ?></td>
            <td class="dataTableContent text-right"><?php echo zen_display_tax_value($product_tax); ?>%</td>
            <td class="dataTableContent text-right"><strong><?php echo $currencies->format($product_price, true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format($product_onetime, true, $currency, $currency_value) : ''); ?></strong></td>
            <td class="dataTableContent text-right"><strong><?php echo $currencies->format(zen_add_tax($product_price, $product_tax), true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format(zen_add_tax($product_onetime, $product_tax), true, $currency, $currency_value) : ''); ?></strong></td>
            <td class="dataTableContent text-right"><strong><?php echo $currencies->format(zen_round($product_price, $currencies->get_decimal_places($currency)) * $product_qty, true, $currency, $currency_value) . ($product_onetime != 0 ? '<br />' . $currencies->format($product_onetime, true, $currency, $currency_value) : ''); ?></strong></td>
            <td class="dataTableContent text-right"><strong><?php echo $priceIncTax . ($product_onetime != 0 ? '<br />' . $currencies->format(zen_add_tax($product_onetime, $product_tax), true, $currency, $currency_value) : ''); ?></strong></td>
        </tr>
<?php
    }  // END products foreach
?>
        <tr>
            <td colspan="8"><table style="margin-right: 0; margin-left: auto;">
<?php
    foreach ($multiship_info['totals'] as $currentTotal) {
?>
                <tr>
                    <td class="text-right <?php echo str_replace('_', '-', $currentTotal['class']); ?>-Text"><?php echo $currentTotal['title']; ?></td>
                    <td class="text-right <?php echo str_replace('_', '-', $currentTotal['class']); ?>-Amount"><?php echo $currencies->format($currentTotal['value'], false); ?></td>
                </tr>
<?php
    }  // END totals foreach
?>
            </table></td>
        </tr>
    </table></div>
<?php
}  // END multiship address foreach
?>
    <div class="row" style="border-top: 1px solid #414141;"><strong><?php echo MULTISHIP_GRAND_TOTALS; ?></strong></div>
      
    <div class="row"><table class="table">
<?php
foreach ($order->totals as $currentTotal) {
?>
        <tr>
            <td class="text-right <?php echo str_replace('_', '-', $currentTotal['class']); ?>-Text"><?php echo $currentTotal['title']; ?></td>
            <td class="text-right <?php echo str_replace('_', '-', $currentTotal['class']); ?>-Amount"><?php echo $currencies->format($currentTotal['value'], false); ?></td>
        </tr>
<?php
}  // END totals foreach
?>
    </table></div>
