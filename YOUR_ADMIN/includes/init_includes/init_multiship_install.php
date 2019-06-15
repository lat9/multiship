<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.6 and later
//
// Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// This module is required by the plugin's init_multiship.php if the plugin has
// not yet been installed.
//
// Note: The base initialization script has created the configuration group
// for the plugin!  The value is available in the $cgi variable.
//
//----
// Create each of the database tables for the plugin, if they don't already exist.
//
$db->Execute(
    "CREATE TABLE IF NOT EXISTS  " . TABLE_ORDERS_MULTISHIP . " (
        orders_multiship_id int(11) NOT NULL auto_increment,
        orders_id int(11) NOT NULL default '0',
        delivery_name varchar(64) NOT NULL default '',
        delivery_company varchar(64) default NULL,
        delivery_street_address varchar(64) NOT NULL default '',
        delivery_suburb varchar(32) default NULL,
        delivery_city varchar(32) NOT NULL default '',
        delivery_postcode varchar(10) NOT NULL default '',
        delivery_state varchar(32) default NULL,
        delivery_country varchar(32) NOT NULL default '',
        delivery_address_format_id int(5) NOT NULL default '0',
        last_modified datetime default NULL,
        orders_status int(5) NOT NULL default '0',
        content_type char(8) NOT NULL default '',
    PRIMARY KEY (orders_multiship_id))"
);

$db->Execute(
    "CREATE TABLE IF NOT EXISTS " . TABLE_ORDERS_MULTISHIP_TOTAL . " (
        orders_multiship_total_id int(11) unsigned NOT NULL auto_increment,
        orders_id int(11) NOT NULL default '0',
        orders_multiship_id int(11) NOT NULL default '0',
        title varchar(255) NOT NULL default '',
        text varchar(255) NOT NULL default '',
        value decimal(15,4) NOT NULL default '0.0000',
        class varchar(32) NOT NULL default '',
        sort_order int(11) NOT NULL default '0',
    PRIMARY KEY (orders_multiship_total_id))"
);

if (!$sniffer->field_exists(TABLE_ORDERS_PRODUCTS, 'orders_multiship_id')) {
    $db->Execute(
        "ALTER TABLE " . TABLE_ORDERS_PRODUCTS . " 
           ADD orders_multiship_id int(11) NOT NULL default '0' AFTER orders_id"
    );
}

$ms_date_added = MULTISHIP_UPDATE_DATE . ' 00:00:00';
$db->Execute(
    "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function) 
        VALUES 
            ('Plugin Version', 'MODULE_MULTISHIP_VERSION', '0.0.0', 'The Multiple Ship-to Addresses version number', $cgi, '1', '$multiship_update_date', 'trim(')"
);

// -----
// If not already present, record the multiship versions of the invoice and packing-slip pages in the admin_pages.
//
if (!zen_page_key_exists('customersInvoiceMultiship')) {
    zen_register_admin_page('customersInvoiceMultiship', 'BOX_CUSTOMERS_INVOICE_MULTISHIP', 'FILENAME_INVOICE_MULTISHIP', '', 'customers', 'N');
}

if (!zen_page_key_exists('customersPackingslipMultiship')) {
    zen_register_admin_page('customersPackingslipMultiship', 'BOX_CUSTOMERS_PACKINGSLIP_MULTISHIP', 'FILENAME_PACKINGSLIP_MULTISHIP', '', 'customers', 'N');
}

define('MODULE_MULTISHIP_VERSION', '0.0.0');
