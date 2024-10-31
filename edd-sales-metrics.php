<?php
/**
 * Plugin Name:   Easy Digital Downloads - Sales Metrics
 * Plugin URI:      http://eddsm.com
 * Description:     Advanced sales reports and metrics for Easy Digital Downloads
 * Version:         0.0.1
 * Author:          chenster
 * Author URI:      http://eddsm.com
 * Text Domain:     edd-sales-metrics
 *
 * @package         EDD\SaleMetrics
 * @author          Richard Chen
 * @copyright       Copyright (c) 2016
 *
 * IMPORTANT! Ensure that you make the following adjustments
 * before releasing your extension:
 *
 * - Replace all instances of plugin-name with the name of your plugin.
 *   By WordPress coding standards, the folder name, plugin file name,
 *   and text domain should all match. For the purposes of standardization,
 *   the folder name, plugin file name, and text domain are all the
 *   lowercase form of the actual plugin name, replacing spaces with
 *   hyphens.
 *
 * - Replace all instances of Plugin_Name with the name of your plugin.
 *   For the purposes of standardization, the camel case form of the plugin
 *   name, replacing spaces with underscores, is used to define classes
 *   in your extension.
 *
 * - Replace all instances of PLUGIN_NAME with the name of your plugin.
 *   For the purposes of standardization, the uppercase form of the plugin
 *   name, removing spaces, is used to define plugin constants.
 *
 * - Replace all instances of Plugin Name with the actual name of your
 *   plugin. This really doesn't need to be anywhere other than in the
 *   EDD Licensing call in the hooks method.
 *
 * - Find all instances of @todo in the plugin and update the relevant
 *   areas as necessary.
 *
 * - All functions that are not class methods MUST be prefixed with the
 *   plugin name, replacing spaces with underscores. NOT PREFIXING YOUR
 *   FUNCTIONS CAN CAUSE PLUGIN CONFLICTS!
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_Sales_Metrics' ) ) {

    /**
     * Main EDD_Sales_Metrics class
     *
     * @since       0.0.1
     */
    class EDD_Sales_Metrics {

        /**
         * @var         EDD_Sales_Metrics $instance The one true EDD_Sales_Metrics
         * @since       0.0.1
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       0.0.1
         * @return      object self::$instance The one true EDD_Sales_Metrics
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_Sales_Metrics();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       0.0.1
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_SALES_METRICS_VER', '0.0.1' );

            // Plugin path
            define( 'EDD_SALES_METRICS_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_SALES_METRICS_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       0.0.1
         * @return      void
         */
        private function includes() {
            // Include scripts
            require_once EDD_SALES_METRICS_DIR . 'includes/scripts.php';
            require_once EDD_SALES_METRICS_DIR . 'includes/functions.php';
            require_once EDD_SALES_METRICS_DIR . 'includes/class.edd-salesmetrics-utility.php';           

            if ( is_admin() ) {
                require_once EDD_SALES_METRICS_DIR . 'includes/admin/class.edd-salesmetrics-stats.php';           
                require_once EDD_SALES_METRICS_DIR . 'includes/admin/admin-pages.php';
            }
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       0.0.1
         * @return      void
         *
         * @todo        The hooks listed in this section are a guideline, and
         *              may or may not be relevant to your particular extension.
         *              Please remove any unnecessary lines, and refer to the
         *              WordPress codex and EDD documentation for additional
         *              information on the included hooks.
         *
         *              This method should be used to add any filters or actions
         *              that are necessary to the core of your extension only.
         *              Hooks that are relevant to meta boxes, widgets and
         *              the like can be placed in their respective files.
         *
         *              IMPORTANT! If you are releasing your extension as a
         *              commercial extension in the EDD store, DO NOT remove
         *              the license check!
         */
        private function hooks() {
            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

            // Handle licensing
            // @todo        Replace the Sales Metrics and Your Name with your data
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Sales Metrics', EDD_SALES_METRICS_VER, 'Your Name' );
            }
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       0.0.1
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_SALES_METRICS_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_sales_metrics_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-sales-metrics' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-sales-metrics', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-sales-metrics/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-sales-metrics/ folder
                load_textdomain( 'edd-sales-metrics', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-sales-metrics/languages/ folder
                load_textdomain( 'edd-sales-metrics', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-sales-metrics', false, $lang_dir );
            }
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       0.0.1
         * @param       array $settings The existing EDD settings array
         * @return      array The modified EDD settings array
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_sales_metrics_settings',
                    'name'  => '<strong>' . __( 'Sales Metrics Settings', 'edd-sales-metrics' ) . '</strong>',
                    'desc'  => __( 'Configure Sales Metrics Settings', 'edd-sales-metrics' ),
                    'type'  => 'header',
                )
            );

            return array_merge( $settings, $new_settings );
        }
    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Sales_Metrics
 * instance to functions everywhere
 *
 * @since       0.0.1
 * @return      \EDD_Sales_Metrics The one true EDD_Sales_Metrics
 *
 * @todo        Inclusion of the activation code below isn't mandatory, but
 *              can prevent any number of errors, including fatal errors, in
 *              situations where your extension is activated but EDD is not
 *              present.
 */
function edd_sales_metrics_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class.extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_Sales_Metrics::instance();
    }
}
add_action( 'plugins_loaded', 'edd_sales_metrics_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       0.0.1
 * @return      void
 */
function edd_sales_metrics_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'edd_sales_metrics_activation' );