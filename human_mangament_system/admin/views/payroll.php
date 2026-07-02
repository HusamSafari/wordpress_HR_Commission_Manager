<?php
/**
 * Payroll Management View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected period and branch
$selected_month  = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_branch = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : ( isset( $_GET['br_id'] ) ? absint( $_GET['br_id'] ) : 0 );

$branches = SWVT_HR_Branch::get_active();

// Fetch payroll rows for this period
$payroll_query = "SELECT pay.*, emp.full_name, emp.job_title, b.name as branch_name 
                  FROM {$p}payroll pay
                  JOIN {$p}employees emp ON emp.id = pay.employee_id
                  JOIN {$p}branches b ON b.id = pay.branch_id
                  WHERE pay.period_month = %d AND pay.period_year = %d";
                  
if ( $selected_branch ) {
    $payroll_query .= $wpdb->prepare( " AND pay.branch_id = %d", $selected_branch );
}
$payroll_query .= " ORDER BY pay.id ASC";

$payroll_rows = $wpdb->get_results( $wpdb->prepare( $payroll_query, $selected_month, $selected_year ) );

// Calculate totals
$totals = [
    'basic'   => 0.00,
    'comm'    => 0.00,
    'absence' => 0.00,
    'bonus'   => 0.00,
    'other'   => 0.00,
    'net'     => 0.00,
    'count'   => count( $payroll_rows )
];

foreach ( $payroll_rows as $row ) {
    $totals['basic']   += (float) $row->basic_salary;
    $totals['comm']    += (float) $row->commission;
    $totals['absence'] += (float) $row->absence_deduction;
    $totals['bonus']   += (float) $row->bonus;
    $totals['other']   += (float) $row->other_deduction;
    $totals['net']     += (float) $row->net_salary;
}

$settings = get_option( 'swvt_hr_settings' );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : 'EGP';

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$employees_query = "SELECT e.id, e.full_name, e.job_title, e.branch_id, b.name as branch_name
                    FROM {$p}employees e
                    LEFT JOIN {$p}branches b ON b.id = e.branch_id
                    WHERE e.status = 'active'";

if ( $selected_branch ) {
    $employees_query .= $wpdb->prepare( " AND e.branch_id = %d", $selected_branch );
}

$employees_query .= " ORDER BY e.id ASC";
$yearly_employees = $wpdb->get_results( $employees_query );

$yearly_payroll_query = "SELECT pay.employee_id, pay.period_month, pay.net_salary, pay.status
                         FROM {$p}payroll pay
                         WHERE pay.period_year = %d";

if ( $selected_branch ) {
    $yearly_payroll_query .= $wpdb->prepare( " AND pay.branch_id = %d", $selected_branch );
}

$yearly_payroll_query .= " ORDER BY pay.employee_id ASC, pay.period_month ASC";
$yearly_payroll_rows = $wpdb->get_results( $wpdb->prepare( $yearly_payroll_query, $selected_year ) );

$yearly_payroll_map = [];
$yearly_employee_totals = [];
foreach ( $yearly_payroll_rows as $year_row ) {
    $employee_id = (int) $year_row->employee_id;
    $month_num   = (int) $year_row->period_month;

    if ( ! isset( $yearly_payroll_map[ $employee_id ] ) ) {
        $yearly_payroll_map[ $employee_id ] = [];
    }

    $yearly_payroll_map[ $employee_id ][ $month_num ] = [
        'status' => $year_row->status,
        'net'    => (float) $year_row->net_salary,
    ];

    if ( ! isset( $yearly_employee_totals[ $employee_id ] ) ) {
        $yearly_employee_totals[ $employee_id ] = 0.00;
    }

    $yearly_employee_totals[ $employee_id ] += (float) $year_row->net_salary;
}

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="12" y1="4" x2="12" y2="20"></line><line x1="2" y1="10" x2="22" y2="10"></line></svg>';
$page_title = __( 'Payroll & Financial Ledger', 'swvt-hr' );
$page_sub   = __( 'Compile monthly payroll statements, insert adjustments, and log staff payouts.', 'swvt-hr' );
$selected_period_label = sprintf(
    /* translators: 1: month name, 2: month number, 3: year */
    __( '%1$s (Month %2$d) %3$d', 'swvt-hr' ),
    $months_english[ $selected_month ],
    $selected_month,
    $selected_year
);
?>

