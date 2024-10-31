<?php
/**
 * Admin Pages
 *
 * @package     EDD\SalesMetrics
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2015, Richard Chen
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates the admin submenu pages under the Downloads menu and assigns their
 * links to global variables
 *
*/
function edd_sales_metrics_add_link() {
    // Add a new top-level menu 
    add_menu_page(__( 'Easy Digital Download - Sales Metrics','edd-sales-metrics'), __('Sales Metrics','edd-sales-metrics'), 'manage_options', 'edd-sales-metrics', 'edd_sales_metrics_dashboard' );
    //add_submenu_page( 'mt-top-level-handle', 'Events Management', 'Events Management', 'manage_options', 'mt-top-level-handle', 'admin_calendar');
    //add_submenu_page( 'mt-top-level-handle', __('Imports','menu-pec'), __('Imports','menu-pec'), 'manage_options', 'mt-sub-level-handle', 'mt_settings_page');
    //add_submenu_page( 'mt-top-level-handle', 'PHP Event Calendar Premium Add-on', 'Premium Add-Ons', 'manage_options', 'pec-premium-add-ons', 'premium_addons');

    /*
	$edd_payments_page      = add_submenu_page( 'edit.php?post_type=download', $edd_payment->labels->name, $edd_payment->labels->menu_name, 'edit_shop_payments', 'edd-payment-history', 'edd_payment_history_page' );
	$edd_customers_page     = add_submenu_page( 'edit.php?post_type=download', __( 'Customers', 'edd' ), __( 'Customers', 'edd' ), 'view_shop_reports', 'edd-customers', 'edd_customers_page' );
	$edd_discounts_page     = add_submenu_page( 'edit.php?post_type=download', __( 'Discount Codes', 'edd' ), __( 'Discount Codes', 'edd' ), 'manage_shop_discounts', 'edd-discounts', 'edd_discounts_page' );
	$edd_reports_page       = add_submenu_page( 'edit.php?post_type=download', __( 'Earnings and Sales Reports', 'edd' ), __( 'Reports', 'edd' ), 'view_shop_reports', 'edd-reports', 'edd_reports_page' );
	$edd_settings_page      = add_submenu_page( 'edit.php?post_type=download', __( 'Easy Digital Download Settings', 'edd' ), __( 'Settings', 'edd' ), 'manage_shop_settings', 'edd-settings', 'edd_options_page' );
	$edd_tools_page         = add_submenu_page( 'edit.php?post_type=download', __( 'Easy Digital Download Info and Tools', 'edd' ), __( 'Tools', 'edd' ), 'install_plugins', 'edd-tools', 'edd_tools_page' );
	$edd_add_ons_page       = add_submenu_page( 'edit.php?post_type=download', __( 'Easy Digital Download Extensions', 'edd' ), __( 'Extensions', 'edd' ), 'install_plugins', 'edd-addons', 'edd_add_ons_page' );
	$edd_upgrades_screen    = add_submenu_page( null, __( 'EDD Upgrades', 'edd' ), __( 'EDD Upgrades', 'edd' ), 'manage_shop_settings', 'edd-upgrades', 'edd_upgrades_screen' );
	*/
}
add_action( 'admin_menu', 'edd_sales_metrics_add_link', 10 );