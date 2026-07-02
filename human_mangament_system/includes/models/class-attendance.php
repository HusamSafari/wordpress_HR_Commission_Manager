<?php
/**
 * Attendance Model.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Attendance {

    public static function get_by_employee_period( $employee_id, $month, $year ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}swvt_hr_attendance WHERE employee_id = %d AND period_month = %d AND period_year = %d",
            $employee_id, $month, $year
        ) );
    }
}
