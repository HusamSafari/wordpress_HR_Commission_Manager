<?php
/**
 * Advanced Enterprise Admin Dashboard (Odoo Style).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$selected_month = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year  = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );

$start_month = sprintf( '%d-%02d-01', $selected_year, $selected_month );
$end_month   = date( 'Y-m-t', strtotime( $start_month ) );

// ==========================================
// CALCULATING ALL 15 DYNAMIC WIDGET METRICS
// ==========================================

// 1. Total Branches
$total_branches = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$p}branches" );

// 2. Total Employees
$total_employees = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$p}employees" );

// 3. Active Employees
$active_employees = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$p}employees WHERE status = 'active'" );

// 4. Inactive Employees
$inactive_employees = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$p}employees WHERE status = 'inactive'" );

// 5. Monthly Sales
$monthly_sales = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$p}sales_entries WHERE entry_date >= %s AND entry_date <= %s",
    $start_month, $end_month
) );

// 6. Monthly Target
$monthly_target = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(sales_target), 0) FROM {$p}branch_targets WHERE target_type = 'monthly' AND period_value = %d AND period_year = %d",
    $selected_month, $selected_year
) );
if ( $monthly_target <= 0 ) {
    $monthly_target = (float) $wpdb->get_var( "SELECT COALESCE(SUM(sales_target), 0) FROM {$p}branches WHERE status = 'active'" );
}

// 7. Target Achievement %
$achievement_pct = $monthly_target > 0 ? ( $monthly_sales / $monthly_target ) * 100 : 0.00;

// 8. Monthly Payroll
$monthly_payroll = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(net_salary), 0) FROM {$p}payroll WHERE period_month = %d AND period_year = %d",
    $selected_month, $selected_year
) );

// 9. Total Commissions
$total_commissions = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(final_amount), 0) FROM {$p}commissions c JOIN {$p}sales s ON c.sales_id = s.id WHERE s.period_month = %d AND s.period_year = %d",
    $selected_month, $selected_year
) );

// 10. Today Attendance (Attendance Rate)
$attendance_rate = 100.00;
$att_average = $wpdb->get_var( $wpdb->prepare(
    "SELECT AVG(100 - (absence_days * 5)) FROM {$p}attendance WHERE period_month = %d AND period_year = %d",
    $selected_month, $selected_year
) );
if ( ! is_null( $att_average ) ) {
    $attendance_rate = max( 0, min( 100, (float) $att_average ) );
}

// 11. Absent Today (Total absences logged this month)
$absent_days = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(absence_days), 0) FROM {$p}attendance WHERE period_month = %d AND period_year = %d",
    $selected_month, $selected_year
) );

// 12. Late Today (Total late hours logged this month)
$late_hours = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(late_hours), 0) FROM {$p}attendance WHERE period_month = %d AND period_year = %d",
    $selected_month, $selected_year
) );

// 13. Pending Payroll (Count of pending payroll slips)
$pending_payroll_count = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(id) FROM {$p}payroll WHERE period_month = %d AND period_year = %d AND status = 'pending'",
    $selected_month, $selected_year
) );

// 14/15. Best / Worst Performing Branch
$branch_performance = $wpdb->get_results( $wpdb->prepare(
    "SELECT b.name, COALESCE(SUM(se.amount), 0) as total_amount 
     FROM {$p}branches b 
     LEFT JOIN {$p}sales_entries se ON b.id = se.branch_id AND se.entry_date >= %s AND se.entry_date <= %s
     WHERE b.status = 'active'
     GROUP BY b.id 
     ORDER BY total_amount DESC",
    $start_month, $end_month
) );

$best_branch_name = '—';
$best_branch_amount = 0.00;
$worst_branch_name = '—';
$worst_branch_amount = 0.00;

if ( ! empty( $branch_performance ) ) {
    $best = $branch_performance[0];
    if ( $best->total_amount > 0 ) {
        $best_branch_name = $best->name;
        $best_branch_amount = (float) $best->total_amount;
    }
    
    $worst = end( $branch_performance );
    if ( $worst && $worst->total_amount >= 0 ) {
        $worst_branch_name = $worst->name;
        $worst_branch_amount = (float) $worst->total_amount;
    }
}

// Expiring Contracts check
$expiring_contracts = $wpdb->get_results(
    "SELECT id, full_name, hire_date FROM {$p}employees WHERE status='active' AND hire_date IS NOT NULL ORDER BY hire_date ASC LIMIT 3"
);

// Recent activity logs
$recent_activities = $wpdb->get_results(
    "SELECT * FROM {$p}activity_logs ORDER BY created_at DESC LIMIT 6"
);

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = __( 'ERP Administration Dashboard', 'swvt-hr' );
?>

<style>
    .swvt-hr-dash-widgets-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .swvt-hr-dash-widget-card {
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        transition: all 0.2s ease;
    }
    .swvt-hr-dash-widget-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border-color: #2271b1;
    }
    .swvt-hr-dash-widget-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .swvt-hr-dash-widget-icon {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f6fc;
        color: #2271b1;
    }
    .swvt-hr-dash-widget-title {
        font-size: 10.5px;
        font-weight: 700;
        color: #646970;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .swvt-hr-dash-widget-value {
        font-size: 17px;
        font-weight: 700;
        color: #1d2327;
        font-variant-numeric: tabular-nums;
    }
    .swvt-hr-dash-widget-desc {
        font-size: 10px;
        color: #8c8f94;
        margin-top: 4px;
    }
    .swvt-hr-dash-widget-trend {
        font-size: 9.5px;
        font-weight: 700;
        position: absolute;
        right: 12px;
        bottom: 12px;
    }
    .swvt-hr-dash-layout-split {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    @media (max-width: 1200px) {
        .swvt-hr-dash-widgets-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 768px) {
        .swvt-hr-dash-widgets-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .swvt-hr-dash-layout-split {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 480px) {
        .swvt-hr-dash-widgets-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap swvt-hr-wrap">
    
    <!-- Header bar -->
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            <div>
                <h1 style="font-size: 21px; font-weight: 600; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
                <p style="font-size: 12px; color:#646970; margin:0;"><?php esc_html_e( 'Enterprise operational summary metrics, targets analysis, and staff overview dashboard.', 'swvt-hr' ); ?></p>
            </div>
        </div>

        <form method="get" style="display:flex; gap:6px; margin:0;">
            <select name="m" class="swvt-hr-select" style="width: 120px;">
                <?php foreach ( $months_english as $num => $name ) : ?>
                    <option value="<?php echo $num; ?>" <?php selected( $selected_month, $num ); ?>><?php echo esc_html( $name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="y" class="swvt-hr-select" style="width: 90px;">
                <?php 
                $curr_year = (int) date('Y');
                for ( $year_val = $curr_year + 1; $year_val >= $curr_year - 5; $year_val-- ) : 
                ?>
                    <option value="<?php echo $year_val; ?>" <?php selected( $selected_year, $year_val ); ?>><?php echo $year_val; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary"><?php esc_html_e( 'Update Filters', 'swvt-hr' ); ?></button>
        </form>
    </div>

    <!-- Quick Actions Panel -->
    <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:8px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
        <h4 style="margin: 0 0 12px; font-size:12px; font-weight:700; color:#1d2327; text-transform:uppercase;">Quick Actions Launcher</h4>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-branches-add'); ?>" class="swvt-hr-btn swvt-hr-btn-primary">+ Add Branch</a>
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-employees-add'); ?>" class="swvt-hr-btn swvt-hr-btn-primary">+ Add Employee</a>
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-sales'); ?>" class="swvt-hr-btn">Log Sales</a>
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-payroll'); ?>" class="swvt-hr-btn">Run Payroll</a>
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-attendance'); ?>" class="swvt-hr-btn">Mark Attendance</a>
            <a href="<?php echo admin_url('admin.php?page=swvt-hr-activity-logs'); ?>" class="swvt-hr-btn">Audit Logs</a>
        </div>
    </div>

    <!-- 15 Dynamic ERP Widgets Grid -->
    <div class="swvt-hr-dash-widgets-grid">
        <!-- 1. Total Branches -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-branches'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 9h7a1 1 0 0 1 1 1v11"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Total Branches</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo $total_branches; ?></div>
            <span class="swvt-hr-dash-widget-desc">Registered locations</span>
        </div>

        <!-- 2. Total Employees -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-employees'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Total Staff</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo $total_employees; ?></div>
            <span class="swvt-hr-dash-widget-desc">Overall payroll directory</span>
        </div>

        <!-- 3. Active Employees -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#e6f4ea; color:#137333;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Active Staff</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo $active_employees; ?></div>
            <span class="swvt-hr-dash-widget-desc">Onboarded active staff</span>
        </div>

        <!-- 4. Inactive Employees -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fce8e6; color:#c5221f;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Inactive Staff</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo $inactive_employees; ?></div>
            <span class="swvt-hr-dash-widget-desc">Suspended or left</span>
        </div>

        <!-- 5. Monthly Sales -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-sales'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#eaf2fb; color:#1a73e8;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Monthly Sales</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($monthly_sales, 0); ?></div>
            <span class="swvt-hr-dash-widget-desc">Aggregated sales this month</span>
        </div>

        <!-- 6. Monthly Target -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Sales Target</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($monthly_target, 0); ?></div>
            <span class="swvt-hr-dash-widget-desc">Monthly active target quota</span>
        </div>

        <!-- 7. Target Achievement % -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#eaf8f2; color:#0f9d58;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14M22 4 12 14.01l-3-3"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Achievement</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($achievement_pct, 1); ?>%</div>
            <span class="swvt-hr-dash-widget-desc">Quota success run-rate</span>
        </div>

        <!-- 8. Monthly Payroll -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-payroll'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fdf4dd; color:#b78a00;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Monthly Payroll</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($monthly_payroll, 0); ?></div>
            <span class="swvt-hr-dash-widget-desc">Net salary payouts</span>
        </div>

        <!-- 9. Total Commissions -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-commissions'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fce8e6; color:#c5221f;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Commissions</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($total_commissions, 0); ?></div>
            <span class="swvt-hr-dash-widget-desc">Shared commission rewards</span>
        </div>

        <!-- 10. Today Attendance -->
        <div class="swvt-hr-dash-widget-card" onclick="window.location.href='admin.php?page=swvt-hr-attendance'">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#e8f0fe; color:#1a73e8;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Attendance</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($attendance_rate, 1); ?>%</div>
            <span class="swvt-hr-dash-widget-desc">Active average attendance</span>
        </div>

        <!-- 11. Absent Today -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fce8e6; color:#c5221f;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Absentees</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($absent_days, 1); ?> days</div>
            <span class="swvt-hr-dash-widget-desc">Absences logged this month</span>
        </div>

        <!-- 12. Late Today -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fdf4dd; color:#b78a00;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Lateness</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo number_format($late_hours, 1); ?> hrs</div>
            <span class="swvt-hr-dash-widget-desc">Late hours logged this month</span>
        </div>

        <!-- 13. Pending Payroll -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fce8e6; color:#c5221f;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4M12 16h.01M22 12A10 10 0 1 1 12 2a10 10 0 0 1 10 10Z"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Pending Slips</span>
            </div>
            <div class="swvt-hr-dash-widget-value"><?php echo $pending_payroll_count; ?> slips</div>
            <span class="swvt-hr-dash-widget-desc">Unpaid payroll records</span>
        </div>

        <!-- 14. Best Branch -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#eaf8f2; color:#0f9d58;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.45 1-1 1H4v2h16v-2h-5c-.55 0-1-.45-1-1v-2.34M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Best Branch</span>
            </div>
            <div class="swvt-hr-dash-widget-value" style="font-size: 13.5px;"><?php echo esc_html($best_branch_name); ?></div>
            <span class="swvt-hr-dash-widget-desc"><?php echo number_format($best_branch_amount, 0); ?> EGP</span>
        </div>

        <!-- 15. Worst Branch -->
        <div class="swvt-hr-dash-widget-card">
            <div class="swvt-hr-dash-widget-header">
                <div class="swvt-hr-dash-widget-icon" style="background:#fce8e6; color:#c5221f;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v4M2 9l3 3-3 3M14 12H2"/></svg></div>
                <span class="swvt-hr-dash-widget-title">Worst Branch</span>
            </div>
            <div class="swvt-hr-dash-widget-value" style="font-size: 13.5px;"><?php echo esc_html($worst_branch_name); ?></div>
            <span class="swvt-hr-dash-widget-desc"><?php echo number_format($worst_branch_amount, 0); ?> EGP</span>
        </div>
    </div>

    <!-- Main Dashboard Split Layout -->
    <div class="swvt-hr-dash-layout-split">
        
        <!-- Left: Recent System-wide Activity Logs -->
        <div class="swvt-hr-dash-card">
            <div class="swvt-hr-dash-card-header">
                <h3 class="swvt-hr-dash-card-title">Recent Activity Logs</h3>
                <a href="<?php echo admin_url('admin.php?page=swvt-hr-activity-logs'); ?>" style="font-size:11.5px; text-decoration:none; color:#2271b1;">View All &rarr;</a>
            </div>
            <div style="padding: 16px;">
                <?php if ( empty( $recent_activities ) ) : ?>
                    <p style="color:#787c82; font-size:12.5px;"><?php esc_html_e( 'No system operations logged yet.', 'swvt-hr' ); ?></p>
                <?php else : ?>
                    <ul style="list-style:none; padding:0; margin:0; font-size:12.5px;">
                        <?php foreach ( $recent_activities as $act ) : 
                            $act_user = get_userdata( $act->user_id );
                            $act_name = $act_user ? $act_user->display_name : 'System';
                        ?>
                            <li style="border-bottom:1px solid #f0f1f2; padding:8px 0; display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <span style="font-size:9.5px; font-weight:700; background:#f0f6fc; color:#2271b1; padding:2px 5px; border-radius:3px; text-transform:uppercase; margin-right:6px;"><?php echo esc_html($act->module_key); ?></span>
                                    <strong><?php echo esc_html($act_name); ?></strong>: <?php echo esc_html($act->description); ?>
                                </div>
                                <span style="font-size:10.5px; color:#8c8f94; font-variant-numeric:tabular-nums;"><?php echo date('H:i', strtotime($act->created_at)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Warnings & Notifications -->
        <div style="display:flex; flex-direction:column; gap:15px;">
            <!-- Expiring Contracts -->
            <div class="swvt-hr-dash-card" style="margin-bottom:0;">
                <div class="swvt-hr-dash-card-header">
                    <h3 class="swvt-hr-dash-card-title">Staff Onboarding Status</h3>
                </div>
                <div style="padding:15px; font-size:12.5px;">
                    <?php if ( empty( $expiring_contracts ) ) : ?>
                        <p style="color:#787c82;"><?php esc_html_e( 'No onboarding profiles found.', 'swvt-hr' ); ?></p>
                    <?php else : 
                        foreach ( $expiring_contracts as $ec ) :
                    ?>
                        <div style="margin-bottom:10px; border-bottom:1px solid #f0f1f2; padding-bottom:8px;">
                            <div><strong><?php echo esc_html($ec->full_name); ?></strong></div>
                            <div style="font-size:11px; color:#787c82;">Hired: <?php echo esc_html($ec->hire_date); ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
