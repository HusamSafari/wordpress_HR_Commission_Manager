<?php
/**
 * Plugin Name:       Yam_human_mangament_sytem
 * Plugin URI:        https://example.com/swvt-hr
 * Description:       Manage branches, employee directory, monthly sales target inputs, automated role-based commission distribution, attendance tracking, and monthly payroll reports.
 * Version:           2.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            SWVT
 * Text Domain:       swvt-hr
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SWVT_HR_VERSION', '2.1' );
define( 'SWVT_HR_FILE', __FILE__ );
define( 'SWVT_HR_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWVT_HR_URL', plugin_dir_url( __FILE__ ) );
define( 'SWVT_HR_CAP', 'manage_swvt_hr' );

require_once SWVT_HR_DIR . 'includes/class-activator.php';
require_once SWVT_HR_DIR . 'includes/class-deactivator.php';
require_once SWVT_HR_DIR . 'includes/class-swvt-hr.php';

register_activation_hook( __FILE__, [ 'SWVT_HR_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SWVT_HR_Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'swvt-hr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    SWVT_HR_Activator::maybe_upgrade();
    SWVT_HR::instance();
} );
