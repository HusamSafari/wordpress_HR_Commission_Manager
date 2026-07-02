<?php
/**
 * Reports and Analytics View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected period and branch
$selected_month  = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_branch = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;
$branches        = SWVT_HR_Branch::get_active();

$report_branches = $branches;
if ( $selected_branch ) {
    $report_branches = array_values( array_filter( $branches, function( $branch ) use ( $selected_branch ) {
        return (int) $branch->id === (int) $selected_branch;
    } ) );
}

// Fetch reports data
$rep_kpis      = SWVT_HR_Report_Service::get_rep_kpis( $selected_month, $selected_year, $selected_branch );
$sales_data    = SWVT_HR_Report_Service::get_sales_by_branch( $selected_month, $selected_year, $selected_branch );
$payroll_data  = SWVT_HR_Report_Service::get_payroll_by_branch( $selected_month, $selected_year, $selected_branch );
$absence_data  = SWVT_HR_Report_Service::get_absence_by_branch( $selected_month, $selected_year, $selected_branch );
$comm_trend    = SWVT_HR_Report_Service::get_commission_trend( $selected_month, $selected_year, $selected_branch );

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>';
$page_title = __( 'Reports & Operational Analytics', 'swvt-hr' );
$page_sub   = __( 'Review branch sales ratios, employee costs, distributed commission growth, and attendance metrics.', 'swvt-hr' );

$currency = 'EGP';

$sales_matrix_rows = $wpdb->get_results( $wpdb->prepare(
    "SELECT branch_id, period_month, total_sales, target
     FROM {$p}sales
     WHERE period_year = %d",
    $selected_year
) );

$sales_matrix = [];
foreach ( $sales_matrix_rows as $matrix_row ) {
    $branch_id = (int) $matrix_row->branch_id;
    $month_num = (int) $matrix_row->period_month;

    if ( ! isset( $sales_matrix[ $branch_id ] ) ) {
        $sales_matrix[ $branch_id ] = [];
    }

    $sales_matrix[ $branch_id ][ $month_num ] = [
        'actual' => (float) $matrix_row->total_sales,
        'target' => (float) $matrix_row->target,
    ];
}
?>

<div class="wrap swvt-hr-wrap">
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 23px; font-weight: 400; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
            <p style="font-size: 13px; color: #646970; margin: 0;"><?php echo esc_html( $page_sub ); ?></p>
        </div>
        <form method="get" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="swvt-hr-reports" />
            <select name="branch" class="swvt-hr-select" style="width: 180px;">
                <option value="0"><?php esc_html_e( 'All Branches', 'swvt-hr' ); ?></option>
                <?php foreach ( $branches as $branch ) : ?>
                    <option value="<?php echo $branch->id; ?>" <?php selected( $selected_branch, $branch->id ); ?>>
                        <?php echo esc_html( $branch->name ); ?>
                    </option>
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

    <!-- Highlight KPI Cards -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:18px;">
        <!-- Top Branch by Sales -->
        <div class="swvt-hr-kpi-card" style="display:flex; align-items:center; gap:13px;">
            <div class="swvt-hr-kpi-icon" style="background:#eaf2fb; color:#2271b1;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4H22l-6 4.5 2.3 7.1L12 16.6 5.7 21l2.3-7.1-6-4.5h7.6z"></path></svg>
            </div>
            <div style="line-height:1.35;">
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'Top Branch (Sales)', 'swvt-hr' ); ?></div>
                <div style="font-size:17px; font-weight:700;"><?php echo esc_html( $rep_kpis['top_sales']['name'] ); ?></div>
                <div style="font-size:12px; color:#9aa0a6; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $rep_kpis['top_sales']['value'] ); ?> <?php echo $currency; ?></div>
            </div>
        </div>

        <!-- Top Branch by Commission -->
        <div class="swvt-hr-kpi-card" style="display:flex; align-items:center; gap:13px;">
            <div class="swvt-hr-kpi-icon" style="background:#f3edfb; color:#7c3aed;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21v-7M12 21v-9M20 21v-5M1 14h6M9 8h6M17 16h6"></path></svg>
            </div>
            <div style="line-height:1.35;">
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'Top Branch (Commission)', 'swvt-hr' ); ?></div>
                <div style="font-size:17px; font-weight:700;"><?php echo esc_html( $rep_kpis['top_comm']['name'] ); ?></div>
                <div style="font-size:12px; color:#9aa0a6; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $rep_kpis['top_comm']['value'] ); ?> <?php echo $currency; ?></div>
            </div>
        </div>

        <!-- Top Branch by Employee Cost -->
        <div class="swvt-hr-kpi-card" style="display:flex; align-items:center; gap:13px;">
            <div class="swvt-hr-kpi-icon" style="background:#e7f6ec; color:#0a7c2f;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2ZM3 10h18"></path></svg>
            </div>
            <div style="line-height:1.35;">
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'Top Branch (Wages)', 'swvt-hr' ); ?></div>
                <div style="font-size:17px; font-weight:700;"><?php echo esc_html( $rep_kpis['top_pay']['name'] ); ?></div>
                <div style="font-size:12px; color:#9aa0a6; font-variant-numeric:tabular-nums;"><?php echo SWVT_HR::format_number( $rep_kpis['top_pay']['value'] ); ?> <?php echo $currency; ?></div>
            </div>
        </div>

        <!-- Top Branch by Absence Days -->
        <div class="swvt-hr-kpi-card" style="display:flex; align-items:center; gap:13px;">
            <div class="swvt-hr-kpi-icon" style="background:#fbe9ea; color:#b32d2e;">
                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0ZM12 9v4"></path></svg>
            </div>
            <div style="line-height:1.35;">
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'Top Branch (Absence)', 'swvt-hr' ); ?></div>
                <div style="font-size:17px; font-weight:700;"><?php echo esc_html( $rep_kpis['top_abs']['name'] ); ?></div>
                <div style="font-size:12px; color:#9aa0a6; font-variant-numeric:tabular-nums;"><?php echo number_format( $rep_kpis['top_abs']['value'], 1 ); ?> <?php esc_html_e( 'Abs. Days', 'swvt-hr' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Annual Sales Matrix (Full Width for Balance and Compactness) -->
    <div class="swvt-hr-card-padded" style="margin-bottom:18px; width: 100%; box-sizing: border-box;">
        <h4 style="font-size:15px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Branch Sales Details vs Target', 'swvt-hr' ); ?></h4>
        <div style="font-size:12px; color:#787c82; margin-bottom:16px;"><?php printf( __( 'Year %d — Actual branch sales compared with monthly target', 'swvt-hr' ), $selected_year ); ?></div>
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table class="swvt-hr-table" style="font-size:11px; min-width:820px; width: 100%;">
                <thead>
                    <tr>
                        <th style="padding:7px 10px; min-width:140px;"><?php esc_html_e( 'Branch / Row Type', 'swvt-hr' ); ?></th>
                        <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                            <th style="padding:7px 4px; text-align:center; width:50px;"><?php echo esc_html( $m ); ?></th>
                        <?php endfor; ?>
                        <th style="padding:7px 8px; text-align:center; min-width:85px;"><?php esc_html_e( 'Actual Total', 'swvt-hr' ); ?></th>
                        <th style="padding:7px 8px; text-align:center; min-width:85px;"><?php esc_html_e( 'Target Total', 'swvt-hr' ); ?></th>
                        <th style="padding:7px 8px; text-align:center; min-width:80px;"><?php esc_html_e( 'Variance', 'swvt-hr' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $report_branches ) ) : ?>
                        <tr>
                            <td colspan="16" style="text-align:center; color:#787c82; padding:20px;">
                                <?php esc_html_e( 'No active branches found for the selected filter.', 'swvt-hr' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $report_branches as $branch ) : ?>
                            <?php
                            $branch_actual_total = 0.00;
                            $branch_target_total = 0.00;
                            for ( $m = 1; $m <= 12; $m++ ) {
                                $month_actual = isset( $sales_matrix[ $branch->id ][ $m ] ) ? (float) $sales_matrix[ $branch->id ][ $m ]['actual'] : 0.00;
                                $month_target = isset( $sales_matrix[ $branch->id ][ $m ] )
                                    ? (float) $sales_matrix[ $branch->id ][ $m ]['target']
                                    : (float) $branch->sales_target;
                                $branch_actual_total += $month_actual;
                                $branch_target_total += $month_target;
                            }
                            $branch_variance = $branch_actual_total - $branch_target_total;
                            ?>
                            <tr>
                                <td style="padding:8px 10px; font-weight:700; color:#1d2327; white-space:nowrap;">
                                    <?php echo esc_html( $branch->name ); ?>
                                </td>
                                <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                                    <?php $actual_value = isset( $sales_matrix[ $branch->id ][ $m ] ) ? (float) $sales_matrix[ $branch->id ][ $m ]['actual'] : 0.00; ?>
                                    <td style="padding:8px 4px; text-align:center; color:#2271b1; font-weight:600; font-variant-numeric:tabular-nums;">
                                        <?php echo $actual_value > 0 ? esc_html( number_format( $actual_value, 0 ) ) : '—'; ?>
                                    </td>
                                <?php endfor; ?>
                                <td style="padding:8px 8px; text-align:center; color:#2271b1; font-weight:700; font-variant-numeric:tabular-nums;">
                                    <?php echo esc_html( number_format( $branch_actual_total, 0 ) ); ?>
                                </td>
                                <td style="padding:8px 8px; text-align:center; color:#7b8088; font-weight:600; font-variant-numeric:tabular-nums;">
                                    <?php echo esc_html( number_format( $branch_target_total, 0 ) ); ?>
                                </td>
                                <td style="padding:8px 8px; text-align:center; color:<?php echo $branch_variance >= 0 ? '#0a7c2f' : '#b32d2e'; ?>; font-weight:700; font-variant-numeric:tabular-nums;">
                                    <?php echo esc_html( number_format( $branch_variance, 0 ) ); ?>
                                </td>
                            </tr>
                            <tr class="zebra">
                                <td style="padding:7px 10px; font-weight:600; color:#646970; white-space:nowrap;">
                                    <?php printf( __( '%s Target', 'swvt-hr' ), esc_html( $branch->name ) ); ?>
                                </td>
                                <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                                    <?php
                                    $target_value = isset( $sales_matrix[ $branch->id ][ $m ] )
                                        ? (float) $sales_matrix[ $branch->id ][ $m ]['target']
                                        : (float) $branch->sales_target;
                                    ?>
                                    <td style="padding:7px 4px; text-align:center; color:#7b8088; font-weight:500; font-variant-numeric:tabular-nums;">
                                        <?php echo $target_value > 0 ? esc_html( number_format( $target_value, 0 ) ) : '—'; ?>
                                    </td>
                                <?php endfor; ?>
                                <td style="padding:7px 8px; text-align:center; color:#c3c4c7; font-weight:600;">—</td>
                                <td style="padding:7px 8px; text-align:center; color:#7b8088; font-weight:700; font-variant-numeric:tabular-nums;">
                                    <?php echo esc_html( number_format( $branch_target_total, 0 ) ); ?>
                                </td>
                                <td style="padding:7px 8px; text-align:center; color:#c3c4c7; font-weight:600;">—</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Split layouts (Commissions and Absence) -->
    <div class="swvt-hr-grid-2" style="margin-bottom:18px;">
        <!-- Chart 3: Commission Monthly Trend -->
        <div class="swvt-hr-card-padded">
            <h4 style="font-size:15px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Commissions Monthly Trend', 'swvt-hr' ); ?></h4>
            <div style="font-size:12px; color:#787c82; margin-bottom:20px;"><?php esc_html_e( 'Last 6 months — Total distributed company commissions', 'swvt-hr' ); ?></div>
            <div style="height: 220px; position: relative;">
                <canvas id="commissionTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart 4: Absence Days by Branch -->
        <div class="swvt-hr-card-padded">
            <h4 style="font-size:15px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Total Absence Days by Branch', 'swvt-hr' ); ?></h4>
            <div style="font-size:12px; color:#787c82; margin-bottom:20px;"><?php printf( __( '%s %d', 'swvt-hr' ), $months_english[ $selected_month ], $selected_year ); ?></div>
            <div style="height: 220px; position: relative;">
                <canvas id="absenceBranchChart"></canvas>
            </div>
        </div>
    </div>

    <!-- NEW ADVANCED REPORTING & BUSINESS INTELLIGENCE DASHBOARD -->
    <style>
        .swvt-bi-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin-top: 30px;
        }
        .swvt-bi-kpi-card {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.2s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .swvt-bi-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.06);
        }
        .swvt-bi-kpi-val {
            font-size: 18px;
            font-weight: 700;
            margin-top: 4px;
            font-variant-numeric: tabular-nums;
            color: #1d2327;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }
        .swvt-bi-kpi-label {
            font-size: 11px;
            color: #646970;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .swvt-bi-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }
        .swvt-bi-badge-success { background: #e6f4ea; color: #137333; }
        .swvt-bi-badge-info { background: #eaf2fb; color: #2271b1; }
        .swvt-bi-badge-warning { background: #fef7e0; color: #b06000; }
        .swvt-bi-badge-danger { background: #fce8e6; color: #c5221f; }
        .swvt-bi-badge-secondary { background: #f1f2f3; color: #50575e; }
        
        .swvt-bi-sticky-header th {
            position: sticky;
            top: 0;
            background: #f6f7f7;
            z-index: 2;
        }
        
        #swvt-bi-container .swvt-hr-table th,
        #swvt-bi-container .swvt-hr-table td {
            padding: 6px 8px !important;
            font-size: 11px !important;
            height: auto !important;
        }
        
        /* Responsive layout tweaks */
        @media (max-width: 900px) {
            #kpi-row-1, #kpi-row-2 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            .swvt-bi-grid-responsive {
                grid-template-columns: 1fr !important;
            }
        }
        @media (max-width: 600px) {
            #kpi-row-1, #kpi-row-2 {
                grid-template-columns: 1fr !important;
            }
            .swvt-hr-tabs {
                flex-direction: column;
                gap: 2px;
            }
            .swvt-hr-tab-btn {
                border-radius: 4px !important;
                border-bottom: 1px solid #dcdcde !important;
            }
        }
        
        /* Print BI styles */
        body.swvt-print-bi-only * {
            visibility: hidden !important;
        }
        body.swvt-print-bi-only #swvt-bi-container,
        body.swvt-print-bi-only #swvt-bi-container * {
            visibility: visible !important;
        }
        body.swvt-print-bi-only #swvt-bi-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            box-shadow: none !important;
        }
    </style>

    <div style="margin: 30px 0; border-bottom: 2px dashed #ccd0d4;"></div>

    <div id="swvt-bi-container" class="swvt-bi-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; flex-wrap:wrap; gap:15px;">
            <div>
                <h2 style="font-size:20px; font-weight:600; margin:0; color:#1d2327;">📊 Branch Sales Analytics & BI Reports</h2>
                <p style="font-size:12px; color:#646970; margin:4px 0 0 0;">Comprehensive branch business intelligence matrix, performance levels, commissions forecasting and trend reports.</p>
            </div>
            <!-- Export Options -->
            <div style="display:flex; gap:8px;">
                <button type="button" id="swvt-export-excel-btn" class="swvt-hr-btn swvt-hr-btn-success" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    Export Excel
                </button>
                <button type="button" id="swvt-export-pdf-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8M17 21H7a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2z"></path></svg>
                    Export PDF
                </button>
                <button type="button" id="swvt-print-report-btn" class="swvt-hr-btn" style="padding:6px 12px; font-size:12px; display:flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"></path></svg>
                    Print Report
                </button>
            </div>
        </div>

        <!-- Executive Dashboard KPI Cards -->
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:14px;" id="kpi-row-1">
            <!-- Populated via JS -->
        </div>
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:25px;" id="kpi-row-2">
            <!-- Populated via JS -->
        </div>

        <!-- Tab List -->
        <div class="swvt-hr-tabs" style="margin-bottom:15px; border-bottom: 1px solid #dcdcde;" id="analytics-tabs">
            <button type="button" class="swvt-hr-tab-btn is-active" data-analytics-tab="perf-rank" style="font-size:12.5px; padding:8px 16px;">📊 Performance & Rankings</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="monthly-matrix" style="font-size:12.5px; padding:8px 16px;">📅 Monthly Details</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="variance-analysis" style="font-size:12.5px; padding:8px 16px;">⚠️ Variance Analysis</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="activity-target" style="font-size:12.5px; padding:8px 16px;">⚡ Activity & Target</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="commission-forecasting" style="font-size:12.5px; padding:8px 16px;">💼 Commissions (HR)</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="payroll-report" style="font-size:12.5px; padding:8px 16px;">💵 Payroll Report</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="attendance-report" style="font-size:12.5px; padding:8px 16px;">📅 Attendance Report</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="employee-cost-report" style="font-size:12.5px; padding:8px 16px;">👤 Employee Cost Burden</button>
            <button type="button" class="swvt-hr-tab-btn" data-analytics-tab="visual-charts" style="font-size:12.5px; padding:8px 16px;">📈 Analytical Charts</button>
        </div>

        <!-- Tab Contents -->
        <!-- Tab 1: Performance & Rankings -->
        <div class="swvt-hr-tab-content is-active" id="analytics-tab-perf-rank" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div class="swvt-bi-grid-responsive" style="display:grid; grid-template-columns: 3fr 2fr; gap:20px;">
                <div>
                    <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <span>Branch Performance Summary</span>
                        <input type="text" id="perf-search" class="swvt-hr-input" placeholder="Search branches..." style="width:160px; padding:3px 8px; font-size:11px;" />
                    </h4>
                    <div style="overflow-x:auto;">
                        <table class="swvt-hr-table swvt-bi-sticky-header" style="font-size:11.5px;" id="perf-summary-table">
                            <thead>
                                <tr>
                                    <th style="cursor:pointer; padding:9px 12px;" data-sort="name">Branch Name ⇅</th>
                                    <th style="cursor:pointer; text-align:right; padding:9px 12px;" data-sort="actual">Actual Sales ⇅</th>
                                    <th style="cursor:pointer; text-align:right; padding:9px 12px;" data-sort="target">Target Sales ⇅</th>
                                    <th style="cursor:pointer; text-align:right; padding:9px 12px;" data-sort="variance">Variance ⇅</th>
                                    <th style="cursor:pointer; text-align:right; padding:9px 12px;" data-sort="achievement">Achievement % ⇅</th>
                                    <th style="text-align:center; padding:9px 12px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600; display:flex; justify-content:space-between; align-items:center;">
                        <span>Branch Rankings</span>
                        <select id="ranking-criteria" class="swvt-hr-select" style="font-size:11px; padding:2px 20px 2px 8px; width:160px;">
                            <option value="actual">Highest Actual Sales</option>
                            <option value="achievement">Highest Achievement %</option>
                            <option value="variance">Lowest Variance</option>
                        </select>
                    </h4>
                    <div style="overflow-x:auto;">
                        <table class="swvt-hr-table" style="font-size:11.5px;" id="ranking-table">
                            <thead>
                                <tr>
                                    <th style="width:50px; text-align:center;">Rank</th>
                                    <th>Branch</th>
                                    <th style="text-align:right;">Actual Sales</th>
                                    <th style="text-align:right;">Achievement %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Monthly Sales breakdown -->
        <div class="swvt-hr-tab-content" id="analytics-tab-monthly-matrix" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <span>Monthly Sales Report by Branch</span>
                <select id="monthly-branch-select" class="swvt-hr-select" style="width:200px; font-size:11px; padding:2px 20px 2px 8px;">
                    <!-- Filled dynamically -->
                </select>
            </h4>
            
            <div class="swvt-bi-grid-responsive" style="display:grid; grid-template-columns: 3fr 1fr; gap:20px;">
                <div style="overflow-x:auto;">
                    <table class="swvt-hr-table" style="font-size:11.5px;" id="monthly-sales-table">
                        <thead>
                            <tr>
                                <th style="padding:9px 12px;">Month</th>
                                <th style="text-align:right; padding:9px 12px;">Actual Sales</th>
                                <th style="text-align:right; padding:9px 12px;">Target</th>
                                <th style="text-align:right; padding:9px 12px;">Variance</th>
                                <th style="text-align:right; padding:9px 12px;">Achievement %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Highlights Panel -->
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div class="swvt-hr-card-padded" style="border-right:4px solid #137333; background:#e6f4ea; padding:12px;">
                        <div style="font-size:9.5px; color:#137333; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">🏆 Best Month</div>
                        <div style="font-size:15px; font-weight:700; margin:4px 0 2px; color:#137333;" id="best-month-name">-</div>
                        <div style="font-size:11px; color:#274d32; font-variant-numeric:tabular-nums;" id="best-month-value">-</div>
                    </div>
                    <div class="swvt-hr-card-padded" style="border-right:4px solid #c5221f; background:#fce8e6; padding:12px;">
                        <div style="font-size:9.5px; color:#c5221f; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">⚠️ Worst Month</div>
                        <div style="font-size:15px; font-weight:700; margin:4px 0 2px; color:#c5221f;" id="worst-month-name">-</div>
                        <div style="font-size:11px; color:#5c221f; font-variant-numeric:tabular-nums;" id="worst-month-value">-</div>
                    </div>
                    <div class="swvt-hr-card-padded" style="border-right:4px solid #185a9d; background:#eaf2fb; padding:12px;">
                        <div style="font-size:9.5px; color:#185a9d; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">📈 Highest Growth Month</div>
                        <div style="font-size:15px; font-weight:700; margin:4px 0 2px; color:#185a9d;" id="growth-month-name">-</div>
                        <div style="font-size:11px; color:#185a9d; font-variant-numeric:tabular-nums;" id="growth-month-value">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Variance Report -->
        <div class="swvt-hr-tab-content" id="analytics-tab-variance-analysis" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600;">Variance Report (Ordered by Largest Negative Variance)</h4>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11.5px;" id="variance-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Branch</th>
                            <th style="text-align:right; padding:9px 12px;">Target Sales</th>
                            <th style="text-align:right; padding:9px 12px;">Actual Sales</th>
                            <th style="text-align:right; padding:9px 12px;">Variance</th>
                            <th style="text-align:right; padding:9px 12px;">Variance %</th>
                            <th style="text-align:center; padding:9px 12px;">Risk Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 4: Activity & Target -->
        <div class="swvt-hr-tab-content" id="analytics-tab-activity-target" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600;">Branch Activity & Progress Bars</h4>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11.5px;" id="activity-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Branch Name</th>
                            <th style="padding:9px 12px;">Activity Status</th>
                            <th style="text-align:center; padding:9px 12px;">Active Months (Sales > 0)</th>
                            <th style="width: 320px; padding:9px 12px;">Target Achievement Progress</th>
                            <th style="text-align:right; padding:9px 12px;">Achievement %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 5: Commission Report -->
        <div class="swvt-hr-tab-content" id="analytics-tab-commission-forecasting" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h4 style="margin:0; font-size:13.5px; font-weight:600;">Commission Forecast & Eligibility Report (HR)</h4>
                    <p style="font-size:11px; color:#787c82; margin:2px 0 0 0;">Note: Default threshold is 80% achievement. Branches below 80% do not qualify for commission payouts.</p>
                </div>
                <!-- Configurator slider -->
                <div style="display:flex; align-items:center; gap:8px; background:#f6f7f7; padding:6px 12px; border-radius:6px; border:1px solid #dcdcde;">
                    <label for="comm-threshold" style="font-size:11px; font-weight:700; color:#50575e;">Threshold:</label>
                    <input type="range" id="comm-threshold" min="0" max="150" value="80" style="width:100px; height: 4px;" />
                    <span id="comm-threshold-val" style="font-size:11px; font-weight:700; color:#2271b1; min-width:30px; display:inline-block; text-align:right;">80%</span>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11.5px;" id="commission-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Branch</th>
                            <th style="text-align:right; padding:9px 12px;">Achievement %</th>
                            <th style="text-align:center; padding:9px 12px;">Commission Eligible</th>
                            <th style="text-align:right; padding:9px 12px;">Commission Rate</th>
                            <th style="text-align:right; padding:9px 12px;">Estimated Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 5b: Payroll Report -->
        <div class="swvt-hr-tab-content" id="analytics-tab-payroll-report" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600;">Monthly Payroll Ledger Report</h4>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11px;" id="payroll-report-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Month</th>
                            <th>Employee</th>
                            <th>Branch</th>
                            <th style="text-align:right;">Basic Salary</th>
                            <th style="text-align:right;">Commissions</th>
                            <th style="text-align:right;">Absence Deduction</th>
                            <th style="text-align:right;">Bonus / Reimbursement</th>
                            <th style="text-align:right;">Other Deductions</th>
                            <th style="text-align:right;">Net Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 5c: Attendance Report -->
        <div class="swvt-hr-tab-content" id="analytics-tab-attendance-report" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600;">Monthly Attendance & Penalty Deductions Report</h4>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11px;" id="attendance-report-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Month</th>
                            <th>Employee</th>
                            <th>Branch</th>
                            <th style="text-align:center;">Absence Days</th>
                            <th style="text-align:center;">Late Hours</th>
                            <th style="text-align:right;">Absence Deduction</th>
                            <th>Reason / Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 5d: Employee Cost Report -->
        <div class="swvt-hr-tab-content" id="analytics-tab-employee-cost-report" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 12px; font-size:13.5px; font-weight:600;">Annual Employee Cost Burden Analysis</h4>
            <div style="overflow-x:auto;">
                <table class="swvt-hr-table" style="font-size:11px;" id="employee-cost-report-table">
                    <thead>
                        <tr>
                            <th style="padding:9px 12px;">Employee Name</th>
                            <th>Code</th>
                            <th>Branch</th>
                            <th style="text-align:right;">Basic Salary Sum</th>
                            <th style="text-align:right;">Total Commissions</th>
                            <th style="text-align:right;">Total Deductions</th>
                            <th style="text-align:right;">Annual Cost Burden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab 6: Visual Charts -->
        <div class="swvt-hr-tab-content" id="analytics-tab-visual-charts" style="background:#fff; border:1px solid #dcdcde; border-top:none; padding:18px; border-radius:0 0 8px 8px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:none;">
            <h4 style="margin:0 0 15px; font-size:13.5px; font-weight:600;">BI Visual Chart Dashboards</h4>
            
            <div class="swvt-bi-grid-responsive" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="swvt-hr-card-padded" style="border:1px solid #dcdcde;">
                    <h5 style="margin:0 0 10px; font-size:12px; font-weight:600; color:#3c434a;">Monthly Sales Trend</h5>
                    <div style="height:220px; position:relative;">
                        <canvas id="monthlySalesTrendChart"></canvas>
                    </div>
                </div>
                <div class="swvt-hr-card-padded" style="border:1px solid #dcdcde;">
                    <h5 style="margin:0 0 10px; font-size:12px; font-weight:600; color:#3c434a;">Actual vs Target by Branch</h5>
                    <div style="height:220px; position:relative;">
                        <canvas id="actualVsTargetChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="swvt-bi-grid-responsive" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px;">
                <div class="swvt-hr-card-padded" style="border:1px solid #dcdcde;">
                    <h5 style="margin:0 0 10px; font-size:12px; font-weight:600; color:#3c434a;">Achievement % by Branch</h5>
                    <div style="height:200px; position:relative;">
                        <canvas id="achievementBranchChart"></canvas>
                    </div>
                </div>
                <div class="swvt-hr-card-padded" style="border:1px solid #dcdcde;">
                    <h5 style="margin:0 0 10px; font-size:12px; font-weight:600; color:#3c434a;">Branch Contribution Share</h5>
                    <div style="height:200px; position:relative;">
                        <canvas id="branchContributionChart"></canvas>
                    </div>
                </div>
                <div class="swvt-hr-card-padded" style="border:1px solid #dcdcde;">
                    <h5 style="margin:0 0 10px; font-size:12px; font-weight:600; color:#3c434a;">Top 5 Branches</h5>
                    <div style="height:200px; position:relative;">
                        <canvas id="topBranchesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Cost by Branch Chart (Placed last as requested) -->
    <div class="swvt-hr-card-padded" style="margin-top: 25px; margin-bottom: 20px;">
        <h4 style="font-size:15px; font-weight:600; margin-bottom:4px;"><?php esc_html_e( 'Payroll Cost by Branch', 'swvt-hr' ); ?></h4>
        <div style="font-size:12px; color:#787c82; margin-bottom:20px;"><?php printf( __( '%s %d — in EGP', 'swvt-hr' ), $months_english[ $selected_month ], $selected_year ); ?></div>
        <div style="height: 250px; position: relative;">
            <canvas id="payrollBranchChart"></canvas>
        </div>
    </div>
