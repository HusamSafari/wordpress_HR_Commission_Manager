<?php
/**
 * KPI Cards Grid Partial (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $kpis ) ) {
    $m = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
    $y = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
    $kpis = SWVT_HR_Report_Service::get_kpis( $m, $y );
}

$currency = 'EGP';
?>

<div class="swvt-hr-grid-6">
    <!-- 1. Branches KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#eaf2fb; color:#2271b1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 9h7a1 1 0 0 1 1 1v11"></path></svg>
            </div>
            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970; font-size:11px;">
                <?php printf( __( '%d Active', 'swvt-hr' ), $kpis['active_branches'] ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value"><?php echo number_format( $kpis['total_branches'] ); ?></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Total Branches', 'swvt-hr' ); ?></div>
    </div>

    <!-- 2. Employees KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#f3edfb; color:#7c3aed;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path></svg>
            </div>
            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970; font-size:11px;">
                <?php printf( __( 'of %d', 'swvt-hr' ), $kpis['total_employees'] ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value"><?php echo number_format( $kpis['active_employees'] ); ?></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Active Employees', 'swvt-hr' ); ?></div>
    </div>

    <!-- 3. Monthly Sales KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#fdf4dd; color:#b78a00;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 14l4-4 3 3 5-6"></path></svg>
            </div>
            <span class="swvt-hr-badge swvt-hr-badge-success" style="font-size:11px;">
                <?php esc_html_e( 'Sales', 'swvt-hr' ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value" style="font-size: 20px; font-variant-numeric: tabular-nums;"><?php echo number_format( $kpis['sales_sum'], 0 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;"><?php echo $currency; ?></span></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Monthly Sales', 'swvt-hr' ); ?></div>
    </div>

    <!-- 4. Commissions KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#eaf2fb; color:#2271b1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21v-7M12 21v-9M20 21v-5M1 14h6M9 8h6M17 16h6"></path></svg>
            </div>
            <span class="swvt-hr-badge swvt-hr-badge-success" style="font-size:11px;">
                <?php esc_html_e( 'Commissions', 'swvt-hr' ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value" style="font-size: 20px; font-variant-numeric: tabular-nums;"><?php echo number_format( $kpis['commission_sum'], 2 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;"><?php echo $currency; ?></span></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Total Commissions', 'swvt-hr' ); ?></div>
    </div>

    <!-- 5. Payroll KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#e7f6ec; color:#0a7c2f;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2ZM3 10h18"></path></svg>
            </div>
            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970; font-size:11px;">
                <?php esc_html_e( 'Payroll', 'swvt-hr' ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value" style="font-size: 20px; font-variant-numeric: tabular-nums;"><?php echo number_format( $kpis['payroll_sum'], 0 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;"><?php echo $currency; ?></span></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Total Payroll', 'swvt-hr' ); ?></div>
    </div>

    <!-- 6. Absence KPI -->
    <div class="swvt-hr-kpi-card">
        <div class="swvt-hr-kpi-header">
            <div class="swvt-hr-kpi-icon" style="background:#fbe9ea; color:#b32d2e;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4"></path></svg>
            </div>
            <span class="swvt-hr-badge swvt-hr-badge-error" style="font-size:11px;">
                <?php printf( _n( '%d Case', '%d Cases', $kpis['absence_cases'], 'swvt-hr' ), $kpis['absence_cases'] ); ?>
            </span>
        </div>
        <div class="swvt-hr-kpi-value" style="font-size: 20px; font-variant-numeric: tabular-nums;"><?php echo number_format( $kpis['absence_deduction'], 2 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;"><?php echo $currency; ?></span></div>
        <div class="swvt-hr-kpi-label"><?php esc_html_e( 'Absence Deductions', 'swvt-hr' ); ?></div>
    </div>
</div>
