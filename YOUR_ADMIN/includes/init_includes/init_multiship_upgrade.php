<?php
// -----
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.6 and later
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// This module is required by the plugin's init_multiship.php if the plugin has
// been initially installed or upgraded.
//
// Note: The base initialization script has created the configuration group
// for the plugin!  The value is available in the $cgi variable.
//

// -----
// Deal with incremental configuration changes, based on the current version.
//
switch (true) {
    // -----
    // v2.0.0:
    // - The plugin now has its own configuration group.
    // - Add setting to identify compatible payment methods.
    //
    case version_compare(MODULE_MULTISHIP_VERSION, '2.0.0', '<'):
        // -----
        // Modify the pre-existing settings' titles and descriptions, moving them to their own
        // configuration group (were previously 'hidden' in Modules).
        //
        $db->Execute(
           "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_group_id = $cgi,
                    configuration_title = 'Plugin Version/Release Date',
                    configuration_description = 'The Multiple Ship-to Addresses version number.  The <code>Date Added</code> shows the version\'s release date.',
                    sort_order = 1,
                    date_added = '$multiship_update_date',
                    set_function = 'trim('
              WHERE configuration_key = 'MODULE_MULTISHIP_VERSION'
              LIMIT 1"
        );
        $db->Execute(
           "DELETE FROM " . TABLE_CONFIGURATION . "
             WHERE configuration_key = 'MODULE_MULTISHIP_RELEASE_DATE'
             LIMIT 1"
        );
        
        // -----
        // Add a configuration setting through which the admin can identify the payment methods that
        // 'support' multiple ship-to addresses.
        //
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) 
            VALUES
                ('Enable Multiple Ship-to Addresses?', 'MODULE_MULTISHIP_ENABLE', 'false', 'Should multiple ship-to addresses be offered to customers?', $cgi, 10, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
                
                ('Supported Payment Methods', 'MODULE_MULTISHIP_PAYMENT_METHODS', '', 'Identify, using a comma-separated list (intervening blanks are OK), the payment methods to be offered if an order has multiple ship-to addresses &mdash; <em>all other payment methods will be <b>disabled</b></em> if a customer chooses to supply multiple ship-to addresses!<br /><br />Leave the setting as an empty string (the default) to enable the plugin for <b>all</b> payment methods.<br />', $cgi, 20, now(), NULL, NULL),
                
                ('Enable debug?', 'MODULE_MULTISHIP_DEBUG', 'false', 'Enable the plugin\'s debug?', $cgi, 500, now(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        
        // -----
        // If it's not already created, add the admin-page for the plugin's configuration menu.
        //
        if (!zen_page_key_exists('configMultiship')) {
            zen_register_admin_page('configMultiship', 'BOX_CONFIG_MULTISHIP', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
        }
        
    default:                            //- Fall through from above
        break;
}

// -----
// Finally, record the plugin's current version/release date into the database.
//
$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . " 
        SET configuration_value = '" . MULTISHIP_CURRENT_VERSION . "',
            date_added = '$multiship_update_date'
      WHERE configuration_key = 'MODULE_MULTISHIP_VERSION'
      LIMIT 1"
);
