<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Preserve live data by default. To allow full cleanup, explicitly define:
// define( 'SWVT_HR_DELETE_DATA_ON_UNINSTALL', true );
if ( ! defined( 'SWVT_HR_DELETE_DATA_ON_UNINSTALL' ) || true !== SWVT_HR_DELETE_DATA_ON_UNINSTALL ) {
    return;
}

global $wpdb;

// Drop all custom tables in reverse order of foreign key dependency
$tables = [
    'payroll',
    'commissions',
    'attendance',
    'sales',
    'employees',
    'branches'
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swvt_hr_{$table}" );
}

// Delete settings options
delete_option( 'swvt_hr_settings' );
delete_option( 'swvt_hr_db_version' );
delete_option( 'swvt_hr_seeded' );

// Remove capability
$role = get_role( 'administrator' );
if ( $role ) {
    $role->remove_cap( 'manage_swvt_hr' );
}
