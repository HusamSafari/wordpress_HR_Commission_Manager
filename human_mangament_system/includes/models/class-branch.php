<?php
/**
 * Branch Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Branch {

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swvt_hr_branches ORDER BY id ASC" );
    }

    public static function get_active() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swvt_hr_branches WHERE status = 'active' ORDER BY id ASC" );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swvt_hr_branches WHERE id = %d", $id ) );
    }

    public static function get_by_code( $code ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swvt_hr_branches WHERE code = %s", $code ) );
    }
}
