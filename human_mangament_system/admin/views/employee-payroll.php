<?php
/**
 * Employee Payroll & Inflation Tracker View.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected period and branch
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_branch = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;

$branches = SWVT_HR_Branch::get_active();

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

// Fetch active employees
$employees_query = "SELECT e.id, e.full_name, e.job_title, e.branch_id, b.name as branch_name
                    FROM {$p}employees e
                    LEFT JOIN {$p}branches b ON b.id = e.branch_id
                    WHERE e.status = 'active'";

if ( $selected_branch ) {
    $employees_query .= $wpdb->prepare( " AND e.branch_id = %d", $selected_branch );
}

$employees_query .= " ORDER BY e.id ASC";
$yearly_employees = $wpdb->get_results( $employees_query );

// Fetch yearly payroll data (including basic_salary, commission, net_salary)
$yearly_payroll_query = "SELECT pay.employee_id, pay.period_month, pay.basic_salary, pay.commission, pay.net_salary, pay.status
                         FROM {$p}payroll pay
                         WHERE pay.period_year = %d";

if ( $selected_branch ) {
    $yearly_payroll_query .= $wpdb->prepare( " AND pay.branch_id = %d", $selected_branch );
}

$yearly_payroll_query .= " ORDER BY pay.employee_id ASC, pay.period_month ASC";
$yearly_payroll_rows = $wpdb->get_results( $wpdb->prepare( $yearly_payroll_query, $selected_year ) );

// Map payroll data
$yearly_payroll_map = [];
$yearly_employee_totals = [];
$yearly_employee_commissions = [];
$yearly_employee_basics = [];

foreach ( $yearly_payroll_rows as $year_row ) {
    $employee_id = (int) $year_row->employee_id;
    $month_num   = (int) $year_row->period_month;

    if ( ! isset( $yearly_payroll_map[ $employee_id ] ) ) {
        $yearly_payroll_map[ $employee_id ] = [];
    }

    $yearly_payroll_map[ $employee_id ][ $month_num ] = [
        'status'     => $year_row->status,
        'net'        => (float) $year_row->net_salary,
        'commission' => (float) $year_row->commission,
        'basic'      => (float) $year_row->basic_salary,
    ];

    if ( ! isset( $yearly_employee_totals[ $employee_id ] ) ) {
        $yearly_employee_totals[ $employee_id ] = 0.00;
    }
    if ( ! isset( $yearly_employee_commissions[ $employee_id ] ) ) {
        $yearly_employee_commissions[ $employee_id ] = 0.00;
    }
    if ( ! isset( $yearly_employee_basics[ $employee_id ] ) ) {
        $yearly_employee_basics[ $employee_id ] = 0.00;
    }

    $yearly_employee_totals[ $employee_id ]      += (float) $year_row->net_salary;
    $yearly_employee_commissions[ $employee_id ] += (float) $year_row->commission;
    $yearly_employee_basics[ $employee_id ]      += (float) $year_row->basic_salary;
}

$settings = get_option( 'swvt_hr_settings' );
$currency = isset( $settings['currency'] ) ? $settings['currency'] : 'EGP';

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>';
$page_title = __( 'Employee Payroll & Inflation Tracker', 'swvt-hr' );
$page_sub   = __( 'Analyze employee annual payouts including commissions and compare salary growth against inflation.', 'swvt-hr' );
?>

<div class="wrap swvt-hr-wrap">
    <!-- Header -->
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 23px; font-weight: 400; margin: 0 0 4px;"><?php echo $page_icon; ?> <?php echo esc_html( $page_title ); ?></h1>
            <p style="font-size: 13px; color: #646970; margin: 0;"><?php echo esc_html( $page_sub ); ?></p>
        </div>
        <form method="get" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="swvt-hr-employee-payroll" />
            <select name="branch" class="swvt-hr-select" style="width: 160px;">
                <option value="0"><?php esc_html_e( 'All Branches', 'swvt-hr' ); ?></option>
                <?php foreach ( $branches as $b ) : ?>
                    <option value="<?php echo $b->id; ?>" <?php selected( $selected_branch, $b->id ); ?>><?php echo esc_html( $b->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="y" class="swvt-hr-select" style="width: 100px;">
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

    <!-- Inflation parameters & explanation -->
    <div class="swvt-hr-card" style="padding: 20px; margin-bottom: 20px; background: linear-gradient(135deg, #fff, #f6f9fc); border-left: 4px solid #2271b1;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-top: 0; margin-bottom: 8px; font-size: 16px; color: #1d2327;">
                    🔍 مقارنة نمو الدخل السنوي بمعدل التضخم في مصر (Inflation Analysis)
                </h3>
                <p style="font-size: 13px; color: #50575e; line-height: 1.6; margin: 0;">
                    تقوم هذه الصفحة باحتساب إجمالي مستحقات الموظف شهرياً <strong>(الراتب الصافي + العمولة المستلمة)</strong> وتتبع تطور الدخل على مدار العام. 
                    يقارن مؤشر النمو نسبة التغير بين أول شهر نشط في السنة وآخر شهر نشط لتحديد مدى تناسب زيادة الدخل مع معدل التضخم المحدد.
                </p>
            </div>
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #dcdcde; display: flex; flex-direction: column; gap: 8px; min-width: 200px;">
                <label for="inflation-rate" style="font-weight: 600; font-size: 12.5px; color: #1d2327;">معدل التضخم السنوي المستهدف:</label>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <input type="number" id="inflation-rate" class="swvt-hr-input" value="35" min="0" max="200" step="0.5" style="width: 90px; padding: 6px; font-size: 14px; font-weight: bold; text-align: center;" />
                    <span style="font-weight: bold; font-size: 15px; color: #3c434a;">%</span>
                </div>
                <div style="font-size: 11.5px; color: #787c82; margin-top: 2px;">
                    * يتم تحديث الجدول تلقائياً عند تغيير المعدل.
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="swvt-hr-card">
        <div style="overflow-x:auto;">
            <table class="swvt-hr-table" style="font-size:12px; min-width:1300px;">
                <thead>
                    <tr style="background:#f6f7f7;">
                        <th style="padding:15px 18px; width:220px; font-weight:700;"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                        <?php foreach ( $months_english as $month_num => $month_name ) : ?>
                            <th style="text-align:center; font-weight:600; width:80px; padding:12px 6px;">
                                <?php echo esc_html( $month_num ); ?>
                            </th>
                        <?php endforeach; ?>
                        <th style="text-align:left; font-weight:700; width:120px; padding:12px 10px;"><?php esc_html_e( 'Basic Total', 'swvt-hr' ); ?></th>
                        <th style="text-align:left; font-weight:700; width:120px; padding:12px 10px; color:#2271b1;"><?php esc_html_e( 'Comm Total', 'swvt-hr' ); ?></th>
                        <th style="text-align:left; font-weight:700; width:130px; padding:12px 10px;"><?php esc_html_e( 'Net Grand Total', 'swvt-hr' ); ?></th>
                        <th style="text-align:center; font-weight:700; width:100px; padding:12px 10px;"><?php esc_html_e( 'Growth Rate', 'swvt-hr' ); ?></th>
                        <th style="text-align:center; font-weight:700; width:140px; padding:12px 18px;">الوضع مقابل التضخم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $yearly_employees ) ) : ?>
                        <tr>
                            <td colspan="18" style="text-align:center; color:#787c82; padding:40px;">
                                <?php esc_html_e( 'No active employees found for the selected filter.', 'swvt-hr' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php 
                        $grand_basic = 0.00;
                        $grand_comm = 0.00;
                        $grand_net = 0.00;
                        
                        foreach ( $yearly_employees as $employee ) : 
                            $total_basic = isset( $yearly_employee_basics[ $employee->id ] ) ? $yearly_employee_basics[ $employee->id ] : 0.00;
                            $total_comm  = isset( $yearly_employee_commissions[ $employee->id ] ) ? $yearly_employee_commissions[ $employee->id ] : 0.00;
                            $total_net   = isset( $yearly_employee_totals[ $employee->id ] ) ? $yearly_employee_totals[ $employee->id ] : 0.00;
                            
                            $grand_basic += $total_basic;
                            $grand_comm  += $total_comm;
                            $grand_net   += $total_net;
                            
                            // Calculate growth rate between first month net and last month net
                            $growth_rate = null;
                            $first_income = 0;
                            $last_income = 0;
                            if ( isset( $yearly_payroll_map[ $employee->id ] ) && count( $yearly_payroll_map[ $employee->id ] ) >= 1 ) {
                                $active_months = array_keys( $yearly_payroll_map[ $employee->id ] );
                                sort( $active_months );
                                $first_month = $active_months[0];
                                $last_month = end( $active_months );
                                
                                $first_income = $yearly_payroll_map[ $employee->id ][$first_month]['net'];
                                $last_income = $yearly_payroll_map[ $employee->id ][$last_month]['net'];
                                
                                if ( $first_income > 0 ) {
                                    $growth_rate = (($last_income - $first_income) / $first_income) * 100;
                                } else {
                                    $growth_rate = 0.00;
                                }
                            }
                        ?>
                            <tr class="employee-payroll-row" data-growth="<?php echo $growth_rate !== null ? esc_attr( number_format($growth_rate, 2, '.', '') ) : ''; ?>">
                                <td style="padding:12px 18px; vertical-align:middle; border-bottom:1px solid #f0f1f2;">
                                    <div style="font-weight:600; color:#1d2327; font-size:13px;"><?php echo esc_html( $employee->full_name ); ?></div>
                                    <div style="font-size:11px; color:#787c82; margin-top:2px;">
                                        <?php echo esc_html( $employee->job_title ); ?> · <strong><?php echo esc_html( $employee->branch_name ); ?></strong>
                                    </div>
                                </td>
                                
                                <?php foreach ( $months_english as $month_num => $month_name ) : ?>
                                    <td style="text-align:center; padding:10px 4px; vertical-align:middle; border-bottom:1px solid #f0f1f2; font-variant-numeric:tabular-nums;">
                                        <?php if ( isset( $yearly_payroll_map[ $employee->id ][ $month_num ] ) ) : 
                                            $m_data = $yearly_payroll_map[ $employee->id ][ $month_num ];
                                            $m_net = $m_data['net'];
                                            $m_comm = $m_data['commission'];
                                            $m_status = $m_data['status'];
                                        ?>
                                            <div style="font-weight:700; color:#2c3338; font-size:12px;">
                                                <?php echo number_format( $m_net, 0 ); ?>
                                            </div>
                                            <?php if ( $m_comm > 0 ) : ?>
                                                <div style="color:#2271b1; font-size:10px; font-weight:600; margin-top:2px;" title="العمولة">
                                                    +<?php echo number_format( $m_comm, 0 ); ?>
                                                </div>
                                            <?php else : ?>
                                                <div style="color:#a7aaad; font-size:10px; margin-top:2px;">—</div>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span style="color:#d5d7d9;">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <td style="text-align:left; vertical-align:middle; font-weight:500; font-variant-numeric:tabular-nums; border-bottom:1px solid #f0f1f2; padding:12px 10px;">
                                    <?php echo SWVT_HR::format_number( $total_basic ); ?>
                                </td>
                                <td style="text-align:left; vertical-align:middle; font-weight:600; font-variant-numeric:tabular-nums; color:#2271b1; border-bottom:1px solid #f0f1f2; padding:12px 10px;">
                                    <?php echo SWVT_HR::format_number( $total_comm ); ?>
                                </td>
                                <td style="text-align:left; vertical-align:middle; font-weight:700; font-variant-numeric:tabular-nums; color:#1d2327; border-bottom:1px solid #f0f1f2; padding:12px 10px; background:#fafbfe;">
                                    <?php echo SWVT_HR::format_number( $total_net ); ?> <?php echo esc_html( $currency ); ?>
                                </td>
                                
                                <!-- Growth Rate Cell -->
                                <td style="text-align:center; vertical-align:middle; font-weight:700; font-variant-numeric:tabular-nums; border-bottom:1px solid #f0f1f2; padding:12px 10px;">
                                    <?php if ( $growth_rate !== null ) : ?>
                                        <span style="color: <?php echo $growth_rate >= 0 ? '#0a7c2f' : '#b32d2e'; ?>;">
                                            <?php echo ($growth_rate >= 0 ? '+' : '') . number_format($growth_rate, 1); ?>%
                                        </span>
                                    <?php else : ?>
                                        <span style="color:#787c82;">جديد / —</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Inflation Status Cell -->
                                <td class="inflation-status-cell" style="text-align:center; vertical-align:middle; border-bottom:1px solid #f0f1f2; padding:12px 18px;">
                                    <!-- Dynamic content updated by JS -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if ( ! empty( $yearly_employees ) ) : ?>
                    <tfoot>
                        <tr class="swvt-hr-table-footer" style="background:#f6f7f7;">
                            <td style="padding:15px 18px; font-weight:700;"><?php esc_html_e( 'Annual Grand Totals', 'swvt-hr' ); ?></td>
                            <td colspan="12"></td>
                            <td style="text-align:left; font-weight:700; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $grand_basic ); ?></td>
                            <td style="text-align:left; font-weight:700; font-variant-numeric:tabular-nums; color:#2271b1;"><?php echo SWVT_HR::format_number( $grand_comm ); ?></td>
                            <td style="text-align:left; font-weight:800; font-variant-numeric:tabular-nums; color:#1d2327;" colspan="3">
                                <?php echo SWVT_HR::format_number( $grand_net ); ?> <?php echo esc_html( $currency ); ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    function evaluateInflation() {
        var inflationRate = parseFloat($('#inflation-rate').val()) || 0;
        
        $('.employee-payroll-row').each(function() {
            var row = $(this);
            var growthAttr = row.data('growth');
            var statusCell = row.find('.inflation-status-cell');
            
            if (growthAttr === '' || growthAttr === undefined) {
                statusCell.html('<span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970; font-size:11px;">بيانات غير كافية</span>');
                return;
            }
            
            var growth = parseFloat(growthAttr);
            
            if (growth >= inflationRate) {
                statusCell.html(
                    '<span class="swvt-hr-badge" style="background:#e6f4ea; color:#137333; font-weight:700; font-size:11.5px; display:inline-block; width:100%; text-align:center;">' +
                    '🟢 مواكب للتضخم (' + growth.toFixed(1) + '%)' +
                    '</span>'
                );
            } else if (growth > 0) {
                statusCell.html(
                    '<span class="swvt-hr-badge" style="background:#fef7e0; color:#b06000; font-weight:700; font-size:11.5px; display:inline-block; width:100%; text-align:center;">' +
                    '🟡 أقل من التضخم (' + growth.toFixed(1) + '%)' +
                    '</span>'
                );
            } else {
                statusCell.html(
                    '<span class="swvt-hr-badge" style="background:#fce8e6; color:#c5221f; font-weight:700; font-size:11.5px; display:inline-block; width:100%; text-align:center;">' +
                    '🔴 لم يزد / متناقص (' + growth.toFixed(1) + '%)' +
                    '</span>'
                );
            }
        });
    }

    // Run on load
    evaluateInflation();

    // Run on change
    $('#inflation-rate').on('input change', function() {
        evaluateInflation();
    });
});
</script>
