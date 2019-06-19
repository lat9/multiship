DROP TABLE orders_multiship;
DROP TABLE orders_multiship_total;
ALTER TABLE orders_products DROP orders_multiship_id;
DELETE FROM configuration WHERE configuration_key LIKE 'MODULE_MULTISHIP_%';
DELETE FROM configuration_group WHERE configuration_group_title = 'Multiple Ship-to Addresses' LIMIT 1;
DELETE FROM admin_pages WHERE page_key IN ('customersInvoiceMultiship', 'customersPackingslipMultiship', 'configMultiship');