</div>


<?php
// Prepare data array for Javascript
$pay_labels = [];
$pay_values = [];
foreach ( $payroll_data as $pd ) {
    $pay_labels[] = esc_js( str_replace( 'Branch ', '', $pd->branch_name ) );
    $pay_values[] = (float) $pd->total_payroll;
}

$trend_labels = [];
$trend_values = [];
foreach ( $comm_trend as $ct ) {
    $trend_labels[] = esc_js( $ct['label'] );
    $trend_values[] = (float) $ct['value'];
}

$abs_labels = [];
$abs_values = [];
foreach ( $absence_data as $ad ) {
    $abs_labels[] = esc_js( str_replace( 'Branch ', '', $ad->branch_name ) );
    $abs_values[] = (float) $ad->total_absence_days;
}

// Advanced BI serialized data
$branches_list = [];
foreach ( $branches as $b ) {
    $branches_list[] = [
        'id'              => (int) $b->id,
        'name'            => $b->name,
        'sales_target'    => (float) $b->sales_target,
        'commission_rate' => (float) $b->commission_rate,
        'status'          => $b->status
    ];
}

$sales_matrix_data = [];
foreach ( $branches as $branch ) { // use all active branches for full annual calculations
    $sales_matrix_data[ $branch->id ] = [];
    for ( $m = 1; $m <= 12; $m++ ) {
        $actual_value = isset( $sales_matrix[ $branch->id ][ $m ] ) ? (float) $sales_matrix[ $branch->id ][ $m ]['actual'] : 0.00;
        $target_value = isset( $sales_matrix[ $branch->id ][ $m ] )
            ? (float) $sales_matrix[ $branch->id ][ $m ]['target']
            : (float) $branch->sales_target;
        $sales_matrix_data[ $branch->id ][ $m ] = [
            'actual' => $actual_value,
            'target' => $target_value
        ];
    }
}

