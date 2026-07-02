<?php
/**
 * Employee Profile Details View.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$employee_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$selected_year = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );

// If no employee_id is selected, default to the first active employee
if ( ! $employee_id ) {
    $first_emp_id = (int) $wpdb->get_var( "SELECT id FROM {$p}employees WHERE status='active' ORDER BY id ASC LIMIT 1" );
    if ( $first_emp_id ) {
        $employee_id = $first_emp_id;
    }
}

// Fetch all employees for switcher dropdown
$all_employees = $wpdb->get_results( "SELECT id, full_name, code FROM {$p}employees ORDER BY full_name ASC" );

// Handle Note Saving Action
$note_saved = false;
if ( isset( $_POST['swvt_save_employee_notes_nonce'] ) && wp_verify_nonce( $_POST['swvt_save_employee_notes_nonce'], 'swvt_save_employee_notes' ) ) {
    $wpdb->update(
        $p . 'employees',
        [ 'notes' => sanitize_textarea_field( $_POST['notes'] ) ],
        [ 'id' => $employee_id ]
    );
    $note_saved = true;
}

// Handle Document Submission Action (Visual Simulation)
$doc_saved = false;
if ( isset( $_POST['swvt_upload_doc_nonce'] ) && wp_verify_nonce( $_POST['swvt_upload_doc_nonce'], 'swvt_upload_doc' ) ) {
    $doc_name = sanitize_text_field( $_POST['doc_name'] );
    // Visual placeholder storage inside wp_options
    $submitted_docs = get_option( 'swvt_hr_employee_docs_' . $employee_id, [] );
    $submitted_docs[$doc_name] = [
        'date' => date('Y-m-d H:i'),
        'status' => 'Submitted'
    ];
    update_option( 'swvt_hr_employee_docs_' . $employee_id, $submitted_docs );
    $doc_saved = true;
}

// Fetch employee details
$employee = null;
if ( $employee_id ) {
    $employee = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}employees WHERE id = %d", $employee_id ) );
}

if ( ! $employee ) {
    echo '<div class="wrap"><h2>' . esc_html__( 'Employee not found.', 'swvt-hr' ) . '</h2></div>';
    return;
}

// Fetch branch details
$branch_name = __( 'Not Assigned', 'swvt-hr' );
if ( $employee->branch_id ) {
    $branch = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$p}branches WHERE id = %d", $employee->branch_id ) );
    if ( $branch ) {
        $branch_name = $branch->name;
    }
}

// Fetch attendance entries for this employee
$attendance_logs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}attendance WHERE employee_id = %d AND period_year = %d ORDER BY period_month ASC",
    $employee_id, $selected_year
) );

// Fetch payroll ledger entries for this employee
$payroll_history = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}payroll WHERE employee_id = %d AND period_year = %d ORDER BY period_month ASC",
    $employee_id, $selected_year
) );

// Fetch commissions distributed to this employee
$commissions_history = $wpdb->get_results( $wpdb->prepare(
    "SELECT c.*, s.period_month, s.period_year
     FROM {$p}commissions c
     JOIN {$p}sales s ON c.sales_id = s.id
     WHERE c.employee_id = %d AND s.period_year = %d
     ORDER BY s.period_month ASC",
    $employee_id, $selected_year
) );

// Fetch employee docs
$employee_docs = get_option( 'swvt_hr_employee_docs_' . $employee_id, [
    'National ID Copy' => ['date' => '2026-01-10', 'status' => 'Submitted'],
    'Employment Contract' => ['date' => '2026-01-10', 'status' => 'Submitted'],
    'CV / Resume' => ['date' => '2026-01-10', 'status' => 'Submitted']
] );

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = sprintf( __( 'Employee Profile: %s', 'swvt-hr' ), $employee->full_name );
?>

<style>
    .swvt-hr-profile-header-card {
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .swvt-hr-profile-meta-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        flex: 1;
    }
    .swvt-hr-profile-meta-item {
        border-right: 1px solid #f0f1f2;
        padding-right: 10px;
    }
    .swvt-hr-profile-meta-item:last-child {
        border-right: none;
    }
    .swvt-hr-profile-meta-label {
        font-size: 11px;
        color: #787c82;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .swvt-hr-profile-meta-value {
        font-size: 14px;
        font-weight: 700;
        color: #1d2327;
    }
    .swvt-hr-tab-container {
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    .swvt-hr-profile-tabs {
        display: flex;
        background: #fafafb;
        border-bottom: 1px solid #dcdcde;
        margin: 0;
        padding: 0;
        list-style: none;
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
</style>

<div class="wrap swvt-hr-wrap">
    
    <!-- Top Action Bar -->
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
            <div>
                <h1 style="font-size: 21px; font-weight: 600; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
                <p style="font-size: 12px; color:#646970; margin:0;"><?php esc_html_e( 'Review salary specifications, attendance logs, documents checklists, and historical payroll outputs.', 'swvt-hr' ); ?></p>
            </div>
        </div>

        <!-- Switcher and edit button -->
        <div style="display:flex; gap:8px; align-items:center;">
            <form method="get" style="display:flex; gap:6px; margin:0;">
                <input type="hidden" name="page" value="swvt-hr-employee-profile" />
                <select name="id" class="swvt-hr-select" onchange="this.form.submit()" style="width: 200px;">
                    <?php foreach ( $all_employees as $ae ) : ?>
                        <option value="<?php echo $ae->id; ?>" <?php selected( $employee_id, $ae->id ); ?>><?php echo esc_html( $ae->full_name ); ?> (<?php echo esc_html( $ae->code ); ?>)</option>
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
            <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employees-edit&id=' . $employee->id ); ?>" class="swvt-hr-btn" style="padding:6px 12px; font-size:12px;"><?php esc_html_e( 'Edit Details', 'swvt-hr' ); ?></a>
        </div>
    </div>

    <?php if ( $note_saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Employee note updated successfully.', 'swvt-hr' ); ?></p></div>
    <?php endif; ?>
    <?php if ( $doc_saved ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Document registry uploaded/updated successfully.', 'swvt-hr' ); ?></p></div>
    <?php endif; ?>

    <!-- Profile Header Card -->
    <div class="swvt-hr-profile-header-card">
        <div class="swvt-hr-profile-meta-grid">
            <div class="swvt-hr-profile-meta-item">
                <div class="swvt-hr-profile-meta-label"><?php esc_html_e( 'Employee Code / Dept', 'swvt-hr' ); ?></div>
                <div class="swvt-hr-profile-meta-value"><?php echo esc_html( $employee->code ); ?> <span style="font-weight:normal; color:#787c82;">(<?php echo esc_html( $employee->department ); ?>)</span></div>
            </div>
            <div class="swvt-hr-profile-meta-item">
                <div class="swvt-hr-profile-meta-label"><?php esc_html_e( 'Job Title / Role key', 'swvt-hr' ); ?></div>
                <div class="swvt-hr-profile-meta-value"><?php echo esc_html( $employee->job_title ); ?> <span style="font-size:11px; font-weight:normal; color:#646970;">(<?php echo esc_html( $employee->role_key ); ?>)</span></div>
            </div>
            <div class="swvt-hr-profile-meta-item">
                <div class="swvt-hr-profile-meta-label"><?php esc_html_e( 'Assigned Branch', 'swvt-hr' ); ?></div>
                <div class="swvt-hr-profile-meta-value"><?php echo esc_html( $branch_name ); ?></div>
            </div>
            <div class="swvt-hr-profile-meta-item">
                <div class="swvt-hr-profile-meta-label"><?php esc_html_e( 'Status / Hire Date', 'swvt-hr' ); ?></div>
                <div class="swvt-hr-profile-meta-value">
                    <span class="swvt-hr-pill <?php echo $employee->status === 'active' ? 'swvt-hr-pill-active' : 'swvt-hr-pill-inactive'; ?>" style="transform:scale(0.85); display:inline-block; vertical-align:middle;"><i></i><?php echo esc_html( $employee->status ); ?></span>
                    <span style="font-size:11.5px; font-weight:normal; color:#787c82; vertical-align:middle; margin-left:5px;"><?php echo $employee->hire_date ? $employee->hire_date : '—'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Container -->
    <div class="swvt-hr-tab-container">
        <ul class="swvt-hr-profile-tabs" id="employee-profile-tabs">
            <li class="swvt-hr-profile-tab-item is-active" data-profile-tab="personal">👤 Personal Info</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="salary">💰 Salary Details</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="attendance">📅 Attendance Log</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="payroll">💵 Payroll Wages</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="commission">🏆 Commissions</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="documents">📄 Documents & Files</li>
            <li class="swvt-hr-profile-tab-item" data-profile-tab="notes">📝 Notes Log</li>
        </ul>

        <!-- 1. Personal Info Tab -->
        <div class="swvt-hr-profile-tab-content is-active" id="profile-tab-content-personal">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; font-size:13px;">
                <div style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde;">
                    <div style="margin-bottom:8px;"><strong>Full Name:</strong> <?php echo esc_html( $employee->full_name ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Employee Code:</strong> <?php echo esc_html( $employee->code ); ?></div>
                    <div style="margin-bottom:8px;"><strong>National ID / Passport:</strong> <?php echo esc_html( $employee->national_id ? $employee->national_id : '—' ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Hire Date:</strong> <?php echo esc_html( $employee->hire_date ? $employee->hire_date : '—' ); ?></div>
                </div>
                <div style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde;">
                    <div style="margin-bottom:8px;"><strong>Phone Number:</strong> <?php echo esc_html( $employee->phone ? $employee->phone : '—' ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Email Address:</strong> <?php echo esc_html( $employee->email ? $employee->email : '—' ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Department:</strong> <?php echo esc_html( $employee->department ); ?></div>
                    <div style="margin-bottom:8px;"><strong>Job Title:</strong> <?php echo esc_html( $employee->job_title ); ?></div>
                </div>
            </div>
        </div>

        <!-- 2. Salary Details Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-salary">
            <div style="background:#f6f7f7; padding:18px; border-radius:8px; border:1px solid #dcdcde; max-width: 500px; font-size:13px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #e0e0e0; padding-bottom:6px;">
                    <strong>Basic Monthly Salary:</strong>
                    <span style="font-weight:700; color:#0a7c2f; font-variant-numeric:tabular-nums;"><?php echo number_format($employee->basic_salary, 2); ?> EGP</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #e0e0e0; padding-bottom:6px;">
                    <strong>Commission Eligible:</strong>
                    <span><?php echo $employee->commission_eligible ? '🟢 Eligible' : '🔴 Ineligible'; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; padding-bottom:4px;">
                    <strong>Custom Commission Share Override:</strong>
                    <span style="font-weight:700; color:#2271b1;"><?php echo $employee->commission_share ? number_format($employee->commission_share, 1) . '%' : 'Default'; ?></span>
                </div>
            </div>
        </div>

        <!-- 3. Attendance Log Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-attendance">
            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:12px 14px;">Period</th>
                        <th style="text-align:center;">Absence Days</th>
                        <th style="text-align:center;">Late Hours</th>
                        <th style="text-align:right;">Salary Deductions</th>
                        <th>Absence Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $attendance_logs ) ) : ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No attendance logs registered in this period.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $attendance_logs as $att ) :
                    ?>
                        <tr>
                            <td style="padding:10px 14px;"><strong><?php echo $months_english[ $att->period_month ] . ' ' . $att->period_year; ?></strong></td>
                            <td style="text-align:center; font-variant-numeric:tabular-nums;"><?php echo number_format($att->absence_days, 1); ?></td>
                            <td style="text-align:center; font-variant-numeric:tabular-nums; color:#787c82;"><?php echo number_format($att->late_hours, 1); ?></td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#c5221f;">-<?php echo number_format($att->deduction, 2); ?> EGP</td>
                            <td><?php echo esc_html($att->reason ? $att->reason : '—'); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Payroll Wages Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-payroll">
            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:12px 14px;">Period</th>
                        <th style="text-align:right;">Basic Salary</th>
                        <th style="text-align:right;">Bonus / Incentives</th>
                        <th style="text-align:right;">Absence Deduction</th>
                        <th style="text-align:right;">Other Deductions</th>
                        <th style="text-align:right;">Net Salary Paid</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $payroll_history ) ) : ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No historical payroll slips generated for this employee.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $payroll_history as $pr ) :
                    ?>
                        <tr>
                            <td style="padding:10px 14px;"><strong><?php echo $months_english[ $pr->period_month ] . ' ' . $pr->period_year; ?></strong></td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format($pr->basic_salary, 0); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; color:#2271b1;">+<?php echo number_format($pr->bonus, 0); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-<?php echo number_format($pr->absence_deduction, 0); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; color:#646970;">-<?php echo number_format($pr->other_deduction, 0); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;"><?php echo number_format($pr->net_salary, 0); ?> EGP</td>
                            <td style="text-align:center;">
                                <span class="swvt-hr-badge <?php echo $pr->status === 'paid' ? 'swvt-hr-badge-success' : 'swvt-hr-badge-warning'; ?>"><?php echo esc_html($pr->status); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 5. Commissions Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-commission">
            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:12px 14px;">Period</th>
                        <th>Role Distribution</th>
                        <th style="text-align:right;">Role share %</th>
                        <th style="text-align:right;">Assigned Amount</th>
                        <th style="text-align:right;">Absence Penalty</th>
                        <th style="text-align:right;">Final Net Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $commissions_history ) ) : ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#787c82; padding:20px;"><?php esc_html_e( 'No commissions distributed in this period.', 'swvt-hr' ); ?></td>
                        </tr>
                    <?php else : 
                        foreach ( $commissions_history as $ch ) :
                    ?>
                        <tr>
                            <td style="padding:10px 14px;"><strong><?php echo $months_english[ $ch->period_month ] . ' ' . $ch->period_year; ?></strong></td>
                            <td style="text-transform:uppercase; font-weight:600; font-size:11px;"><?php echo esc_html($ch->role_key); ?></td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums;"><?php echo number_format($ch->role_percent, 1); ?>%</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; color:#2271b1;"><?php echo number_format($ch->amount, 2); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-<?php echo number_format($ch->absence_deduction, 2); ?> EGP</td>
                            <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;"><?php echo number_format($ch->final_amount, 2); ?> EGP</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 6. Documents & Files Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-documents">
            <h4 style="margin:0 0 15px; font-size:14px; font-weight:700; color:#1d2327;">Employee Files Registry</h4>
            
            <table class="swvt-hr-table" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th style="padding:12px 14px;">Document Name</th>
                        <th>Submission Date</th>
                        <th>Verification Status</th>
                        <th style="text-align:center;">Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $employee_docs as $doc_name => $meta ) : ?>
                        <tr>
                            <td style="padding:10px 14px;"><strong>📄 <?php echo esc_html($doc_name); ?></strong></td>
                            <td><?php echo esc_html($meta['date']); ?></td>
                            <td><span class="swvt-hr-pill swvt-hr-pill-active" style="transform: scale(0.9);"><?php echo esc_html($meta['status']); ?></span></td>
                            <td style="text-align:center;"><button type="button" class="swvt-hr-btn" style="padding: 2px 8px; font-size: 11px;" onclick="alert('Document download triggers download of mock file copy.');">Download</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Add new document form -->
            <form method="post" style="background:#f6f7f7; padding:15px; border-radius:6px; border:1px solid #dcdcde; max-width: 400px;">
                <?php wp_nonce_field( 'swvt_upload_doc', 'swvt_upload_doc_nonce' ); ?>
                <h5 style="margin:0 0 10px; font-size:12.5px; font-weight:700;">Add Document Record</h5>
                <div style="margin-bottom:10px;">
                    <label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Document Type</label>
                    <select name="doc_name" class="swvt-hr-select" style="width: 100%;">
                        <option value="ID Card Copy">National ID Card Copy</option>
                        <option value="Employment Contract">Employment Contract Agreement</option>
                        <option value="Resume / CV">CV / Resume Summary</option>
                        <option value="Criminal Record Cert">Criminal Record Certificate</option>
                        <option value="Health Certificate">Health Evaluation Copy</option>
                    </select>
                </div>
                <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="padding: 5px 12px; font-size:11.5px;">Add File Record</button>
            </form>
        </div>

        <!-- 7. Notes Log Tab -->
        <div class="swvt-hr-profile-tab-content" id="profile-tab-content-notes">
            <h4 style="margin:0 0 12px; font-size:14px; font-weight:700; color:#1d2327;">HR Notes & Logs</h4>
            <form method="post">
                <?php wp_nonce_field( 'swvt_save_employee_notes', 'swvt_save_employee_notes_nonce' ); ?>
                <textarea name="notes" class="swvt-hr-input" style="width:100%; height:120px; font-family:inherit; font-size:13px; padding:10px; margin-bottom:12px;" placeholder="Add internal notes about employee status, review remarks, warnings, or appraisal feedback..."><?php echo esc_textarea( $employee->notes ); ?></textarea>
                <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary"><?php esc_html_e( 'Save Notes Record', 'swvt-hr' ); ?></button>
            </form>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    $('#employee-profile-tabs .swvt-hr-profile-tab-item').on('click', function() {
        var clicked = $(this);
        var tabKey = clicked.data('profile-tab');

        $('#employee-profile-tabs .swvt-hr-profile-tab-item').removeClass('is-active');
        clicked.addClass('is-active');

        $('.swvt-hr-profile-tab-content').removeClass('is-active');
        $('#profile-tab-content-' + tabKey).addClass('is-active');
    });
});
</script>
