<?php
/**
 * Payroll Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Payroll {

    public static function get_by_employee_period( $employee_id, $month, $year ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swvt_hr_payroll WHERE employee_id = %d AND period_month = %d AND period_year = %d",
            $employee_id, $month, $year
        ) );
    }

    public static function get_by_period( $month, $year, $branch_id = 0 ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $where = $wpdb->prepare( "period_month = %d AND period_year = %d", $month, $year );
        if ( $branch_id ) {
            $where .= $wpdb->prepare( " AND branch_id = %d", $branch_id );
        }
        return $wpdb->get_results( "SELECT * FROM {$p}payroll WHERE {$where} ORDER BY id ASC" );
    }
}