// Fetch operational reports datasets
$payroll_all = $wpdb->get_results( $wpdb->prepare(
    "SELECT p.*, e.full_name, e.code as emp_code, b.name as branch_name 
     FROM {$p}payroll p
     JOIN {$p}employees e ON p.employee_id = e.id
     JOIN {$p}branches b ON p.branch_id = b.id
     WHERE p.period_year = %d",
    $selected_year
) );

$attendance_all = $wpdb->get_results( $wpdb->prepare(
    "SELECT a.*, e.full_name, e.code as emp_code, b.name as branch_name 
     FROM {$p}attendance a
     JOIN {$p}employees e ON a.employee_id = e.id
     JOIN {$p}branches b ON a.branch_id = b.id
     WHERE a.period_year = %d",
    $selected_year
) );

$payroll_list = [];
foreach ( $payroll_all as $pr ) {
    $payroll_list[] = [
        'id'           => (int) $pr->id,
        'branch_id'    => (int) $pr->branch_id,
        'branch_name'  => $pr->branch_name,
        'emp_name'     => $pr->full_name,
        'emp_code'     => $pr->emp_code,
        'month'        => (int) $pr->period_month,
        'basic'        => (float) $pr->basic_salary,
        'commission'   => (float) $pr->commission,
        'absence'      => (float) $pr->absence_deduction,
        'bonus'        => (float) $pr->bonus,
        'other'        => (float) $pr->other_deduction,
        'net'          => (float) $pr->net_salary,
        'status'       => $pr->status
    ];
}

