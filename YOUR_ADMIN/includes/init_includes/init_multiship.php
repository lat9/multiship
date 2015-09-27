<?php
// ---------------------------------------------------------------------------
// Part of the Multiple Shipping Addresses plugin for Zen Cart v1.5.1 and later
//
// Copyright (C) 2014-2015, Vinos de Frutas Tropicales (lat9)
//
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
// ---------------------------------------------------------------------------

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('MULTISHIP_CURRENT_VERSION', '1.1.3');
define('MULTISHIP_UPDATE_DATE', '2015-10-xx');

if (!defined('MODULE_MULTISHIP_VERSION')) {
  //----
  // Create each of the database tables for the events plugin, if they don't already exist.
  //
  $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_ORDERS_MULTISHIP . " (
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
    PRIMARY KEY  (orders_multiship_id)
  )";
  $db->Execute($sql);

  $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_ORDERS_MULTISHIP_TOTAL . " (
    orders_multiship_total_id int(11) unsigned NOT NULL auto_increment,
    orders_id int(11) NOT NULL default '0',
    orders_multiship_id int(11) NOT NULL default '0',
    title varchar(255) NOT NULL default '',
    text varchar(255) NOT NULL default '',
    value decimal(15,4) NOT NULL default '0.0000',
    class varchar(32) NOT NULL default '',
    sort_order int(11) NOT NULL default '0',
    PRIMARY KEY  (orders_multiship_total_id)
  )";
  $db->Execute($sql);

  if (!$sniffer->field_exists(TABLE_ORDERS_PRODUCTS, 'orders_multiship_id')) {
    $db->Execute("ALTER TABLE " . TABLE_ORDERS_PRODUCTS . " ADD orders_multiship_id int(11) NOT NULL default '0' AFTER orders_id");
    
  }
  
  $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Multiple-ShipTo Addresses: Version', 'MODULE_MULTISHIP_VERSION', '" . MULTISHIP_CURRENT_VERSION . "', 'The multiship plugin version number', '6', '100', now())");
  $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Multiple-ShipTo Addresses: Release Date', 'MODULE_MULTISHIP_RELEASE_DATE', '" . MULTISHIP_UPDATE_DATE . "', 'The multiship plugin release date', '6', '101', now())");
  
  define ('MODULE_MULTISHIP_VERSION', MULTISHIP_CURRENT_VERSION);
  define ('MODULE_MULTISHIP_RELEASE_DATE', MULTISHIP_UPDATE_DATE);

}

// -----
// Update the configuration table to reflect the current version, if it's not already set.
//
if (MODULE_MULTISHIP_VERSION != MULTISHIP_CURRENT_VERSION || MODULE_MULTISHIP_RELEASE_DATE != MULTISHIP_UPDATE_DATE) {
  $db->Execute ("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . MULTISHIP_CURRENT_VERSION . "' WHERE configuration_key = 'MODULE_MULTISHIP_VERSION'");
  $db->Execute ("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . MULTISHIP_UPDATE_DATE . "' WHERE configuration_key = 'MODULE_MULTISHIP_RELEASE_DATE'");
}

// -----
// If not already present, record the multiship versions of the invoice and packing-slip pages in the admin_pages.
//
$next_sort_info = $db->Execute('SELECT MAX(sort_order) as max_sort FROM ' . TABLE_ADMIN_PAGES . " WHERE menu_key='customers'");
$next_sort = $next_sort_info->fields['max_sort'] + 1;

if (!zen_page_key_exists('customersInvoiceMultiship')) {
  zen_register_admin_page('customersInvoiceMultiship', 'BOX_CUSTOMERS_INVOICE_MULTISHIP', 'FILENAME_INVOICE_MULTISHIP', '', 'customers', 'N', $next_sort);
}

if (!zen_page_key_exists('customersPackingslipMultiship')) {
  zen_register_admin_page('customersPackingslipMultiship', 'BOX_CUSTOMERS_PACKINGSLIP_MULTISHIP', 'FILENAME_PACKINGSLIP_MULTISHIP', '', 'customers', 'N', $next_sort+1);
}