<?php
/**
 * Employee Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Employee {

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}swvt_hr_employees ORDER BY id ASC" );
    }

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swvt_hr_employees WHERE id = %d", $id ) );
    }

    public static function get_by_branch( $branch_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swvt_hr_employees WHERE branch_id = %d AND status = 'active' ORDER BY id ASC",
            $branch_id
        ) );
    }
}
