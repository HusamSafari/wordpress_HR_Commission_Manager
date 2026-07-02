<?php
/**
 * System Activity Logs View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Sorting & Pagination Config
$items_per_page = 15;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$offset = ( $current_page - 1 ) * $items_per_page;

$orderby = isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], [ 'created_at', 'action_type', 'module_key' ] ) ? $_GET['orderby'] : 'created_at';
$order = isset( $_GET['order'] ) && in_array( $_GET['order'], [ 'ASC', 'DESC' ] ) ? $_GET['order'] : 'DESC';

// Filters
$filter_module = isset( $_GET['filter_module'] ) ? sanitize_text_field( $_GET['filter_module'] ) : '';
$filter_action = isset( $_GET['filter_action'] ) ? sanitize_text_field( $_GET['filter_action'] ) : '';

$where = [ '1=1' ];
if ( ! empty( $filter_module ) ) {
    $where[] = $wpdb->prepare( "module_key = %s", $filter_module );
}
if ( ! empty( $filter_action ) ) {
    $where[] = $wpdb->prepare( "action_type = %s", $filter_action );
}
$where_sql = implode( ' AND ', $where );

// Fetch items
$logs = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$p}activity_logs 
     WHERE {$where_sql} 
     ORDER BY {$orderby} {$order} 
     LIMIT %d OFFSET %d",
    $items_per_page,
    $offset
) );

$total_items = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$p}activity_logs WHERE {$where_sql}" );
$total_pages = ceil( $total_items / $items_per_page );

// Available actions and modules for filters dropdown
$all_modules = $wpdb->get_col( "SELECT DISTINCT module_key FROM {$p}activity_logs WHERE module_key != ''" );
$all_actions = $wpdb->get_col( "SELECT DISTINCT action_type FROM {$p}activity_logs WHERE action_type != ''" );

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>';
$page_title = __( 'System Activity Logs Ledger', 'swvt-hr' );
?>

<div class="wrap swvt-hr-wrap">
    <div style="background:#fff; border-bottom:1px solid #e2e4e7; padding:12px 26px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom: 20px; border-radius:8px;">
        <div style="display:flex; align-items:center; gap:11px; margin-right:auto;">
            <div style="width:38px; height:38px; border-radius:9px; background:#f0f6fc; display:flex; align-items:center; justify-content:center; color:#2271b1;">
                <?php echo $page_icon; ?>
            </div>
            <div style="line-height:1.3;">
                <div style="font-size:18px; font-weight:600;"><?php echo esc_html( $page_title ); ?></div>
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'System audit trail logging employee creations, sales targets adjustments, setting alterations, and payroll runs.', 'swvt-hr' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Active Filters Row -->
    <div style="background:#fff; border:1px solid #dcdcde; padding:15px; border-radius:8px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
        <form method="get" style="display:inline-flex; gap:10px; align-items:center; margin:0;">
            <input type="hidden" name="page" value="swvt-hr-activity-logs" />
            
            <select name="filter_module" class="swvt-hr-select" style="width: 150px;">
                <option value=""><?php esc_html_e( 'All Modules', 'swvt-hr' ); ?></option>
                <?php foreach ( $all_modules as $mod ) : ?>
                    <option value="<?php echo esc_attr( $mod ); ?>" <?php selected( $filter_module, $mod ); ?>><?php echo esc_html( ucfirst( $mod ) ); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="filter_action" class="swvt-hr-select" style="width: 170px;">
                <option value=""><?php esc_html_e( 'All Action Types', 'swvt-hr' ); ?></option>
                <?php foreach ( $all_actions as $act ) : ?>
                    <option value="<?php echo esc_attr( $act ); ?>" <?php selected( $filter_action, $act ); ?>><?php echo esc_html( str_replace( '_', ' ', ucfirst( $act ) ) ); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary"><?php esc_html_e( 'Apply Filters', 'swvt-hr' ); ?></button>
            <?php if ( ! empty( $filter_module ) || ! empty( $filter_action ) ) : ?>
                <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-activity-logs' ); ?>" class="swvt-hr-btn"><?php esc_html_e( 'Clear', 'swvt-hr' ); ?></a>
            <?php endif; ?>
        </form>

        <span style="font-size:12.5px; color:#646970; font-variant-numeric:tabular-nums;">
            <?php printf( __( 'Showing %d of %d log rows', 'swvt-hr' ), count( $logs ), $total_items ); ?>
        </span>
    </div>

    <!-- Logs Table -->
    <div style="background:#fff; border:1px solid #dcdcde; border-radius:8px; overflow:hidden;">
        <table class="swvt-hr-table">
            <thead>
                <tr>
                    <th style="padding:12px 18px; width: 180px;">
                        <a href="<?php echo add_query_arg( [ 'orderby' => 'created_at', 'order' => $order === 'ASC' ? 'DESC' : 'ASC' ] ); ?>" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:4px;">
                            <?php esc_html_e( 'Timestamp', 'swvt-hr' ); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="<?php echo $order === 'ASC' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6'; ?>"/></svg>
                        </a>
                    </th>
                    <th style="width: 140px;">
                        <a href="<?php echo add_query_arg( [ 'orderby' => 'action_type', 'order' => $order === 'ASC' ? 'DESC' : 'ASC' ] ); ?>" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:4px;">
                            <?php esc_html_e( 'Action', 'swvt-hr' ); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="<?php echo $order === 'ASC' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6'; ?>"/></svg>
                        </a>
                    </th>
                    <th style="width: 130px;">
                        <a href="<?php echo add_query_arg( [ 'orderby' => 'module_key', 'order' => $order === 'ASC' ? 'DESC' : 'ASC' ] ); ?>" style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:4px;">
                            <?php esc_html_e( 'Module', 'swvt-hr' ); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="<?php echo $order === 'ASC' ? 'm18 15-6-6-6 6' : 'm6 9 6 6 6-6'; ?>"/></svg>
                        </a>
                    </th>
                    <th><?php esc_html_e( 'User Operator', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Detailed Log Description', 'swvt-hr' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $logs ) ) : ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align:center;">
                            <!-- Empty State Illustration/Markup -->
                            <div style="max-width: 320px; margin: 0 auto;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#8c8f94" stroke-width="1.5" style="margin-bottom:10px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                <h4 style="margin: 0 0 5px; font-weight:600; font-size:14px; color:#1d2327;"><?php esc_html_e( 'No Audit Logs Recorded', 'swvt-hr' ); ?></h4>
                                <p style="font-size:12px; color:#646970; margin:0;"><?php esc_html_e( 'Activity audits appear here once admin operators start adding branches, processing payrolls, or editing commission targets.', 'swvt-hr' ); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else : 
                    foreach ( $logs as $log ) :
                        $operator_user = get_userdata( $log->user_id );
                        $operator_name = $operator_user ? $operator_user->display_name : 'System Scheduler';
                ?>
                    <tr>
                        <td style="padding:11px 18px; font-variant-numeric:tabular-nums; color:#646970;"><?php echo esc_html( $log->created_at ); ?></td>
                        <td><span style="font-size:10px; font-weight:700; background:#f0f6fc; color:#2271b1; padding:3px 6px; border-radius:4px; text-transform:uppercase;"><?php echo esc_html( str_replace( '_', ' ', $log->action_type ) ); ?></span></td>
                        <td><span style="font-size:10px; font-weight:700; background:#faf7ed; color:#b78a00; padding:3px 6px; border-radius:4px; text-transform:uppercase;"><?php echo esc_html( $log->module_key ); ?></span></td>
                        <td><strong><?php echo esc_html( $operator_name ); ?></strong></td>
                        <td style="color:#50575e;"><?php echo esc_html( $log->description ); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Reusable Pagination Bar -->
        <?php if ( $total_pages > 1 ) : ?>
            <div style="background:#fafafb; border-top:1px solid #dcdcde; padding:12px 18px; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:12px; color:#646970;">
                    <?php printf( __( 'Showing page %d of %d', 'swvt-hr' ), $current_page, $total_pages ); ?>
                </span>
                <div style="display:inline-flex; gap:4px;">
                    <?php if ( $current_page > 1 ) : ?>
                        <a href="<?php echo add_query_arg( 'paged', $current_page - 1 ); ?>" class="swvt-hr-btn" style="font-size:11px; padding:3px 8px;">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ( $i = 1; $i <= $total_pages; $i++ ) : 
                        $active_style = ($i === $current_page) ? 'background:#2271b1; color:#fff; border-color:#2271b1;' : '';
                    ?>
                        <a href="<?php echo add_query_arg( 'paged', $i ); ?>" class="swvt-hr-btn" style="font-size:11px; padding:3px 8px; <?php echo $active_style; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ( $current_page < $total_pages ) : ?>
                        <a href="<?php echo add_query_arg( 'paged', $current_page + 1 ); ?>" class="swvt-hr-btn" style="font-size:11px; padding:3px 8px;">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
