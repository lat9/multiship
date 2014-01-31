<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
?>
<div class="centerColumn" id="checkoutMultishipDefault">

  <h1 id="checkoutMultishipDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

<?php if ($messageStack->size('multiship') > 0) echo $messageStack->output('multiship'); ?>
  <div id="checkoutMultishipShipping"><?php echo TEXT_CURRENT_SHIPPING_METHOD; ?><strong><?php echo $_SESSION['shipping']['title']; ?></strong>&nbsp;&nbsp;<a href="<?php echo zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_EDIT_SMALL, TEXT_SHIPPING_METHOD_CHANGE) ; ?></a></div>
  <div id="checkoutMultishipInstructions"><?php echo TEXT_MULTISHIP_INSTRUCTIONS; ?></div>
  <div id="checkoutMultishipNewAddress"><?php echo TEXT_NEED_ANOTHER_ADDRESS; ?><a href="<?php echo zen_href_link(FILENAME_ADDRESS_BOOK, '', 'SSL'); ?>"><?php echo TEXT_ENTER_NEW_ADDRESS; ?></a></div>
  <?php echo zen_draw_form('checkout_multiship', zen_href_link(FILENAME_CHECKOUT_MULTISHIP, '', 'SSL')); ?>
  <div id="multishipTable">
    <div id="multishipTableHeading">
      <div class="item"><?php echo HEADING_ITEM; ?></div>
      <div class="price"><?php echo HEADING_PRICE; ?></div>
      <div class="qty"><?php echo HEADING_QTY; ?></div>
      <div class="sendto"><?php echo HEADING_SENDTO; ?></div>
    </div>
<?php
$even_odd = ' even';
foreach ($productsArray as $currentProduct) {
?>
    <div class="multishipTableItem<?php echo $even_odd . (($currentProduct['is_physical']) ? '' : ' virtual'); ?>">
      <div class="item">
        <div class="msipItemName"><?php echo $currentProduct['name'] . zen_draw_hidden_field('prid[]', $currentProduct['id']); ?></div>
<?php
  if (isset($currentProduct['attributes'])) {
?>
        <div class="msipItemAttr"><ul>
<?php
    foreach ($currentProduct['attributes'] as $name => $value) {
?>
          <li><?php echo $name . TEXT_OPTION_DIVIDER . nl2br($value); ?></li>
<?php
    }
?>
        </ul></div>
<?php
  }
?>
      </div>
      <div class="msipPrice"><?php echo $currentProduct['price']; ?></div>
      <div class="qty"><?php echo zen_draw_input_field('qty[]', 1); ?></div>
      <div class="sendto"><?php echo zen_draw_pull_down_menu('address[]', $multishipAddresses, $currentProduct['sendto'], 'onchange="this.form.submit();"') . ' ' . $_SESSION['multiship']->get_noship_image($currentProduct['sendto']); ?></div>
    </div>
<?php
  $even_odd = ($even_odd == ' even') ? ' odd' : ' even';
}
?>
  </div>
<?php
if ($products_onetime_charges) {
?>
  <div id="onetime_charges"><span class="onetime_charge"><?php echo ONETIME_CHARGE_INDICATOR; ?></span><?php echo TEXT_ONETIME_CHARGES_APPLY; ?></div>
<?php
}
?>

  <div class="buttonRow back"><?php echo zen_image_submit(BUTTON_IMAGE_UPDATE, BUTTON_UPDATE_ALT, 'name="update"'); ?></div>
  <div class="buttonRow forward"><a href="<?php echo zen_href_link(FILENAME_CHECKOUT_CONFIRMATION, '', 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_CONTINUE_CHECKOUT, TEXT_RETURN_TO_CONFIRMATION); ?></a></div>
  </form>
</div>