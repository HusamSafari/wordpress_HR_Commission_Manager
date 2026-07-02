<?php
/**
 * Payroll Service.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Payroll_Service {

    /**
     * Compute daily salary and return the absence deduction:
     * daily_salary = basic_salary / divisor (30)
     * absence_deduction = round(absence_days * daily_salary, 2)
     */
    public static function absence_deduction( $basic_salary, $absence_days ) {
        $settings = get_option( 'swvt_hr_settings' );
        $divisor = isset( $settings['daily_salary_divisor'] ) ? (int) $settings['daily_salary_divisor'] : 30;
        if ( $divisor <= 0 ) {
            $divisor = 30;
        }
        $daily = $basic_salary / $divisor;
        return round( $absence_days * $daily, 2 );
    }

    /**
     * Build or refresh payroll rows for a specific month/year.
     * Optionally filters by a single branch.
     */
    public static function generate( $month, $year, $branch_id = 0 ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        
        // Find active employees (optionally filtered by branch)
        $where = "status='active'";
        if ( $branch_id ) {
            $where .= $wpdb->prepare( ' AND branch_id = %d', $branch_id );
        }
        $emps = $wpdb->get_results( "SELECT * FROM {$p}employees WHERE {$where}" );

        foreach ( $emps as $e ) {
            // 1. Pull accumulated commission for the period
            $commission = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(c.final_amount), 0)
                 FROM {$p}commissions c
                 JOIN {$p}sales s ON s.id = c.sales_id
                 WHERE c.employee_id = %d AND s.period_month = %d AND s.period_year = %d",
                $e->id, $month, $year
            ) );

            // 2. Pull attendance absence deduction
            $att = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$p}attendance WHERE employee_id = %d AND period_month = %d AND period_year = %d",
                $e->id, $month, $year
            ) );
            $absence = $att ? (float) $att->deduction : 0.0;

            // 3. Keep existing manual fields (bonus / other_deduction) if the row exists
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT bonus, other_deduction, status FROM {$p}payroll
                 WHERE employee_id = %d AND period_month = %d AND period_year = %d",
                $e->id, $month, $year
            ) );
            $bonus = $existing ? (float) $existing->bonus : 0.0;
            $other = $existing ? (float) $existing->other_deduction : 0.0;
            $status = $existing ? $existing->status : 'pending';

            // 4. Compute Net Salary
            $net = round( $e->basic_salary + $commission + $bonus - $absence - $other, 2 );

            // 5. Save or update payroll record
            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$p}payroll
                   (employee_id, branch_id, period_month, period_year, basic_salary, commission,
                    absence_deduction, bonus, other_deduction, net_salary, status)
                 VALUES (%d, %d, %d, %d, %f, %f, %f, %f, %f, %f, %s)
                 ON DUPLICATE KEY UPDATE
                    basic_salary = VALUES(basic_salary),
                    commission = VALUES(commission),
                    absence_deduction = VALUES(absence_deduction),
                    net_salary = VALUES(net_salary)",
                $e->id, $e->branch_id, $month, $year,
                $e->basic_salary, $commission, $absence, $bonus, $other, $net, $status
            ) );
        }
    }
}
