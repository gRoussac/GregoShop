<?php
/**
 * Content-step module checkbox defaults and category placement.
 *
 * Edit selected_exclude to change which modules start UNCHECKED for
 * "Select the modules to install". Use technical module folder names.
 * Edit category_overrides to force a module into an install list category
 * (CategoriesProvider key: Administration, other, …). Display only; BO/FO
 * still use the module tab → parent map.
 * Unknown names are ignored. Restart the installer step to pick up changes
 * when no content selection is stored in session yet.
 */

declare(strict_types=1);

return [
    'category_overrides' => [
        'gsitemap' => 'other', // Google sitemap
        // Display under Administration; tab stays payments_gateways → FO for BO/FO radios.
        'ps_checkpayment' => 'Administration', // Check payment
        'ps_wirepayment' => 'Administration', // Wire payment
    ],
    'selected_exclude' => [
        'statsdata', // Data mining for statistics
        'ps_googleanalytics', // Google Analytics
        'statscatalog', // Catalog statistics
        'statsbestcategories', // Best categories
        'statsbestcustomers', // Best customers
        'statsbestmanufacturers', // Best manufacturers
        'statsbestsuppliers', // Best suppliers
        'statsbestvouchers', // Best vouchers
        'statsbestproducts', // Best-selling products
        'statsforecast', // Stats Dashboard
        'statscarrier', // Carrier distribution
        'productcomments', // Product Comments
        'ps_supplierlist', // Supplier List
        'ps_facetedsearch', // Faceted search
        'ps_contactinfo', // Contact information
        'ps_brandlist', // Brand list
        'ps_checkpayment', // Check payment
        'ps_wirepayment', // Wire payment
        'gsitemap', // Google sitemap
        'ps_cashondelivery', // Cash on delivery (COD)
        'dashactivity', // Dashboard Activity
        'dashgoals', // Dashboard Goals
        'dashproducts', // Dashboard Products
        'dashtrends', // Dashboard Trends
        'ps_themecusto', // Theme Customization
    ],
];
