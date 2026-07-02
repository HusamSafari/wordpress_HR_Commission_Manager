<?php
/**
 * ERP Services Module (Odoo/ERPNext Style).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_ERP_Service {

    /**
     * Log a branch timeline activity.
     */
    public static function log_activity( $branch_id, $event_type, $description ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $wpdb->insert( $p . 'branch_timeline', [
            'branch_id'   => $branch_id,
            'event_type'  => $event_type,
            'description' => $description,
            'created_at'  => current_time( 'mysql' )
        ] );
    }

    /**
     * Add or update a branch target config.
     */
    public static function save_target( $branch_id, $target_type, $period_value, $period_year, $sales_target, $orders_target = 0, $customers_target = 0, $profit_target = 0.00 ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$p}branch_targets 
             WHERE branch_id = %d AND target_type = %s AND period_value = %d AND period_year = %d",
            $branch_id, $target_type, $period_value, $period_year
        ) );

        $data = [
            'branch_id'        => $branch_id,
            'target_type'      => $target_type,
            'period_value'     => $period_value,
            'period_year'      => $period_year,
            'sales_target'     => $sales_target,
            'orders_target'    => $orders_target,
            'customers_target' => $customers_target,
            'profit_target'    => $profit_target
        ];

        if ( $existing_id ) {
            $wpdb->update( $p . 'branch_targets', $data, [ 'id' => $existing_id ] );
            self::log_activity( $branch_id, 'target_updated', sprintf( 'Updated %s target for %s/%s', $target_type, $period_value, $period_year ) );
        } else {
            $wpdb->insert( $p . 'branch_targets', $data );
            self::log_activity( $branch_id, 'target_updated', sprintf( 'Set new %s target for %s/%s', $target_type, $period_value, $period_year ) );
        }
    }

    /**
     * Add a branch expense category record.
     */
    public static function add_expense( $branch_id, $category, $amount, $date, $notes = '' ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $wpdb->insert( $p . 'branch_expenses', [
            'branch_id'    => $branch_id,
            'category'     => $category,
            'amount'       => $amount,
            'expense_date' => $date,
            'notes'        => $notes,
            'created_at'   => current_time( 'mysql' )
        ] );

        self::log_activity( $branch_id, 'expense_logged', sprintf( 'Logged %s expense: %s EGP', ucfirst( $category ), number_format( $amount, 2 ) ) );
    }

    /**
     * Add a branch document registry record.
     */
    public static function add_document( $branch_id, $doc_type, $title, $file_url ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $wpdb->insert( $p . 'branch_documents', [
            'branch_id'   => $branch_id,
            'doc_type'    => $doc_type,
            'title'       => $title,
            'file_url'    => $file_url,
            'uploaded_at' => current_time( 'mysql' )
        ] );

        self::log_activity( $branch_id, 'document_uploaded', sprintf( 'Uploaded document: %s (%s)', $title, $doc_type ) );
    }

    /**
     * Delete a branch document registry record.
     */
    public static function delete_document( $branch_id, $doc_id ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $wpdb->delete( $p . 'branch_documents', [ 'id' => $doc_id ] );
        self::log_activity( $branch_id, 'document_deleted', 'Deleted document registry record.' );
    }

    /**
     * Synchronize sales entries to legacy monthly sales aggregates.
     * This keeps legacy commissions and payroll calculators working seamlessly.
     */
    public static function sync_sales_entries_to_legacy( $branch_id, $month, $year ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $start_date = sprintf( '%d-%02d-01', $year, $month );
        $end_date   = date( 'Y-m-t', strtotime( $start_date ) );

        // 1. Calculate sum from detailed entries
        $agg = $wpdb->get_row( $wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) as total_amount
             FROM {$p}sales_entries
             WHERE branch_id = %d AND entry_date >= %s AND entry_date <= %s",
            $branch_id, $start_date, $end_date
        ) );
        $total_sales = $agg ? (float) $agg->total_amount : 0.00;

        // 2. Load branch settings
        $branch = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}branches WHERE id = %d", $branch_id ) );
        if ( ! $branch ) return;

        $settings = get_option( 'swvt_hr_settings' );
        $default_rate = isset( $settings['default_commission_rate'] ) ? (float) $settings['default_commission_rate'] : 0.0020;
        $rate = ( ! empty( $branch->commission_rate ) && $branch->commission_rate > 0 ) ? (float) $branch->commission_rate : $default_rate;
        $commission_base = $total_sales * $rate;

        // Get the target defined for this month
        $target_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT sales_target FROM {$p}branch_targets 
             WHERE branch_id = %d AND target_type = 'monthly' AND period_value = %d AND period_year = %d",
            $branch_id, $month, $year
        ) );
        $target_value = $target_row ? (float) $target_row->sales_target : (float) $branch->sales_target;

        // 3. Save or update the legacy sales table row
        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$p}sales
               (branch_id, period_month, period_year, total_sales, target, commission_rate, commission_base)
             VALUES (%d, %d, %d, %f, %f, %f, %f)
             ON DUPLICATE KEY UPDATE
                total_sales = VALUES(total_sales),
                target = VALUES(target),
                commission_rate = VALUES(commission_rate),
                commission_base = VALUES(commission_base)",
              $branch_id, $month, $year, $total_sales, $target_value, $rate, $commission_base
        ) );
    }

    /**
     * Log a system-wide user action to the activity_logs ledger.
     */
    public static function log_system_event( $action_type, $description, $module_key, $item_id = null ) {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $user_id = get_current_user_id();

        $wpdb->insert( $p . 'activity_logs', [
            'action_type' => $action_type,
            'description' => $description,
            'user_id'     => $user_id ? $user_id : null,
            'module_key'  => $module_key,
            'item_id'     => $item_id,
            'created_at'  => current_time( 'mysql' )
        ] );
    }
}
