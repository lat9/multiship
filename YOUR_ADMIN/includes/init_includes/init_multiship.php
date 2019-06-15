<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.5 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('MULTISHIP_CURRENT_VERSION', '2.0.0-beta1');
define('MULTISHIP_UPDATE_DATE', '2019-06-14');

$multiship_update_date = MULTISHIP_UPDATE_DATE . ' 00:00:00';

// -----
// Wait until an admin is logged in to perform any operations, so that any generated
// messages will be seen.
//
if (empty($_SESSION['admin_id'])) {
    return;
}

//----
// Create the Configuration->Multiple Ship-to Addresses item, if it's not already there.
//
$configurationGroupTitle = 'Multiple Ship-to Addresses';
$configuration = $db->Execute(
    "SELECT configuration_group_id 
       FROM " . TABLE_CONFIGURATION_GROUP . " 
      WHERE configuration_group_title = '$configurationGroupTitle' 
      ORDER BY configuration_group_id ASC"
);
if ($configuration->EOF) {
    $db->Execute(
        "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
            (configuration_group_title, configuration_group_description, sort_order, visible) 
         VALUES 
            ('$configurationGroupTitle', 'Multiple Ship-to Addresses', '1', '1')"
    );
    $cgi = $db->Insert_ID();
  
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION_GROUP . " 
            SET sort_order = $cgi 
          WHERE configuration_group_id = $cgi 
          LIMIT 1"
    );
} else {
    $cgi = $configuration->fields['configuration_group_id'];
}

// -----
// If this is a 'fresh' install, bring in the plugin's initial installation script.
//
if (!defined('MODULE_MULTISHIP_VERSION')) {
    require DIR_WS_INCLUDES . 'init_includes/init_multiship_install.php';
}

// -----
// If this is an upgrade (or an initial install), bring in the plugin's upgrade script,
// which records the current version/release date into the database, too.
//
if (MODULE_MULTISHIP_VERSION != MULTISHIP_CURRENT_VERSION) {
    require DIR_WS_INCLUDES . 'init_includes/init_multiship_upgrade.php';
}

// -----
// If the current page-request is for an order's invoice or packingslip, check to
// see if the order includes multiple ship-to addresses and, if so, redirect to the
// multi-ship version of the script.
//
if (($current_page == FILENAME_ORDERS_INVOICE . '.php' || $current_page == FILENAME_ORDERS_PACKINGSLIP . '.php') && !empty($_GET['oID'])) {
    $oID = (int)$_GET['oID'];
    if ($multiship->isMultiShipOrder($oID)) {
        if ($current_page == FILENAME_ORDERS_INVOICE . '.php') {
            zen_redirect(zen_href_link(FILENAME_INVOICE_MULTISHIP, "oID=$oID"));
        } else {
            zen_redirect(zen_href_link(FILENAME_PACKINGSLIP_MULTISHIP, "oID=$oID"));
        }
    }
}
