<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------
?>
<script type="text/javascript">
var canleave = true;
function ok2leave() {
  canleave = true;
}
function notok2leave() {
  canleave = false;
}
function goodbye(e) {
  if (!e) e = window.event;
  
  if (!canleave) {
    //e.cancelBubble is supported by IE - this will kill the bubbling process.
    e.cancelBubble = true;
    e.returnValue = '<?php echo TEXT_QUANTITIES_CHANGED; ?>'; // This is displayed on the dialog

    //e.stopPropagation works in Firefox.
    if (e.stopPropagation) {
      e.stopPropagation();
      e.preventDefault();
    }
  }
}
window.onbeforeunload=goodbye;
</script>