<div class="wrap swvt-hr-wrap">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 23px; font-weight: 400; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
            <p style="font-size: 13px; color: #646970; margin: 0;"><?php echo esc_html( $page_sub ); ?></p>
        </div>
        <form method="get" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="swvt-hr-payroll" />
            <select name="branch" class="swvt-hr-select" style="width: 150px;">
                <option value="0"><?php esc_html_e( 'All Branches', 'swvt-hr' ); ?></option>
                <?php foreach ( SWVT_HR_Branch::get_active() as $b ) : ?>
                    <option value="<?php echo $b->id; ?>" <?php selected( $selected_branch, $b->id ); ?>><?php echo esc_html( $b->name ); ?></option>
                <?php endforeach; ?>
            </select>
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
            <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary"><?php esc_html_e( 'Filter', 'swvt-hr' ); ?></button>
        </form>
    </div>

    <div class="swvt-hr-card" style="padding:14px 18px; margin-bottom:18px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; background:linear-gradient(180deg,#fff,#fbfcfe);">
        <div>
            <div style="font-size:14px; font-weight:700; color:#1d2327;">
                <?php printf( __( 'Current payroll period: %s', 'swvt-hr' ), esc_html( $selected_period_label ) ); ?>
            </div>
            <div style="font-size:12px; color:#787c82; margin-top:4px;">
                <?php esc_html_e( 'If you want to mark an older payroll as paid, select the old month and year from the filters above, then press Filter.', 'swvt-hr' ); ?>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="swvt-hr-badge" style="background:#eaf2fb; color:#2271b1; font-weight:700;">
                <?php printf( __( 'Month No. %d', 'swvt-hr' ), $selected_month ); ?>
            </span>
            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970;">
                <?php echo esc_html( $selected_year ); ?>
            </span>
        </div>
    </div>

    <?php if ( empty( $payroll_rows ) ) : ?>
        <div class="swvt-hr-card-padded" style="text-align:center; padding:50px 24px;">
            <div style="width:66px; height:66px; border-radius:16px; background:#fdf4dd; display:inline-flex; align-items:center; justify-content:center; margin-bottom:18px; color:#b78a00;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M12 18v-6M9 15h6"></path></svg>
            </div>
            <div style="font-size:19px; font-weight:600; margin-bottom:8px;"><?php esc_html_e( 'Payroll Not Compiled for this Period', 'swvt-hr' ); ?></div>
            <div style="font-size:13.5px; color:#787c82; max-width:420px; margin:0 auto 22px;">
                <?php esc_html_e( 'The employee wages have not been calculated yet. Press generate below to calculate salary logs.', 'swvt-hr' ); ?>
            </div>
            <button type="button" id="swvt-hr-generate-payroll-initial" class="swvt-hr-btn swvt-hr-btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                <?php esc_html_e( 'Generate Payroll Logs Now', 'swvt-hr' ); ?>
            </button>
        </div>
    <?php else : ?>
        <!-- Summary Cards Grid -->
        <div style="display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:18px;">
            <div class="swvt-hr-kpi-card">
                <div style="font-size:12px; color:#787c82; margin-bottom:6px;"><?php esc_html_e( 'Total Basic Salaries', 'swvt-hr' ); ?></div>
                <div style="font-size:20px; font-weight:700; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['basic'] ); ?> <span style="font-size:11px; font-weight:normal;"><?php echo $currency; ?></span></div>
            </div>
            <div class="swvt-hr-kpi-card">
                <div style="font-size:12px; color:#787c82; margin-bottom:6px;"><?php esc_html_e( 'Distributed Commissions', 'swvt-hr' ); ?></div>
                <div style="font-size:20px; font-weight:700; color:#2271b1; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['comm'] ); ?> <span style="font-size:11px; font-weight:normal;"><?php echo $currency; ?></span></div>
            </div>
            <div class="swvt-hr-kpi-card">
                <div style="font-size:12px; color:#787c82; margin-bottom:6px;"><?php esc_html_e( 'Absence Deductions', 'swvt-hr' ); ?></div>
                <div style="font-size:20px; font-weight:700; color:#b32d2e; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['absence'] ); ?> <span style="font-size:11px; font-weight:normal;"><?php echo $currency; ?></span></div>
            </div>
            <div class="swvt-hr-kpi-card">
                <div style="font-size:12px; color:#787c82; margin-bottom:6px;"><?php esc_html_e( 'Total Bonuses Added', 'swvt-hr' ); ?></div>
                <div style="font-size:20px; font-weight:700; color:#0a7c2f; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['bonus'] ); ?> <span style="font-size:11px; font-weight:normal;"><?php echo $currency; ?></span></div>
            </div>
            <div class="swvt-hr-kpi-card" style="background:linear-gradient(135deg,#2271b1,#135e96); color:#fff;">
                <div style="font-size:12px; color:#cfe3f5; margin-bottom:6px;"><?php esc_html_e( 'Net Salary Payable', 'swvt-hr' ); ?></div>
                <div style="font-size:20px; font-weight:700; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['net'] ); ?> <span style="font-size:11px; font-weight:normal; color:#cfe3f5;"><?php echo $currency; ?></span></div>
            </div>
        </div>

        <!-- Action Bar -->
        <div style="display:flex; align-items:center; gap:9px; margin-bottom:18px; flex-wrap:wrap;">
            <button type="button" id="swvt-hr-recalc-payroll-btn" class="swvt-hr-btn swvt-hr-btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6M1 20v-6h6"></path><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"></path></svg>
                <?php esc_html_e( 'Re-calculate Splits & Net', 'swvt-hr' ); ?>
            </button>
            <a href="<?php echo admin_url( 'admin-ajax.php?action=swvt_hr_export_payroll&nonce=' . wp_create_nonce('swvt_hr_nonce') . '&month=' . $selected_month . '&year=' . $selected_year . '&branch=' . $selected_branch ); ?>" class="swvt-hr-btn swvt-hr-btn-success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M8 13l3 3 5-5"></path></svg>
                <?php esc_html_e( 'Export to CSV / Excel', 'swvt-hr' ); ?>
            </a>
            <button type="button" onclick="window.print();" class="swvt-hr-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"></path></svg>
                <?php esc_html_e( 'Print Statement', 'swvt-hr' ); ?>
            </button>
            
            <button type="button" id="swvt-hr-mark-all-paid-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="margin-left:auto;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg>
                <?php esc_html_e( 'Mark All as Paid', 'swvt-hr' ); ?>
            </button>
        </div>

        <!-- Payroll Table Card -->
        <div class="swvt-hr-card">
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:12.5px; min-width:1040px;">
                    <thead>
                        <tr>
                            <th style="padding:11px 18px;"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                            <th><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                            <th style="text-align:left;"><?php esc_html_e( 'Basic Salary', 'swvt-hr' ); ?></th>
                            <th style="text-align:left;"><?php esc_html_e( 'Commission', 'swvt-hr' ); ?></th>
                            <th style="text-align:left;"><?php esc_html_e( 'Absence Ded.', 'swvt-hr' ); ?></th>
                            <th style="text-align:left; width:100px;"><?php esc_html_e( 'Bonus (+)', 'swvt-hr' ); ?></th>
                            <th style="text-align:left; width:100px;"><?php esc_html_e( 'Other Ded. (-)', 'swvt-hr' ); ?></th>
                            <th style="text-align:left;"><?php esc_html_e( 'Net Salary', 'swvt-hr' ); ?></th>
                            <th style="text-align:center;"><?php esc_html_e( 'Status', 'swvt-hr' ); ?></th>
                            <th style="text-align:center; width:90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $zebra_idx = 0;
                        foreach ( $payroll_rows as $p_row ) : 
                            $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                            $zebra_idx++;
                        ?>
                            <tr class="<?php echo $zebra_class; ?>" id="payroll-row-<?php echo $p_row->id; ?>">
                                <td style="padding:10px 18px;">
                                    <div style="font-weight:600;"><?php echo esc_html( $p_row->full_name ); ?></div>
                                    <div style="font-size:11px; color:#9aa0a6;"><?php echo esc_html( $p_row->job_title ); ?></div>
                                </td>
                                <td style="color:#3c434a;"><?php echo esc_html( $p_row->branch_name ); ?></td>
                                <td style="text-align:left; font-variant-numeric:tabular-nums;" class="pay-basic-cell" data-val="<?php echo $p_row->basic_salary; ?>">
                                    <?php echo SWVT_HR::format_number( $p_row->basic_salary ); ?>
                                </td>
                                <td style="text-align:left; color:#2271b1; font-variant-numeric:tabular-nums;" class="pay-comm-cell" data-val="<?php echo $p_row->commission; ?>">
                                    <?php echo SWVT_HR::format_number( $p_row->commission ); ?>
                                </td>
                                <td style="text-align:left; color:#b32d2e; font-variant-numeric:tabular-nums;" class="pay-absence-cell" data-val="<?php echo $p_row->absence_deduction; ?>">
                                    <?php echo $p_row->absence_deduction > 0 ? '- ' . SWVT_HR::format_number( $p_row->absence_deduction ) : '—'; ?>
                                </td>
                                <td>
                                    <input type="number" step="10" min="0" class="swvt-hr-input pay-bonus-input" value="<?php echo (float)$p_row->bonus; ?>" style="padding:4px 8px; font-size:12px; font-variant-numeric:tabular-nums; text-align:left;" />
                                </td>
                                <td>
                                    <input type="number" step="10" min="0" class="swvt-hr-input pay-other-input" value="<?php echo (float)$p_row->other_deduction; ?>" style="padding:4px 8px; font-size:12px; font-variant-numeric:tabular-nums; text-align:left;" />
                                </td>
                                <td style="text-align:left; font-weight:700; font-variant-numeric:tabular-nums;" class="pay-net-cell">
                                    <?php echo SWVT_HR::format_number( $p_row->net_salary ); ?>
                                </td>
                                <td style="text-align:center;">
                                    <span class="swvt-hr-pill swvt-hr-pill-<?php echo esc_attr( $p_row->status ); ?>">
                                        <i></i><?php echo $p_row->status === 'paid' ? __( 'Paid', 'swvt-hr' ) : ( $p_row->status === 'pending' ? __( 'Pending', 'swvt-hr' ) : __( 'On Hold', 'swvt-hr' ) ); ?>
                                    </span>
                                </td>
                                <td style="text-align:center; padding:10px 18px;">
                                    <?php if ( $p_row->status === 'pending' ) : ?>
                                        <button type="button" class="swvt-hr-btn swvt-hr-btn-success swvt-hr-pay-individual-btn" data-id="<?php echo $p_row->id; ?>" style="padding:4px 9px; font-size:11.5px; white-space:nowrap;">
                                            <?php esc_html_e( 'Pay', 'swvt-hr' ); ?>
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#9aa0a6; font-size:11px;"><?php esc_html_e( 'Settled', 'swvt-hr' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="swvt-hr-table-footer">
                            <td style="padding:12px 18px;"><?php printf( __( 'Total (%d Staff)', 'swvt-hr' ), $totals['count'] ); ?></td>
                            <td></td>
                            <td style="text-align:left; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['basic'] ); ?></td>
                            <td style="text-align:left; color:#2271b1; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $totals['comm'] ); ?></td>
                            <td style="text-align:left; color:#b32d2e; font-variant-numeric:tabular-nums;"><?php echo $totals['absence'] > 0 ? '- ' . SWVT_HR::format_number( $totals['absence'] ) : '—'; ?></td>
                            <td style="text-align:left; color:#0a7c2f; font-variant-numeric:tabular-nums;" id="total-bonus-sum-cell"><?php echo SWVT_HR::format_number( $totals['bonus'] ); ?></td>
                            <td style="text-align:left; color:#b32d2e; font-variant-numeric:tabular-nums;" id="total-other-sum-cell"><?php echo $totals['other'] > 0 ? '- ' . SWVT_HR::format_number( $totals['other'] ) : '—'; ?></td>
                            <td style="text-align:left; font-weight:800; font-variant-numeric:tabular-nums;" id="total-net-sum-cell"><?php echo SWVT_HR::format_number( $totals['net'] ); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="swvt-hr-card swvt-hr-annual-payroll-card" style="margin-top:18px;">
        <div class="swvt-hr-card-header">
            <div>
                <h3 class="swvt-hr-card-title"><?php esc_html_e( 'Annual Payroll Tracking', 'swvt-hr' ); ?></h3>
                <div class="swvt-hr-annual-payroll-subtitle">
                    <?php
                    printf(
                        __( 'Year %1$d overview. Current payment month: %2$s.', 'swvt-hr' ),
                        $selected_year,
                        esc_html( $months_english[ $selected_month ] )
                    );
                    ?>
                </div>
            </div>
            <span class="swvt-hr-badge" style="background:#eaf2fb; color:#2271b1;">
                <?php printf( __( '%d Employees', 'swvt-hr' ), count( $yearly_employees ) ); ?>
            </span>
        </div>

        <div style="overflow-x:auto;">
            <table class="swvt-hr-table swvt-hr-annual-payroll-table">
                <thead>
                    <tr>
                        <th class="swvt-hr-annual-payroll-employee"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                        <?php foreach ( $months_english as $month_num => $month_name ) : ?>
                            <?php $is_current_column = (int) $selected_month === (int) $month_num; ?>
                            <th class="swvt-hr-annual-payroll-month-head <?php echo $is_current_column ? 'is-current-col is-current-head' : ''; ?>">
                                <?php echo esc_html( $month_num ); ?>
                                <?php if ( $is_current_column ) : ?>
                                    <div style="font-size:10px; font-weight:700; margin-top:3px;"><?php esc_html_e( 'Current', 'swvt-hr' ); ?></div>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="swvt-hr-annual-payroll-total"><?php esc_html_e( 'Year Total', 'swvt-hr' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $yearly_employees ) ) : ?>
                        <tr>
                            <td colspan="14" style="text-align:center; color:#787c82; padding:30px;">
                                <?php esc_html_e( 'No active employees found for the selected branch.', 'swvt-hr' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php $yearly_grand_total = 0.00; ?>
                        <?php foreach ( $yearly_employees as $employee ) : ?>
                            <?php
                            $employee_total = isset( $yearly_employee_totals[ $employee->id ] ) ? (float) $yearly_employee_totals[ $employee->id ] : 0.00;
                            $yearly_grand_total += $employee_total;
                            ?>
                            <tr>
                                <td class="swvt-hr-annual-payroll-employee">
                                    <div class="swvt-hr-annual-payroll-employee-name"><?php echo esc_html( $employee->full_name ); ?></div>
                                    <div class="swvt-hr-annual-payroll-employee-meta">
                                        <?php
                                        echo esc_html( $employee->job_title );
                                        if ( ! empty( $employee->branch_name ) ) {
                                            echo ' · ' . esc_html( $employee->branch_name );
                                        }
                                        ?>
                                    </div>
                                </td>
                                <?php foreach ( $months_english as $month_num => $month_name ) : ?>
                                    <?php
                                    $month_data = isset( $yearly_payroll_map[ $employee->id ][ $month_num ] ) ? $yearly_payroll_map[ $employee->id ][ $month_num ] : null;
                                    $is_current_column = (int) $selected_month === (int) $month_num;
                                    ?>
                                    <td class="swvt-hr-annual-payroll-status <?php echo $is_current_column ? 'is-current-col' : ''; ?>">
                                        <?php if ( $month_data ) : ?>
                                            <?php if ( 'paid' === $month_data['status'] ) : ?>
                                                <div class="swvt-hr-annual-payroll-dot is-paid">&#10003;</div>
                                                <div class="swvt-hr-annual-payroll-label is-paid"><?php esc_html_e( 'Paid', 'swvt-hr' ); ?></div>
                                            <?php else : ?>
                                                <div class="swvt-hr-annual-payroll-dot is-pending">...</div>
                                                <div class="swvt-hr-annual-payroll-label is-pending"><?php esc_html_e( 'Pending', 'swvt-hr' ); ?></div>
                                            <?php endif; ?>
                                            <div class="swvt-hr-annual-payroll-amount">
                                                <?php echo number_format( $month_data['net'], 0 ); ?>
                                            </div>
                                        <?php else : ?>
                                            <span class="swvt-hr-annual-payroll-empty">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="swvt-hr-annual-payroll-total">
                                    <?php echo SWVT_HR::format_number( $employee_total ); ?> <?php echo esc_html( $currency ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if ( ! empty( $yearly_employees ) ) : ?>
                    <tfoot>
                        <tr class="swvt-hr-table-footer">
                            <td style="padding:12px 18px;"><?php esc_html_e( 'Annual Total', 'swvt-hr' ); ?></td>
                            <td colspan="12"></td>
                            <td style="text-align:left; font-weight:800; font-variant-numeric:tabular-nums;">
                                <?php echo SWVT_HR::format_number( $yearly_grand_total ); ?> <?php echo esc_html( $currency ); ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Recalculate Confirmation Modal -->
    <div class="swvt-hr-modal-overlay" style="display:none;" id="swvt-hr-confirm-modal">
        <div class="swvt-hr-modal">
            <div class="swvt-hr-modal-body">
                <div class="swvt-hr-modal-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01"></path></svg>
                </div>
                <div style="line-height:1.45;">
                    <div style="font-size:16px; font-weight:600;"><?php esc_html_e( 'Confirm Recalculating Payroll', 'swvt-hr' ); ?></div>
                    <div style="font-size:13px; color:#787c82; margin-top:5px;">
                        <?php printf( __( 'This will recalculate all employee net wages for %s %d using the latest sales targets, commissions, and absences. This action cannot be undone.', 'swvt-hr' ), $months_english[ $selected_month ], $selected_year ); ?>
                    </div>
                </div>
            </div>
            <div class="swvt-hr-modal-actions">
                <button type="button" class="swvt-hr-btn swvt-hr-btn-primary" id="swvt-hr-recalc-confirm-btn">
                    <?php esc_html_e( 'Yes, Recalculate Now', 'swvt-hr' ); ?>
                </button>
                <button type="button" class="swvt-hr-btn" id="swvt-hr-recalc-cancel-btn">
                    <?php esc_html_e( 'Cancel', 'swvt-hr' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 1. Generate Payroll click
    $('#swvt-hr-generate-payroll-initial').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'swvt-hr' ); ?>');
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_generate_payroll',
            nonce: SWVT_HR.nonce,
            month: <?php echo $selected_month; ?>,
            year: <?php echo $selected_year; ?>,
            branch: <?php echo $selected_branch; ?>
        }, function(res) {
            if(res.success) {
                window.location.reload();
            } else {
                alert(res.data.message || 'Error occurred.');
                btn.prop('disabled', false).text('<?php esc_html_e( 'Generate Payroll Logs Now', 'swvt-hr' ); ?>');
            }
        });
    });

    // 2. Recalculate confirmation modal
    $('#swvt-hr-recalc-payroll-btn').on('click', function() {
        $('#swvt-hr-confirm-modal').fadeIn(200);
    });

    $('#swvt-hr-recalc-cancel-btn').on('click', function() {
        $('#swvt-hr-confirm-modal').fadeOut(150);
    });

    $('#swvt-hr-recalc-confirm-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('<?php esc_html_e( 'Recalculating...', 'swvt-hr' ); ?>');
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_recalc_payroll',
            nonce: SWVT_HR.nonce,
            month: <?php echo $selected_month; ?>,
            year: <?php echo $selected_year; ?>,
            branch: <?php echo $selected_branch; ?>
        }, function(res) {
            $('#swvt-hr-confirm-modal').hide();
            if(res.success) {
                window.location.reload();
            } else {
                alert(res.data.message || 'Error occurred.');
                btn.prop('disabled', false).text('<?php esc_html_e( 'Yes, Recalculate Now', 'swvt-hr' ); ?>');
            }
        });
    });

    // 3. Mark individual paid click
    $('.swvt-hr-pay-individual-btn').on('click', function() {
        var btn = $(this);
        var payrollId = btn.data('id');
        btn.prop('disabled', true).text('...');
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_mark_paid',
            nonce: SWVT_HR.nonce,
            id: payrollId
        }, function(res) {
            if(res.success) {
                window.location.reload();
            } else {
                alert(res.data.message || 'Error occurred.');
                btn.prop('disabled', false).text('<?php esc_html_e( 'Pay', 'swvt-hr' ); ?>');
            }
        });
    });

    // 4. Mark all paid click
    $('#swvt-hr-mark-all-paid-btn').on('click', function() {
        if(!confirm('Are you sure you want to mark all pending payroll entries as paid?')) {
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true).text('...');
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_mark_all_paid',
            nonce: SWVT_HR.nonce,
            month: <?php echo $selected_month; ?>,
            year: <?php echo $selected_year; ?>,
            branch: <?php echo $selected_branch; ?>
        }, function(res) {
            if(res.success) {
                window.location.reload();
            } else {
                alert(res.data.message || 'Error occurred.');
                btn.prop('disabled', false).text('<?php esc_html_e( 'Mark All as Paid', 'swvt-hr' ); ?>');
            }
        });
    });

    // 5. Live update of net salary in the browser when bonus/other deductions change
    $('.pay-bonus-input, .pay-other-input').on('input', function() {
        var row = $(this).closest('tr');
        var basic = parseFloat(row.find('.pay-basic-cell').data('val')) || 0;
        var comm = parseFloat(row.find('.pay-comm-cell').data('val')) || 0;
        var absence = parseFloat(row.find('.pay-absence-cell').data('val')) || 0;
        var bonus = parseFloat(row.find('.pay-bonus-input').val()) || 0;
        var other = parseFloat(row.find('.pay-other-input').val()) || 0;
        
        var net = basic + comm + bonus - absence - other;
        row.find('.pay-net-cell').text(net.toLocaleString('en-US', {minimumFractionDigits: 2}));
        
        recalcTableFooterTotals();
    });

    // 6. Save modifications to database when bonus or other deduction changes
    $('.pay-bonus-input, .pay-other-input').on('change', function() {
        var row = $(this).closest('tr');
        var payrollId = row.attr('id').replace('payroll-row-', '');
        var bonus = parseFloat(row.find('.pay-bonus-input').val()) || 0;
        var other = parseFloat(row.find('.pay-other-input').val()) || 0;

        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_save_payroll_adjustments',
            nonce: SWVT_HR.nonce,
            id: payrollId,
            bonus: bonus,
            other_deduction: other
        }, function(res) {
            if(res.success) {
                row.find('.pay-net-cell').text(parseFloat(res.data.net_salary).toLocaleString('en-US', {minimumFractionDigits: 2}));
                row.css('background-color', '#f6fbf7');
                setTimeout(function() {
                    row.css('background-color', '');
                }, 1000);
                recalcTableFooterTotals();
            } else {
                alert(res.data.message || 'Error occurred.');
            }
        });
    });

    function recalcTableFooterTotals() {
        var basicSum = 0;
        var commSum = 0;
        var absenceSum = 0;
        var bonusSum = 0;
        var otherSum = 0;
        var netSum = 0;
        
        $('.pay-basic-cell').each(function() {
            basicSum += parseFloat($(this).data('val')) || 0;
        });
        $('.pay-comm-cell').each(function() {
            commSum += parseFloat($(this).data('val')) || 0;
        });
        $('.pay-absence-cell').each(function() {
            absenceSum += parseFloat($(this).data('val')) || 0;
        });
        $('.pay-bonus-input').each(function() {
            bonusSum += parseFloat($(this).val()) || 0;
        });
        $('.pay-other-input').each(function() {
            otherSum += parseFloat($(this).val()) || 0;
        });
        
        netSum = basicSum + commSum + bonusSum - absenceSum - otherSum;
        
        $('#total-bonus-sum-cell').text(bonusSum.toLocaleString('en-US', {minimumFractionDigits: 2}));
        $('#total-other-sum-cell').text(otherSum > 0 ? '- ' + otherSum.toLocaleString('en-US', {minimumFractionDigits: 2}) : '—');
        $('#total-net-sum-cell').text(netSum.toLocaleString('en-US', {minimumFractionDigits: 2}));
    }
});
</script>
