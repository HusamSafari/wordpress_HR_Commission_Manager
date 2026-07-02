<?php
/**
 * Commission Service.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Commission_Service {

    /**
     * Get the commission rate for a branch: branch override if set, otherwise default settings.
     */
    public static function rate_for_branch( $branch ) {
        $settings = get_option( 'swvt_hr_settings' );
        return ( ! empty( $branch->commission_rate ) && $branch->commission_rate > 0 ) 
            ? (float) $branch->commission_rate 
            : (float) $settings['default_commission_rate'];
    }

    /**
     * Compute commission base: total_sales * rate (rounded to 2 decimal places).
     */
    public static function base( $total_sales, $rate ) {
        return round( $total_sales * $rate, 2 );
    }

    /**
     * Distributes the commission base across the branch's active, eligible employees by role percentage.
     * Returns an array of rows ready to insert into the commissions table.
     */
    public static function distribute( $sales_row ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $settings = get_option( 'swvt_hr_settings' );
        $dist = $settings['role_distribution']; // e.g. ['manager'=>60, 'accountant'=>20, 'delivery'=>10, 'prep'=>10]
        $base = $sales_row->commission_base;

        $employees = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}employees
             WHERE branch_id = %d AND status = 'active' AND commission_eligible = 1",
            $sales_row->branch_id
        ) );

        // Group employees by role key so we can distribute role's amount equally
        $by_role = [];
        foreach ( $employees as $e ) {
            $by_role[ $e->role_key ][] = $e;
        }

        $rows = [];
        foreach ( $dist as $role_key => $percent ) {
            $peers = $by_role[ $role_key ] ?? [];
            if ( empty( $peers ) ) {
                continue; // No staff in this role, skip distribution for this role
            }

            // Role total amount
            $role_amount = round( $base * ( $percent / 100 ), 2 );
            // Amount per employee
            $each = round( $role_amount / count( $peers ), 2 );

            foreach ( $peers as $emp ) {
                $absence = self::absence_on_commission( $emp->id, $sales_row->period_month, $sales_row->period_year );
                $rows[] = [
                    'sales_id'          => $sales_row->id,
                    'employee_id'       => $emp->id,
                    'role_key'          => $role_key,
                    'role_percent'      => (float) ( $percent / count( $peers ) ),
                    'amount'            => $each,
                    'absence_deduction' => $absence,
                    'final_amount'      => round( $each - $absence, 2 ),
                ];
            }
        }
        return $rows;
    }

    private static function absence_on_commission( $emp_id, $m, $y ) {
        // Optional: pro-rate commission by absence. Default is 0.00 (absence only hits basic salary).
        return 0.00;
    }

    /**
     * Validate role distribution total. Must be exactly 100%.
     */
    public static function is_valid_distribution( array $dist ) {
        return array_sum( $dist ) === 100;
    }
}
