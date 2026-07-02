<?php
/**
 * Advanced ERP Branch Profile Dashboard (Odoo Style).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$branch_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$selected_year = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_month = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );

// If no branch_id is selected, default to the first active branch
if ( ! $branch_id ) {
    $first_branch_id = (int) $wpdb->get_var( "SELECT id FROM {$p}branches WHERE status='active' ORDER BY id ASC LIMIT 1" );
    if ( $first_branch_id ) {
        $branch_id = $first_branch_id;
    }
}

// Fetch all branches for switcher dropdown
$all_branches = $wpdb->get_results( "SELECT id, name, code FROM {$p}branches ORDER BY name ASC" );

// Load active branch details
$branch = null;
if ( $branch_id ) {
    $branch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}branches WHERE id = %d", $branch_id ) );
}

if ( ! $branch ) {
    echo '<div class="wrap"><h2>' . esc_html__( 'Branch not found.', 'swvt-hr' ) . '</h2></div>';
    return;
}

// ==========================================
// CONTROLLERS FOR CORE ERP INTERACTIONS
// ==========================================

// 1. Assign Employee
if ( isset( $_POST['swvt_assign_employee_nonce'] ) && wp_verify_nonce( $_POST['swvt_assign_employee_nonce'], 'swvt_assign_employee' ) ) {
    $emp_id = absint( $_POST['assign_employee_id'] );
    if ( $emp_id ) {
        $wpdb->update( $p . 'employees', [ 'branch_id' => $branch_id ], [ 'id' => $emp_id ] );
        $emp_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $emp_id ) );
        SWVT_HR_ERP_Service::log_activity( $branch_id, 'employee_joined', sprintf( 'Employee assigned: %s', $emp_name ) );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Employee assigned successfully.', 'swvt-hr' ) . '</p></div>';
    }
}

// 2. Transfer Employee
if ( isset( $_POST['swvt_transfer_employee_nonce'] ) && wp_verify_nonce( $_POST['swvt_transfer_employee_nonce'], 'swvt_transfer_employee' ) ) {
    $emp_id = absint( $_POST['transfer_employee_id'] );
    $target_branch_id = absint( $_POST['transfer_target_branch_id'] );
    if ( $emp_id && $target_branch_id ) {
        $wpdb->update( $p . 'employees', [ 'branch_id' => $target_branch_id ], [ 'id' => $emp_id ] );
        $emp_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $emp_id ) );
        $target_branch_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$p}branches WHERE id = %d", $target_branch_id ) );
        
        SWVT_HR_ERP_Service::log_activity( $branch_id, 'employee_removed', sprintf( 'Transferred %s to %s', $emp_name, $target_branch_name ) );
        SWVT_HR_ERP_Service::log_activity( $target_branch_id, 'employee_joined', sprintf( 'Received %s transfer from %s', $emp_name, $branch->name ) );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Employee transferred successfully.', 'swvt-hr' ) . '</p></div>';
    }
}

// 3. Remove Employee
if ( isset( $_GET['remove_emp_id'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'remove_employee_' . $_GET['remove_emp_id'] ) ) {
    $emp_id = absint( $_GET['remove_emp_id'] );
    $emp_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $emp_id ) );
    $wpdb->update( $p . 'employees', [ 'branch_id' => null ], [ 'id' => $emp_id ] );
    SWVT_HR_ERP_Service::log_activity( $branch_id, 'employee_removed', sprintf( 'Employee detached: %s', $emp_name ) );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Employee removed from branch.', 'swvt-hr' ) . '</p></div>';
}

// 4. Save Targets
if ( isset( $_POST['swvt_save_target_nonce'] ) && wp_verify_nonce( $_POST['swvt_save_target_nonce'], 'swvt_save_target' ) ) {
    $target_type = sanitize_text_field( $_POST['target_type'] );
    $period_val = absint( $_POST['period_value'] );
    $period_yr = absint( $_POST['period_year'] );
    $s_target = floatval( $_POST['sales_target'] );
    $o_target = absint( $_POST['orders_target'] );
    $c_target = absint( $_POST['customers_target'] );
    $p_target = floatval( $_POST['profit_target'] );

    SWVT_HR_ERP_Service::save_target( $branch_id, $target_type, $period_val, $period_yr, $s_target, $o_target, $c_target, $p_target );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Targets updated successfully.', 'swvt-hr' ) . '</p></div>';
}

// 5. Add Expense
if ( isset( $_POST['swvt_add_expense_nonce'] ) && wp_verify_nonce( $_POST['swvt_add_expense_nonce'], 'swvt_add_expense' ) ) {
    $category = sanitize_text_field( $_POST['expense_category'] );
    $amount = floatval( $_POST['expense_amount'] );
    $date = sanitize_text_field( $_POST['expense_date'] );
    $notes = sanitize_textarea_field( $_POST['expense_notes'] );

    SWVT_HR_ERP_Service::add_expense( $branch_id, $category, $amount, $date, $notes );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Expense logged successfully.', 'swvt-hr' ) . '</p></div>';
}

// 6. Upload Document
if ( isset( $_POST['swvt_add_doc_nonce'] ) && wp_verify_nonce( $_POST['swvt_add_doc_nonce'], 'swvt_add_doc' ) ) {
    $doc_type = sanitize_text_field( $_POST['doc_type'] );
    $doc_title = sanitize_text_field( $_POST['doc_title'] );
    $file_url = esc_url_raw( $_POST['file_url'] );

    SWVT_HR_ERP_Service::add_document( $branch_id, $doc_type, $doc_title, $file_url );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Document registered successfully.', 'swvt-hr' ) . '</p></div>';
}

// 7. Delete Document
if ( isset( $_GET['delete_doc_id'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_doc_' . $_GET['delete_doc_id'] ) ) {
    $doc_id = absint( $_GET['delete_doc_id'] );
    SWVT_HR_ERP_Service::delete_document( $branch_id, $doc_id );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Document deleted.', 'swvt-hr' ) . '</p></div>';
}

// ==========================================
// DATA EXTRACTIONS & AGGREGATIONS
// ==========================================

// Fetch branch manager details
$manager_name = __( 'Not Assigned', 'swvt-hr' );
if ( $branch->manager_id ) {
    $mgr = $wpdb->get_row( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $branch->manager_id ) );
    if ( $mgr ) {
        $manager_name = $mgr->full_name;
    }
}

// Employees
$employees = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}employees WHERE branch_id = %d ORDER BY full_name ASC",
    $branch_id
) );

// Non-assigned employees (for assign picker dropdown)
$available_employees = $wpdb->get_results( "SELECT id, full_name, job_title FROM {$p}employees WHERE branch_id IS NULL AND status = 'active' ORDER BY full_name ASC" );

// All active employees from other branches (for transfer picker dropdown)
$transferable_employees = $wpdb->get_results( $wpdb->prepare( "SELECT id, full_name, job_title FROM {$p}employees WHERE branch_id != %d AND status = 'active' ORDER BY full_name ASC", $branch_id ) );

// Sales Entries (Detailed)
$start_date = sprintf( '%d-%02d-01', $selected_year, $selected_month );
$end_date   = date( 'Y-m-t', strtotime( $start_date ) );

$actual_sales = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$p}sales_entries WHERE branch_id = %d AND entry_date >= %s AND entry_date <= %s",
    $branch_id, $start_date, $end_date
) );

$actual_orders = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(orders), 0) FROM {$p}sales_entries WHERE branch_id = %d AND entry_date >= %s AND entry_date <= %s",
    $branch_id, $start_date, $end_date
) );

$actual_customers = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(customers), 0) FROM {$p}sales_entries WHERE branch_id = %d AND entry_date >= %s AND entry_date <= %s",
    $branch_id, $start_date, $end_date
) );

// Target Configurations (Filtered by active month/year)
$target_row = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$p}branch_targets WHERE branch_id = %d AND target_type = 'monthly' AND period_value = %d AND period_year = %d",
    $branch_id, $selected_month, $selected_year
) );

$monthly_target_sales = $target_row ? (float) $target_row->sales_target : (float) $branch->sales_target;
$monthly_target_orders = $target_row ? (int) $target_row->orders_target : 0;
$monthly_target_customers = $target_row ? (int) $target_row->customers_target : 0;
$monthly_target_profit = $target_row ? (float) $target_row->profit_target : 0.00;

// Expenses logs
$expenses_list = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}branch_expenses WHERE branch_id = %d AND expense_date >= %s AND expense_date <= %s ORDER BY expense_date DESC",
    $branch_id, sprintf( '%d-01-01', $selected_year ), sprintf( '%d-12-31', $selected_year )
) );

$total_expenses_monthly = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$p}branch_expenses WHERE branch_id = %d AND expense_date >= %s AND expense_date <= %s",
    $branch_id, $start_date, $end_date
) );

$total_expenses_yearly = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM {$p}branch_expenses WHERE branch_id = %d AND expense_date >= %s AND expense_date <= %s",
    $branch_id, sprintf( '%d-01-01', $selected_year ), sprintf( '%d-12-31', $selected_year )
) );

// Payroll & commissions logs
$payroll_total_monthly = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(net_salary), 0) FROM {$p}payroll WHERE branch_id = %d AND period_month = %d AND period_year = %d",
    $branch_id, $selected_month, $selected_year
) );

$commission_total_monthly = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(final_amount), 0) 
     FROM {$p}commissions c
     JOIN {$wpdb->prefix}swvt_hr_sales s ON c.sales_id = s.id
     WHERE s.branch_id = %d AND s.period_month = %d AND s.period_year = %d",
    $branch_id, $selected_month, $selected_year
) );

// Documents
$documents = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}branch_documents WHERE branch_id = %d ORDER BY id DESC",
    $branch_id
) );

// Timeline audit log
$timeline = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}branch_timeline WHERE branch_id = %d ORDER BY created_at DESC LIMIT 50",
    $branch_id
) );

// Performance calculations
$achievement_rate = $monthly_target_sales > 0 ? ($actual_sales / $monthly_target_sales) * 100 : 0.00;
$sales_variance = $actual_sales - $monthly_target_sales;
$net_profit_monthly = $actual_sales - $total_expenses_monthly - $payroll_total_monthly;
$expense_ratio = $actual_sales > 0 ? ($total_expenses_monthly / $actual_sales) * 100 : 0.00;

// Average attendance rate calculations
$attendance_rate = 0.00;
if ( ! empty( $employees ) ) {
    $emp_ids = wp_list_pluck( $employees, 'id' );
    $format_ids = implode( ',', array_map( 'absint', $emp_ids ) );
    $att_average = $wpdb->get_var( $wpdb->prepare(
        "SELECT AVG(100 - (absence_days * 5)) FROM {$p}attendance 
         WHERE employee_id IN ($format_ids) AND period_month = %d AND period_year = %d",
        $selected_month, $selected_year
    ) );
    $attendance_rate = $att_average ? max( 0, min( 100, (float) $att_average ) ) : 100.00;
}

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = sprintf( __( 'ERP Profile Center: %s', 'swvt-hr' ), $branch->name );
?>

<!-- Overriding sidebar layout styles for Odoo/ERPNext premium look -->
<style>
    .swvt-hr-erp-grid-performance {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    .swvt-hr-erp-card-kpi {
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .swvt-hr-erp-kpi-title {
        font-size: 11px;
        color: #646970;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .swvt-hr-erp-kpi-value {
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px;
        color: #1d2327;
    }
    .swvt-hr-erp-kpi-meta {
        font-size: 11px;
        color: #8c8f94;
        margin-top: 4px;
    }
    .swvt-hr-profile-tabs {
        display: flex;
        background: #fafafb;
        border-bottom: 1px solid #dcdcde;
        margin: 0;
        padding: 0;
        list-style: none;
        flex-wrap: wrap;
    }
    .swvt-hr-profile-tab-item {
        padding: 12px 18px;
        font-size: 12.5px;
        font-weight: 600;
        color: #50575e;
        cursor: pointer;
        border-right: 1px solid #dcdcde;
        transition: all 0.2s ease;
    }
    .swvt-hr-profile-tab-item:hover {
        background: #f0f0f1;
        color: #2271b1;
    }
    .swvt-hr-profile-tab-item.is-active {
        background: #ffffff;
        color: #2271b1;
        border-bottom: 2px solid #2271b1;
        font-weight: 700;
    }
    .swvt-hr-profile-tab-content {
        padding: 20px;
        display: none;
    }
    .swvt-hr-profile-tab-content.is-active {
        display: block;
    }
    .swvt-hr-timeline-container {
        border-left: 2px solid #dcdcde;
        padding-left: 20px;
        margin-left: 10px;
    }
    .swvt-hr-timeline-event {
        position: relative;
        margin-bottom: 16px;
    }
    .swvt-hr-timeline-event::before {
        content: '';
        width: 10px;
        height: 10px;
        background: #2271b1;
        border-radius: 50%;
        position: absolute;
        left: -26px;
        top: 4px;
        border: 2px solid #fff;
    }
    @media(max-width:992px) {
        .swvt-hr-erp-grid-performance {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media(max-width:600px) {
        .swvt-hr-erp-grid-performance {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap swvt-hr-wrap">

    <!-- Top Action Bar -->
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2"><path d="M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 9h7a1 1 0 0 1 1 1v11"/></svg>
            <div>
                <h1 style="font-size: 21px; font-weight: 600; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
                <p style="font-size: 12px; color:#646970; margin:0;"><?php esc_html_e( 'Enterprise Central Control Panel & Audit timeline for this branch.', 'swvt-hr' ); ?></p>
            </div>
        </div>

        <!-- Branch switcher and filter -->
        <div style="display:flex; gap:8px; align-items:center;">
            <form method="get" style="display:flex; gap:6px; margin:0;">
                <input type="hidden" name="page" value="swvt-hr-branch-profile" />
                <select name="id" class="swvt-hr-select" onchange="this.form.submit()" style="width: 200px;">
                    <?php foreach ( $all_branches as $ab ) : ?>
                        <option value="<?php echo $ab->id; ?>" <?php selected( $branch_id, $ab->id ); ?>><?php echo esc_html( $ab->name ); ?> (<?php echo esc_html( $ab->code ); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <select name="m" class="swvt-hr-select" onchange="this.form.submit()" style="width: 120px;">
                    <?php foreach ( $months_english as $num => $name ) : ?>
                        <option value="<?php echo $num; ?>" <?php selected( $selected_month, $num ); ?>><?php echo esc_html( $name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="y" class="swvt-hr-select" onchange="this.form.submit()" style="width: 90px;">
                    <?php 
                    $curr_year = (int) date('Y');
                    for ( $year_val = $curr_year + 1; $year_val >= $curr_year - 5; $year_val-- ) : 
                    ?>
                        <option value="<?php echo $year_val; ?>" <?php selected( $selected_year, $year_val ); ?>><?php echo $year_val; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-branches-edit&id=' . $branch->id ); ?>" class="swvt-hr-btn swvt-hr-btn-primary" style="padding:6px 12px; font-size:12px;"><?php esc_html_e( 'Edit Branch Details', 'swvt-hr' ); ?></a>
        </div>
    </div>

    <!-- Executive KPI Cards -->
    <div class="swvt-hr-erp-grid-performance">
        <div class="swvt-hr-erp-card-kpi" style="border-left: 4px solid #2271b1;">
            <div class="swvt-hr-erp-kpi-title"><?php esc_html_e( 'Actual Sales', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-erp-kpi-value"><?php echo number_format( $actual_sales, 0 ); ?> EGP</div>
            <div class="swvt-hr-erp-kpi-meta"><?php printf( __( 'Target: %s EGP', 'swvt-hr' ), number_format( $monthly_target_sales, 0 ) ); ?></div>
        </div>
        <div class="swvt-hr-erp-card-kpi" style="border-left: 4px solid #107c41;">
            <div class="swvt-hr-erp-kpi-title"><?php esc_html_e( 'Monthly Net Profit', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-erp-kpi-value" style="color: <?php echo $net_profit_monthly >= 0 ? '#107c41' : '#a80000'; ?>;">
                <?php echo number_format( $net_profit_monthly, 0 ); ?> EGP
            </div>
            <div class="swvt-hr-erp-kpi-meta"><?php printf( __( 'Expenses: %s EGP', 'swvt-hr' ), number_format( $total_expenses_monthly, 0 ) ); ?></div>
        </div>
        <div class="swvt-hr-erp-card-kpi" style="border-left: 4px solid #7c3aed;">
            <div class="swvt-hr-erp-kpi-title"><?php esc_html_e( 'Target Achievement', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-erp-kpi-value"><?php echo number_format( $achievement_rate, 1 ); ?>%</div>
            <div class="swvt-hr-erp-kpi-meta"><?php printf( __( 'Variance: %s EGP', 'swvt-hr' ), number_format( $sales_variance, 0 ) ); ?></div>
        </div>
        <div class="swvt-hr-erp-card-kpi" style="border-left: 4px solid #b78a00;">
            <div class="swvt-hr-erp-kpi-title"><?php esc_html_e( 'Wages / Expense Ratio', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-erp-kpi-value"><?php echo number_format( $expense_ratio, 1 ); ?>%</div>
            <div class="swvt-hr-erp-kpi-meta"><?php printf( __( 'Wages: %s EGP', 'swvt-hr' ), number_format( $payroll_total_monthly, 0 ) ); ?></div>
        </div>
    </div>

    <!-- Tabbed Dashboard Navigation -->
    <div class="swvt-hr-tab-container" style="background:#fff; border:1px solid #dcdcde; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
        <ul class="swvt-hr-profile-tabs" id="branch-profile-tabs">
            <li class="swvt-hr-profile-tab-item is-active" data-profile-tab="info">🏢 Branch Information</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="targets">🎯 Targets Module</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="employees">👥 Employees Registry (<?php echo count($employees); ?>)</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="expenses">💸 Expenses Tracker</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="performance">📊 Performance metrics</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="documents">📄 Documents Registry</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="timeline">⏱️ Timeline Logs</li>
        </ul>

        <!-- 1. Branch Information Tab -->
        <div class="swvt-hr-profile-tab-content is-active" id="profile-tab-content-info">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; font-size:13px;">
                <div style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde;">
                    <div style="margin-bottom:8px;"><strong>Branch Name:</strong> <?php echo esc_html( $branch->name ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Branch Code:</strong> <?php echo esc_html( $branch->code ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Branch Type:</strong> <span style="text-transform:capitalize; font-weight:600;"><?php echo isset($branch->type) ? esc_html($branch->type) : 'Retail'; ?></span></div>
                    <div style="margin-bottom:8px;"><strong>Opening Date:</strong> <?php echo ! empty($branch->opening_date) ? esc_html($branch->opening_date) : '—'; ?></div>
                    <div style="margin-bottom:8px;"><strong>Region / City:</strong> <?php echo isset($branch->region) ? esc_html($branch->region) : ''; ?> (<?php echo esc_html( $branch->city ); ?>)</div>
                </div>
                <div style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde;">
                    <div style="margin-bottom:8px;"><strong>Branch Manager:</strong> <strong><?php echo esc_html( $manager_name ); ?></strong></div>
                    <div style="margin-bottom:8px;"><strong>Working Hours:</strong> <?php echo isset($branch->working_hours) ? esc_html($branch->working_hours) : '—'; ?></div>
                    <div style="margin-bottom:8px;"><strong>Phone Number:</strong> <?php echo esc_html( $branch->phone ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Email Address:</strong> <?php echo isset($branch->email) ? esc_html($branch->email) : '—'; ?></div>
                    <?php if ( ! empty($branch->google_maps_url) ) : ?>
                        <div style="margin-bottom:8px;"><strong>Google Maps Location:</strong> <a href="<?php echo esc_url($branch->google_maps_url); ?>" target="_blank" style="text-decoration:none; color:#2271b1;">View Map &rarr;</a></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( ! empty($branch->notes) ) : ?>
                <div style="background:#fffef5; border-left:3px solid #e0a800; padding:12px; border-radius:4px; font-size:12.5px; margin-top:15px;">
                    <strong>Operational Notes:</strong> <?php echo esc_html($branch->notes); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Targets Module Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-targets">
            <h4 style="margin: 0 0 15px; font-size:14px; font-weight:700; color:#1d2327;">Configure Targets Specs</h4>
            
            <form method="post" style="background:#f6f7f7; padding:18px; border-radius:6px; border:1px solid #dcdcde; margin-bottom:20px; display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; align-items:flex-end;">
                <?php wp_nonce_field( 'swvt_save_target', 'swvt_save_target_nonce' ); ?>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Target Type</label>
                    <select name="target_type" class="swvt-hr-select">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Period value (index)</label>
                    <input type="number" min="1" max="2030" name="period_value" value="<?php echo $selected_month; ?>" class="swvt-hr-input" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Period Year</label>
                    <input type="number" min="2020" max="2035" name="period_year" value="<?php echo $selected_year; ?>" class="swvt-hr-input" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Sales Target (EGP)</label>
                    <input type="number" step="100" name="sales_target" value="<?php echo $monthly_target_sales; ?>" class="swvt-hr-input" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Orders Target</label>
                    <input type="number" name="orders_target" value="<?php echo $monthly_target_orders; ?>" class="swvt-hr-input" />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Customers Target</label>
                    <input type="number" name="customers_target" value="<?php echo $monthly_target_customers; ?>" class="swvt-hr-input" />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Profit Target (EGP)</label>
                    <input type="number" step="100" name="profit_target" value="<?php echo $monthly_target_profit; ?>" class="swvt-hr-input" />
                </div>
                <div>
                    <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="width: 100%; height:38px;">Save Target</button>
                </div>
            </form>

            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:10px 12px;">Target Metric</th>
                        <th style="text-align:right;">Target Value</th>
                        <th style="text-align:right;">Actual Value</th>
                        <th style="text-align:right;">Achievement %</th>
                        <th style="text-align:right;">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:10px 12px;"><strong>Sales Target</strong></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format($monthly_target_sales, 2); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#2271b1;"><?php echo number_format($actual_sales, 2); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:<?php echo $achievement_rate >= 100 ? '#107c41' : '#b78a00'; ?>;"><?php echo number_format($achievement_rate, 1); ?>%</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:<?php echo $sales_variance >= 0 ? '#107c41' : '#a80000'; ?>;"><?php echo ($sales_variance >= 0 ? '+' : '') . number_format($sales_variance, 2); ?> EGP</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 12px;"><strong>Orders Target</strong></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format($monthly_target_orders, 0); ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#2271b1;"><?php echo number_format($actual_orders, 0); ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700;"><?php echo $monthly_target_orders > 0 ? number_format(($actual_orders / $monthly_target_orders)*100, 1) . '%' : '—'; ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600;"><?php echo ($actual_orders - $monthly_target_orders); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:10px 12px;"><strong>Customers Target</strong></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format($monthly_target_customers, 0); ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#2271b1;"><?php echo number_format($actual_customers, 0); ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700;"><?php echo $monthly_target_customers > 0 ? number_format(($actual_customers / $monthly_target_customers)*100, 1) . '%' : '—'; ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600;"><?php echo ($actual_customers - $monthly_target_customers); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. Employees Registry Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-employees">
            
            <div style="margin-bottom: 20px; display:flex; gap:12px; flex-wrap:wrap;">
                <!-- Assign Employee form -->
                <form method="post" style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde; display:inline-flex; align-items:flex-end; gap:8px;">
                    <?php wp_nonce_field( 'swvt_assign_employee', 'swvt_assign_employee_nonce' ); ?>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Assign Employee</label>
                        <select name="assign_employee_id" class="swvt-hr-select" style="width: 220px;" required>
                            <option value=""><?php esc_html_e( 'Select Unassigned Employee', 'swvt-hr' ); ?></option>
                            <?php foreach ( $available_employees as $ae ) : ?>
                                <option value="<?php echo $ae->id; ?>"><?php echo esc_html( $ae->full_name ) . ' (' . esc_html( $ae->job_title ) . ')'; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="height: 38px;">Assign</button>
                </form>

                <!-- Transfer Employee form -->
                <form method="post" style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde; display:inline-flex; align-items:flex-end; gap:8px;">
                    <?php wp_nonce_field( 'swvt_transfer_employee', 'swvt_transfer_employee_nonce' ); ?>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Transfer Employee</label>
                        <select name="transfer_employee_id" class="swvt-hr-select" style="width: 200px;" required>
                            <option value=""><?php esc_html_e( 'Select Employee', 'swvt-hr' ); ?></option>
                            <?php foreach ( $transferable_employees as $te ) : ?>
                                <option value="<?php echo $te->id; ?>"><?php echo esc_html( $te->full_name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">To Branch</label>
                        <select name="transfer_target_branch_id" class="swvt-hr-select" style="width: 170px;" required>
                            <option value=""><?php esc_html_e( 'Select Branch', 'swvt-hr' ); ?></option>
                            <?php foreach ( $all_branches as $ab ) : if ($ab->id != $branch_id) : ?>
                                <option value="<?php echo $ab->id; ?>"><?php echo esc_html( $ab->name ); ?></option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="height: 38px;">Transfer</button>
                </form>
            </div>

            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:10px 12px;">Employee</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th style="text-align:right;">Salary</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $employees ) ) : ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No employees assigned to this branch.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $employees as $emp ) :
                            $remove_url = wp_nonce_url( admin_url( 'admin.php?page=swvt-hr-branch-profile&id=' . $branch_id . '&remove_emp_id=' . $emp->id ), 'remove_employee_' . $emp->id );
                    ?>
                        <tr>
                            <td style="padding:9px 12px;"><strong><?php echo esc_html( $emp->full_name ); ?></strong> (<?php echo esc_html($emp->code); ?>)</td>
                            <td><?php echo esc_html( $emp->job_title ); ?></td>
                            <td><?php echo esc_html( $emp->department ); ?></td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format( $emp->basic_salary, 0 ); ?> EGP</td>
                            <td style="text-align:center;"><span class="swvt-hr-pill <?php echo $emp->status === 'active' ? 'swvt-hr-pill-active' : 'swvt-hr-pill-inactive'; ?>" style="transform:scale(0.85);"><?php echo $emp->status; ?></span></td>
                            <td style="text-align:center;">
                                <a href="<?php echo $remove_url; ?>" class="swvt-hr-btn swvt-hr-btn-danger" style="padding: 2px 7px; font-size: 11px;" onclick="return confirm('Are you sure you want to remove this employee from this branch?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Expenses Tracker Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-expenses">
            <h4 style="margin: 0 0 15px; font-size:14px; font-weight:700; color:#1d2327;">Log Operational Expense</h4>
            
            <form method="post" style="background:#f6f7f7; padding:18px; border-radius:6px; border:1px solid #dcdcde; margin-bottom:20px; display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; align-items:flex-end;">
                <?php wp_nonce_field( 'swvt_add_expense', 'swvt_add_expense_nonce' ); ?>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Expense Category</label>
                    <select name="expense_category" class="swvt-hr-select" required>
                        <option value="rent">Rent</option>
                        <option value="utilities">Utilities</option>
                        <option value="internet">Internet</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="transportation">Transportation</option>
                        <option value="marketing">Marketing</option>
                        <option value="salaries">Salaries</option>
                        <option value="other">Other Category</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Expense Amount (EGP)</label>
                    <input type="number" step="0.01" name="expense_amount" class="swvt-hr-input" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Expense Date</label>
                    <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" class="swvt-hr-input" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Expense Description Notes</label>
                    <input type="text" name="expense_notes" class="swvt-hr-input" placeholder="e.g. Paid utility bill" />
                </div>
                <div style="grid-column: span 4; text-align:right; margin-top:8px;">
                    <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary">Add Expense Entry</button>
                </div>
            </form>

            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:10px 12px;">Expense Date</th>
                        <th>Category</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Description Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $expenses_list ) ) : ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No expenses logged for this year.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $expenses_list as $exp ) :
                    ?>
                        <tr>
                            <td style="padding:9px 12px; font-variant-numeric:tabular-nums;"><?php echo esc_html($exp->expense_date); ?></td>
                            <td><span style="text-transform:uppercase; font-weight:700; font-size:10px; background:#f0f0f1; padding:3px 6px; border-radius:3px; color:#50575e;"><?php echo esc_html($exp->category); ?></span></td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#c5221f;">-<?php echo number_format($exp->amount, 2); ?> EGP</td>
                            <td><?php echo esc_html($exp->notes ? $exp->notes : '—'); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 5. Performance Metrics Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-performance">
            <h4 style="margin:0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Branch Operational Performance Indicators (KPIs)</h4>
            
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom:20px;">
                <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:6px;">
                    <div style="font-size:11px; color:#787c82; font-weight:700; text-transform:uppercase;">Customer Count</div>
                    <div style="font-size:22px; font-weight:700; color:#2271b1; margin-top:6px;"><?php echo number_format($actual_customers, 0); ?></div>
                    <div style="font-size:11px; color:#646970; margin-top:4px;">Total visitors served this month.</div>
                </div>
                <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:6px;">
                    <div style="font-size:11px; color:#787c82; font-weight:700; text-transform:uppercase;">Orders Served</div>
                    <div style="font-size:22px; font-weight:700; color:#107c41; margin-top:6px;"><?php echo number_format($actual_orders, 0); ?></div>
                    <div style="font-size:11px; color:#646970; margin-top:4px;">Total invoices issued this month.</div>
                </div>
                <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:6px;">
                    <div style="font-size:11px; color:#787c82; font-weight:700; text-transform:uppercase;">Attendance Rate</div>
                    <div style="font-size:22px; font-weight:700; color:#7c3aed; margin-top:6px;"><?php echo number_format($attendance_rate, 1); ?>%</div>
                    <div style="font-size:11px; color:#646970; margin-top:4px;">Average attendance rate this month.</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:6px;">
                    <div style="font-size:11px; color:#787c82; font-weight:700; text-transform:uppercase;">Payroll Cost</div>
                    <div style="font-size:22px; font-weight:700; color:#1d2327; margin-top:6px;"><?php echo number_format($payroll_total_monthly, 0); ?> EGP</div>
                    <div style="font-size:11px; color:#646970; margin-top:4px;">Total base salary paid to employees.</div>
                </div>
                <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:6px;">
                    <div style="font-size:11px; color:#787c82; font-weight:700; text-transform:uppercase;">Commission Cost</div>
                    <div style="font-size:22px; font-weight:700; color:#2271b1; margin-top:6px;"><?php echo number_format($commission_total_monthly, 0); ?> EGP</div>
                    <div style="font-size:11px; color:#646970; margin-top:4px;">Distributed commission rewards.</div>
                </div>
            </div>
        </div>

        <!-- 6. Documents Registry Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-documents">
            <h4 style="margin: 0 0 15px; font-size:14px; font-weight:700; color:#1d2327;">Branch Documents Vault</h4>
            
            <form method="post" style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde; display:grid; grid-template-columns: 1fr 1.5fr 2fr auto; gap:10px; align-items:flex-end; margin-bottom:20px;">
                <?php wp_nonce_field( 'swvt_add_doc', 'swvt_add_doc_nonce' ); ?>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Doc Type</label>
                    <select name="doc_type" class="swvt-hr-select" required>
                        <option value="contract">Contract</option>
                        <option value="license">License</option>
                        <option value="rent_agreement">Rent Agreement</option>
                        <option value="image">Image</option>
                        <option value="other">Other Document</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Title Name</label>
                    <input type="text" name="doc_title" class="swvt-hr-input" placeholder="e.g. Health License 2026" required />
                </div>
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Mock URL path</label>
                    <input type="text" name="file_url" class="swvt-hr-input" placeholder="e.g. /uploads/agreements/contract.pdf" required />
                </div>
                <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="height:38px;">Add Document</button>
            </form>

            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:10px 12px;">Document Title</th>
                        <th>Type</th>
                        <th>Uploaded Date</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $documents ) ) : ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No documents registered for this branch.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $documents as $doc ) :
                            $delete_url = wp_nonce_url( admin_url( 'admin.php?page=swvt-hr-branch-profile&id=' . $branch_id . '&delete_doc_id=' . $doc->id ), 'delete_doc_' . $doc->id );
                    ?>
                        <tr>
                            <td style="padding:9px 12px;"><strong>📄 <?php echo esc_html($doc->title); ?></strong></td>
                            <td><span style="text-transform:uppercase; font-size:9.5px; background:#eaf2fb; color:#2271b1; padding:2px 6px; border-radius:3px; font-weight:600;"><?php echo esc_html($doc->doc_type); ?></span></td>
                            <td style="font-variant-numeric:tabular-nums; color:#787c82;"><?php echo esc_html($doc->uploaded_at); ?></td>
                            <td style="text-align:center;">
                                <a href="<?php echo $delete_url; ?>" class="swvt-hr-btn swvt-hr-btn-danger" style="padding:2px 8px; font-size:11px;" onclick="return confirm('Are you sure?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 7. Timeline Logs Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-timeline">
            <h4 style="margin: 0 0 20px; font-size:14px; font-weight:700; color:#1d2327;">Branch ERP Audit Timeline Logs</h4>
            
            <div class="swvt-hr-timeline-container">
                <?php if ( empty( $timeline ) ) : ?>
                    <div style="color:#787c82; padding:10px 0;"><?php esc_html_e( 'No timeline activities recorded yet.', 'swvt-hr' ); ?></div>
                <?php else : 
                    foreach ( $timeline as $log ) :
                ?>
                    <div class="swvt-hr-timeline-event">
                        <div style="font-size:11.5px; color:#8c8f94; font-variant-numeric:tabular-nums;"><?php echo esc_html($log->created_at); ?></div>
                        <div style="font-size:13.5px; font-weight:600; color:#1d2327; margin-top:2px;"><?php echo esc_html($log->description); ?></div>
                        <div style="font-size:10px; color:#a8abb1; text-transform:uppercase; margin-top:1px;">Event: <?php echo esc_html($log->event_type); ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#branch-profile-tabs .swvt-hr-profile-tab-item').on('click', function() {
        var clicked = $(this);
        var tabKey = clicked.data('profile-tab');

        $('#branch-profile-tabs .swvt-hr-profile-tab-item').removeClass('is-active');
        clicked.addClass('is-active');

        $('.swvt-hr-profile-tab-content').removeClass('is-active');
        $('#profile-tab-content-' + tabKey).addClass('is-active');
    });
});
</script>
