<?php
/**
 * ERP Sales Management Hub (ERPNext Style).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Selected Filters
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_month  = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_quarter = isset( $_GET['q'] ) ? absint( $_GET['q'] ) : (int) ceil( date( 'n' ) / 3 );
$selected_branch = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;
$date_start      = isset( $_GET['ds'] ) ? sanitize_text_field( $_GET['ds'] ) : '';
$date_end        = isset( $_GET['de'] ) ? sanitize_text_field( $_GET['de'] ) : '';

$branches = SWVT_HR_Branch::get_active();

// ==========================================
// CONTROLLER HANDLERS
// ==========================================

// 1. Save Sales Entry
if ( isset( $_POST['swvt_add_sale_entry_nonce'] ) && wp_verify_nonce( $_POST['swvt_add_sale_entry_nonce'], 'swvt_add_sale_entry' ) ) {
    $branch_id = absint( $_POST['entry_branch_id'] );
    $entry_type = in_array( $_POST['entry_type'], [ 'daily', 'weekly', 'monthly' ] ) ? $_POST['entry_type'] : 'daily';
    $entry_date = sanitize_text_field( $_POST['entry_date'] );
    $amount = floatval( $_POST['entry_amount'] );
    $orders = absint( $_POST['entry_orders'] );
    $customers = absint( $_POST['entry_customers'] );
    $refunds = floatval( $_POST['entry_refunds'] );
    $discounts = floatval( $_POST['entry_discounts'] );
    $notes = sanitize_textarea_field( $_POST['entry_notes'] );

    $wpdb->insert( $p . 'sales_entries', [
        'branch_id'  => $branch_id,
        'entry_type' => $entry_type,
        'entry_date' => $entry_date,
        'amount'     => $amount,
        'orders'     => $orders,
        'customers'  => $customers,
        'refunds'    => $refunds,
        'discounts'  => $discounts,
        'notes'      => $notes
    ] );

    // Trigger legacy monthly sync
    $time = strtotime( $entry_date );
    $m = (int) date( 'n', $time );
    $y = (int) date( 'Y', $time );
    
    SWVT_HR_ERP_Service::sync_sales_entries_to_legacy( $branch_id, $m, $y );
    SWVT_HR_Report_Service::clear_cache( $m, $y );
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sales entry logged and synchronized successfully.', 'swvt-hr' ) . '</p></div>';
}

// 2. Delete Sales Entry
if ( isset( $_GET['delete_entry_id'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_entry_' . $_GET['delete_entry_id'] ) ) {
    $entry_id = absint( $_GET['delete_entry_id'] );
    $entry = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}sales_entries WHERE id = %d", $entry_id ) );
    if ( $entry ) {
        $wpdb->delete( $p . 'sales_entries', [ 'id' => $entry_id ] );
        $time = strtotime( $entry->entry_date );
        $m = (int) date( 'n', $time );
        $y = (int) date( 'Y', $time );
        
        SWVT_HR_ERP_Service::sync_sales_entries_to_legacy( $entry->branch_id, $m, $y );
        SWVT_HR_Report_Service::clear_cache( $m, $y );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sales entry deleted and synced.', 'swvt-hr' ) . '</p></div>';
    }
}

// ==========================================
// DATA RETRIEVAL & QUERY CALCULATIONS
// ==========================================

// Build filters for history query
$where_clauses = [ '1=1' ];
if ( $selected_branch ) {
    $where_clauses[] = $wpdb->prepare( "se.branch_id = %d", $selected_branch );
}
if ( ! empty( $date_start ) ) {
    $where_clauses[] = $wpdb->prepare( "se.entry_date >= %s", $date_start );
}
if ( ! empty( $date_end ) ) {
    $where_clauses[] = $wpdb->prepare( "se.entry_date <= %s", $date_end );
}
if ( empty( $date_start ) && empty( $date_end ) ) {
    // Default to selected year / month
    $start_m = sprintf( '%d-%02d-01', $selected_year, $selected_month );
    $end_m   = date( 'Y-m-t', strtotime( $start_m ) );
    $where_clauses[] = $wpdb->prepare( "se.entry_date >= %s AND se.entry_date <= %s", $start_m, $end_m );
}
$where_sql = implode( ' AND ', $where_clauses );

// Query History Log
$history_entries = $wpdb->get_results( "
    SELECT se.*, b.name as branch_name, b.code as branch_code
    FROM {$p}sales_entries se
    JOIN {$p}branches b ON se.branch_id = b.id
    WHERE {$where_sql}
    ORDER BY se.entry_date DESC, se.id DESC
" );

// KPI Analytics sums
$today_date = date('Y-m-d');
$kpi_today = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$p}sales_entries WHERE entry_date = %s", $today_date ) );

$start_year = sprintf( '%d-01-01', $selected_year );
$end_year   = sprintf( '%d-12-31', $selected_year );
$kpi_year = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$p}sales_entries WHERE entry_date >= %s AND entry_date <= %s", $start_year, $end_year ) );

$start_month = sprintf( '%d-%02d-01', $selected_year, $selected_month );
$end_month   = date( 'Y-m-t', strtotime( $start_month ) );
$kpi_month = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM {$p}sales_entries WHERE entry_date >= %s AND entry_date <= %s", $start_month, $end_month ) );

$kpi_orders_month = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(orders) FROM {$p}sales_entries WHERE entry_date >= %s AND entry_date <= %s", $start_month, $end_month ) );
$kpi_refunds_month = (float) $wpdb->get_var( $wpdb->prepare( "SELECT SUM(refunds) FROM {$p}sales_entries WHERE entry_date >= %s AND entry_date <= %s", $start_month, $end_month ) );

$days_passed = (int) date('d');
$avg_daily_sales = $days_passed > 0 ? ($kpi_month / $days_passed) : 0.00;

// Target achievement configurations for EOM forecasts
$total_monthly_target = (float) $wpdb->get_var( $wpdb->prepare(
    "SELECT SUM(sales_target) FROM {$p}branches WHERE status = 'active'"
) );

// Forecasts
$days_in_month = (int) date('t', strtotime($start_month));
$predicted_eom = $avg_daily_sales * $days_in_month;
$remaining_needed = max( 0.00, $total_monthly_target - $kpi_month );
$days_left = $days_in_month - $days_passed;
$required_daily_speed = $days_left > 0 ? ($remaining_needed / $days_left) : 0.00;

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = __( 'ERP Sales Management Hub', 'swvt-hr' );
?>

<style>
    .swvt-hr-sales-tabs {
        display: flex;
        background: #fafafb;
        border: 1px solid #dcdcde;
        border-bottom: none;
        margin: 0;
        padding: 0;
        list-style: none;
        border-radius: 8px 8px 0 0;
    }
    .swvt-hr-sales-tab-item {
        padding: 12px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #50575e;
        cursor: pointer;
        border-right: 1px solid #dcdcde;
        transition: all 0.2s ease;
    }
    .swvt-hr-sales-tab-item:hover {
        background: #f0f0f1;
        color: #2271b1;
    }
    .swvt-hr-sales-tab-item.is-active {
        background: #ffffff;
        color: #2271b1;
        border-bottom: 2px solid #2271b1;
        font-weight: 700;
    }
    .swvt-hr-sales-tab-content {
        background: #ffffff;
        border: 1px solid #dcdcde;
        padding: 24px;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        display: none;
        margin-bottom: 25px;
    }
    .swvt-hr-sales-tab-content.is-active {
        display: block;
    }
    .swvt-hr-sales-kpis {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    .swvt-hr-sales-kpi-card {
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .swvt-hr-sales-kpi-label {
        font-size: 10.5px;
        color: #646970;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .swvt-hr-sales-kpi-value {
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px;
        color: #1d2327;
        font-variant-numeric: tabular-nums;
    }
    .swvt-hr-sales-kpi-meta {
        font-size: 11px;
        color: #8c8f94;
        margin-top: 4px;
    }
    @media(max-width:992px) {
        .swvt-hr-sales-kpis { grid-template-columns: repeat(2, 1fr); }
    }
    @media(max-width:600px) {
        .swvt-hr-sales-kpis { grid-template-columns: 1fr; }
    }
</style>

<div class="wrap swvt-hr-wrap">

    <!-- Header / Title -->
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <div>
                <h1 style="font-size: 21px; font-weight: 600; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
                <p style="font-size: 12px; color:#646970; margin:0;"><?php esc_html_e( 'ERP Sales Ledger, Daily/Weekly logs, forecasting algorithms, and target analysis metrics.', 'swvt-hr' ); ?></p>
            </div>
        </div>

        <form method="get" style="display:flex; gap:6px; margin:0;">
            <input type="hidden" name="page" value="swvt-hr-sales" />
            <select name="branch" class="swvt-hr-select" style="width: 170px;">
                <option value="0"><?php esc_html_e( 'All Branches', 'swvt-hr' ); ?></option>
                <?php foreach ( $branches as $b ) : ?>
                    <option value="<?php echo $b->id; ?>" <?php selected( $selected_branch, $b->id ); ?>><?php echo esc_html( $b->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="m" class="swvt-hr-select" style="width: 110px;">
                <?php foreach ( $months_english as $num => $name ) : ?>
                    <option value="<?php echo $num; ?>" <?php selected( $selected_month, $num ); ?>><?php echo esc_html( $name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="y" class="swvt-hr-select" style="width: 85px;">
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

    <!-- Sales Modules KPIs -->
    <div class="swvt-hr-sales-kpis">
        <div class="swvt-hr-sales-kpi-card" style="border-left: 4px solid #2271b1;">
            <div class="swvt-hr-sales-kpi-label"><?php esc_html_e( 'Month Payouts', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-sales-kpi-value"><?php echo number_format($kpi_month, 0); ?> EGP</div>
            <div class="swvt-hr-sales-kpi-meta"><?php printf( __( 'Today\'s Sales: %s EGP', 'swvt-hr' ), number_format($kpi_today, 0) ); ?></div>
        </div>
        <div class="swvt-hr-sales-kpi-card" style="border-left: 4px solid #107c41;">
            <div class="swvt-hr-sales-kpi-label"><?php esc_html_e( 'Orders Sum', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-sales-kpi-value"><?php echo number_format($kpi_orders_month, 0); ?></div>
            <div class="swvt-hr-sales-kpi-meta"><?php printf( __( 'Year-to-date: %s EGP', 'swvt-hr' ), number_format($kpi_year, 0) ); ?></div>
        </div>
        <div class="swvt-hr-sales-kpi-card" style="border-left: 4px solid #7c3aed;">
            <div class="swvt-hr-sales-kpi-label"><?php esc_html_e( 'Predicted EOM Sales', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-sales-kpi-value"><?php echo number_format($predicted_eom, 0); ?> EGP</div>
            <div class="swvt-hr-sales-kpi-meta"><?php printf( __( 'Daily Avg: %s EGP', 'swvt-hr' ), number_format($avg_daily_sales, 0) ); ?></div>
        </div>
        <div class="swvt-hr-sales-kpi-card" style="border-left: 4px solid #b78a00;">
            <div class="swvt-hr-sales-kpi-label"><?php esc_html_e( 'Speed Needed', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-sales-kpi-value" style="color: <?php echo $required_daily_speed > 0 ? '#b78a00' : '#107c41'; ?>;">
                <?php echo number_format($required_daily_speed, 0); ?> EGP
            </div>
            <div class="swvt-hr-sales-kpi-meta"><?php printf( __( 'Target Gap: %s EGP', 'swvt-hr' ), number_format($remaining_needed, 0) ); ?></div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="swvt-hr-sales-tabs" id="sales-module-tabs">
        <li class="swvt-hr-sales-tab-item is-active" data-sales-tab="entry">✍️ Sales Entry Logs</li>
        <li class="swvt-hr-sales-tab-item" data-sales-tab="history">📋 History Ledger</li>
        <li class="swvt-hr-sales-tab-item" data-sales-tab="compare">📊 Sales Comparison</li>
        <li class="swvt-hr-sales-tab-item" data-sales-tab="forecast">🎯 Target Predictions</li>
    </ul>

    <!-- Tab 1: Sales Entry Form -->
    <div class="swvt-hr-sales-tab-content is-active" id="sales-tab-content-entry">
        <h3 style="font-size:14px; font-weight:700; margin:0 0 15px;"><?php esc_html_e( 'Add Sales Records Log', 'swvt-hr' ); ?></h3>
        
        <form method="post" style="max-width: 600px;">
            <?php wp_nonce_field( 'swvt_add_sale_entry', 'swvt_add_sale_entry_nonce' ); ?>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:12px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></label>
                    <select name="entry_branch_id" class="swvt-hr-select" required>
                        <?php foreach ( $branches as $b ) : ?>
                            <option value="<?php echo $b->id; ?>"><?php echo esc_html($b->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Entry Period Type', 'swvt-hr' ); ?></label>
                    <select name="entry_type" class="swvt-hr-select" required>
                        <option value="daily">Daily Entry</option>
                        <option value="weekly">Weekly Entry</option>
                        <option value="monthly">Monthly Entry</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:12px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Entry date', 'swvt-hr' ); ?></label>
                    <input type="date" name="entry_date" value="<?php echo date('Y-m-d'); ?>" class="swvt-hr-input" required />
                </div>
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Sales Amount (EGP)', 'swvt-hr' ); ?></label>
                    <input type="number" step="0.01" min="0" name="entry_amount" class="swvt-hr-input" style="font-size: 16px; font-weight:700;" required />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:12px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Orders Count', 'swvt-hr' ); ?></label>
                    <input type="number" min="0" name="entry_orders" class="swvt-hr-input" required />
                </div>
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Customers Count', 'swvt-hr' ); ?></label>
                    <input type="number" min="0" name="entry_customers" class="swvt-hr-input" required />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:12px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Refunds Amount (Optional)', 'swvt-hr' ); ?></label>
                    <input type="number" step="0.01" min="0" name="entry_refunds" value="0" class="swvt-hr-input" />
                </div>
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Discounts Amount (Optional)', 'swvt-hr' ); ?></label>
                    <input type="number" step="0.01" min="0" name="entry_discounts" value="0" class="swvt-hr-input" />
                </div>
            </div>

            <div class="swvt-hr-field-group">
                <label class="swvt-hr-label"><?php esc_html_e( 'Notes / Details', 'swvt-hr' ); ?></label>
                <textarea name="entry_notes" class="swvt-hr-input" rows="2"></textarea>
            </div>

            <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" style="margin-top: 10px;"><?php esc_html_e( 'Log entry & Sync aggregates', 'swvt-hr' ); ?></button>
        </form>
    </div>

    <!-- Tab 2: Sales History Ledger -->
    <div class="swvt-hr-sales-tab-content" id="sales-tab-content-history">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 style="font-size:14px; font-weight:700; margin:0;"><?php esc_html_e( 'Recorded Sales Entry Logs Ledger', 'swvt-hr' ); ?></h3>
            <div style="display:flex; gap:8px;">
                <button type="button" class="swvt-hr-btn" onclick="window.print()">Print Page</button>
            </div>
        </div>

        <table class="swvt-hr-table">
            <thead>
                <tr>
                    <th style="padding:10px 12px;">Date</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th style="text-align:right;">Amount (EGP)</th>
                    <th style="text-align:center;">Orders</th>
                    <th style="text-align:center;">Customers</th>
                    <th style="text-align:right;">Deducts (Refunds/Discounts)</th>
                    <th style="text-align:center;">Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $history_entries ) ) : ?>
                    <tr>
                        <td colspan="8" style="text-align:center; color:#787c82; padding:30px;"><?php esc_html_e( 'No entries match selected date/branch parameters.', 'swvt-hr' ); ?></td>
                    </tr>
                <?php else : 
                    foreach ( $history_entries as $he ) :
                        $delete_url = wp_nonce_url( admin_url( 'admin.php?page=swvt-hr-sales&delete_entry_id=' . $he->id ), 'delete_entry_' . $he->id );
                ?>
                    <tr>
                        <td style="padding:9px 12px; font-variant-numeric:tabular-nums;"><strong><?php echo esc_html($he->entry_date); ?></strong></td>
                        <td><?php echo esc_html($he->branch_name); ?></td>
                        <td><span style="text-transform:uppercase; font-size:9.5px; background:#f0f0f1; color:#50575e; padding:2px 5px; border-radius:3px; font-weight:600;"><?php echo esc_html($he->entry_type); ?></span></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#107c41;"><?php echo number_format($he->amount, 2); ?></td>
                        <td style="text-align:center; font-variant-numeric:tabular-nums; color:#787c82;"><?php echo number_format($he->orders, 0); ?></td>
                        <td style="text-align:center; font-variant-numeric:tabular-nums; color:#787c82;"><?php echo number_format($he->customers, 0); ?></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-<?php echo number_format($he->refunds + $he->discounts, 2); ?></td>
                        <td style="text-align:center;">
                            <a href="<?php echo $delete_url; ?>" class="swvt-hr-btn swvt-hr-btn-danger" style="padding:2px 7px; font-size:11px;" onclick="return confirm('Are you sure you want to delete this sales log?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab 3: Sales Comparison Matrix -->
    <div class="swvt-hr-sales-tab-content" id="sales-tab-content-compare">
        <h3 style="font-size:14px; font-weight:700; margin:0 0 15px;"><?php esc_html_e( 'Multi-Branch Sales Comparison Matrix', 'swvt-hr' ); ?></h3>
        
        <table class="swvt-hr-table">
            <thead>
                <tr>
                    <th style="padding:10px 12px;">Branch Name</th>
                    <th style="text-align:right;">Monthly Target</th>
                    <th style="text-align:right;">Actual Sales</th>
                    <th style="text-align:right;">Achievement %</th>
                    <th style="text-align:right;">Variance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ( $branches as $b ) :
                    // Query actual sum for this month/year for this branch
                    $start_b = sprintf( '%d-%02d-01', $selected_year, $selected_month );
                    $end_b   = date( 'Y-m-t', strtotime( $start_b ) );
                    
                    $act = (float) $wpdb->get_var( $wpdb->prepare(
                        "SELECT SUM(amount) FROM {$p}sales_entries WHERE branch_id = %d AND entry_date >= %s AND entry_date <= %s",
                        $b->id, $start_b, $end_b
                    ) );

                    $target_row_b = $wpdb->get_row( $wpdb->prepare(
                        "SELECT sales_target FROM {$p}branch_targets WHERE branch_id = %d AND target_type = 'monthly' AND period_value = %d AND period_year = %d",
                        $b->id, $selected_month, $selected_year
                    ) );
                    
                    $tar = $target_row_b ? (float) $target_row_b->sales_target : (float) $b->sales_target;
                    $ach_b = $tar > 0 ? ($act / $tar) * 100 : 0.00;
                    $var_b = $act - $tar;
                ?>
                    <tr>
                        <td style="padding:9px 12px;"><strong><?php echo esc_html($b->name); ?></strong></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; color:#787c82;"><?php echo number_format($tar, 0); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:#2271b1;"><?php echo number_format($act, 0); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:<?php echo $ach_b >= 100 ? '#107c41' : '#b78a00'; ?>;"><?php echo number_format($ach_b, 1); ?>%</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:<?php echo $var_b >= 0 ? '#107c41' : '#a80000'; ?>;"><?php echo ($var_b >= 0 ? '+' : '') . number_format($var_b, 0); ?> EGP</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab 4: Target Predictions -->
    <div class="swvt-hr-sales-tab-content" id="sales-tab-content-forecast">
        <h3 style="font-size:14px; font-weight:700; margin:0 0 15px;"><?php esc_html_e( 'Target Predictive Forecasts Panel', 'swvt-hr' ); ?></h3>
        <p style="font-size: 12px; color: #646970; margin-top:-10px; margin-bottom:20px;">Calculated using linear monthly run-rate algorithms based on the current day's speed.</p>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; font-size:13px;">
            <div style="background:#f6f7f7; padding:18px; border-radius:6px; border:1px solid #dcdcde;">
                <h4 style="margin:0 0 10px; font-size:13px; font-weight:700;">Remaining Target Forecast</h4>
                <div style="margin-bottom:8px;">Total Monthly Target: <strong><?php echo number_format($total_monthly_target, 2); ?> EGP</strong></div>
                <div style="margin-bottom:8px;">Total Actual Sales: <strong><?php echo number_format($kpi_month, 2); ?> EGP</strong></div>
                <div style="margin-bottom:8px;">Estimated End-of-Month: <strong style="color:#2271b1;"><?php echo number_format($predicted_eom, 2); ?> EGP</strong></div>
                <div style="margin-bottom:8px;">Target achievement level: <strong style="color:<?php echo $predicted_eom >= $total_monthly_target ? '#107c41' : '#b78a00'; ?>;"><?php echo $total_monthly_target > 0 ? number_format(($predicted_eom / $total_monthly_target)*100, 1) . '%' : '0%'; ?></strong></div>
            </div>
            <div style="background:#f6f7f7; padding:18px; border-radius:6px; border:1px solid #dcdcde;">
                <h4 style="margin:0 0 10px; font-size:13px; font-weight:700;">Required Target Velocity</h4>
                <div style="margin-bottom:8px;">Remaining target gap: <strong><?php echo number_format($remaining_needed, 2); ?> EGP</strong></div>
                <div style="margin-bottom:8px;">Days remaining in period: <strong><?php echo $days_left; ?> days</strong></div>
                <div style="margin-bottom:8px;">Required Daily Sales speed: <strong style="color:<?php echo $required_daily_speed > 0 ? '#b78a00' : '#107c41'; ?>;"><?php echo number_format($required_daily_speed, 2); ?> EGP</strong></div>
            </div>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    $('#sales-module-tabs .swvt-hr-sales-tab-item').on('click', function() {
        var clicked = $(this);
        var tabKey = clicked.data('sales-tab');

        $('#sales-module-tabs .swvt-hr-sales-tab-item').removeClass('is-active');
        clicked.addClass('is-active');

        $('.swvt-hr-sales-tab-content').removeClass('is-active');
        $('#sales-tab-content-' + tabKey).addClass('is-active');
    });
});
</script>
