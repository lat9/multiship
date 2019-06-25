<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
?>
<div class="centerColumn" id="checkoutMultishipDefault">
    <h1 id="checkoutMultishipDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<?php 
if ($messageStack->size('multiship') > 0) {
    echo $messageStack->output('multiship');
}
if ($messageStack->size('shopping_cart') > 0) {
    echo $messageStack->output('shopping_cart'); 
}
$checkout_shipping_anchor = '<a href="' . $checkout_shipping_link . '">' . SHIP_TO_MULTIPLE_HERE . '</a>';
?>
    <div id="checkoutMultishipShipping"><?php echo TEXT_CURRENT_SHIPPING_METHOD; ?><strong><?php echo $_SESSION['shipping']['title']; ?></strong>. <?php echo sprintf(TEXT_SHIPPING_METHOD_CHANGE, $checkout_shipping_anchor); ?></div>
    <div id="checkoutMultishipInstructions"><?php echo TEXT_MULTISHIP_INSTRUCTIONS; ?></div>
    <div id="checkoutMultishipNewAddress"><?php echo TEXT_NEED_ANOTHER_ADDRESS; ?><a href="<?php echo zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'); ?>"><?php echo TEXT_ENTER_NEW_ADDRESS; ?></a></div>
    <?php echo zen_draw_form('checkout_multiship', zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL')); ?>
    <table id="multishipTable">
        <tr>
            <th class="item"><?php echo HEADING_ITEM; ?></th>
            <th class="price"><?php echo HEADING_PRICE; ?></th>
            <th class="qty"><?php echo HEADING_QTY; ?></th>
            <th class="sendto"><?php echo HEADING_SENDTO; ?></th>
        </tr>
<?php
foreach ($productsArray as $currentProduct) {
?>
        <tr class="multishipItem<?php echo ($currentProduct['is_physical']) ? '' : ' virtual'; ?>">
            <td>
                <div class="msipItemName"><?php echo $currentProduct['name'] . zen_draw_hidden_field('prid[]', $currentProduct['id']); ?></div>
<?php
    if (isset($currentProduct['attributes'])) {
?>
                <div class="msipItemAttr"><ul>
<?php
        foreach ($currentProduct['attributes'] as $currentAttribute) {
?>
                    <li><?php echo $currentAttribute['name'] . TEXT_OPTION_DIVIDER . nl2br($currentAttribute['value']); ?></li>
<?php
        }
?>
                </ul></div>
<?php
    }
?>
            </td>
            <td class="msipPrice"><?php echo $currentProduct['price']; ?></td>
            <td class="qty"><?php echo zen_draw_input_field('qty[]', 1, 'onchange="notok2leave();"'); ?></td>
            <td class="sendto"><?php echo zen_draw_pull_down_menu('address[]', $multishipAddresses, $currentProduct['sendto'], 'onchange="ok2leave(); this.form.submit();"') . ' ' . $_SESSION['multiship']->getNoShipIcon($currentProduct['sendto']); ?></td>
        </tr>
<?php
}
?>
    </table>
<?php
if ($products_onetime_charges) {
?>
    <div id="onetime_charges"><span class="onetime_charge"><?php echo ONETIME_CHARGE_INDICATOR; ?></span><?php echo TEXT_ONETIME_CHARGES_APPLY; ?></div>
<?php
}
?>

    <div class="buttonRow back"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT, 'name="update" onclick="ok2leave();"'); ?></div>
    <div class="buttonRow forward"><?php echo sprintf(TEXT_RETURN_TO_SHIPPING, $checkout_shipping_anchor); ?></div>
    </form>
</div>
