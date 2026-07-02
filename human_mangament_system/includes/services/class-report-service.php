<?php
/**
 * Report Service (English).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Report_Service {

    private static function get_cached_query( $key, $month, $year, $query_fn, $branch_id = 0 ) {
        $transient_key = sprintf( 'swvt_hr_rep_%s_%d_%d_%d', $key, $month, $year, $branch_id );
        $cached = get_transient( $transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $result = $query_fn();
        set_transient( $transient_key, $result, 5 * MINUTE_IN_SECONDS );
        return $result;
    }

    public static function clear_cache( $month, $year, $branch_id = 0 ) {
        $keys = [ 'sales_branch', 'comm_branch', 'pay_branch', 'abs_branch', 'top_sales', 'top_comm', 'top_pay', 'top_abs', 'kpis', 'comm_trend' ];
        foreach ( $keys as $key ) {
            delete_transient( sprintf( 'swvt_hr_rep_%s_%d_%d_%d', $key, $month, $year, $branch_id ) );
        }
    }

    public static function get_sales_by_branch( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'sales_branch', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            $sql = $wpdb->prepare(
                "SELECT b.name as branch_name, COALESCE(s.total_sales, 0.00) as total_sales, COALESCE(s.target, b.sales_target) as target
                 FROM {$p}branches b
                 LEFT JOIN {$p}sales s ON b.id = s.branch_id AND s.period_month = %d AND s.period_year = %d
                 WHERE b.status = 'active'",
                $month, $year
            );

            if ( $branch_id ) {
                $sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }

            $sql .= ' ORDER BY b.id ASC';

            return $wpdb->get_results( $sql );
        }, $branch_id );
    }

    public static function get_commissions_by_branch( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'comm_branch', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            $sql = $wpdb->prepare(
                "SELECT b.name as branch_name, COALESCE(SUM(c.final_amount), 0.00) as total_commission
                 FROM {$p}branches b
                 LEFT JOIN {$p}sales s ON b.id = s.branch_id AND s.period_month = %d AND s.period_year = %d
                 LEFT JOIN {$p}commissions c ON s.id = c.sales_id
                 WHERE b.status = 'active'",
                $month, $year
            );

            if ( $branch_id ) {
                $sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }

            $sql .= ' GROUP BY b.id ORDER BY b.id ASC';

            return $wpdb->get_results( $sql );
        }, $branch_id );
    }

    public static function get_payroll_by_branch( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'pay_branch', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            $sql = $wpdb->prepare(
                "SELECT b.name as branch_name, COALESCE(SUM(pay.net_salary), 0.00) as total_payroll
                 FROM {$p}branches b
                 LEFT JOIN {$p}payroll pay ON b.id = pay.branch_id AND pay.period_month = %d AND pay.period_year = %d
                 WHERE b.status = 'active'",
                $month, $year
            );

            if ( $branch_id ) {
                $sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }

            $sql .= ' GROUP BY b.id ORDER BY b.id ASC';

            return $wpdb->get_results( $sql );
        }, $branch_id );
    }

    public static function get_absence_by_branch( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'abs_branch', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            $sql = $wpdb->prepare(
                "SELECT b.name as branch_name, COALESCE(SUM(att.absence_days), 0.00) as total_absence_days
                 FROM {$p}branches b
                 LEFT JOIN {$p}attendance att ON b.id = att.branch_id AND att.period_month = %d AND att.period_year = %d
                 WHERE b.status = 'active'",
                $month, $year
            );

            if ( $branch_id ) {
                $sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }

            $sql .= ' GROUP BY b.id ORDER BY b.id ASC';

            return $wpdb->get_results( $sql );
        }, $branch_id );
    }

    public static function get_kpis( $month, $year ) {
        return self::get_cached_query( 'kpis', $month, $year, function() use ( $month, $year ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';

            $total_branches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}branches" );
            $active_branches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}branches WHERE status='active'" );
            $active_employees = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}employees WHERE status='active'" );
            $total_employees = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}employees" );

            $sales_sum = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(total_sales) FROM {$p}sales WHERE period_month = %d AND period_year = %d",
                $month, $year
            ) );

            $commission_sum = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(c.final_amount)
                 FROM {$p}commissions c
                 JOIN {$p}sales s ON s.id = c.sales_id
                 WHERE s.period_month = %d AND s.period_year = %d",
                $month, $year
            ) );

            $payroll_sum = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(net_salary) FROM {$p}payroll WHERE period_month = %d AND period_year = %d",
                $month, $year
            ) );

            $absence_deduction_sum = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(deduction) FROM {$p}attendance WHERE period_month = %d AND period_year = %d",
                $month, $year
            ) );

            $absence_cases = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$p}attendance WHERE period_month = %d AND period_year = %d AND absence_days > 0",
                $month, $year
            ) );

            return [
                'total_branches'   => $total_branches,
                'active_branches'  => $active_branches,
                'active_employees' => $active_employees,
                'total_employees'  => $total_employees,
                'sales_sum'        => $sales_sum,
                'commission_sum'   => $commission_sum,
                'payroll_sum'      => $payroll_sum,
                'absence_deduction'=> $absence_deduction_sum,
                'absence_cases'    => $absence_cases,
            ];
        } );
    }

    public static function get_rep_kpis( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'top_sales', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';

            $branch_sales_sql = $wpdb->prepare(
                "SELECT b.name, s.total_sales
                 FROM {$p}sales s
                 JOIN {$p}branches b ON b.id = s.branch_id
                 WHERE s.period_month = %d AND s.period_year = %d",
                $month, $year
            );
            if ( $branch_id ) {
                $branch_sales_sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }
            $branch_sales_sql .= ' ORDER BY s.total_sales DESC LIMIT 1';
            $top_sales = $wpdb->get_row( $branch_sales_sql );

            $top_comm_sql = $wpdb->prepare(
                "SELECT b.name, SUM(c.final_amount) as total_commission
                 FROM {$p}commissions c
                 JOIN {$p}sales s ON s.id = c.sales_id
                 JOIN {$p}branches b ON b.id = s.branch_id
                 WHERE s.period_month = %d AND s.period_year = %d",
                $month, $year
            );
            if ( $branch_id ) {
                $top_comm_sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }
            $top_comm_sql .= ' GROUP BY b.id ORDER BY total_commission DESC LIMIT 1';
            $top_comm = $wpdb->get_row( $top_comm_sql );

            $top_pay_sql = $wpdb->prepare(
                "SELECT b.name, SUM(pay.net_salary) as total_payroll
                 FROM {$p}payroll pay
                 JOIN {$p}branches b ON b.id = pay.branch_id
                 WHERE pay.period_month = %d AND pay.period_year = %d",
                $month, $year
            );
            if ( $branch_id ) {
                $top_pay_sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }
            $top_pay_sql .= ' GROUP BY b.id ORDER BY total_payroll DESC LIMIT 1';
            $top_pay = $wpdb->get_row( $top_pay_sql );

            $top_abs_sql = $wpdb->prepare(
                "SELECT b.name, SUM(att.absence_days) as total_absence
                 FROM {$p}attendance att
                 JOIN {$p}branches b ON b.id = att.branch_id
                 WHERE att.period_month = %d AND att.period_year = %d",
                $month, $year
            );
            if ( $branch_id ) {
                $top_abs_sql .= $wpdb->prepare( ' AND b.id = %d', $branch_id );
            }
            $top_abs_sql .= ' GROUP BY b.id ORDER BY total_absence DESC LIMIT 1';
            $top_abs = $wpdb->get_row( $top_abs_sql );

            return [
                'top_sales' => $top_sales ? [ 'name' => $top_sales->name, 'value' => $top_sales->total_sales ] : [ 'name' => 'None', 'value' => 0.00 ],
                'top_comm'  => $top_comm ? [ 'name' => $top_comm->name, 'value' => $top_comm->total_commission ] : [ 'name' => 'None', 'value' => 0.00 ],
                'top_pay'   => $top_pay ? [ 'name' => $top_pay->name, 'value' => $top_pay->total_payroll ] : [ 'name' => 'None', 'value' => 0.00 ],
                'top_abs'   => $top_abs ? [ 'name' => $top_abs->name, 'value' => $top_abs->total_absence ] : [ 'name' => 'None', 'value' => 0.00 ],
            ];
        }, $branch_id );
    }

    public static function get_commission_trend( $month, $year, $branch_id = 0 ) {
        return self::get_cached_query( 'comm_trend', $month, $year, function() use ( $month, $year, $branch_id ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';

            $months = [];
            for ( $i = 5; $i >= 0; $i-- ) {
                $time = mktime( 0, 0, 0, $month - $i, 1, $year );
                $m = (int) date( 'n', $time );
                $y = (int) date( 'Y', $time );
                $months[] = [ 'month' => $m, 'year' => $y, 'label' => self::get_month_name( $m ) ];
            }

            $trend = [];
            foreach ( $months as $item ) {
                $sql = $wpdb->prepare(
                    "SELECT SUM(c.final_amount)
                     FROM {$p}commissions c
                     JOIN {$p}sales s ON s.id = c.sales_id
                     WHERE s.period_month = %d AND s.period_year = %d",
                    $item['month'], $item['year']
                );
                if ( $branch_id ) {
                    $sql .= $wpdb->prepare( ' AND s.branch_id = %d', $branch_id );
                }
                $total = (float) $wpdb->get_var( $sql );
                $trend[] = [
                    'label' => $item['label'],
                    'value' => $total
                ];
            }
            return $trend;
        }, $branch_id );
    }

    private static function get_month_name( $m ) {
        $names = [
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        return $names[ $m ] ?? '';
    }
}
