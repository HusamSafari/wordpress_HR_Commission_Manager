<?php
/**
 * Admin Menu Class (English).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Menu {

    public function register_menus() {
        // 1. Dashboard top-level
        add_menu_page(
            __( 'HR Dashboard', 'swvt-hr' ),
            __( 'HR Dashboard', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr',
            [ $this, 'load_view_dashboard' ],
            'dashicons-dashboard',
            25.001
        );
        add_submenu_page(
            'swvt-hr',
            __( 'Administrative Dashboard', 'swvt-hr' ),
            __( 'Dashboard', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr',
            [ $this, 'load_view_dashboard' ]
        );

        // 2. Branches top-level
        add_menu_page(
            __( 'Branches Manager', 'swvt-hr' ),
            __( 'HR Branches', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-branches',
            [ $this, 'load_view_branches' ],
            'dashicons-store',
            25.002
        );
        add_submenu_page(
            'swvt-hr-branches',
            __( 'Branches Directory', 'swvt-hr' ),
            __( 'All Branches', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-branches',
            [ $this, 'load_view_branches' ]
        );
        add_submenu_page(
            'swvt-hr-branches',
            __( 'Add New Branch', 'swvt-hr' ),
            __( 'Add Branch', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-branches-add',
            [ $this, 'load_view_branches_add' ]
        );
        add_submenu_page(
            'swvt-hr-branches',
            __( 'Branch Profile Details', 'swvt-hr' ),
            __( 'Branch Profile', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-branch-profile',
            [ $this, 'load_view_branch_profile' ]
        );
        // Hidden edit branch page
        add_submenu_page(
            null,
            __( 'Edit Branch Details', 'swvt-hr' ),
            __( 'Edit Branch', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-branches-edit',
            [ $this, 'load_view_branch_edit' ]
        );

        // 3. Employees top-level
        add_menu_page(
            __( 'Employees Registry', 'swvt-hr' ),
            __( 'HR Employees', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employees',
            [ $this, 'load_view_employees' ],
            'dashicons-groups',
            25.003
        );
        add_submenu_page(
            'swvt-hr-employees',
            __( 'Employees List', 'swvt-hr' ),
            __( 'All Employees', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employees',
            [ $this, 'load_view_employees' ]
        );
        add_submenu_page(
            'swvt-hr-employees',
            __( 'Add New Employee', 'swvt-hr' ),
            __( 'Add Employee', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employees-add',
            [ $this, 'load_view_employees_add' ]
        );
        add_submenu_page(
            'swvt-hr-employees',
            __( 'Employee Profile Card', 'swvt-hr' ),
            __( 'Employee Profile', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employee-profile',
            [ $this, 'load_view_employee_profile' ]
        );
        // Hidden edit employee page
        add_submenu_page(
            null,
            __( 'Edit Employee Profile', 'swvt-hr' ),
            __( 'Edit Employee', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employees-edit',
            [ $this, 'load_view_employee_edit' ]
        );

        // 4. Sales top-level
        add_menu_page(
            __( 'Sales Targets & Registry', 'swvt-hr' ),
            __( 'HR Sales', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-sales',
            [ $this, 'load_view_sales' ],
            'dashicons-chart-bar',
            25.004
        );
        add_submenu_page(
            'swvt-hr-sales',
            __( 'Sales Targets Input', 'swvt-hr' ),
            __( 'Sales Targets', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-sales',
            [ $this, 'load_view_sales' ]
        );

        // 5. Payroll top-level
        add_menu_page(
            __( 'Payroll & Wages Ledger', 'swvt-hr' ),
            __( 'HR Payroll', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-payroll',
            [ $this, 'load_view_payroll' ],
            'dashicons-media-spreadsheet',
            25.005
        );
        add_submenu_page(
            'swvt-hr-payroll',
            __( 'Payroll Processing', 'swvt-hr' ),
            __( 'Payroll Ledger', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-payroll',
            [ $this, 'load_view_payroll' ]
        );
        add_submenu_page(
            'swvt-hr-payroll',
            __( 'Employee Payroll Tracker', 'swvt-hr' ),
            __( 'Employee Payroll', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-employee-payroll',
            [ $this, 'load_view_employee_payroll' ]
        );
        add_submenu_page(
            'swvt-hr-payroll',
            __( 'Salary Appraisal & Cost of Living', 'swvt-hr' ),
            __( 'Salary Appraisal', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-salary-appraisal',
            [ $this, 'load_view_salary_appraisal' ]
        );

        // 6. Commissions top-level
        add_menu_page(
            __( 'Commissions Calculations', 'swvt-hr' ),
            __( 'HR Commissions', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-commissions',
            [ $this, 'load_view_commissions' ],
            'dashicons-awards',
            25.006
        );
        add_submenu_page(
            'swvt-hr-commissions',
            __( 'Distributed Commissions List', 'swvt-hr' ),
            __( 'Distributed Commissions', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-commissions',
            [ $this, 'load_view_commissions' ]
        );
        add_submenu_page(
            'swvt-hr-commissions',
            __( 'Commission Rules', 'swvt-hr' ),
            __( 'Commission Rules', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-commission-rules-calc',
            [ $this, 'load_view_rules' ]
        );

        // 7. Attendance top-level
        add_menu_page(
            __( 'Attendance & Absence Logs', 'swvt-hr' ),
            __( 'HR Attendance', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-attendance',
            [ $this, 'load_view_attendance' ],
            'dashicons-welcome-write-paper',
            25.007
        );
        add_submenu_page(
            'swvt-hr-attendance',
            __( 'Attendance Tracking Logs', 'swvt-hr' ),
            __( 'Attendance Tracking', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-attendance',
            [ $this, 'load_view_attendance' ]
        );

        // 8. Performance top-level
        add_menu_page(
            __( 'Performance Analytics', 'swvt-hr' ),
            __( 'HR Performance', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-performance',
            [ $this, 'load_view_performance' ],
            'dashicons-chart-area',
            25.008
        );
        add_submenu_page(
            'swvt-hr-performance',
            __( 'Performance Summary & Rankings', 'swvt-hr' ),
            __( 'Performance', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-performance',
            [ $this, 'load_view_performance' ]
        );

        // 9. Reports top-level
        add_menu_page(
            __( 'Reports & Analytics Center', 'swvt-hr' ),
            __( 'HR Reports', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-reports',
            [ $this, 'load_view_reports' ],
            'dashicons-chart-pie',
            25.009
        );
        add_submenu_page(
            'swvt-hr-reports',
            __( 'Reports & Operational Analytics', 'swvt-hr' ),
            __( 'Operational Reports', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-reports',
            [ $this, 'load_view_reports' ]
        );
        add_submenu_page(
            'swvt-hr-reports',
            __( 'System Activity Logs', 'swvt-hr' ),
            __( 'Activity Logs', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-activity-logs',
            [ $this, 'load_view_activity_logs' ]
        );

        // 10. Settings top-level
        add_menu_page(
            __( 'Settings & Configuration Dashboard', 'swvt-hr' ),
            __( 'HR Settings', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-settings',
            [ $this, 'load_view_settings' ],
            'dashicons-admin-generic',
            25.010
        );
        add_submenu_page(
            'swvt-hr-settings',
            __( 'Settings Configuration', 'swvt-hr' ),
            __( 'Settings', 'swvt-hr' ),
            SWVT_HR_CAP,
            'swvt-hr-settings',
            [ $this, 'load_view_settings' ]
        );
    }

    public function load_view_dashboard() {
        $this->load_view( 'dashboard' );
    }

    public function load_view_branches() {
        $this->load_view( 'branches' );
    }

    public function load_view_branches_add() {
        $this->load_view( 'branch-edit' );
    }

    public function load_view_branch_profile() {
        $this->load_view( 'branch-profile' );
    }

    public function load_view_branch_edit() {
        $this->load_view( 'branch-edit' );
    }

    public function load_view_employees() {
        $this->load_view( 'employees' );
    }

    public function load_view_employees_add() {
        $this->load_view( 'employee-edit' );
    }

    public function load_view_employee_profile() {
        $this->load_view( 'employee-profile' );
    }

    public function load_view_employee_edit() {
        $this->load_view( 'employee-edit' );
    }

    public function load_view_sales() {
        $this->load_view( 'sales' );
    }

    public function load_view_rules() {
        $this->load_view( 'commission-rules' );
    }

    public function load_view_attendance() {
        $this->load_view( 'attendance' );
    }

    public function load_view_payroll() {
        $this->load_view( 'payroll' );
    }

    public function load_view_employee_payroll() {
        $this->load_view( 'employee-payroll' );
    }

    public function load_view_salary_appraisal() {
        $this->load_view( 'salary-appraisal' );
    }

    public function load_view_commissions() {
        $this->load_view( 'commissions-view' );
    }

    public function load_view_performance() {
        $_GET['tab'] = 'perf-rank';
        $this->load_view( 'reports' );
    }

    public function load_view_reports() {
        $this->load_view( 'reports' );
    }

    public function load_view_settings() {
        $this->load_view( 'settings' );
    }

    public function load_view_activity_logs() {
        $this->load_view( 'activity-log' );
    }

    private function load_view( $view_name ) {
        if ( ! current_user_can( SWVT_HR_CAP ) ) {
            wp_die( __( 'You are not allowed to view this page.', 'swvt-hr' ) );
        }

        $this->render_erp_header();

        $view_file = SWVT_HR_DIR . 'admin/views/' . $view_name . '.php';
        if ( file_exists( $view_file ) ) {
            include $view_file;
        } else {
            echo '<div class="wrap"><h2>' . esc_html__( 'Error: View not found.', 'swvt-hr' ) . '</h2></div>';
        }

        $this->render_erp_footer();
    }

    private function render_erp_header() {
        $user_id = get_current_user_id();
        $theme = get_user_meta( $user_id, 'swvt_hr_theme', true );
        if ( ! $theme ) {
            $theme = 'light';
        }
        ?>
        <div class="swvt-hr-erp-shell <?php echo $theme === 'dark' ? 'swvt-hr-dark' : ''; ?>" id="swvt-hr-erp-container">
            
            <!-- Toast notification system container -->
            <div id="swvt-hr-toast-container"></div>

            <!-- Top navigation bar -->
            <div class="swvt-hr-top-bar">
                <div class="swvt-hr-top-bar-left">
                    <span class="swvt-hr-brand">SWVT HR & Sales</span>
                </div>
                <div class="swvt-hr-top-bar-right">
                    <!-- Global Search trigger -->
                    <div class="swvt-hr-search-trigger" id="swvt-hr-search-bar-trigger">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        <span>Search anything...</span>
                        <kbd>⌘K</kbd>
                    </div>

                    <!-- Light / Dark Theme toggler -->
                    <button class="swvt-hr-icon-btn" id="swvt-hr-theme-toggle" title="Toggle Theme">
                        <!-- Sun Icon (visible in dark) -->
                        <svg class="sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <!-- Moon Icon (visible in light) -->
                        <svg class="moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    </button>
                </div>
            </div>

            <!-- Page Main Content wrapper -->
            <div class="swvt-hr-main-body" id="swvt-hr-main-body-content">
        <?php
    }

    private function render_erp_footer() {
        ?>
            </div> <!-- .swvt-hr-main-body -->

            <!-- Global Search Overlay Modal -->
            <div class="swvt-hr-modal-overlay" id="swvt-hr-search-modal">
                <div class="swvt-hr-modal-card search-modal-card">
                    <div class="swvt-hr-search-modal-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
                        <input type="text" id="swvt-hr-global-search-input" placeholder="Type to search branches, employees, sales, payroll..." autocomplete="off" />
                        <button type="button" class="swvt-hr-search-modal-close" id="swvt-hr-search-modal-close">&times;</button>
                    </div>
                    <div class="swvt-hr-search-results" id="swvt-hr-global-search-results">
                        <div class="swvt-hr-search-placeholder">Type at least 2 characters to search...</div>
                    </div>
                </div>
            </div>

        </div> <!-- .swvt-hr-erp-shell -->
        <?php
    }
}
