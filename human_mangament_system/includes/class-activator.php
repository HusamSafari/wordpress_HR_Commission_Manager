<?php
/**
 * Plugin Activation Database Builder & Seeder (English).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Activator {

    public static function activate() {
        self::run_migrations();
        self::ensure_defaults();
        self::ensure_capabilities();

        // Seed data only once for brand new installs.
        if ( ! get_option( 'swvt_hr_seeded' ) ) {
            self::seed_data();
            add_option( 'swvt_hr_seeded', 1 );
        }

        update_option( 'swvt_hr_db_version', SWVT_HR_VERSION );
    }

    public static function maybe_upgrade() {
        $installed_version = get_option( 'swvt_hr_db_version' );
        if ( $installed_version === SWVT_HR_VERSION ) {
            return;
        }

        self::run_migrations();
        self::ensure_defaults();
        self::ensure_capabilities();
        update_option( 'swvt_hr_db_version', SWVT_HR_VERSION );
    }

    private static function run_migrations() {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Branches Table (Expanded with ERP fields)
        dbDelta( "CREATE TABLE {$p}branches (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            code VARCHAR(20) NOT NULL,
            city VARCHAR(100) NOT NULL,
            manager_id BIGINT UNSIGNED NULL,
            phone VARCHAR(50) NULL,
            address TEXT NULL,
            commission_rate DECIMAL(7,4) NULL,
            sales_target DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            type VARCHAR(40) NOT NULL DEFAULT 'retail',
            opening_date DATE NULL,
            region VARCHAR(100) NULL,
            working_hours VARCHAR(100) NULL,
            google_maps_url TEXT NULL,
            email VARCHAR(191) NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset;" );

        // 2. Employees Table
        dbDelta( "CREATE TABLE {$p}employees (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NULL,
            full_name VARCHAR(191) NOT NULL,
            code VARCHAR(40) NOT NULL,
            job_title VARCHAR(100) NOT NULL,
            role_key VARCHAR(40) NOT NULL,
            department VARCHAR(100) NOT NULL DEFAULT 'Operations',
            phone VARCHAR(50) NULL,
            email VARCHAR(191) NULL,
            national_id VARCHAR(50) NULL,
            hire_date DATE NULL,
            basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            commission_eligible TINYINT(1) NOT NULL DEFAULT 1,
            commission_share DECIMAL(5,2) NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 3. Sales Table (Legacy summary table synced from sales_entries)
        dbDelta( "CREATE TABLE {$p}sales (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            period_month TINYINT NOT NULL,
            period_year SMALLINT NOT NULL,
            total_sales DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            target DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            commission_rate DECIMAL(7,4) NOT NULL,
            commission_base DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY branch_period (branch_id,period_month,period_year)
        ) $charset;" );

        // 4. Commissions Table
        dbDelta( "CREATE TABLE {$p}commissions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sales_id BIGINT UNSIGNED NOT NULL,
            employee_id BIGINT UNSIGNED NOT NULL,
            role_key VARCHAR(40) NOT NULL,
            role_percent DECIMAL(6,3) NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            absence_deduction DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            final_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY sales_id (sales_id),
            KEY employee_id (employee_id)
        ) $charset;" );

        // 5. Attendance Table
        dbDelta( "CREATE TABLE {$p}attendance (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id BIGINT UNSIGNED NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            period_month TINYINT NOT NULL,
            period_year SMALLINT NOT NULL,
            absence_days DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            late_hours DECIMAL(6,2) NOT NULL DEFAULT 0.00,
            deduction DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            reason VARCHAR(191) NULL,
            notes TEXT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY emp_period (employee_id,period_month,period_year),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 6. Payroll Table
        dbDelta( "CREATE TABLE {$p}payroll (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            employee_id BIGINT UNSIGNED NOT NULL,
            branch_id BIGINT UNSIGNED NOT NULL,
            period_month TINYINT NOT NULL,
            period_year SMALLINT NOT NULL,
            basic_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            absence_deduction DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            bonus DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            other_deduction DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            net_salary DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending','paid','hold') NOT NULL DEFAULT 'pending',
            paid_at DATETIME NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY emp_period (employee_id,period_month,period_year),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 7. Branch Targets Table
        dbDelta( "CREATE TABLE {$p}branch_targets (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            target_type VARCHAR(40) NOT NULL,
            period_value SMALLINT NOT NULL,
            period_year SMALLINT NOT NULL,
            sales_target DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            orders_target INT UNSIGNED NOT NULL DEFAULT 0,
            customers_target INT UNSIGNED NOT NULL DEFAULT 0,
            profit_target DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 8. Branch Expenses Table
        dbDelta( "CREATE TABLE {$p}branch_expenses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            category VARCHAR(50) NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            expense_date DATE NOT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 9. Branch Documents Table
        dbDelta( "CREATE TABLE {$p}branch_documents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            doc_type VARCHAR(50) NOT NULL,
            title VARCHAR(191) NOT NULL,
            file_url TEXT NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 10. Branch Timeline Table
        dbDelta( "CREATE TABLE {$p}branch_timeline (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 11. Sales Entries Table (Daily/Weekly/Monthly Logs)
        dbDelta( "CREATE TABLE {$p}sales_entries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            branch_id BIGINT UNSIGNED NOT NULL,
            entry_type VARCHAR(40) NOT NULL DEFAULT 'daily',
            entry_date DATE NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            orders INT UNSIGNED NOT NULL DEFAULT 0,
            customers INT UNSIGNED NOT NULL DEFAULT 0,
            refunds DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            discounts DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY branch_id (branch_id)
        ) $charset;" );

        // 12. Activity Logs Table
        dbDelta( "CREATE TABLE {$p}activity_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            action_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            module_key VARCHAR(50) NOT NULL,
            item_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY module_key (module_key)
        ) $charset;" );
    }

    private static function ensure_defaults() {
        $defaults = [
            'default_commission_rate' => 0.0020,
            'role_distribution' => [
                'manager'    => 60,
                'accountant' => 20,
                'delivery'   => 10,
                'prep'       => 10,
            ],
            'daily_salary_divisor' => 30,
            'currency' => 'EGP',
            'amount_decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'job_titles' => [
                'Branch Manager',
                'Branch Accountant',
                'Assistant Worker',
                'Cleaning Worker',
                'Delivery Driver',
                'Sales Representative',
                'Branches Manager',
                'Sales Manager',
                'oGm1',
                'oGm2',
                'oGm3'
            ]
        ];

        $settings = get_option( 'swvt_hr_settings' );
        if ( ! is_array( $settings ) ) {
            add_option( 'swvt_hr_settings', $defaults );
            return;
        }

        update_option( 'swvt_hr_settings', array_replace_recursive( $defaults, $settings ) );
    }

    private static function ensure_capabilities() {
        $role = get_role( 'administrator' );
        if ( $role ) {
            $role->add_cap( SWVT_HR_CAP );
        }
    }

    private static function seed_data() {
        global $wpdb;
        $p = $wpdb->prefix . 'swvt_hr_';

        // 1. Insert Branches
        $branches = [
            [ 'name' => 'Nasr City Branch', 'city' => 'Cairo', 'code' => 'BR-01', 'sales_target' => 1500000, 'status' => 'active' ],
            [ 'name' => 'Mohandessin Branch', 'city' => 'Giza', 'code' => 'BR-02', 'sales_target' => 1400000, 'status' => 'active' ],
            [ 'name' => 'Smouha Branch', 'city' => 'Alexandria', 'code' => 'BR-03', 'sales_target' => 1700000, 'status' => 'active' ],
            [ 'name' => 'Mansoura Branch', 'city' => 'Dakahlia', 'code' => 'BR-04', 'sales_target' => 1100000, 'status' => 'active' ],
            [ 'name' => 'Tanta Branch', 'city' => 'Gharbia', 'code' => 'BR-05', 'sales_target' => 1000000, 'status' => 'active' ],
            [ 'name' => 'Assiut Branch', 'city' => 'Assiut', 'code' => 'BR-06', 'sales_target' => 900000, 'status' => 'inactive' ],
        ];

        $branch_ids = [];
        foreach ( $branches as $b ) {
            $wpdb->insert( $p . 'branches', $b );
            $branch_ids[ $b['code'] ] = $wpdb->insert_id;
        }

        // 2. Insert Employees
        $employees = [
            [ 'name' => 'Ahmed Selim', 'code' => 'EMP-1001', 'branch' => 'BR-01', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 12000, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Mona Hassan', 'code' => 'EMP-1002', 'branch' => 'BR-01', 'title' => 'Branch Accountant', 'role' => 'accountant', 'salary' => 8000, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Mahmoud Adel', 'code' => 'EMP-1003', 'branch' => 'BR-01', 'title' => 'Sales Officer', 'role' => 'sales', 'salary' => 5500, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Samir Fathy', 'code' => 'EMP-1004', 'branch' => 'BR-01', 'title' => 'Delivery Driver', 'role' => 'delivery', 'salary' => 4200, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Mohamed Fouad', 'code' => 'EMP-2001', 'branch' => 'BR-02', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 12000, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Dalia Ramzy', 'code' => 'EMP-2002', 'branch' => 'BR-02', 'title' => 'Branch Accountant', 'role' => 'accountant', 'salary' => 7800, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Karim Abdullah', 'code' => 'EMP-3001', 'branch' => 'BR-03', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 12500, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Noura Sherif', 'code' => 'EMP-3002', 'branch' => 'BR-03', 'title' => 'Sales Officer', 'role' => 'sales', 'salary' => 5200, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Hisham Raafat', 'code' => 'EMP-4001', 'branch' => 'BR-04', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 11500, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Amr Zaki', 'code' => 'EMP-4002', 'branch' => 'BR-04', 'title' => 'Prep Specialist', 'role' => 'prep', 'salary' => 4000, 'elig' => 0, 'status' => 'active' ],
            [ 'name' => 'Waleed Saber', 'code' => 'EMP-5001', 'branch' => 'BR-05', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 11000, 'elig' => 1, 'status' => 'active' ],
            [ 'name' => 'Yasser Mostafa', 'code' => 'EMP-6001', 'branch' => 'BR-06', 'title' => 'Branch Manager', 'role' => 'manager', 'salary' => 10500, 'elig' => 1, 'status' => 'inactive' ],
        ];

        foreach ( $employees as $e ) {
            $wpdb->insert( $p . 'employees', [
                'branch_id' => $branch_ids[ $e['branch'] ] ?? null,
                'full_name' => $e['name'],
                'code' => $e['code'],
                'job_title' => $e['title'],
                'role_key' => $e['role'],
                'department' => $e['role'] === 'manager' ? 'Management' : ( $e['role'] === 'accountant' ? 'Finance' : ( $e['role'] === 'sales' ? 'Sales' : 'Operations' ) ),
                'phone' => '+20 100 123 456' . rand(0,9),
                'basic_salary' => $e['salary'],
                'commission_eligible' => $e['elig'],
                'status' => $e['status']
            ] );
            $emp_id = $wpdb->insert_id;

            if ( $e['role'] === 'manager' ) {
                $wpdb->update( $p . 'branches', [ 'manager_id' => $emp_id ], [ 'id' => $branch_ids[ $e['branch'] ] ] );
            }
        }
    }
}
