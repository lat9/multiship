<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
//
// Copyright (C) 2014-2017, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Note:  Specified text message doesn't display on FireFox, known bug, won't fix
//        https://bugzilla.mozilla.org/show_bug.cgi?id=641509
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
        e.returnValue = '<?php echo TEXT_QUANTITIES_CHANGED; ?>'; // This is displayed on the dialog, except for FireFox

        //e.stopPropagation works in Firefox.
        if (e.stopPropagation) {
            e.stopPropagation();
            e.preventDefault();
        }
    }
}
window.onbeforeunload=goodbye;
</script>