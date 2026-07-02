<?php
/**
 * Sales Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Sales {

    public static function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swvt_hr_sales WHERE id = %d", $id ) );
    }

    public static function get_by_branch_period( $branch_id, $month, $year ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swvt_hr_sales WHERE branch_id = %d AND period_month = %d AND period_year = %d",
            $branch_id, $month, $year
        ) );
    }
}
