<?php
/**
 * Commissions Management & Ledger View.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$selected_month  = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : (int) date( 'n' );
$selected_year   = isset( $_GET['y'] ) ? absint( $_GET['y'] ) : (int) date( 'Y' );
$selected_branch = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;

$branches = SWVT_HR_Branch::get_active();

// Fetch distributed commissions for this period
$comm_query = "SELECT c.*, e.full_name, e.code as emp_code, b.name as branch_name, s.total_sales, s.target as sales_target
               FROM {$p}commissions c
               JOIN {$p}employees e ON c.employee_id = e.id
               JOIN {$p}sales s ON c.sales_id = s.id
               JOIN {$p}branches b ON s.branch_id = b.id
               WHERE s.period_month = %d AND s.period_year = %d";

if ( $selected_branch ) {
    $comm_query .= $wpdb->prepare( " AND s.branch_id = %d", $selected_branch );
}
$comm_query .= " ORDER BY c.id ASC";

$commissions = $wpdb->get_results( $wpdb->prepare( $comm_query, $selected_month, $selected_year ) );

// Fetch overall KPIs for the selected period
$total_allocated = 0.00;
$total_deducted = 0.00;
$total_final = 0.00;
$eligible_count = 0;

foreach ( $commissions as $c ) {
    $total_allocated += (float) $c->amount;
    $total_deducted += (float) $c->absence_deduction;
    $total_final += (float) $c->final_amount;
    $eligible_count++;
}

$months_english = [
    1  => 'January', 2  => 'February', 3  => 'March', 4  => 'April',
    5  => 'May',      6  => 'June',     7  => 'July',  8  => 'August',
    9  => 'September',10 => 'October',  11 => 'November',12 => 'December'
];

$page_title = __( 'HR Commissions & Incentives Ledger', 'swvt-hr' );
?>

<style>
    .swvt-hr-comm-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    .swvt-hr-comm-card {
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .swvt-hr-comm-label {
        font-size: 11px;
        color: #646970;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
    }
    .swvt-hr-comm-value {
        font-size: 22px;
        font-weight: 700;
        margin-top: 6px;
        color: #1d2327;
        font-variant-numeric: tabular-nums;
    }
    @media(max-width:768px) {
        .swvt-hr-comm-grid-3 {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="wrap swvt-hr-wrap">
    
    <!-- Header & Navigation controls -->
    <div style="margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="6"/></svg>
            <div>
                <h1 style="font-size: 21px; font-weight: 600; margin: 0 0 4px;"><?php echo esc_html( $page_title ); ?></h1>
                <p style="font-size: 12px; color:#646970; margin:0;"><?php printf( __( 'Role-based sales commission payouts list for %s %d', 'swvt-hr' ), $months_english[ $selected_month ], $selected_year ); ?></p>
            </div>
        </div>

        <form method="get" style="display:flex; align-items:center; gap:8px;">
            <input type="hidden" name="page" value="swvt-hr-commissions" />
            <select name="branch" class="swvt-hr-select" style="width: 170px;">
                <option value="0"><?php esc_html_e( 'All Branches', 'swvt-hr' ); ?></option>
                <?php foreach ( $branches as $b ) : ?>
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

    <!-- Quick statistics blocks -->
    <div class="swvt-hr-comm-grid-3">
        <div class="swvt-hr-comm-card" style="border-right: 4px solid #7c3aed; background: linear-gradient(180deg, #fff, #f3edfb1A);">
            <div class="swvt-hr-comm-label"><?php esc_html_e( 'Total Allocated Commissions', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-comm-value"><?php echo number_format( $total_allocated, 2 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;">EGP</span></div>
        </div>
        <div class="swvt-hr-comm-card" style="border-right: 4px solid #c5221f; background: linear-gradient(180deg, #fff, #fce8e61A);">
            <div class="swvt-hr-comm-label"><?php esc_html_e( 'Absence Penalties Deducted', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-comm-value" style="color: #c5221f;"><?php echo number_format( $total_deducted, 2 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;">EGP</span></div>
        </div>
        <div class="swvt-hr-comm-card" style="border-right: 4px solid #0a7c2f; background: linear-gradient(180deg, #fff, #e7f6ec1A);">
            <div class="swvt-hr-comm-label"><?php esc_html_e( 'Final Payout Sum', 'swvt-hr' ); ?></div>
            <div class="swvt-hr-comm-value" style="color: #0a7c2f;"><?php echo number_format( $total_final, 2 ); ?> <span style="font-size:12px; font-weight:500; color:#787c82;">EGP</span></div>
        </div>
    </div>

    <!-- Commissions List Ledger Card -->
    <div class="swvt-hr-card">
        <div style="padding:14px 16px; border-bottom:1px solid #dcdcde; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size: 14px; font-weight: 700; margin: 0; color:#1d2327;"><?php esc_html_e( 'Distributed Commissions Log', 'swvt-hr' ); ?></h3>
            <button type="button" class="swvt-hr-btn swvt-hr-btn-success" style="padding:4px 10px; font-size:12px;" onclick="window.print()"><?php esc_html_e( 'Print Log', 'swvt-hr' ); ?></button>
        </div>

        <table class="swvt-hr-table">
            <thead>
                <tr>
                    <th style="padding:12px 16px;"><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Role Class', 'swvt-hr' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Achievement %', 'swvt-hr' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Assigned Share', 'swvt-hr' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Absence Deduction', 'swvt-hr' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Final Commission', 'swvt-hr' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $commissions ) ) : ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#787c82; padding:30px;">
                            <?php esc_html_e( 'No commissions distributed for this period.', 'swvt-hr' ); ?>
                        </td>
                    </tr>
                <?php else : 
                    $zebra_idx = 0;
                    foreach ( $commissions as $c ) : 
                        $ach = $c->sales_target > 0 ? ( $c->total_sales / $c->sales_target ) * 100 : 0;
                        $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                        $zebra_idx++;
                ?>
                    <tr class="<?php echo $zebra_class; ?>">
                        <td style="padding:10px 16px;">
                            <div style="font-weight:600;"><a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employee-profile&id=' . $c->employee_id ); ?>" style="text-decoration:none; color:#2271b1;"><?php echo esc_html( $c->full_name ); ?></a></div>
                            <div style="font-size:11px; color:#787c82;"><?php echo esc_html($c->emp_code); ?></div>
                        </td>
                        <td><?php echo esc_html( $c->branch_name ); ?></td>
                        <td><span style="font-weight:600; text-transform:uppercase; font-size:10px; background:#f0f0f1; padding:3px 6px; border-radius:3px; color:#50575e;"><?php echo esc_html($c->role_key); ?> (<?php echo number_format($c->role_percent, 1); ?>%)</span></td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:600; color:<?php echo $ach >= 100 ? '#137333' : '#b06000'; ?>;"><?php echo number_format($ach, 1); ?>%</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; color:#787c82;"><?php echo number_format($c->amount, 2); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; color:#c5221f;">-<?php echo number_format($c->absence_deduction, 2); ?> EGP</td>
                        <td style="text-align:right; font-variant-numeric:tabular-nums; font-weight:700; color:#0a7c2f;"><?php echo number_format($c->final_amount, 2); ?> EGP</td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>
