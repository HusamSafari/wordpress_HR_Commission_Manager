<?php
/**
 * Commission Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Commission {

    public static function get_by_sales( $sales_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swvt_hr_commissions WHERE sales_id = %d ORDER BY amount DESC",
            $sales_id
        ) );
    }

    public static function get_employee_commissions( $employee_id, $month, $year ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT c.* FROM {$wpdb->prefix}swvt_hr_commissions c
             JOIN {$wpdb->prefix}swvt_hr_sales s ON s.id = c.sales_id
             WHERE c.employee_id = %d AND s.period_month = %d AND s.period_year = %d",
            $employee_id, $month, $year
        ) );
    }
}
