<?php
/**
 * Commission Rules View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected period
$selected_month = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year  = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );

$settings = get_option( 'swvt_hr_settings' );
$default_rate = isset( $settings['default_commission_rate'] ) ? (float) $settings['default_commission_rate'] : 0.0020;
$dist = isset( $settings['role_distribution'] ) ? $settings['role_distribution'] : [ 'manager'=>60,'accountant'=>20,'delivery'=>10,'prep'=>10 ];

// Convert rate to "in the thousand" format
$default_rate_thousand = $default_rate * 1000;

// Fetch all branches for overrides
$branches = SWVT_HR_Branch::get_all();

// Fetch employees for eligibility exceptions
$employees = SWVT_HR_Employee::get_all();

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"></path></svg>';
$page_title = __( 'Commission Rules & Rates', 'swvt-hr' );
$page_sub   = __( 'Define company-wide default commission rates, role distributions, branch overrides, and eligibility rules.', 'swvt-hr' );

// Worked Example Math (Al-Jumhouria, sales 971,181)
$ex_sales = 971181.00;
$ex_base = round( $ex_sales * $default_rate, 2 );

$role_labels = [
    'manager'    => __( 'Branch Manager', 'swvt-hr' ),
    'accountant' => __( 'Branch Accountant', 'swvt-hr' ),
    'delivery'   => __( 'Delivery Driver', 'swvt-hr' ),
    'prep'       => __( 'Prep Specialist', 'swvt-hr' )
];
?>

<div class="wrap swvt-hr-wrap">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 23px; font-weight: 400; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
            <p style="font-size: 13px; color: #646970; margin: 0;"><?php echo esc_html( $page_sub ); ?></p>
        </div>
        <form method="get" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="page" value="swvt-hr-rules" />
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

    <form id="swvt-hr-rules-form" method="post" action="">
        <!-- Top forms: default rate & worked example -->
        <div class="swvt-hr-grid-rules">
            <!-- Default Rate Card -->
            <div class="swvt-hr-card-padded">
                <h3 style="font-size:15px; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; color:#2271b1;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    <?php esc_html_e( 'Default Company Commission Rate', 'swvt-hr' ); ?>
                </h3>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Default rate from branch sales', 'swvt-hr' ); ?></label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="number" step="0.1" name="default_rate_thousand" id="default_rate_thousand" value="<?php echo $default_rate_thousand; ?>" style="width:80px; padding:10px 12px; border:1px solid #d5d8db; border-radius:8px; font-size:20px; font-weight:700; text-align:center; outline:none; font-variant-numeric:tabular-nums;" required />
                        <div style="line-height:1.35;">
                            <div style="font-size:14px; font-weight:600;"><?php esc_html_e( 'in the thousand', 'swvt-hr' ); ?></div>
                            <div style="font-size:12px; color:#787c82;" id="default_rate_pct_label">
                                <?php printf( __( 'Equivalent to %s%% of sales', 'swvt-hr' ), number_format( $default_rate * 100, 2 ) ); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:16px; background:#f6f7f8; border-radius:9px; padding:12px 14px; font-size:13px; color:#3c434a; line-height:1.6;">
                    <span style="color:#787c82;"><?php esc_html_e( 'Commission Base Formula:', 'swvt-hr' ); ?></span><br>
                    <b><?php esc_html_e( 'Commission Pool = Branch Sales × Commission Rate', 'swvt-hr' ); ?></b>
                </div>
            </div>

            <!-- Worked Example Card -->
            <div class="swvt-hr-card-padded" style="display:flex; flex-direction:column; justify-content:center;">
                <div style="font-size:13px; color:#787c82; margin-bottom:14px;">
                    <?php esc_html_e( 'Worked Example — Al-Jumhouria Branch, monthly sales of 971,181.00 EGP', 'swvt-hr' ); ?>
                </div>
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:12px; color:#787c82; margin-bottom:3px;"><?php esc_html_e( 'Branch Sales', 'swvt-hr' ); ?></div>
                        <div style="font-size:22px; font-weight:700; font-variant-numeric:tabular-nums;"><?php echo number_format( $ex_sales, 2 ); ?></div>
                    </div>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c3c4c7" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    <div style="text-align:center;">
                        <div style="font-size:12px; color:#787c82; margin-bottom:3px;" id="example_rate_label"><?php printf( __( '× %s in the thousand', 'swvt-hr' ), $default_rate_thousand ); ?></div>
                        <div style="font-size:15px; font-weight:600; color:#b78a00;" id="example_pct_label"><?php echo ( $default_rate * 100 ) . '%'; ?></div>
                    </div>
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#c3c4c7" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    <div style="background:#fdf4dd; border:1px solid #f0e0a8; border-radius:10px; padding:10px 18px; text-align:center;">
                        <div style="font-size:12px; color:#8a6100; margin-bottom:3px;"><?php esc_html_e( 'Calculated Commission Pool', 'swvt-hr' ); ?></div>
                        <div style="font-size:24px; font-weight:700; color:#6a5300; font-variant-numeric:tabular-nums;" id="example_base_value">
                            <?php echo number_format( $ex_base, 2 ); ?> <span style="font-size:14px; font-weight:500; color: #8a6100;"><?php echo $settings['currency']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rules Table & Sidebar columns -->
        <div class="swvt-hr-grid-rules-bottom">
            <!-- Left Side: Role distribution rules -->
            <div>
                <div class="swvt-hr-card" style="margin-bottom:18px;">
                    <div class="swvt-hr-card-header" style="flex-wrap:wrap; gap:10px;">
                        <div>
                            <h3 class="swvt-hr-card-title"><?php esc_html_e( 'Role Commission Distributions', 'swvt-hr' ); ?></h3>
                            <div style="font-size:12px; color:#787c82; margin-top:2px;">
                                <?php esc_html_e( 'Default company splitting rules applied to all branches.', 'swvt-hr' ); ?>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span id="rules-sum-badge" class="swvt-hr-badge swvt-hr-badge-success" style="display:inline-flex; align-items:center; gap:6px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"></path></svg>
                                <?php esc_html_e( 'Total Balanced = 100%', 'swvt-hr' ); ?>
                            </span>
                        </div>
                    </div>

                    <table class="swvt-hr-table">
                        <thead>
                            <tr>
                                <th style="color:#4a3d00; border-bottom:1px solid #dcb000;"><?php esc_html_e( 'Functional Role Group', 'swvt-hr' ); ?></th>
                                <th style="color:#4a3d00; border-bottom:1px solid #dcb000; text-align:center; width:150px;"><?php esc_html_e( 'Percentage Split', 'swvt-hr' ); ?></th>
                                <th style="color:#4a3d00; border-bottom:1px solid #dcb000;"><?php esc_html_e( 'Visual Share', 'swvt-hr' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $zebra_idx = 0;
                            foreach ( $dist as $role_key => $pct ) : 
                                $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                                $zebra_idx++;
                            ?>
                                <tr class="<?php echo $zebra_class; ?>">
                                    <td>
                                        <div style="font-weight:600;"><?php echo isset( $role_labels[ $role_key ] ) ? esc_html( $role_labels[ $role_key ] ) : esc_html( $role_key ); ?></div>
                                        <div style="font-size:11.5px; color:#9aa0a6;">
                                            <?php 
                                            if ( $role_key === 'manager' ) esc_html_e( 'Branch Manager in charge.', 'swvt-hr' );
                                            elseif ( $role_key === 'accountant' ) esc_html_e( 'Branch Accountant in charge.', 'swvt-hr' );
                                            else esc_html_e( 'Share will be split equally among active, eligible employees in this role.', 'swvt-hr' );
                                            ?>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">
                                        <div style="display:inline-flex; align-items:center; gap:4px; border:1px solid #d5d8db; border-radius:8px; padding:4px 8px; background:#fff;">
                                            <input type="number" name="role_dist[<?php echo esc_attr( $role_key ); ?>]" class="role-pct-input" value="<?php echo (int)$pct; ?>" style="width:42px; border:none; outline:none; font-size:15px; font-weight:700; text-align:center; font-variant-numeric:tabular-nums; background:transparent;" min="0" max="100" />
                                            <span style="color:#787c82; font-weight:600;">%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="background:#f0f0f1; border-radius:999px; height:8px; overflow:hidden;">
                                            <div class="role-progress-bar" data-role="<?php echo esc_attr( $role_key ); ?>" style="height:8px; width:<?php echo $pct; ?>%; background:#2271b1; border-radius:999px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="swvt-hr-table-footer">
                                <td><?php esc_html_e( 'Total', 'swvt-hr' ); ?></td>
                                <td style="text-align:center; font-size:15px;" id="total-pct-sum-label">100%</td>
                                <td style="color:#787c82; font-size:12.5px; font-weight: normal;"><?php esc_html_e( 'Must sum up to exactly 100% to save changes.', 'swvt-hr' ); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Branch Sales Reports (Annual & Averages) -->
                <div class="swvt-hr-card">
                    <div style="padding:14px 18px; border-bottom:1px solid #eef0f1; font-size:15px; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
                        <span><?php printf( __( 'Sales Performance Reports (%d)', 'swvt-hr' ), $selected_year ); ?></span>
                        <span style="font-size:12px; font-weight:normal; color:#787c82;"><?php esc_html_e( 'Selected Year', 'swvt-hr' ); ?></span>
                    </div>
                    
                    <table class="swvt-hr-table">
                        <thead>
                            <tr>
                                <th style="padding:9px 18px; font-size:12px; font-weight: 600;"><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                                <th style="padding:9px 12px; text-align:right; color:#2271b1; font-size:12px; font-weight: 600;"><?php esc_html_e( 'Avg Monthly Sales', 'swvt-hr' ); ?></th>
                                <th style="padding:9px 18px; text-align:right; color:#0a7c2f; font-size:12px; font-weight: 600;"><?php printf( __( 'Annual Sales (%d)', 'swvt-hr' ), $selected_year ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total_annual = 0.00;
                            
                            $monthly_sums_raw = $wpdb->get_results( $wpdb->prepare( "
                                SELECT period_month, SUM(total_sales) as monthly_sum 
                                FROM {$p}sales 
                                WHERE period_year = %d 
                                GROUP BY period_month
                            ", $selected_year ) );
                            
                            $monthly_sums = [];
                            foreach ( $monthly_sums_raw as $m_sum ) {
                                $monthly_sums[ $m_sum->period_month ] = (float) $m_sum->monthly_sum;
                            }
                            $grand_avg_monthly = count( $monthly_sums ) > 0 ? array_sum( $monthly_sums ) / count( $monthly_sums ) : 0.00;

                            $zebra_idx = 0;
                            foreach ( $branches as $b ) : 
                                $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                                $zebra_idx++;
                                
                                $avg_monthly = $wpdb->get_var( $wpdb->prepare( "
                                    SELECT AVG(total_sales) 
                                    FROM {$p}sales 
                                    WHERE branch_id = %d
                                ", $b->id ) );
                                $avg_monthly = $avg_monthly ? (float) $avg_monthly : 0.00;

                                $total_annual = $wpdb->get_var( $wpdb->prepare( "
                                    SELECT SUM(total_sales) 
                                    FROM {$p}sales 
                                    WHERE branch_id = %d AND period_year = %d
                                ", $b->id, $selected_year ) );
                                $total_annual = $total_annual ? (float) $total_annual : 0.00;
                                $grand_total_annual += $total_annual;
                            ?>
                                <tr class="<?php echo $zebra_class; ?>" style="border-bottom:1px solid #f4f5f6;">
                                    <td style="padding:11px 18px; font-weight:600;"><?php echo esc_html( $b->name ); ?></td>
                                    <td style="padding:11px 12px; text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#2271b1;">
                                        <?php echo number_format( $avg_monthly, 2 ); ?> EGP
                                    </td>
                                    <td style="padding:11px 18px; text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;">
                                        <?php echo number_format( $total_annual, 2 ); ?> EGP
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="swvt-hr-table-footer" style="background:#f4f9f4; border-top: 1px solid #cce5cc;">
                                <td style="font-weight:700; color:#0a7c2f;"><?php esc_html_e( 'Total Annual (All)', 'swvt-hr' ); ?></td>
                                <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#646970;"></td>
                                <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:800; color:#0a7c2f; font-size:14px;">
                                    <?php echo number_format( $grand_total_annual, 2 ); ?> EGP
                                </td>
                            </tr>
                            <tr class="swvt-hr-table-footer" style="background:#f4f8fc; border-top: 1px solid #ccddeb;">
                                <td style="font-weight:700; color:#2271b1;"><?php esc_html_e( 'Avg Monthly (All)', 'swvt-hr' ); ?></td>
                                <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#2271b1; font-size:14px;">
                                    <?php echo number_format( $grand_avg_monthly, 2 ); ?> EGP
                                </td>
                                <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#646970;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Right Side: Branch overrides & Employee exemptions -->
            <div>
                <!-- Per-branch overrides -->
                <div class="swvt-hr-card">
                    <div style="padding:14px 18px; border-bottom:1px solid #eef0f1; display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-size:14px; font-weight:600;"><?php esc_html_e( 'Branch Overrides', 'swvt-hr' ); ?></span>
                        <span style="font-size:11.5px; color:#787c82;"><?php esc_html_e( 'Overrides default rate', 'swvt-hr' ); ?></span>
                    </div>
                    <table class="swvt-hr-table" style="font-size:12.5px;">
                        <tbody>
                            <?php 
                            $zebra_idx = 0;
                            foreach ( $branches as $b ) : 
                                $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                                $zebra_idx++;
                                $has_override = ! is_null( $b->commission_rate ) && $b->commission_rate > 0;
                                $rate_display = $has_override ? (float) $b->commission_rate : $default_rate;
                            ?>
                                <tr class="<?php echo $zebra_class; ?>">
                                    <td style="padding:9px 18px; font-weight:600;"><?php echo esc_html( $b->name ); ?></td>
                                    <td style="padding:9px 12px; text-align:center;">
                                        <input type="number" step="0.0001" min="0" max="1" name="branch_overrides[<?php echo $b->id; ?>]" class="swvt-hr-input branch-override-input" value="<?php echo $has_override ? esc_attr( $b->commission_rate ) : ''; ?>" placeholder="<?php echo esc_attr( $default_rate ); ?>" style="width:90px; padding:4px 8px; font-size:12px; font-variant-numeric:tabular-nums; text-align:center;" />
                                    </td>
                                    <td style="padding:9px 18px; text-align:right;">
                                        <?php if ( $has_override ) : ?>
                                            <span class="swvt-hr-badge" style="background:#eaf2fb; color:#2271b1; font-weight:600;"><?php esc_html_e( 'Custom', 'swvt-hr' ); ?></span>
                                        <?php else : ?>
                                            <span class="swvt-hr-badge" style="background:#f1f2f3; color:#646970;"><?php esc_html_e( 'Default', 'swvt-hr' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Employee Exceptions -->
                <div class="swvt-hr-card" style="padding:16px 18px;">
                    <div style="font-size:14px; font-weight:600; margin-bottom:12px;"><?php esc_html_e( 'Commission Eligibility exceptions', 'swvt-hr' ); ?></div>
                    <div style="display:flex; flex-direction:column; gap:11px; max-height: 290px; overflow-y: auto; padding-right: 5px;">
                        <?php foreach ( $employees as $emp ) : ?>
                            <div style="display:flex; align-items:center; gap:11px; border-bottom:1px solid #f4f5f6; padding-bottom:8px;">
                                <div style="line-height:1.35; flex:1;">
                                    <div style="font-size:13px; font-weight:600;"><?php echo esc_html( $emp->full_name ); ?></div>
                                    <div style="font-size:11.5px; color:#9aa0a6;">
                                        <?php echo esc_html( $emp->job_title ); ?> 
                                        <?php echo $emp->branch_id ? ' · ' . esc_html( SWVT_HR_Branch::get( $emp->branch_id )->name ) : ''; ?>
                                    </div>
                                </div>
                                <div class="swvt-hr-toggle swvt-hr-eligibility-toggle <?php echo $emp->commission_eligible ? 'active' : ''; ?>" data-id="<?php echo $emp->id; ?>">
                                    <input type="hidden" name="employee_eligibility[<?php echo $emp->id; ?>]" value="<?php echo $emp->commission_eligible ? 1 : 0; ?>" />
                                    <div class="swvt-hr-toggle-knob"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="font-size:11px; color:#9aa0a6; border-top:1px solid #f0f1f2; padding-top:10px; margin-top: 10px;">
                        <?php esc_html_e( 'Green toggle indicates the employee will automatically participate in branch commission splits.', 'swvt-hr' ); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Submit button -->
        <div style="display:flex; gap:10px; margin-top:20px; background:#fff; padding:15px 20px; border-radius:12px; border:1px solid #e2e4e7;">
            <button type="submit" id="swvt-hr-rules-submit" class="swvt-hr-btn swvt-hr-btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                <?php esc_html_e( 'Save Commission Rules & Config', 'swvt-hr' ); ?>
            </button>
            <button type="button" onclick="window.location.reload();" class="swvt-hr-btn"><?php esc_html_e( 'Cancel', 'swvt-hr' ); ?></button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // 1. Math Live Calculation on input overrides
    $('#default_rate_thousand').on('input', function() {
        var valThousand = parseFloat($(this).val()) || 0;
        var valRate = valThousand / 1000;
        var valPct = (valRate * 100).toFixed(2);
        
        $('#default_rate_pct_label').text('Equivalent to ' + valPct + '% of sales');
    });

    // 2. Role distribution inputs sum validation
    $('.role-pct-input').on('input', function() {
        var sum = 0;
        $('.role-pct-input').each(function() {
            sum += parseInt($(this).val()) || 0;
        });
        
        $('#total-pct-sum-label').text(sum + '%');
        
        // Update bar widths
        $('.role-pct-input').each(function() {
            var role = $(this).attr('name').match(/\[(.*?)\]/)[1];
            var pct = parseInt($(this).val()) || 0;
            $('.role-progress-bar[data-role="' + role + '"]').css('width', pct + '%');
        });

        if (sum === 100) {
            $('#rules-sum-badge')
                .removeClass('swvt-hr-badge-error')
                .addClass('swvt-hr-badge-success')
                .html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"></path></svg> Total Balanced = 100%');
            $('#swvt-hr-rules-submit').prop('disabled', false).css('opacity', 1);
            $('#total-pct-sum-label').css('color', '#0a7c2f');
        } else {
            $('#rules-sum-badge')
                .removeClass('swvt-hr-badge-success')
                .addClass('swvt-hr-badge-error')
                .html('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Sum Imbalanced (' + sum + '%)');
            $('#swvt-hr-rules-submit').prop('disabled', true).css('opacity', 0.5);
            $('#total-pct-sum-label').css('color', '#b32d2e');
        }
    });

    // 3. Employee exceptions toggle
    $('.swvt-hr-eligibility-toggle').on('click', function() {
        var toggle = $(this);
        var input = toggle.find('input');
        var currentVal = parseInt(input.val());
        var newVal = currentVal === 1 ? 0 : 1;
        
        input.val(newVal);
        if (newVal === 1) {
            toggle.addClass('active');
        } else {
            toggle.removeClass('active');
        }
    });
});
</script>
