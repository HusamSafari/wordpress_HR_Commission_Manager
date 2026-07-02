<?php
/**
 * Admin Notifications Notices Partial (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$m = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$y = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$branch_id = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;

// 1. Check if sales entries exist
$sales_count_query = "SELECT COUNT(*) FROM {$p}sales WHERE period_month = %d AND period_year = %d";
if ( $branch_id ) {
    $sales_count_query .= $wpdb->prepare( " AND branch_id = %d", $branch_id );
}
$sales_exists = (int) $wpdb->get_var( $wpdb->prepare( $sales_count_query, $m, $y ) ) > 0;

// 2. Check if attendance registers exist
$att_count_query = "SELECT COUNT(*) FROM {$p}attendance WHERE period_month = %d AND period_year = %d";
if ( $branch_id ) {
    $att_count_query .= $wpdb->prepare( " AND branch_id = %d", $branch_id );
}
$att_exists = (int) $wpdb->get_var( $wpdb->prepare( $att_count_query, $m, $y ) ) > 0;

// 3. Check if payroll is generated
$pay_count_query = "SELECT COUNT(*) FROM {$p}payroll WHERE period_month = %d AND period_year = %d";
if ( $branch_id ) {
    $pay_count_query .= $wpdb->prepare( " AND branch_id = %d", $branch_id );
}
$pay_exists = (int) $wpdb->get_var( $wpdb->prepare( $pay_count_query, $m, $y ) ) > 0;
?>

<!-- Actionable Warnings / Alerts -->
<div style="margin-bottom: 20px;">
    <?php if ( ! $sales_exists ) : ?>
        <div class="swvt-hr-alert swvt-hr-alert-warning">
            <div class="swvt-hr-alert-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"></path><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path></svg>
            </div>
            <div style="line-height:1.4;">
                <div style="font-weight:600; font-size:13.5px;"><?php esc_html_e( 'Sales Data Missing', 'swvt-hr' ); ?></div>
                <div style="font-size:12px; color:#646970;">
                    <?php esc_html_e( 'Monthly sales targets have not been submitted for this period. Commissions and distributions cannot be calculated.', 'swvt-hr' ); ?>
                    <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-sales&m=' . $m . '&y=' . $y . '&branch=' . $branch_id ); ?>" style="color:#b78a00; font-weight:600; text-decoration:underline; margin-left:5px;"><?php esc_html_e( 'Enter Sales Now', 'swvt-hr' ); ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! $att_exists ) : ?>
        <div class="swvt-hr-alert swvt-hr-alert-info">
            <div class="swvt-hr-alert-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            </div>
            <div style="line-height:1.4;">
                <div style="font-weight:600; font-size:13.5px;"><?php esc_html_e( 'Attendance Logs Empty', 'swvt-hr' ); ?></div>
                <div style="font-size:12px; color:#646970;">
                    <?php esc_html_e( 'Employee attendance and absence logs have not been registered for this period. Absence salary deductions will not apply.', 'swvt-hr' ); ?>
                    <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-attendance&m=' . $m . '&y=' . $y . '&branch=' . $branch_id ); ?>" style="color:#2271b1; font-weight:600; text-decoration:underline; margin-left:5px;"><?php esc_html_e( 'Record Attendance Now', 'swvt-hr' ); ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( ! $pay_exists ) : ?>
        <div class="swvt-hr-alert swvt-hr-alert-error">
            <div class="swvt-hr-alert-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
            </div>
            <div style="line-height:1.4;">
                <div style="font-weight:600; font-size:13.5px;"><?php esc_html_e( 'Payroll Not Compiled', 'swvt-hr' ); ?></div>
                <div style="font-size:12px; color:#646970;">
                    <?php esc_html_e( 'The monthly payroll sheet has not been compiled or calculated for this period.', 'swvt-hr' ); ?>
                    <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-payroll&m=' . $m . '&y=' . $y . '&branch=' . $branch_id ); ?>" style="color:#d63638; font-weight:600; text-decoration:underline; margin-left:5px;"><?php esc_html_e( 'Generate Payroll Sheet', 'swvt-hr' ); ?></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