$attendance_list = [];
foreach ( $attendance_all as $ar ) {
    $attendance_list[] = [
        'id'           => (int) $ar->id,
        'branch_id'    => (int) $ar->branch_id,
        'branch_name'  => $ar->branch_name,
        'emp_name'     => $ar->full_name,
        'emp_code'     => $ar->emp_code,
        'month'        => (int) $ar->period_month,
        'absence_days' => (float) $ar->absence_days,
        'late_hours'   => (float) $ar->late_hours,
        'deduction'    => (float) $ar->deduction,
        'reason'       => $ar->reason
    ];
}
?>

<script>
jQuery(document).ready(function($) {
    // 1. Payroll Branch Chart
    new Chart(document.getElementById('payrollBranchChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode( $pay_labels ); ?>,
            datasets: [{
                label: '<?php esc_html_e( 'Net Wages Cost', 'swvt-hr' ); ?>',
                data: <?php echo json_encode( $pay_values ); ?>,
                backgroundColor: '#12a150',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'IBM Plex Sans Arabic' } } }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 2. Commission Trend Chart
    new Chart(document.getElementById('commissionTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode( $trend_labels ); ?>,
            datasets: [{
                label: '<?php esc_html_e( 'Distributed Commissions', 'swvt-hr' ); ?>',
                data: <?php echo json_encode( $trend_values ); ?>,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'IBM Plex Sans Arabic' } } }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 3. Absence Branch Chart
    new Chart(document.getElementById('absenceBranchChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode( $abs_labels ); ?>,
            datasets: [{
                label: '<?php esc_html_e( 'Absence Days', 'swvt-hr' ); ?>',
                data: <?php echo json_encode( $abs_values ); ?>,
                backgroundColor: '#e0a800',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'IBM Plex Sans Arabic' } } }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    // ==========================================
    // NEW BUSINESS INTELLIGENCE DASHBOARD LOGIC
    // ==========================================
    var swvtBranches = <?php echo json_encode( $branches_list ); ?>;
    var swvtSalesMatrix = <?php echo json_encode( $sales_matrix_data ); ?>;
    var swvtSelectedYear = <?php echo $selected_year; ?>;
    var swvtSelectedMonth = <?php echo $selected_month; ?>;
    var swvtSelectedBranch = <?php echo $selected_branch; ?>;
    var swvtPayrollList = <?php echo json_encode( $payroll_list ); ?>;
    var swvtAttendanceList = <?php echo json_encode( $attendance_list ); ?>;

    function setupBIPanel() {
        var processedBranches = swvtBranches.map(function(branch) {
            var branchMatrix = swvtSalesMatrix[branch.id] || {};
            var actualSum = 0;
            var targetSum = 0;
            var activeMonthsCount = 0;
            var monthlyData = [];

            for (var m = 1; m <= 12; m++) {
                var mData = branchMatrix[m] || { actual: 0, target: branch.sales_target };
                actualSum += mData.actual;
                targetSum += mData.target;
                if (mData.actual > 0) {
                    activeMonthsCount++;
                }
                monthlyData.push({
                    month: m,
                    actual: mData.actual,
                    target: mData.target,
                    variance: mData.actual - mData.target,
                    achievement: mData.target > 0 ? (mData.actual / mData.target) * 100 : 0
                });
            }

            var achievement = targetSum > 0 ? (actualSum / targetSum) * 100 : 0;
            var variance = actualSum - targetSum;
            var varPercent = targetSum > 0 ? (variance / targetSum) * 100 : 0;

            var status = 'Critical';
            if (achievement >= 110) status = 'Excellent';
            else if (achievement >= 95) status = 'Good';
            else if (achievement >= 80) status = 'Average';
            else if (achievement >= 50) status = 'Poor';

            var activity = 'No Sales Recorded';
            if (actualSum > 0) {
                activity = activeMonthsCount > 4 ? 'Active' : 'Low Activity';
            }

            var risk = 'Healthy';
            if (varPercent <= -20) risk = 'Critical';
            else if (varPercent <= -5) risk = 'Needs Attention';

            return {
                id: branch.id,
                name: branch.name,
                actual: actualSum,
                target: targetSum,
                variance: variance,
                achievement: achievement,
                status: status,
                activeMonths: activeMonthsCount,
                activity: activity,
                varPercent: varPercent,
                risk: risk,
                rate: branch.commission_rate,
                monthly: monthlyData
            };
        });

        // Filter display branches based on selected branch
        var displayBranches = processedBranches;
        if (swvtSelectedBranch) {
            displayBranches = processedBranches.filter(function(b) {
                return b.id === swvtSelectedBranch;
            });
        }

        // Executive dashboard metrics
        var totalActual = displayBranches.reduce(function(sum, b) { return sum + b.actual; }, 0);
        var totalTarget = displayBranches.reduce(function(sum, b) { return sum + b.target; }, 0);
        var overallAch = totalTarget > 0 ? (totalActual / totalTarget) * 100 : 0;
        var totalVar = totalActual - totalTarget;

        var bestBranch = { name: 'N/A', ach: 0 };
        var worstBranch = { name: 'N/A', ach: Infinity };
        var activeCount = 0;
        var inactiveCount = 0;

        displayBranches.forEach(function(b) {
            if (b.actual > 0) {
                if (b.achievement > bestBranch.ach) {
                    bestBranch = { name: b.name, ach: b.achievement };
                }
                if (b.achievement < worstBranch.ach) {
                    worstBranch = { name: b.name, ach: b.achievement };
                }
            }
            if (b.activity === 'Active') {
                activeCount++;
            } else {
                inactiveCount++;
            }
        });

        if (worstBranch.ach === Infinity) worstBranch = { name: 'N/A', ach: 0 };

        function createKPICard(label, value, bg, color) {
            return '<div class="swvt-bi-kpi-card" style="border-right: 4px solid ' + color + '; background: linear-gradient(180deg, #fff, ' + bg + '1A);">' +
                   '<div class="swvt-bi-kpi-label">' + label + '</div>' +
                   '<div class="swvt-bi-kpi-val" style="color: ' + color + ';">' + value + '</div>' +
                   '</div>';
        }

        $('#kpi-row-1').html(
            createKPICard('Total Actual Sales', totalActual.toLocaleString() + ' EGP', '#eaf2fb', '#2271b1') +
            createKPICard('Total Target Sales', totalTarget.toLocaleString() + ' EGP', '#f1f2f3', '#50575e') +
            createKPICard('Overall Achievement %', overallAch.toFixed(1) + '%', overallAch >= 95 ? '#e6f4ea' : '#fef7e0', overallAch >= 95 ? '#137333' : '#b06000') +
            createKPICard('Total Variance', (totalVar >= 0 ? '+' : '') + totalVar.toLocaleString() + ' EGP', totalVar >= 0 ? '#e6f4ea' : '#fce8e6', totalVar >= 0 ? '#137333' : '#c5221f')
        );

        $('#kpi-row-2').html(
            createKPICard('Best Performing Branch', bestBranch.name + ' (' + bestBranch.ach.toFixed(1) + '%)', '#e6f4ea', '#137333') +
            createKPICard('Worst Performing Branch', worstBranch.name + ' (' + worstBranch.ach.toFixed(1) + '%)', '#fce8e6', '#c5221f') +
            createKPICard('Number of Active Branches', activeCount, '#eaf2fb', '#2271b1') +
            createKPICard('Number of Inactive Branches', inactiveCount, '#fef7e0', '#b06000')
        );

        // Sorting, Filtering & Search configurations for Performance Summary Table
        var sortCol = 'name';
        var sortAsc = true;
        var searchTerm = '';
        var itemsPerPage = 5;
        var currentPage = 1;

        function renderPerfSummaryTable() {
            var filtered = displayBranches.filter(function(b) {
                return b.name.toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1;
            });

            filtered.sort(function(x, y) {
                var valX = x[sortCol];
                var valY = y[sortCol];
                if (typeof valX === 'string') {
                    return sortAsc ? valX.localeCompare(valY) : valY.localeCompare(valX);
                }
                return sortAsc ? valX - valY : valY - valX;
            });

            var totalPages = Math.ceil(filtered.length / itemsPerPage);
            if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
            var startIndex = (currentPage - 1) * itemsPerPage;
            var paginated = filtered.slice(startIndex, startIndex + itemsPerPage);

            var tbodyHtml = '';
            if (paginated.length === 0) {
                tbodyHtml = '<tr><td colspan="6" style="text-align:center; color:#787c82; padding:15px;">No matching branches found.</td></tr>';
            } else {
                paginated.forEach(function(b) {
                    var badgeClass = 'swvt-bi-badge-danger';
                    if (b.status === 'Excellent') badgeClass = 'swvt-bi-badge-success';
                    else if (b.status === 'Good') badgeClass = 'swvt-bi-badge-info';
                    else if (b.status === 'Average') badgeClass = 'swvt-bi-badge-warning';

                    tbodyHtml += '<tr>' +
                                 '<td style="padding:9px 12px;"><strong>' + b.name + '</strong></td>' +
                                 '<td style="text-align:right; padding:9px 12px;">' + b.actual.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; padding:9px 12px;">' + b.target.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; padding:9px 12px; color:' + (b.variance >= 0 ? '#137333' : '#c5221f') + '; font-weight:700;">' + (b.variance >= 0 ? '+' : '') + b.variance.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; padding:9px 12px; font-weight:600;">' + b.achievement.toFixed(1) + '%</td>' +
                                 '<td style="text-align:center; padding:9px 12px;"><span class="swvt-bi-badge ' + badgeClass + '">' + b.status + '</span></td>' +
                                 '</tr>';
                });
            }

            var paginationHtml = '';
            if (totalPages > 1) {
                paginationHtml = '<div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px; font-size:11px; padding: 4px 10px;">' +
                                 '<button type="button" class="swvt-hr-btn" id="perf-prev-btn" ' + (currentPage === 1 ? 'disabled' : '') + ' style="padding:3px 8px; font-size:10px;">&larr; Prev</button>' +
                                 '<span>Page ' + currentPage + ' of ' + totalPages + '</span>' +
                                 '<button type="button" class="swvt-hr-btn" id="perf-next-btn" ' + (currentPage === totalPages ? 'disabled' : '') + ' style="padding:3px 8px; font-size:10px;">Next &rarr;</button>' +
                                 '</div>';
            }

            $('#perf-summary-table tbody').html(tbodyHtml);
            $('#perf-summary-table').parent().find('.pagination-wrapper').remove();
            if (totalPages > 1) {
                $('#perf-summary-table').parent().append('<div class="pagination-wrapper">' + paginationHtml + '</div>');
                $('#perf-prev-btn').on('click', function() { currentPage--; renderPerfSummaryTable(); });
                $('#perf-next-btn').on('click', function() { currentPage++; renderPerfSummaryTable(); });
            }
        }

        // Sort click binds
        $('#perf-summary-table th[data-sort]').on('click', function() {
            var clickedCol = $(this).data('sort');
            if (sortCol === clickedCol) {
                sortAsc = !sortAsc;
            } else {
                sortCol = clickedCol;
                sortAsc = true;
            }
            renderPerfSummaryTable();
        });

        $('#perf-search').on('input', function() {
            searchTerm = $(this).val();
            currentPage = 1;
            renderPerfSummaryTable();
        });

        renderPerfSummaryTable();

        // Branch Rankings table
        function renderRankingTable() {
            var criteria = $('#ranking-criteria').val();
            var ranked = [].concat(processedBranches);

            ranked.sort(function(x, y) {
                if (criteria === 'variance') {
                    return x.variance - y.variance; // lowest variance
                }
                return y[criteria] - x[criteria];
            });

            var tbodyHtml = '';
            ranked.forEach(function(b, idx) {
                tbodyHtml += '<tr>' +
                             '<td style="text-align:center; padding:9px 12px;"><strong>#' + (idx + 1) + '</strong></td>' +
                             '<td style="padding:9px 12px;">' + b.name + '</td>' +
                             '<td style="text-align:right; padding:9px 12px;">' + b.actual.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:600; color:#2271b1;">' + b.achievement.toFixed(1) + '%</td>' +
                             '</tr>';
            });
            $('#ranking-table tbody').html(tbodyHtml);
        }
        $('#ranking-criteria').on('change', renderRankingTable);
        renderRankingTable();

        // Monthly breakdown select population
        var branchSelect = $('#monthly-branch-select');
        branchSelect.html('');
        processedBranches.forEach(function(b) {
            branchSelect.append('<option value="' + b.id + '">' + b.name + '</option>');
        });

        function renderMonthlyReport() {
            var brId = parseInt(branchSelect.val()) || processedBranches[0].id;
            var b = processedBranches.find(function(item) { return item.id === brId; });
            if (!b) return;

            var tbodyHtml = '';
            var monthsNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            
            var bestM = { num: 1, val: -1 };
            var worstM = { num: 1, val: Infinity };
            var maxGrowth = { num: 1, val: -Infinity };

            b.monthly.forEach(function(m, idx) {
                tbodyHtml += '<tr>' +
                             '<td style="padding:9px 12px;"><strong>' + monthsNames[idx] + ' (' + (idx + 1) + ')</strong></td>' +
                             '<td style="text-align:right; padding:9px 12px; color:#2271b1; font-weight:600;">' + (m.actual > 0 ? m.actual.toLocaleString() + ' EGP' : '—') + '</td>' +
                             '<td style="text-align:right; padding:9px 12px; color:#787c82;">' + m.target.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px; color:' + (m.variance >= 0 ? '#137333' : '#c5221f') + '; font-weight:700;">' + (m.variance >= 0 ? '+' : '') + m.variance.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:600;">' + m.achievement.toFixed(1) + '%</td>' +
                             '</tr>';

                if (m.actual > bestM.val) {
                    bestM = { num: m.month, val: m.actual };
                }
                if (m.actual > 0 && m.actual < worstM.val) {
                    worstM = { num: m.month, val: m.actual };
                }

                if (idx > 0) {
                    var prev = b.monthly[idx - 1].actual;
                    if (prev > 0) {
                        var growth = ((m.actual - prev) / prev) * 100;
                        if (growth > maxGrowth.val) {
                            maxGrowth = { num: m.month, val: growth };
                        }
                    }
                }
            });

            $('#monthly-sales-table tbody').html(tbodyHtml);

            // Populate side cards
            $('#best-month-name').text(bestM.val > -1 ? monthsNames[bestM.num - 1] : 'N/A');
            $('#best-month-value').text(bestM.val > -1 ? bestM.val.toLocaleString() + ' EGP' : '—');

            $('#worst-month-name').text(worstM.val < Infinity ? monthsNames[worstM.num - 1] : 'N/A');
            $('#worst-month-value').text(worstM.val < Infinity ? worstM.val.toLocaleString() + ' EGP' : '—');

            $('#growth-month-name').text(maxGrowth.val > -Infinity ? monthsNames[maxGrowth.num - 1] : 'N/A');
            $('#growth-month-value').text(maxGrowth.val > -Infinity ? '+' + maxGrowth.val.toFixed(1) + '%' : '—');
        }
        branchSelect.on('change', renderMonthlyReport);
        renderMonthlyReport();

        // Variance table report
        function renderVarianceReport() {
            var sorted = [].concat(processedBranches);
            sorted.sort(function(x, y) {
                return x.variance - y.variance;
            });

            var tbodyHtml = '';
            sorted.forEach(function(b) {
                var badge = '<span class="swvt-bi-badge swvt-bi-badge-success">Healthy</span>';
                if (b.risk === 'Critical') {
                    badge = '<span class="swvt-bi-badge swvt-bi-badge-danger">Critical</span>';
                } else if (b.risk === 'Needs Attention') {
                    badge = '<span class="swvt-bi-badge swvt-bi-badge-warning">Needs Attention</span>';
                }

                tbodyHtml += '<tr>' +
                             '<td style="padding:9px 12px;"><strong>' + b.name + '</strong></td>' +
                             '<td style="text-align:right; padding:9px 12px;">' + b.target.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px;">' + b.actual.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:700; color:' + (b.variance >= 0 ? '#137333' : '#c5221f') + ';">' + (b.variance >= 0 ? '+' : '') + b.variance.toLocaleString() + ' EGP</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:600;">' + (b.varPercent >= 0 ? '+' : '') + b.varPercent.toFixed(1) + '%</td>' +
                             '<td style="text-align:center; padding:9px 12px;">' + badge + '</td>' +
                             '</tr>';
            });
            $('#variance-table tbody').html(tbodyHtml);
        }
        renderVarianceReport();

        // Activity and Target progress report
        function renderActivityReport() {
            var tbodyHtml = '';
            displayBranches.forEach(function(b) {
                var actBadge = '<span class="swvt-bi-badge swvt-bi-badge-success">Active</span>';
                if (b.activity === 'No Sales Recorded') actBadge = '<span class="swvt-bi-badge swvt-bi-badge-danger">No Sales</span>';
                else if (b.activity === 'Low Activity') actBadge = '<span class="swvt-bi-badge swvt-bi-badge-warning">Low Activity</span>';

                var progressColor = '#c5221f';
                if (b.achievement >= 100) progressColor = '#137333';
                else if (b.achievement >= 95) progressColor = '#2271b1';
                else if (b.achievement >= 80) progressColor = '#b06000';

                var boundedAch = Math.min(100, Math.max(0, b.achievement));
                var progressBar = '<div style="background:#f1f2f3; border-radius:4px; height:12px; overflow:hidden; display:flex; width:100%;">' +
                                  '<div style="width:' + boundedAch + '%; background:' + progressColor + '; height:100%;"></div>' +
                                  '</div>';

                tbodyHtml += '<tr>' +
                             '<td style="padding:9px 12px;"><strong>' + b.name + '</strong></td>' +
                             '<td style="padding:9px 12px;">' + actBadge + '</td>' +
                             '<td style="text-align:center; padding:9px 12px;">' + b.activeMonths + ' / 12</td>' +
                             '<td style="vertical-align:middle; padding:9px 12px;">' + progressBar + '</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:700; color:' + progressColor + ';">' + b.achievement.toFixed(1) + '%</td>' +
                             '</tr>';
            });
            $('#activity-table tbody').html(tbodyHtml);
        }
        renderActivityReport();

        // Commission forecast report
        function renderCommissionReport() {
            var threshold = parseFloat($('#comm-threshold').val()) || 80;
            var tbodyHtml = '';

            displayBranches.forEach(function(b) {
                var isEligible = b.achievement >= threshold;
                var estimatedComm = isEligible ? b.actual * b.rate : 0;

                tbodyHtml += '<tr>' +
                             '<td style="padding:9px 12px;"><strong>' + b.name + '</strong></td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:600;">' + b.achievement.toFixed(1) + '%</td>' +
                             '<td style="text-align:center; padding:9px 12px;">' + (isEligible ? '🟢 <span style="color:#137333; font-weight:700;">Eligible</span>' : '🔴 <span style="color:#c5221f;">Ineligible</span>') + '</td>' +
                             '<td style="text-align:right; padding:9px 12px;">' + (b.rate * 100).toFixed(2) + '%</td>' +
                             '<td style="text-align:right; padding:9px 12px; font-weight:700; color:' + (isEligible ? '#2271b1' : '#787c82') + ';">' + estimatedComm.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' EGP</td>' +
                             '</tr>';
            });
            $('#commission-table tbody').html(tbodyHtml);
        }
        $('#comm-threshold').on('input change', function() {
            var val = $(this).val();
            $('#comm-threshold-val').text(val + '%');
            renderCommissionReport();
        });
        renderCommissionReport();

        // 1. Render Payroll Report Table (Step 9)
        function renderPayrollReportTable() {
            var tbodyHtml = '';
            var monthsNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var filteredPayroll = swvtPayrollList;
            if (swvtSelectedBranch) {
                filteredPayroll = swvtPayrollList.filter(function(p) {
                    return p.branch_id === swvtSelectedBranch;
                });
            }

            if (filteredPayroll.length === 0) {
                tbodyHtml = '<tr><td colspan="9" style="text-align:center; color:#787c82; padding:15px;">No payroll entries found for this period.</td></tr>';
            } else {
                filteredPayroll.forEach(function(p) {
                    tbodyHtml += '<tr>' +
                                 '<td style="padding:8px 12px;"><strong>' + monthsNames[p.month - 1] + ' ' + swvtSelectedYear + '</strong></td>' +
                                 '<td><strong>' + p.emp_name + '</strong> (' + p.emp_code + ')</td>' +
                                 '<td>' + p.branch_name + '</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums;">' + p.basic.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#2271b1;">+' + p.commission.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-' + p.absence.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#137333;">+' + p.bonus.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#646970;">-' + p.other.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;">' + p.net.toLocaleString() + ' EGP</td>' +
                                 '</tr>';
                });
            }
            $('#payroll-report-table tbody').html(tbodyHtml);
        }
        renderPayrollReportTable();

        // 2. Render Attendance Report Table (Step 9)
        function renderAttendanceReportTable() {
            var tbodyHtml = '';
            var monthsNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            var filteredAttendance = swvtAttendanceList;
            if (swvtSelectedBranch) {
                filteredAttendance = swvtAttendanceList.filter(function(a) {
                    return a.branch_id === swvtSelectedBranch;
                });
            }

            if (filteredAttendance.length === 0) {
                tbodyHtml = '<tr><td colspan="7" style="text-align:center; color:#787c82; padding:15px;">No attendance logs found.</td></tr>';
            } else {
                filteredAttendance.forEach(function(a) {
                    tbodyHtml += '<tr>' +
                                 '<td style="padding:8px 12px;"><strong>' + monthsNames[a.month - 1] + ' ' + swvtSelectedYear + '</strong></td>' +
                                 '<td><strong>' + a.emp_name + '</strong> (' + a.emp_code + ')</td>' +
                                 '<td>' + a.branch_name + '</td>' +
                                 '<td style="text-align:center;">' + a.absence_days + '</td>' +
                                 '<td style="text-align:center; color:#787c82;">' + a.late_hours + '</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f; font-weight:600;">-' + a.deduction.toLocaleString() + ' EGP</td>' +
                                 '<td>' + (a.reason ? a.reason : '—') + '</td>' +
                                 '</tr>';
                });
            }
            $('#attendance-report-table tbody').html(tbodyHtml);
        }
        renderAttendanceReportTable();

        // 3. Render Employee Cost Report Table (Step 9)
        function renderEmployeeCostTable() {
            var tbodyHtml = '';
            var costMap = {};
            var filteredPayroll = swvtPayrollList;
            if (swvtSelectedBranch) {
                filteredPayroll = swvtPayrollList.filter(function(p) {
                    return p.branch_id === swvtSelectedBranch;
                });
            }

            filteredPayroll.forEach(function(p) {
                var key = p.emp_code;
                if (!costMap[key]) {
                    costMap[key] = {
                        name: p.emp_name,
                        code: p.emp_code,
                        branch: p.branch_name,
                        basic: 0,
                        commission: 0,
                        deductions: 0,
                        net: 0
                    };
                }
                costMap[key].basic += p.basic;
                costMap[key].commission += p.commission;
                costMap[key].deductions += (p.absence + p.other);
                costMap[key].net += p.net;
            });

            var costArray = Object.values(costMap);
            if (costArray.length === 0) {
                tbodyHtml = '<tr><td colspan="7" style="text-align:center; color:#787c82; padding:15px;">No employee logs recorded.</td></tr>';
            } else {
                costArray.forEach(function(c) {
                    tbodyHtml += '<tr>' +
                                 '<td style="padding:8px 12px;"><strong>' + c.name + '</strong></td>' +
                                 '<td>' + c.code + '</td>' +
                                 '<td>' + c.branch + '</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums;">' + c.basic.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#2271b1;">+' + c.commission.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-' + c.deductions.toLocaleString() + ' EGP</td>' +
                                 '<td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;">' + c.net.toLocaleString() + ' EGP</td>' +
                                 '</tr>';
                });
            }
            $('#employee-cost-report-table tbody').html(tbodyHtml);
        }
        renderEmployeeCostTable();

        // Chart instantiations
        var activeCharts = {};
        function renderBICharts() {
            var monthsLabels = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            
            // 1. Monthly sales trend (Line Chart)
            var trendData = Array(12).fill(0);
            displayBranches.forEach(function(b) {
                b.monthly.forEach(function(m, idx) {
                    trendData[idx] += m.actual;
                });
            });

            if (activeCharts.trend) activeCharts.trend.destroy();
            activeCharts.trend = new Chart(document.getElementById('monthlySalesTrendChart'), {
                type: 'line',
                data: {
                    labels: monthsLabels,
                    datasets: [{
                        label: 'Total Actual Sales (EGP)',
                        data: trendData,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // 2. Actual vs Target by Branch (Grouped Bar Chart)
            var barLabels = displayBranches.map(function(b) { return b.name.replace('Branch ', ''); });
            var actuals = displayBranches.map(function(b) { return b.actual; });
            var targets = displayBranches.map(function(b) { return b.target; });

            if (activeCharts.actVsTar) activeCharts.actVsTar.destroy();
            activeCharts.actVsTar = new Chart(document.getElementById('actualVsTargetChart'), {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [
                        { label: 'Actual Sales', data: actuals, backgroundColor: '#2271b1' },
                        { label: 'Target Sales', data: targets, backgroundColor: '#c3c4c7' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    borderRadius: 4
                }
            });

            // 3. Achievement Percentage by Branch (Horizontal Bar Chart)
            var achs = displayBranches.map(function(b) { return b.achievement; });

            if (activeCharts.achievements) activeCharts.achievements.destroy();
            activeCharts.achievements = new Chart(document.getElementById('achievementBranchChart'), {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Achievement %',
                        data: achs,
                        backgroundColor: '#137333',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });

            // 4. Branch Contribution Share (Donut Chart)
            if (activeCharts.donut) activeCharts.donut.destroy();
            activeCharts.donut = new Chart(document.getElementById('branchContributionChart'), {
                type: 'doughnut',
                data: {
                    labels: barLabels,
                    datasets: [{
                        data: actuals,
                        backgroundColor: ['#2271b1', '#7c3aed', '#12a150', '#e0a800', '#d63638', '#00cfc1', '#f59e0b']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } }
                }
            });

            // 5. Top 5 Branches (Bar Chart)
            var sortedForTop = [].concat(processedBranches);
            sortedForTop.sort(function(x, y) { return y.actual - x.actual; });
            var top5 = sortedForTop.slice(0, 5);

            if (activeCharts.top5) activeCharts.top5.destroy();
            activeCharts.top5 = new Chart(document.getElementById('topBranchesChart'), {
                type: 'bar',
                data: {
                    labels: top5.map(function(b) { return b.name.replace('Branch ', ''); }),
                    datasets: [{
                        label: 'Actual Sales',
                        data: top5.map(function(b) { return b.actual; }),
                        backgroundColor: '#7c3aed',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Tab Switching binds
        $('#analytics-tabs .swvt-hr-tab-btn').on('click', function() {
            var clicked = $(this);
            var tabKey = clicked.data('analytics-tab');

            $('#analytics-tabs .swvt-hr-tab-btn').removeClass('is-active');
            clicked.addClass('is-active');

            $('.swvt-hr-tab-content[id^="analytics-tab-"]').hide();
            $('#analytics-tab-' + tabKey).show();

            if (tabKey === 'visual-charts') {
                setTimeout(renderBICharts, 100);
            }
        });

        // Excel Export
        $('#swvt-export-excel-btn').on('click', function() {
            var csv = [];
            var rows = document.querySelectorAll("#perf-summary-table tr");
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
                }
                csv.push(row.join(","));
            }
            var csvString = csv.join("\n");
            var csvFile = new Blob([csvString], {type: "text/csv;charset=utf-8;"});
            var downloadLink = document.createElement("a");
            downloadLink.download = "swvt_branch_performance_" + swvtSelectedYear + ".csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        });

        // PDF Export & Print Report triggers
        $('#swvt-export-pdf-btn, #swvt-print-report-btn').on('click', function() {
            $('body').addClass('swvt-print-bi-only');
            window.print();
            $('body').removeClass('swvt-print-bi-only');
        });
        // Deep-linking helper to auto-activate tabs via query params
        var urlParams = new URLSearchParams(window.location.search);
        var targetTab = urlParams.get('tab');
        if (targetTab) {
            var tabBtn = $('#analytics-tabs .swvt-hr-tab-btn[data-analytics-tab="' + targetTab + '"]');
            if (tabBtn.length > 0) {
                tabBtn.trigger('click');
            }
        }
    }

    setupBIPanel();
});
</script>
