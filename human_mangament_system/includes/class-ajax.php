<?php
/**
 * AJAX Handlers (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Ajax {

    public function register_handlers() {
        $actions = [
            'save_branch',
            'delete_branch',
            'save_employee',
            'delete_employee',
            'bulk_delete_employees',
            'save_sales',
            'delete_sales',
            'save_rules',
            'save_attendance',
            'generate_payroll',
            'recalc_payroll',
            'mark_paid',
            'mark_all_paid',
            'export_payroll',
            'save_payroll_adjustments',
            'save_settings',
            'global_search',
            'toggle_theme',
            'apply_annual_target_increase',
            'record_single_absence'
        ];

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_swvt_hr_' . $action, [ $this, $action ] );
        }
    }

    private function check_security() {
        check_ajax_referer( 'swvt_hr_nonce', 'nonce' );
        if ( ! current_user_can( SWVT_HR_CAP ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'swvt-hr' ) ], 403 );
        }
    }

    public function save_branch() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $name = sanitize_text_field( $_POST['name'] );
        $code = sanitize_text_field( $_POST['code'] );
        $city = sanitize_text_field( $_POST['city'] );
        $manager_id = isset( $_POST['manager_id'] ) ? absint( $_POST['manager_id'] ) : 0;
        $phone = sanitize_text_field( $_POST['phone'] );
        $address = sanitize_textarea_field( $_POST['address'] );
        $commission_rate = isset( $_POST['commission_rate'] ) && $_POST['commission_rate'] !== '' ? floatval( $_POST['commission_rate'] ) : null;
        $sales_target = isset( $_POST['sales_target'] ) ? floatval( $_POST['sales_target'] ) : 0.00;
        $status = in_array( $_POST['status'], [ 'active', 'inactive', 'maintenance' ] ) ? $_POST['status'] : 'active';
        $type = in_array( $_POST['type'], [ 'retail', 'warehouse', 'office', 'factory' ] ) ? $_POST['type'] : 'retail';
        $opening_date = ! empty( $_POST['opening_date'] ) ? sanitize_text_field( $_POST['opening_date'] ) : null;
        $region = sanitize_text_field( $_POST['region'] );
        $working_hours = sanitize_text_field( $_POST['working_hours'] );
        $google_maps_url = esc_url_raw( $_POST['google_maps_url'] );
        $email = sanitize_email( $_POST['email'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        $data = [
            'name'            => $name,
            'code'            => $code,
            'city'            => $city,
            'manager_id'      => $manager_id ? $manager_id : null,
            'phone'           => $phone,
            'address'         => $address,
            'commission_rate' => $commission_rate,
            'sales_target'    => $sales_target,
            'status'          => $status,
            'type'            => $type,
            'opening_date'    => $opening_date,
            'region'          => $region,
            'working_hours'   => $working_hours,
            'google_maps_url' => $google_maps_url,
            'email'           => $email,
            'notes'           => $notes,
            'updated_at'      => current_time( 'mysql' )
        ];

        if ( $id ) {
            $updated = $wpdb->update( $p . 'branches', $data, [ 'id' => $id ] );
            if ( false !== $updated ) {
                SWVT_HR_ERP_Service::log_activity( $id, 'manager_changed', 'Branch details updated via Settings/Edit form.' );
                SWVT_HR_ERP_Service::log_system_event( 'branch_updated', sprintf( 'Updated Branch: %s (%s)', $name, $code ), 'branches', $id );
                wp_send_json_success( [ 'message' => __( 'Branch updated successfully.', 'swvt-hr' ) ] );
            }
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $inserted = $wpdb->insert( $p . 'branches', $data );
            if ( $inserted ) {
                $new_id = $wpdb->insert_id;
                SWVT_HR_ERP_Service::log_activity( $new_id, 'manager_changed', 'New Branch registered in system.' );
                SWVT_HR_ERP_Service::log_system_event( 'branch_created', sprintf( 'Created Branch: %s (%s)', $name, $code ), 'branches', $new_id );
                wp_send_json_success( [ 'message' => __( 'Branch added successfully.', 'swvt-hr' ) ] );
            }
        }

        wp_send_json_error( [ 'message' => __( 'Failed to save branch details.', 'swvt-hr' ) ] );
    }

    public function delete_branch() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = absint( $_POST['id'] );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid branch ID.', 'swvt-hr' ) ] );
        }

        $branch_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$p}branches WHERE id = %d", $id ) );
        $deleted = $wpdb->delete( $p . 'branches', [ 'id' => $id ] );
        if ( $deleted ) {
            SWVT_HR_ERP_Service::log_system_event( 'branch_deleted', sprintf( 'Deleted Branch: %s', $branch_name ), 'branches', $id );
            wp_send_json_success( [ 'message' => __( 'Branch deleted successfully.', 'swvt-hr' ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Failed to delete branch.', 'swvt-hr' ) ] );
    }

    public function save_employee() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $branch_id = absint( $_POST['branch_id'] );
        $full_name = sanitize_text_field( $_POST['full_name'] );
        $code = sanitize_text_field( $_POST['code'] );
        $job_title = sanitize_text_field( $_POST['job_title'] );
        $role_key = sanitize_text_field( $_POST['role_key'] );
        $department = sanitize_text_field( $_POST['department'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        $email = sanitize_email( $_POST['email'] );
        $national_id = sanitize_text_field( $_POST['national_id'] );
        $hire_date = sanitize_text_field( $_POST['hire_date'] );
        $basic_salary = floatval( $_POST['basic_salary'] );
        $commission_eligible = isset( $_POST['commission_eligible'] ) ? 1 : 0;
        $commission_share = isset( $_POST['commission_share'] ) && $_POST['commission_share'] !== '' ? floatval( $_POST['commission_share'] ) : null;
        $status = in_array( $_POST['status'], [ 'active', 'inactive' ] ) ? $_POST['status'] : 'active';
        $notes = sanitize_textarea_field( $_POST['notes'] );

        $data = [
            'branch_id' => $branch_id ? $branch_id : null,
            'full_name' => $full_name,
            'code' => $code,
            'job_title' => $job_title,
            'role_key' => $role_key,
            'department' => $department,
            'phone' => $phone,
            'email' => $email,
            'national_id' => $national_id,
            'hire_date' => $hire_date ? $hire_date : null,
            'basic_salary' => $basic_salary,
            'commission_eligible' => $commission_eligible,
            'commission_share' => $commission_share,
            'status' => $status,
            'notes' => $notes,
            'updated_at' => current_time( 'mysql' )
        ];

        if ( $id ) {
            $updated = $wpdb->update( $p . 'employees', $data, [ 'id' => $id ] );
            if ( false !== $updated ) {
                SWVT_HR_ERP_Service::log_system_event( 'employee_updated', sprintf( 'Updated Employee: %s (%s)', $full_name, $code ), 'employees', $id );
                wp_send_json_success( [ 'message' => __( 'Employee details updated successfully.', 'swvt-hr' ) ] );
            }
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $inserted = $wpdb->insert( $p . 'employees', $data );
            if ( $inserted ) {
                $new_id = $wpdb->insert_id;
                SWVT_HR_ERP_Service::log_system_event( 'employee_created', sprintf( 'Created Employee: %s (%s)', $full_name, $code ), 'employees', $new_id );
                wp_send_json_success( [ 'message' => __( 'Employee added successfully.', 'swvt-hr' ) ] );
            }
        }

        wp_send_json_error( [ 'message' => __( 'Failed to save employee details.', 'swvt-hr' ) ] );
    }

    public function save_sales() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $branch_id = absint( $_POST['branch_id'] );
        $month = absint( $_POST['period_month'] );
        $year = absint( $_POST['period_year'] );
        $total_sales = floatval( $_POST['total_sales'] );
        $target = floatval( $_POST['target'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        // Retrieve active rate from DB
        $branch = SWVT_HR_Branch::get( $branch_id );
        $settings = get_option( 'swvt_hr_settings' );
        $default_rate = isset( $settings['default_commission_rate'] ) ? (float) $settings['default_commission_rate'] : 0.0020;
        $rate = $default_rate;
        if ( $branch && ! is_null( $branch->commission_rate ) && $branch->commission_rate > 0 ) {
            $rate = (float) $branch->commission_rate;
        }

        $commission_base = SWVT_HR_Commission_Service::base( $total_sales, $rate );

        $sales_data = [
            'branch_id' => $branch_id,
            'period_month' => $month,
            'period_year' => $year,
            'total_sales' => $total_sales,
            'target' => $target,
            'commission_rate' => $rate,
            'commission_base' => $commission_base,
            'notes' => $notes,
            'created_at' => current_time( 'mysql' )
        ];

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$p}sales (branch_id, period_month, period_year, total_sales, target, commission_rate, commission_base, notes, created_at)
             VALUES (%d, %d, %d, %f, %f, %f, %f, %s, %s)
             ON DUPLICATE KEY UPDATE
                total_sales = VALUES(total_sales),
                target = VALUES(target),
                commission_rate = VALUES(commission_rate),
                commission_base = VALUES(commission_base),
                notes = VALUES(notes)",
            $branch_id, $month, $year, $total_sales, $target, $rate, $commission_base, $notes, $sales_data['created_at']
        ) );

        $sales_row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}sales WHERE branch_id = %d AND period_month = %d AND period_year = %d",
            $branch_id, $month, $year
        ) );

        if ( ! $sales_row ) {
            wp_send_json_error( [ 'message' => __( 'Failed to save sales record.', 'swvt-hr' ) ] );
        }

        // Delete old commissions
        $wpdb->delete( $p . 'commissions', [ 'sales_id' => $sales_row->id ] );

        // Generate and bulk insert commissions
        $rows = SWVT_HR_Commission_Service::distribute( $sales_row );
        foreach ( $rows as $row ) {
            $wpdb->insert( $p . 'commissions', $row );
        }

        // Flush transients
        SWVT_HR_Report_Service::clear_cache( $month, $year );

        wp_send_json_success( [ 'message' => __( 'Sales record saved and commissions distributed successfully.', 'swvt-hr' ) ] );
    }

    public function delete_sales() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = absint( $_POST['id'] );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid sales record ID.', 'swvt-hr' ) ] );
        }

        // Fetch sales row to flush transients
        $sales = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}sales WHERE id = %d", $id ) );
        if ( ! $sales ) {
            wp_send_json_error( [ 'message' => __( 'Sales record not found.', 'swvt-hr' ) ] );
        }

        // Delete sales row & commissions
        $wpdb->delete( $p . 'sales', [ 'id' => $id ] );
        $wpdb->delete( $p . 'commissions', [ 'sales_id' => $id ] );

        // Clear report cache for that period
        SWVT_HR_Report_Service::clear_cache( $sales->period_month, $sales->period_year );

        wp_send_json_success( [ 'message' => __( 'Sales record and associated commissions deleted.', 'swvt-hr' ) ] );
    }

    public function save_rules() {
        $this->check_security();
        
        $default_rate = floatval( $_POST['default_commission_rate'] );
        $dist = [
            'manager'    => floatval( $_POST['role_dist']['manager'] ),
            'accountant' => floatval( $_POST['role_dist']['accountant'] ),
            'delivery'   => floatval( $_POST['role_dist']['delivery'] ),
            'prep'       => floatval( $_POST['role_dist']['prep'] ),
        ];

        if ( ! SWVT_HR_Commission_Service::is_valid_distribution( $dist ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid total percentage! The role distribution sum must equal exactly 100%.', 'swvt-hr' ) ] );
        }

        $settings = get_option( 'swvt_hr_settings' );
        $settings['default_commission_rate'] = $default_rate;
        $settings['role_distribution'] = $dist;
        update_option( 'swvt_hr_settings', $settings );

        // Save overrides
        if ( isset( $_POST['branch_overrides'] ) && is_array( $_POST['branch_overrides'] ) ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            foreach ( $_POST['branch_overrides'] as $b_id => $b_rate ) {
                $override_rate = $b_rate === '' ? null : floatval( $b_rate );
                $wpdb->update( $p . 'branches', [ 'commission_rate' => $override_rate ], [ 'id' => absint( $b_id ) ] );
            }
        }

        // Save eligibility
        if ( isset( $_POST['employee_eligibility'] ) && is_array( $_POST['employee_eligibility'] ) ) {
            global $wpdb;
            $p = $wpdb->prefix . 'swvt_hr_';
            foreach ( $_POST['employee_eligibility'] as $emp_id => $val ) {
                $wpdb->update( $p . 'employees', [ 'commission_eligible' => absint( $val ) ], [ 'id' => absint( $emp_id ) ] );
            }
        }

        wp_send_json_success( [ 'message' => __( 'Commission rules and defaults updated successfully.', 'swvt-hr' ) ] );
    }

    public function save_attendance() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $employee_id = absint( $_POST['employee_id'] );
        $month = absint( $_POST['period_month'] );
        $year = absint( $_POST['period_year'] );
        $absence_days = floatval( $_POST['absence_days'] );
        $late_hours = floatval( $_POST['late_hours'] );
        $reason = sanitize_text_field( $_POST['reason'] );
        $notes = sanitize_textarea_field( $_POST['notes'] );

        $emp = $wpdb->get_row( $wpdb->prepare( "SELECT basic_salary, branch_id FROM {$p}employees WHERE id = %d", $employee_id ) );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'Employee not found.', 'swvt-hr' ) ] );
        }

        $deduction = SWVT_HR_Payroll_Service::absence_deduction( $emp->basic_salary, $absence_days );

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$p}attendance (employee_id, branch_id, period_month, period_year, absence_days, late_hours, deduction, reason, notes)
             VALUES (%d, %d, %d, %d, %f, %f, %f, %s, %s)
             ON DUPLICATE KEY UPDATE
                absence_days = VALUES(absence_days),
                late_hours = VALUES(late_hours),
                deduction = VALUES(deduction),
                reason = VALUES(reason),
                notes = VALUES(notes)",
            $employee_id, $emp->branch_id, $month, $year, $absence_days, $late_hours, $deduction, $reason, $notes
        ) );

        wp_send_json_success( [ 'message' => __( 'Attendance record and deduction saved successfully.', 'swvt-hr' ) ] );
    }

    public function generate_payroll() {
        $this->check_security();
        $month = absint( $_POST['month'] );
        $year = absint( $_POST['year'] );
        $branch_id = isset( $_POST['branch'] ) ? absint( $_POST['branch'] ) : 0;

        SWVT_HR_Payroll_Service::generate( $month, $year, $branch_id );
        SWVT_HR_Report_Service::clear_cache( $month, $year );

        SWVT_HR_ERP_Service::log_system_event( 'payroll_generated', sprintf( 'Generated Payroll for %d/%d (Branch ID: %d)', $month, $year, $branch_id ), 'payroll', $branch_id );
        wp_send_json_success( [ 'message' => __( 'Payroll sheet generated successfully.', 'swvt-hr' ) ] );
    }

    public function recalc_payroll() {
        $this->check_security();
        $month = absint( $_POST['month'] );
        $year = absint( $_POST['year'] );
        $branch_id = isset( $_POST['branch'] ) ? absint( $_POST['branch'] ) : 0;

        SWVT_HR_Payroll_Service::generate( $month, $year, $branch_id );
        SWVT_HR_Report_Service::clear_cache( $month, $year );

        SWVT_HR_ERP_Service::log_system_event( 'payroll_recalculated', sprintf( 'Recalculated Payroll for %d/%d (Branch ID: %d)', $month, $year, $branch_id ), 'payroll', $branch_id );
        wp_send_json_success( [ 'message' => __( 'Payroll recalculated successfully.', 'swvt-hr' ) ] );
    }

    public function mark_paid() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = absint( $_POST['id'] );
        $wpdb->update(
            $p . 'payroll',
            [ 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ],
            [ 'id' => $id ]
        );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT employee_id, period_month, period_year FROM {$p}payroll WHERE id = %d", $id ) );
        if ( $row ) {
            $emp_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $row->employee_id ) );
            SWVT_HR_ERP_Service::log_system_event( 'payroll_paid', sprintf( 'Marked Payroll as Paid for %s - %d/%d', $emp_name, $row->period_month, $row->period_year ), 'payroll', $id );
        }

        wp_send_json_success( [ 'message' => __( 'Payroll record marked as paid.', 'swvt-hr' ) ] );
    }

    public function mark_all_paid() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $month = absint( $_POST['month'] );
        $year = absint( $_POST['year'] );
        $branch_id = isset( $_POST['branch'] ) ? absint( $_POST['branch'] ) : 0;

        $where = [
            'period_month' => $month,
            'period_year' => $year,
            'status' => 'pending'
        ];
        if ( $branch_id ) {
            $where['branch_id'] = $branch_id;
        }

        $wpdb->update(
            $p . 'payroll',
            [ 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ],
            $where
        );

        wp_send_json_success( [ 'message' => __( 'All pending payroll entries marked as paid.', 'swvt-hr' ) ] );
    }

    public function save_payroll_adjustments() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = absint( $_POST['id'] );
        $bonus = floatval( $_POST['bonus'] );
        $other = floatval( $_POST['other_deduction'] );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$p}payroll WHERE id = %d", $id ) );
        if ( ! $row ) {
            wp_send_json_error( [ 'message' => __( 'Payroll record not found.', 'swvt-hr' ) ] );
        }

        $net = round( $row->basic_salary + $row->commission + $bonus - $row->absence_deduction - $other, 2 );

        $updated = $wpdb->update(
            $p . 'payroll',
            [
                'bonus' => $bonus,
                'other_deduction' => $other,
                'net_salary' => $net
            ],
            [ 'id' => $id ]
        );

        if ( false !== $updated ) {
            wp_send_json_success( [ 'message' => __( 'Adjustments saved successfully.', 'swvt-hr' ), 'net_salary' => $net ] );
        }

        wp_send_json_error( [ 'message' => __( 'Failed to save adjustments.', 'swvt-hr' ) ] );
    }

    public function save_settings() {
        $this->check_security();
        $default_rate = floatval( $_POST['default_commission_rate'] );
        $divisor = absint( $_POST['daily_salary_divisor'] );
        $currency = sanitize_text_field( $_POST['currency'] );
        $amount_decimals = isset( $_POST['amount_decimals'] ) ? absint( $_POST['amount_decimals'] ) : 2;
        $decimal_separator = isset( $_POST['decimal_separator'] ) ? sanitize_text_field( $_POST['decimal_separator'] ) : '.';
        $thousands_separator = isset( $_POST['thousands_separator'] ) ? sanitize_text_field( $_POST['thousands_separator'] ) : ',';

        if ( isset( $_POST['job_titles_json'] ) ) {
            $job_titles_decoded = json_decode( stripslashes( $_POST['job_titles_json'] ), true );
            $job_titles = [];
            if ( is_array( $job_titles_decoded ) ) {
                foreach ( $job_titles_decoded as $title ) {
                    if ( isset( $title['id'] ) && isset( $title['name'] ) ) {
                        $job_titles[] = [
                            'id' => sanitize_key( $title['id'] ),
                            'name' => sanitize_text_field( $title['name'] ),
                            'parent' => isset( $title['parent'] ) ? sanitize_key( $title['parent'] ) : ''
                        ];
                    }
                }
            }
        } else {
            $job_titles_raw = isset( $_POST['job_titles'] ) ? sanitize_textarea_field( $_POST['job_titles'] ) : '';
            $job_titles = array_values( array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $job_titles_raw ) ) ) ) );
        }

        if ( isset( $_POST['departments_json'] ) ) {
            $departments_decoded = json_decode( stripslashes( $_POST['departments_json'] ), true );
            $departments = [];
            if ( is_array( $departments_decoded ) ) {
                foreach ( $departments_decoded as $dept ) {
                    if ( isset( $dept['id'] ) && isset( $dept['name'] ) ) {
                        $departments[] = [
                            'id' => sanitize_key( $dept['id'] ),
                            'name' => sanitize_text_field( $dept['name'] ),
                            'parent' => isset( $dept['parent'] ) ? sanitize_key( $dept['parent'] ) : ''
                        ];
                    }
                }
            }
        } else {
            $departments_raw = isset( $_POST['departments'] ) ? sanitize_textarea_field( $_POST['departments'] ) : '';
            $departments = array_values( array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", "", $departments_raw ) ) ) ) );
        }

        $manager_pct = isset( $_POST['role_dist_manager'] ) ? floatval( $_POST['role_dist_manager'] ) : 60;
        $accountant_pct = isset( $_POST['role_dist_accountant'] ) ? floatval( $_POST['role_dist_accountant'] ) : 20;
        $delivery_pct = isset( $_POST['role_dist_delivery'] ) ? floatval( $_POST['role_dist_delivery'] ) : 10;
        $prep_pct = isset( $_POST['role_dist_prep'] ) ? floatval( $_POST['role_dist_prep'] ) : 10;

        $absence_rate = isset( $_POST['absence_deduction_rate'] ) ? floatval( $_POST['absence_deduction_rate'] ) : 0;

        if ( $divisor <= 0 ) {
            $divisor = 30;
        }

        if ( $amount_decimals > 4 ) {
            $amount_decimals = 4;
        }

        $allowed_separators = [ '.', ',', ' ', '' ];
        if ( ! in_array( $decimal_separator, $allowed_separators, true ) ) {
            $decimal_separator = '.';
        }
        if ( ! in_array( $thousands_separator, $allowed_separators, true ) ) {
            $thousands_separator = ',';
        }
        if ( $decimal_separator === $thousands_separator && '' !== $decimal_separator ) {
            $thousands_separator = ',';
            if ( $decimal_separator === ',' ) {
                $thousands_separator = '.';
            }
        }

        $settings = get_option( 'swvt_hr_settings' );
        $settings['default_commission_rate'] = $default_rate;
        $settings['daily_salary_divisor'] = $divisor;
        $settings['currency'] = $currency;
        $settings['amount_decimals'] = $amount_decimals;
        $settings['decimal_separator'] = $decimal_separator;
        $settings['thousands_separator'] = $thousands_separator;
        $settings['job_titles'] = $job_titles;
        $settings['departments'] = $departments;
        $settings['role_distribution'] = [
            'manager'    => $manager_pct,
            'accountant' => $accountant_pct,
            'delivery'   => $delivery_pct,
            'prep'       => $prep_pct
        ];
        $settings['absence_deduction_rate'] = $absence_rate;

        update_option( 'swvt_hr_settings', $settings );
        wp_send_json_success( [ 'message' => __( 'Settings updated successfully.', 'swvt-hr' ) ] );
    }

    public function export_payroll() {
        if ( ! current_user_can( SWVT_HR_CAP ) ) {
            wp_die( __( 'You are not allowed to perform this action.', 'swvt-hr' ) );
        }

        $month = absint( $_GET['month'] );
        $year = absint( $_GET['year'] );
        $branch_id = isset( $_GET['branch'] ) ? absint( $_GET['branch'] ) : 0;

        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $sql = "SELECT pay.*, emp.full_name, emp.job_title, b.name as branch_name 
                FROM {$p}payroll pay
                JOIN {$p}employees emp ON emp.id = pay.employee_id
                JOIN {$p}branches b ON b.id = pay.branch_id
                WHERE pay.period_month = %d AND pay.period_year = %d";
        
        if ( $branch_id ) {
            $sql .= $wpdb->prepare( " AND pay.branch_id = %d", $branch_id );
        }

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $month, $year ) );

        $filename = sprintf( 'payroll-%d-%d.csv', $month, $year );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );
        fwrite( $output, "\xEF\xBB\xBF" ); // UTF-8 BOM

        // English column headers
        fputcsv( $output, [
            'Employee',
            'Job Title',
            'Branch',
            'Basic Salary',
            'Commission',
            'Absence Deduction',
            'Bonus',
            'Other Deduction',
            'Net Salary',
            'Status'
        ] );

        foreach ( $rows as $row ) {
            $status_lbl = $row->status === 'paid' ? 'Paid' : ( $row->status === 'pending' ? 'Pending' : 'On Hold' );
            fputcsv( $output, [
                $row->full_name,
                $row->job_title,
                $row->branch_name,
                $row->basic_salary,
                $row->commission,
                $row->absence_deduction,
                $row->bonus,
                $row->other_deduction,
                $row->net_salary,
                $status_lbl
            ] );
        }

        fclose( $output );
        exit;
    }

    public function delete_employee() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $id = absint( $_POST['id'] );
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => __( 'Invalid employee ID.', 'swvt-hr' ) ] );
        }

        $emp_name = $wpdb->get_var( $wpdb->prepare( "SELECT full_name FROM {$p}employees WHERE id = %d", $id ) );
        $deleted = $wpdb->delete( $p . 'employees', [ 'id' => $id ] );
        if ( $deleted ) {
            SWVT_HR_ERP_Service::log_system_event( 'employee_deleted', sprintf( 'Deleted Employee: %s', $emp_name ), 'employees', $id );
            wp_send_json_success( [ 'message' => __( 'Employee deleted successfully.', 'swvt-hr' ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Failed to delete employee.', 'swvt-hr' ) ] );
    }

    public function bulk_delete_employees() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', $_POST['ids'] ) : [];
        if ( empty( $ids ) ) {
            wp_send_json_error( [ 'message' => __( 'No employees selected.', 'swvt-hr' ) ] );
        }

        $ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $query = $wpdb->prepare( "DELETE FROM {$p}employees WHERE id IN ($ids_placeholder)", $ids );

        $deleted = $wpdb->query( $query );
        if ( false !== $deleted ) {
            wp_send_json_success( [ 'message' => sprintf( __( 'Successfully deleted %d employees.', 'swvt-hr' ), $deleted ) ] );
        }

        wp_send_json_error( [ 'message' => __( 'Failed to bulk delete employees.', 'swvt-hr' ) ] );
    }

    public function global_search() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';
        if ( strlen( $query ) < 2 ) {
            wp_send_json_success( [] );
        }

        $like = '%' . $wpdb->esc_like( $query ) . '%';
        $results = [];

        // 1. Search Branches
        $branches = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name, code, city FROM {$p}branches WHERE name LIKE %s OR code LIKE %s OR city LIKE %s LIMIT 5",
            $like, $like, $like
        ) );
        foreach ( $branches as $b ) {
            $results[] = [
                'type' => 'Branch',
                'title' => esc_html( $b->name ) . ' (' . esc_html( $b->code ) . ')',
                'desc' => sprintf( 'Branch located in %s', esc_html( $b->city ) ),
                'link' => admin_url( 'admin.php?page=swvt-hr-branch-profile&id=' . $b->id )
            ];
        }

        // 2. Search Employees
        $employees = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, full_name, code, job_title, department FROM {$p}employees WHERE full_name LIKE %s OR code LIKE %s OR job_title LIKE %s LIMIT 5",
            $like, $like, $like
        ) );
        foreach ( $employees as $emp ) {
            $results[] = [
                'type' => 'Employee',
                'title' => esc_html( $emp->full_name ) . ' (' . esc_html( $emp->code ) . ')',
                'desc' => sprintf( '%s in %s department', esc_html( $emp->job_title ), esc_html( $emp->department ) ),
                'link' => admin_url( 'admin.php?page=swvt-hr-employee-profile&id=' . $emp->id )
            ];
        }

        // 3. Search Sales Entries (by notes or date)
        $sales = $wpdb->get_results( $wpdb->prepare(
            "SELECT se.id, se.entry_date, se.amount, b.name as branch_name 
             FROM {$p}sales_entries se 
             JOIN {$p}branches b ON se.branch_id = b.id 
             WHERE se.notes LIKE %s OR se.entry_date LIKE %s LIMIT 3",
            $like, $like
        ) );
        foreach ( $sales as $s ) {
            $results[] = [
                'type' => 'Sales Entry',
                'title' => sprintf( 'Sales: %s EGP on %s', number_format( $s->amount, 2 ), esc_html( $s->entry_date ) ),
                'desc' => sprintf( 'Logged for %s', esc_html( $s->branch_name ) ),
                'link' => admin_url( 'admin.php?page=swvt-hr-sales&branch=' . $s->id )
            ];
        }

        wp_send_json_success( $results );
    }

    public function toggle_theme() {
        $this->check_security();
        $theme = isset( $_POST['theme'] ) && $_POST['theme'] === 'dark' ? 'dark' : 'light';
        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'swvt_hr_theme', $theme );
        wp_send_json_success( [ 'message' => sprintf( 'Theme updated to %s.', $theme ) ] );
    }

    public function apply_annual_target_increase() {
        $this->check_security();
        
        $year = isset( $_POST['year'] ) ? absint( $_POST['year'] ) : 0;
        $increases = isset( $_POST['increases'] ) ? $_POST['increases'] : [];

        if ( $year < 2000 || empty( $increases ) || ! is_array( $increases ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid parameters provided.', 'swvt-hr' ) ] );
        }

        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $success_count = 0;

        foreach ( $increases as $branch_id => $pct ) {
            $branch_id = absint( $branch_id );
            $pct = floatval( $pct );

            if ( $pct == 0 ) {
                continue;
            }

            // Get branch baseline target
            $branch = $wpdb->get_row( $wpdb->prepare(
                "SELECT sales_target FROM {$p}branches WHERE id = %d",
                $branch_id
            ) );

            if ( ! $branch ) {
                continue;
            }

            $baseline_target = (float) $branch->sales_target;

            for ( $m = 1; $m <= 12; $m++ ) {
                // Try to find target for the selected year
                $current_target = $wpdb->get_var( $wpdb->prepare(
                    "SELECT sales_target FROM {$p}branch_targets 
                     WHERE branch_id = %d AND target_type = 'monthly' AND period_value = %d AND period_year = %d",
                    $branch_id, $m, $year
                ) );

                if ( null !== $current_target ) {
                    $base = (float) $current_target;
                } else {
                    // Try to find target for the previous year
                    $prev_target = $wpdb->get_var( $wpdb->prepare(
                        "SELECT sales_target FROM {$p}branch_targets 
                         WHERE branch_id = %d AND target_type = 'monthly' AND period_value = %d AND period_year = %d",
                        $branch_id, $m, $year - 1
                    ) );

                    if ( null !== $prev_target ) {
                        $base = (float) $prev_target;
                    } else {
                        $base = $baseline_target;
                    }
                }

                $new_target = round( $base * ( 1 + $pct / 100 ), 2 );

                SWVT_HR_ERP_Service::save_target( $branch_id, 'monthly', $m, $year, $new_target );
            }

            $success_count++;
        }

        wp_send_json_success( [
            'message' => sprintf( __( 'Successfully updated targets for %d branch(es) for the year %d.', 'swvt-hr' ), $success_count, $year )
        ] );
    }

    public function record_single_absence() {
        $this->check_security();
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        $employee_id = absint( $_POST['employee_id'] );
        $month       = absint( $_POST['period_month'] );
        $year        = absint( $_POST['period_year'] );
        $day         = absint( $_POST['day'] );
        $weight      = floatval( $_POST['weight'] );
        $reason      = sanitize_text_field( $_POST['reason'] );

        $emp = $wpdb->get_row( $wpdb->prepare( "SELECT basic_salary, branch_id FROM {$p}employees WHERE id = %d", $employee_id ) );
        if ( ! $emp ) {
            wp_send_json_error( [ 'message' => __( 'Employee not found.', 'swvt-hr' ) ] );
        }

        // 1. Fetch existing attendance record
        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$p}attendance WHERE employee_id = %d AND period_month = %d AND period_year = %d",
            $employee_id, $month, $year
        ) );

        $details = [];
        $late_hours = 0.0;
        if ( $existing ) {
            $late_hours = (float) $existing->late_hours;
            $decoded = json_decode( $existing->notes, true );
            if ( is_array( $decoded ) ) {
                $details = $decoded;
            }
        }

        // 2. Update the specific day
        if ( $weight <= 0 ) {
            unset( $details[$day] );
        } else {
            $details[$day] = [
                'weight' => $weight,
                'reason' => $reason
            ];
        }

        // 3. Recalculate total absence days
        $total_absence = 0.0;
        foreach ( $details as $d => $info ) {
            $total_absence += floatval( $info['weight'] );
        }

        // 4. Calculate deduction
        $deduction = SWVT_HR_Payroll_Service::absence_deduction( $emp->basic_salary, $total_absence );

        // 5. Save back to database
        $notes = json_encode( $details );

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO {$p}attendance (employee_id, branch_id, period_month, period_year, absence_days, late_hours, deduction, reason, notes)
             VALUES (%d, %d, %d, %d, %f, %f, %f, %s, %s)
             ON DUPLICATE KEY UPDATE
                absence_days = VALUES(absence_days),
                deduction = VALUES(deduction),
                notes = VALUES(notes)",
            $employee_id, $emp->branch_id, $month, $year, $total_absence, $late_hours, $deduction, '', $notes
        ) );

        wp_send_json_success( [
            'message' => __( 'Absence recorded successfully.', 'swvt-hr' )
        ] );
    }
}
