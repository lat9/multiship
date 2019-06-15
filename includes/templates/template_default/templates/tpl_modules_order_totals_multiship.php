<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Required by tpl_modules_order_totals.php if the customer has registered multiple
// ship-to addresses for the order.  The values $size and $class are set in the
// order_total::output method.
//
for ($i = 0; $i < $size; $i++) {
      if (isset($GLOBALS['multiship_totals'][$GLOBALS[$class]->code])) {
          $amount = $GLOBALS['currencies']->format($GLOBALS['multiship_totals'][$GLOBALS[$class]->code]);
      } else {
          $amount = $GLOBALS[$class]->output[$i]['text'];
      }
?>
<div id="<?php echo str_replace('_', '', $GLOBALS[$class]->code); ?>">
    <div class="totalBox larger forward"><?php echo $amount; ?></div>
    <div class="lineTitle larger forward"><?php echo $GLOBALS[$class]->output[$i]['title']; ?></div>
</div>
<br class="clearBoth" />
<?php 
} 
