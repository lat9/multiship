<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
if (empty($_SESSION['multiship']) || !$_SESSION['multiship']->isEnabled()) {
    return;
}
?>
<script><!--
jQuery(document).ready(function() {
    jQuery($('<input>').attr({
        type: 'hidden',
        id: 'multiship-change',
        name: 'multiship_changed',
        value: '0'
    }).appendTo("form[name='checkout_address']"));
    
    jQuery('input[name=shipping]').on('change', function() {
        jQuery('#multiship-change').val('1');
        jQuery("form[name='checkout_address']").submit();
    });
});
//--></script>
