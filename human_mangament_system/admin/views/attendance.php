<?php
/**
 * Attendance List and Registry View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected period
$selected_month  = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );

// Number of days in selected month
$days_in_month = (int) date( 't', mktime( 0, 0, 0, $selected_month, 1, $selected_year ) );

// Fetch active employees from all branches
$employees = $wpdb->get_results( $wpdb->prepare(
    "SELECT e.*, 
            att.absence_days, att.late_hours, att.deduction, att.reason, att.notes as att_notes,
            b.name as branch_name
     FROM {$p}employees e
     JOIN {$p}branches b ON e.branch_id = b.id
     LEFT JOIN {$p}attendance att ON e.id = att.employee_id AND att.period_month = %d AND att.period_year = %d
     WHERE e.status = 'active'
     ORDER BY b.name ASC, e.full_name ASC",
    $selected_month, $selected_year
) );

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = __( 'Attendance & Absence Logs', 'swvt-hr' );
$page_sub   = __( 'Log employee absence days and calculate daily salary deductions automatically.', 'swvt-hr' );
?>

<style>
    .swvt-attendance-grid-container {
        width: 100%;
        overflow-x: auto;
        margin-top: 15px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #fff;
    }
    .swvt-attendance-day-cell {
        text-align: center;
        vertical-align: middle;
        border-right: 1px solid #f0f0f0;
        padding: 8px 4px !important;
        font-size: 11px;
        min-width: 35px;
    }
    .swvt-attendance-day-cell.absent {
        background: #fef2f2;
    }
    .swvt-hr-table th {
        vertical-align: middle;
        text-align: center;
        font-size: 12px;
        padding: 10px 6px;
    }
</style>

<div class="wrap swvt-hr-wrap">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 23px; font-weight: 400; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
            <p style="font-size: 13px; color: #646970; margin: 0;"><?php echo esc_html( $page_sub ); ?></p>
        </div>
        <form method="get" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="swvt-hr-attendance" />
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

    <!-- Record Absence Card -->
    <div class="swvt-hr-card" style="background:#fff; padding:20px; margin-bottom:20px; border:1px solid #e2e8f0; border-radius:8px;">
        <h3 style="margin:0 0 14px; font-size:15px; font-weight:600; color:#1d2327;"><?php esc_html_e( 'Record New Absence', 'swvt-hr' ); ?></h3>
        <form id="swvt-record-absence-form" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) 120px; gap:12px; align-items:end;">
            <div class="swvt-hr-field-group" style="margin-bottom:0;">
                <label class="swvt-hr-label" style="font-weight:600;"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></label>
                <select id="swvt-absence-employee" class="swvt-hr-select" required>
                    <option value=""><?php esc_html_e( 'Select Employee...', 'swvt-hr' ); ?></option>
                    <?php foreach ($employees as $e) : ?>
                        <option value="<?php echo $e->id; ?>"><?php echo esc_html($e->full_name); ?> (<?php echo esc_html($e->branch_name); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="swvt-hr-field-group" style="margin-bottom:0;">
                <label class="swvt-hr-label" style="font-weight:600;"><?php esc_html_e( 'Day', 'swvt-hr' ); ?></label>
                <select id="swvt-absence-day" class="swvt-hr-select" required>
                    <?php for ($d = 1; $d <= $days_in_month; $d++) : ?>
                        <option value="<?php echo $d; ?>" <?php selected($d, (int)date('j')); ?>><?php echo $d; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="swvt-hr-field-group" style="margin-bottom:0;">
                <label class="swvt-hr-label" style="font-weight:600;"><?php esc_html_e( 'Absence / Deduction Weight', 'swvt-hr' ); ?></label>
                <select id="swvt-absence-weight" class="swvt-hr-select" required>
                    <option value="1.0"><?php esc_html_e( 'Full Day (1.0)', 'swvt-hr' ); ?></option>
                    <option value="0.5"><?php esc_html_e( 'Half Day (0.5)', 'swvt-hr' ); ?></option>
                    <option value="0.25"><?php esc_html_e( 'Quarter Day (0.25)', 'swvt-hr' ); ?></option>
                    <option value="2.0"><?php esc_html_e( 'Two Days (2.0)', 'swvt-hr' ); ?></option>
                    <option value="0.0"><?php esc_html_e( 'Clear / Present (0.0)', 'swvt-hr' ); ?></option>
                </select>
            </div>
            
            <div class="swvt-hr-field-group" style="margin-bottom:0;">
                <label class="swvt-hr-label" style="font-weight:600;"><?php esc_html_e( 'Reason / Notes', 'swvt-hr' ); ?></label>
                <input type="text" id="swvt-absence-reason" class="swvt-hr-input" placeholder="<?php esc_attr_e( 'e.g. Sick Leave', 'swvt-hr' ); ?>" style="height:35px;" />
            </div>
            
            <button type="submit" id="swvt-record-absence-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="height:40px; width:100%;">
                <?php esc_html_e( 'Save', 'swvt-hr' ); ?>
            </button>
        </form>
    </div>

    <!-- Attendance Card -->
    <div class="swvt-hr-card">
        <div class="swvt-hr-card-header">
            <div>
                <h3 class="swvt-hr-card-title"><?php esc_html_e( 'Staff Absences Registry', 'swvt-hr' ); ?></h3>
                <div style="font-size:12px; color:#787c82; margin-top:2px;">
                    <?php printf( __( 'Absences registry for %s %d', 'swvt-hr' ), $months_english[ $selected_month ], $selected_year ); ?>
                </div>
            </div>
            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970;">
                <?php printf( __( 'Total: %d Active Staff', 'swvt-hr' ), count( $employees ) ); ?>
            </span>
        </div>

        <div class="swvt-attendance-grid-container">
            <table class="swvt-hr-table">
                <thead>
                    <tr>
                        <th style="padding:12px 18px; min-width: 150px; text-align: left;"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                        <th style="min-width: 120px; text-align: left;"><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                        <th style="min-width: 100px; text-align: right;"><?php esc_html_e( 'Basic Salary', 'swvt-hr' ); ?></th>
                        
                        <!-- Days of the Month Headers -->
                        <?php for ( $d = 1; $d <= $days_in_month; $d++ ) : ?>
                            <th style="text-align:center; padding: 6px; min-width: 35px; font-size: 11px; font-weight: 600;"><?php echo $d; ?></th>
                        <?php endfor; ?>

                        <th style="width:70px; text-align:center; min-width: 70px;"><?php esc_html_e( 'Absences (Days)', 'swvt-hr' ); ?></th>
                        <th style="text-align:right; width:130px; min-width: 130px; font-weight: 600; color: #b32d2e;"><?php esc_html_e( 'Deduction Amount', 'swvt-hr' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $employees ) ) : ?>
                        <tr>
                            <td colspan="<?php echo 5 + $days_in_month; ?>" style="text-align:center; color:#787c82; padding:30px;">
                                <?php esc_html_e( 'No active employees registered.', 'swvt-hr' ); ?>
                            </td>
                        </tr>
                    <?php else : 
                        $zebra_idx = 0;
                        foreach ( $employees as $e ) : 
                            $absent_details = [];
                            if ( ! empty( $e->att_notes ) ) {
                                $decoded = json_decode( $e->att_notes, true );
                                if ( is_array( $decoded ) ) {
                                    $absent_details = $decoded;
                                }
                            }

                            $abs_days  = count( $absent_details ) > 0 ? (float) $e->absence_days : 0;
                            $deduction = ! is_null( $e->deduction ) ? (float) $e->deduction : 0.00;

                            $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                            $zebra_idx++;
                    ?>
                        <tr class="<?php echo $zebra_class; ?>" id="attendance-row-<?php echo $e->id; ?>">
                            <td style="padding:11px 18px; text-align: left;">
                                <div style="font-weight:600; white-space: nowrap;"><?php echo esc_html( $e->full_name ); ?></div>
                                <div style="font-size:11.5px; color:#9aa0a6; white-space: nowrap;"><?php echo esc_html( $e->job_title ); ?></div>
                            </td>
                            <td style="white-space: nowrap; font-weight: 500; color: #475569; text-align: left;"><?php echo esc_html( $e->branch_name ); ?></td>
                            <td style="font-variant-numeric:tabular-nums; white-space: nowrap; text-align: right;" class="basic-salary-cell" data-val="<?php echo $e->basic_salary; ?>">
                                <?php echo SWVT_HR::format_number( $e->basic_salary ); ?> EGP
                            </td>
                            
                            <!-- Days of the Month Grid -->
                            <?php for ( $d = 1; $d <= $days_in_month; $d++ ) : 
                                $is_absent = isset( $absent_details[$d] );
                                $cell_class = $is_absent ? 'swvt-attendance-day-cell absent' : 'swvt-attendance-day-cell';
                            ?>
                                <td class="<?php echo $cell_class; ?>">
                                    <?php 
                                    if ( $is_absent ) {
                                        $weight = $absent_details[$d]['weight'];
                                        $reason = isset($absent_details[$d]['reason']) ? $absent_details[$d]['reason'] : '';
                                        $weight_text = '';
                                        if ( $weight == 0.5 ) $weight_text = '½';
                                        elseif ( $weight == 0.25 ) $weight_text = '¼';
                                        elseif ( $weight == 2.0 ) $weight_text = '2';
                                        else $weight_text = '1';
                                        
                                        echo '<span style="color:#ef4444; font-weight:700; cursor:help;" title="' . esc_attr($reason) . '">❌ (' . $weight_text . ')</span>';
                                    } else {
                                        echo '<span style="color:#94a3b8;">-</span>';
                                    }
                                    ?>
                                </td>
                            <?php endfor; ?>

                            <td style="text-align:center; font-weight:600;">
                                <?php echo $abs_days; ?>
                            </td>
                            <td style="text-align:right; font-weight:700; color:#b32d2e; font-variant-numeric:tabular-nums;">
                                <?php echo SWVT_HR::format_number( $deduction ); ?> EGP
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Submit single absence
    $('#swvt-record-absence-form').on('submit', function(e) {
        e.preventDefault();
        
        var employeeId = $('#swvt-absence-employee').val();
        var day        = $('#swvt-absence-day').val();
        var weight     = $('#swvt-absence-weight').val();
        var reason     = $('#swvt-absence-reason').val();
        var submitBtn  = $('#swvt-record-absence-btn');
        
        if (!employeeId) {
            alert('Please select an employee.');
            return;
        }

        submitBtn.prop('disabled', true).css('opacity', 0.6).text('...');

        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_record_single_absence',
            nonce: SWVT_HR.nonce,
            employee_id: employeeId,
            period_month: <?php echo $selected_month; ?>,
            period_year: <?php echo $selected_year; ?>,
            day: day,
            weight: weight,
            reason: reason
        }, function(res) {
            submitBtn.prop('disabled', false).css('opacity', 1).text('<?php esc_html_e( 'Save', 'swvt-hr' ); ?>');
            if (res.success) {
                window.location.reload();
            } else {
                alert(res.data.message || 'Error occurred while saving.');
            }
        });
    });
});
</script>
