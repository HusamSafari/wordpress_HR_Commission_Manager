# swvt-Hr — Branch HR & Commission Manager
### WordPress Backend Plugin — Developer Build Specification

> Arabic RTL admin plugin to manage Branches, Employees, Monthly Sales, Commission distribution, Attendance/Absence, Payroll and Reports for a company with 6 branches. Everything lives in **WP Admin** (no front-end). This document maps the approved UI design to a concrete WordPress implementation.

- **Plugin name:** Branch HR & Commission Manager
- **Plugin slug / folder:** `swvt-hr`
- **Text domain:** `swvt-hr`
- **PHP prefix:** `SWVT_HR_` (constants/classes), `swvt_hr_` (functions)
- **DB table prefix:** `{$wpdb->prefix}swvt_hr_` (e.g. `wp_swvt_hr_branches`)
- **Menu slug root:** `swvt-hr`
- **Capability:** custom `manage_swvt_hr` (mapped to admins on activation)
- **Min:** WordPress 6.0+, PHP 8.0+
- **Currency:** Egyptian Pound `ج.م` — store as `DECIMAL(15,2)`, never float.

---

## 1. Folder structure

```
swvt-hr/
├── swvt-hr.php                      # Main plugin file (header + bootstrap)
├── uninstall.php                    # Drop tables + options on uninstall
├── includes/
│   ├── class-swvt-hr.php            # Core loader (singleton)
│   ├── class-activator.php         # dbDelta table creation, seed, caps
│   ├── class-deactivator.php
│   ├── class-menu.php              # Admin menu + submenus
│   ├── class-assets.php            # enqueue CSS/JS (admin only)
│   ├── class-ajax.php             # admin-ajax handlers (recalc, save, export)
│   ├── models/
│   │   ├── class-branch.php
│   │   ├── class-employee.php
│   │   ├── class-sales.php
│   │   ├── class-commission.php
│   │   ├── class-attendance.php
│   │   └── class-payroll.php
│   ├── services/
│   │   ├── class-commission-service.php   # calc + distribution
│   │   ├── class-payroll-service.php      # net salary calc
│   │   └── class-report-service.php       # aggregates for dashboards
│   └── list-tables/
│       ├── class-branches-list-table.php  # extends WP_List_Table
│       ├── class-employees-list-table.php
│       └── class-payroll-list-table.php
├── admin/
│   ├── views/
│   │   ├── dashboard.php
│   │   ├── branches.php
│   │   ├── branch-edit.php
│   │   ├── employees.php
│   │   ├── employee-edit.php
│   │   ├── sales.php               # monthly sales entry + commission preview
│   │   ├── commission-rules.php    # قواعد العمولة
│   │   ├── attendance.php
│   │   ├── payroll.php
│   │   ├── reports.php
│   │   └── settings.php
│   └── partials/
│       ├── header-bar.php          # global month/year/branch filter
│       ├── kpi-cards.php
│       └── notices.php
├── assets/
│   ├── css/admin.css               # RTL styles (port from the HTML design)
│   ├── js/admin.js                 # filters, modals, ajax
│   └── js/charts.js                # Chart.js init (reports)
└── languages/
    └── swvt-hr-ar.po / .mo
```

---

## 2. Main plugin file — `swvt-hr.php`

```php
<?php
/**
 * Plugin Name:       Branch HR & Commission Manager
 * Plugin URI:        https://example.com/swvt-hr
 * Description:       إدارة الفروع والموظفين والمبيعات والعمولات والرواتب والحضور والتقارير.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            SWVT
 * Text Domain:       swvt-hr
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SWVT_HR_VERSION', '1.0.0' );
define( 'SWVT_HR_FILE', __FILE__ );
define( 'SWVT_HR_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWVT_HR_URL', plugin_dir_url( __FILE__ ) );
define( 'SWVT_HR_CAP', 'manage_swvt_hr' );

require_once SWVT_HR_DIR . 'includes/class-activator.php';
require_once SWVT_HR_DIR . 'includes/class-deactivator.php';
require_once SWVT_HR_DIR . 'includes/class-swvt-hr.php';

register_activation_hook( __FILE__, [ 'SWVT_HR_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SWVT_HR_Deactivator', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'swvt-hr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    SWVT_HR::instance();
} );
```

---

## 3. Data model — custom tables (NOT CPTs)

Use **custom tables** rather than Custom Post Types. The data is relational, financial, and reported in aggregate — CPT + postmeta would be slow and messy for `SUM()`/`JOIN`. (CPT is only reasonable for Branches/Employees if you want the block editor; the design below assumes custom tables for all six entities, matching the ERD.)

### ER relationships
```
Branch (1) ──< (∞) Employee
Branch (1) ──< (∞) MonthlySales
MonthlySales (1) ──< (∞) CommissionRecord      (generated on save)
Employee (1) ──< (∞) Attendance
Employee (1) ──< (∞) Payroll
Payroll = basic + commission + bonus − absence_deduction − other_deduction
```

### 3.1 `swvt_hr_branches`
| column | type | notes |
|---|---|---|
| id | BIGINT UNSIGNED PK AI | |
| name | VARCHAR(191) | اسم الفرع |
| code | VARCHAR(50) | كود الفرع (unique) |
| city | VARCHAR(120) | المدينة/المنطقة |
| manager_id | BIGINT UNSIGNED NULL | FK → employees.id (مدير الفرع) |
| phone | VARCHAR(40) | |
| address | TEXT | |
| commission_rate | DECIMAL(7,4) | نسبة العمولة (0.0020 = ٢ في الألف). NULL ⇒ use default |
| sales_target | DECIMAL(15,2) | هدف المبيعات الشهري |
| status | ENUM('active','inactive') DEFAULT 'active' |
| notes | TEXT | |
| created_at / updated_at | DATETIME | |

### 3.2 `swvt_hr_employees`
| column | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| branch_id | BIGINT UNSIGNED | FK → branches.id (INDEX) |
| full_name | VARCHAR(191) | |
| code | VARCHAR(50) | كود الموظف (unique) |
| job_title | VARCHAR(120) | مدير الفرع / محاسب الفرع / موظف مبيعات / عامل توصيل / عامل تحضير |
| role_key | VARCHAR(40) | machine key: `manager`,`accountant`,`sales`,`delivery`,`prep` |
| department | VARCHAR(120) | |
| phone | VARCHAR(40) | |
| email | VARCHAR(191) | |
| national_id | VARCHAR(20) | |
| hire_date | DATE | |
| basic_salary | DECIMAL(15,2) | |
| commission_eligible | TINYINT(1) DEFAULT 1 | مؤهل للعمولة |
| commission_share | DECIMAL(7,4) NULL | per-employee override % of the branch base (optional) |
| status | ENUM('active','inactive') DEFAULT 'active' |
| notes | TEXT | |
| created_at / updated_at | DATETIME | |

### 3.3 `swvt_hr_sales` (monthly branch sales)
| column | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| branch_id | BIGINT UNSIGNED | FK (INDEX) |
| period_month | TINYINT | 1–12 |
| period_year | SMALLINT | |
| total_sales | DECIMAL(15,2) | إجمالي المبيعات |
| target | DECIMAL(15,2) | الهدف (snapshot of branch target) |
| commission_rate | DECIMAL(7,4) | rate used for THIS record (snapshot) |
| commission_base | DECIMAL(15,2) | = total_sales × commission_rate |
| notes | TEXT | |
| created_at | DATETIME | |
| — | UNIQUE KEY (branch_id, period_month, period_year) | one entry per branch per month |

### 3.4 `swvt_hr_commissions` (distribution rows, generated from a sales record)
| column | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| sales_id | BIGINT UNSIGNED | FK → sales.id (INDEX) |
| employee_id | BIGINT UNSIGNED | FK → employees.id |
| role_key | VARCHAR(40) | snapshot of role at time of run |
| role_percent | DECIMAL(6,3) | 60.000 / 20.000 / 10.000 |
| amount | DECIMAL(15,2) | = commission_base × role_percent / 100 |
| absence_deduction | DECIMAL(15,2) DEFAULT 0 | خصم الغياب على العمولة |
| final_amount | DECIMAL(15,2) | = amount − absence_deduction |
| created_at | DATETIME | |

### 3.5 `swvt_hr_attendance`
| column | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| employee_id | BIGINT UNSIGNED | FK (INDEX) |
| branch_id | BIGINT UNSIGNED | denormalized for fast branch reports |
| period_month | TINYINT | |
| period_year | SMALLINT | |
| absence_days | DECIMAL(5,2) DEFAULT 0 | أيام الغياب |
| late_hours | DECIMAL(6,2) DEFAULT 0 | ساعات التأخير |
| deduction | DECIMAL(15,2) DEFAULT 0 | قيمة الخصم (computed) |
| reason | VARCHAR(191) | |
| notes | TEXT | |
| — | UNIQUE KEY (employee_id, period_month, period_year) | |

### 3.6 `swvt_hr_payroll`
| column | type | notes |
|---|---|---|
| id | BIGINT PK AI | |
| employee_id | BIGINT UNSIGNED | FK (INDEX) |
| branch_id | BIGINT UNSIGNED | denormalized |
| period_month | TINYINT | |
| period_year | SMALLINT | |
| basic_salary | DECIMAL(15,2) | |
| commission | DECIMAL(15,2) | pulled from commissions.final_amount |
| absence_deduction | DECIMAL(15,2) DEFAULT 0 | |
| bonus | DECIMAL(15,2) DEFAULT 0 | |
| other_deduction | DECIMAL(15,2) DEFAULT 0 | |
| net_salary | DECIMAL(15,2) | computed |
| status | ENUM('pending','paid','hold') DEFAULT 'pending' | قيد الانتظار / تم الدفع / موقوف |
| paid_at | DATETIME NULL | |
| — | UNIQUE KEY (employee_id, period_month, period_year) | |

### 3.7 Options (commission rules)
Stored in `wp_options` under key `swvt_hr_settings` (single serialized array):
```php
[
  'default_commission_rate' => 0.0020,        // ٢ في الألف
  'role_distribution' => [                      // must total 100
      'manager'    => 60,
      'accountant' => 20,
      'delivery'   => 10,
      'prep'       => 10,
  ],
  'daily_salary_divisor' => 30,                 // يوم = الراتب ÷ 30
  'currency' => 'ج.م',
]
```

---

## 4. Activation — create tables (`class-activator.php`)

```php
class SWVT_HR_Activator {
    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix . 'swvt_hr_';

        dbDelta( "CREATE TABLE {$p}branches (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            code VARCHAR(50) NOT NULL,
            city VARCHAR(120) NULL,
            manager_id BIGINT UNSIGNED NULL,
            phone VARCHAR(40) NULL,
            address TEXT NULL,
            commission_rate DECIMAL(7,4) NULL,
            sales_target DECIMAL(15,2) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset;" );

        // ... repeat dbDelta() for employees, sales, commissions, attendance, payroll
        // (use the column tables in §3; keep each CREATE TABLE in its own dbDelta call)

        // default settings
        if ( ! get_option( 'swvt_hr_settings' ) ) {
            add_option( 'swvt_hr_settings', [
                'default_commission_rate' => 0.0020,
                'role_distribution' => [ 'manager'=>60,'accountant'=>20,'delivery'=>10,'prep'=>10 ],
                'daily_salary_divisor' => 30,
                'currency' => 'ج.م',
            ] );
        }

        // capability → admins
        $role = get_role( 'administrator' );
        if ( $role ) $role->add_cap( SWVT_HR_CAP );

        update_option( 'swvt_hr_db_version', SWVT_HR_VERSION );
    }
}
```

> **dbDelta rules:** two spaces after `PRIMARY KEY`, each field on its own line, no backticks around field names inside the SQL string, and use `KEY`/`UNIQUE KEY` (not `INDEX`). Run one `CREATE TABLE` per `dbDelta()` call.

---

## 5. Admin menu (`class-menu.php`)

Matches the approved sidebar exactly (parent + 9 submenus). Menu order = design order.

```php
class SWVT_HR_Menu {
    public function register() {
        add_menu_page(
            __( 'الموارد البشرية والفروع', 'swvt-hr' ),
            __( 'الموارد البشرية والفروع', 'swvt-hr' ),
            SWVT_HR_CAP, 'swvt-hr',
            [ $this, 'render_dashboard' ],
            'dashicons-groups', 30
        );

        $sub = [
            [ 'swvt-hr',             'لوحة المعلومات', 'render_dashboard' ],
            [ 'swvt-hr-branches',    'الفروع',         'render_branches' ],
            [ 'swvt-hr-employees',   'الموظفين',       'render_employees' ],
            [ 'swvt-hr-sales',       'مبيعات الفروع',  'render_sales' ],
            [ 'swvt-hr-rules',       'قواعد العمولة',  'render_rules' ],
            [ 'swvt-hr-attendance',  'الحضور والغياب', 'render_attendance' ],
            [ 'swvt-hr-payroll',     'الرواتب',        'render_payroll' ],
            [ 'swvt-hr-reports',     'التقارير',       'render_reports' ],
            [ 'swvt-hr-settings',    'الإعدادات',      'render_settings' ],
        ];
        foreach ( $sub as $s ) {
            add_submenu_page( 'swvt-hr', $s[1], $s[1], SWVT_HR_CAP, $s[0], [ $this, $s[2] ] );
        }
    }

    public function render_dashboard()  { require SWVT_HR_DIR . 'admin/views/dashboard.php'; }
    public function render_branches()   { require SWVT_HR_DIR . 'admin/views/branches.php'; }
    // ... one per submenu
}
```

Hook it: `add_action( 'admin_menu', [ new SWVT_HR_Menu(), 'register' ] );`

---

## 6. Assets & RTL (`class-assets.php`)

Enqueue **only** on our pages (check `$hook` / `page` param). Port `assets/css/admin.css` from the approved HTML design (IBM Plex Sans Arabic, white/gray/yellow, green/red/blue states). Add `.rtl` handling — WP already sets `dir="rtl"` when the site language is Arabic.

```php
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'swvt-hr' ) === false ) return;
    wp_enqueue_style( 'swvt-hr-fonts',
        'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap', [], null );
    wp_enqueue_style( 'swvt-hr-admin', SWVT_HR_URL.'assets/css/admin.css', [], SWVT_HR_VERSION );
    wp_enqueue_script( 'chartjs', SWVT_HR_URL.'assets/js/chart.min.js', [], '4.4.0', true );
    wp_enqueue_script( 'swvt-hr-admin', SWVT_HR_URL.'assets/js/admin.js', ['jquery'], SWVT_HR_VERSION, true );
    wp_localize_script( 'swvt-hr-admin', 'SWVT_HR', [
        'ajax'  => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'swvt_hr_nonce' ),
        'i18n'  => [ 'confirmRecalc' => __( 'تأكيد إعادة حساب الرواتب لهذا الشهر؟', 'swvt-hr' ) ],
    ] );
} );
```

> Load the design's CSS as `admin.css`. Every page view (`admin/views/*.php`) outputs the same markup you approved in the prototype — the header filter bar, KPI cards, tables, forms and modals — using PHP loops instead of the JS `sc-for`.

---

## 7. Core logic — Commission

The approved rule: **base = branch sales × ٢ في الألف (0.20%)**, then split by role: مدير ٦٠٪ · محاسب ٢٠٪ · عامل توصيل ١٠٪ · عامل تجهيز ١٠٪.

`includes/services/class-commission-service.php`
```php
class SWVT_HR_Commission_Service {

    /** Rate for a branch: branch override → else global default. */
    public static function rate_for_branch( $branch ) {
        $s = get_option( 'swvt_hr_settings' );
        return $branch->commission_rate ?: $s['default_commission_rate']; // e.g. 0.0020
    }

    /** base = total_sales * rate  (971181 * 0.0020 = 1942.36) */
    public static function base( $total_sales, $rate ) {
        return round( $total_sales * $rate, 2 );
    }

    /**
     * Distribute the base across the branch's eligible employees by role %.
     * Returns rows ready to insert into swvt_hr_commissions.
     */
    public static function distribute( $sales_row ) {
        global $wpdb; $p = $wpdb->prefix . 'swvt_hr_';
        $settings = get_option( 'swvt_hr_settings' );
        $dist     = $settings['role_distribution'];              // ['manager'=>60,...]
        $base     = $sales_row->commission_base;

        $employees = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$p}employees
             WHERE branch_id = %d AND status='active' AND commission_eligible = 1",
            $sales_row->branch_id
        ) );

        // group employees by role so a role's % splits equally among peers
        $by_role = [];
        foreach ( $employees as $e ) $by_role[ $e->role_key ][] = $e;

        $rows = [];
        foreach ( $dist as $role_key => $percent ) {
            $peers = $by_role[ $role_key ] ?? [];
            if ( ! $peers ) continue;                             // role has no staff → skip
            $role_amount = round( $base * $percent / 100, 2 );
            $each        = round( $role_amount / count( $peers ), 2 );
            foreach ( $peers as $emp ) {
                $absence = self::absence_on_commission( $emp->id, $sales_row->period_month, $sales_row->period_year );
                $rows[] = [
                    'sales_id'          => $sales_row->id,
                    'employee_id'       => $emp->id,
                    'role_key'          => $role_key,
                    'role_percent'      => $percent / max( 1, count( $peers ) ),
                    'amount'            => $each,
                    'absence_deduction' => $absence,
                    'final_amount'      => round( $each - $absence, 2 ),
                ];
            }
        }
        return $rows;                                             // caller wipes old rows for sales_id, then bulk-inserts
    }

    private static function absence_on_commission( $emp_id, $m, $y ) {
        // Optional: pro-rate commission by absence. Default 0 (absence hits salary, not commission).
        return 0.00;
    }

    /** Sum of role_distribution — UI must block save unless === 100. */
    public static function is_valid_distribution( array $dist ) {
        return array_sum( $dist ) === 100;
    }
}
```

**On saving a monthly-sales entry** (`class-sales.php::save()`):
1. Snapshot `commission_rate` + compute `commission_base = round(total_sales * rate, 2)`.
2. `INSERT ... ON DUPLICATE KEY UPDATE` the sales row (unique on branch+month+year).
3. `DELETE FROM commissions WHERE sales_id = ?` then bulk-insert `distribute($sales_row)`.
4. Admin notice: «تم حفظ المبيعات وحساب العمولة تلقائيًا».

---

## 8. Core logic — Attendance deduction

```php
// daily_salary = basic_salary / divisor (30)
// absence_deduction = round(absence_days * daily_salary, 2)
public static function absence_deduction( $basic_salary, $absence_days ) {
    $s = get_option( 'swvt_hr_settings' );
    $daily = $basic_salary / $s['daily_salary_divisor'];
    return round( $absence_days * $daily, 2 );
}
```
Store on the attendance row; the payroll run reads it.

---

## 9. Core logic — Payroll

`includes/services/class-payroll-service.php`
```php
class SWVT_HR_Payroll_Service {

    /** Build/refresh payroll rows for a period (optionally one branch). */
    public static function generate( $month, $year, $branch_id = 0 ) {
        global $wpdb; $p = $wpdb->prefix . 'swvt_hr_';
        $where = "status='active'" . ( $branch_id ? $wpdb->prepare(' AND branch_id=%d',$branch_id) : '' );
        $emps  = $wpdb->get_results( "SELECT * FROM {$p}employees WHERE $where" );

        foreach ( $emps as $e ) {
            $commission = (float) $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(c.final_amount),0)
                 FROM {$p}commissions c
                 JOIN {$p}sales s ON s.id = c.sales_id
                 WHERE c.employee_id = %d AND s.period_month = %d AND s.period_year = %d",
                $e->id, $month, $year ) );

            $att = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$p}attendance WHERE employee_id=%d AND period_month=%d AND period_year=%d",
                $e->id, $month, $year ) );
            $absence = $att ? (float) $att->deduction : 0.0;

            // bonus / other_deduction preserved if the row already exists (admin-entered)
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT bonus, other_deduction, status FROM {$p}payroll
                 WHERE employee_id=%d AND period_month=%d AND period_year=%d",
                $e->id, $month, $year ) );
            $bonus = $existing ? (float) $existing->bonus : 0.0;
            $other = $existing ? (float) $existing->other_deduction : 0.0;

            $net = round( $e->basic_salary + $commission + $bonus - $absence - $other, 2 );

            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$p}payroll
                   (employee_id,branch_id,period_month,period_year,basic_salary,commission,
                    absence_deduction,bonus,other_deduction,net_salary,status)
                 VALUES (%d,%d,%d,%d,%f,%f,%f,%f,%f,%f,'pending')
                 ON DUPLICATE KEY UPDATE
                    basic_salary=VALUES(basic_salary), commission=VALUES(commission),
                    absence_deduction=VALUES(absence_deduction), net_salary=VALUES(net_salary)",
                $e->id, $e->branch_id, $month, $year,
                $e->basic_salary, $commission, $absence, $bonus, $other, $net ) );
        }
    }
}
```

**Net salary formula (single source of truth):**
```
net_salary = basic_salary + commission + bonus − absence_deduction − other_deduction
```

Payroll actions (buttons in the design → AJAX in §11): **إنشاء كشف الرواتب** (`generate`), **إعادة الحساب** (confirm modal → `generate`), **تصدير Excel**, **طباعة**, **تعليم كمدفوع** (set `status='paid'`, `paid_at=now`).

---

## 10. Reports (`class-report-service.php`)

Each report is a single aggregate query, cached per period with a transient (`swvt_hr_report_{key}_{m}_{y}`, 5 min).

| Report | Query shape |
|---|---|
| المبيعات حسب الفرع | `SELECT branch_id, SUM(total_sales) FROM sales WHERE month/year GROUP BY branch_id` |
| العمولات حسب الفرع | `SUM(c.final_amount)` joined sales→branch |
| الرواتب حسب الفرع | `SELECT branch_id, SUM(net_salary) FROM payroll GROUP BY branch_id` |
| تكلفة الموظفين لكل فرع | payroll SUM per branch |
| تقرير الغياب | `SUM(absence_days), SUM(deduction)` per branch |
| أعلى فرع مبيعًا | order sales desc limit 1 |
| أعلى الموظفين عمولة | commissions grouped by employee, order desc |
| إجمالي رواتب الشركة | `SUM(net_salary)` all |
| صافي الرواتب بعد الخصومات | `SUM(net_salary)` |

Feed the numbers into `charts.js` (Chart.js: bar for sales/payroll, line for commission trend, horizontal bar for absence) via `wp_add_inline_script` JSON.

Dashboard KPI cards: `عدد الفروع`, `الموظفون النشطون`, `مبيعات الشهر`, `إجمالي العمولات`, `إجمالي الرواتب`, `خصومات الغياب`.

**Alerts** (computed on dashboard load):
- Branch with no `sales` row for the selected month.
- Employee with `basic_salary = 0` or NULL.
- `array_sum(role_distribution) !== 100`.
- No `payroll` rows for the selected month → «لم يتم إنشاء كشف الرواتب».
- Employees with `branch_id` NULL/0.

---

## 11. AJAX & security (`class-ajax.php`)

Every write goes through `admin-ajax.php` (or REST). **Always**: `check_ajax_referer('swvt_hr_nonce')` + `current_user_can(SWVT_HR_CAP)` + `$wpdb->prepare()` + `sanitize_*()` on input and `esc_html/esc_attr` on output.

```php
add_action( 'wp_ajax_swvt_hr_recalc_payroll', function () {
    check_ajax_referer( 'swvt_hr_nonce' );
    if ( ! current_user_can( SWVT_HR_CAP ) ) wp_send_json_error( 'forbidden', 403 );
    $m = absint( $_POST['month'] ); $y = absint( $_POST['year'] );
    $b = absint( $_POST['branch'] ?? 0 );
    SWVT_HR_Payroll_Service::generate( $m, $y, $b );
    wp_send_json_success( [ 'message' => __( 'تمت إعادة حساب الرواتب.', 'swvt-hr' ) ] );
} );
```

Handlers to build: `save_branch`, `delete_branch`, `save_employee`, `save_sales` (auto-runs commission), `save_rules`, `save_attendance`, `generate_payroll`, `recalc_payroll`, `mark_paid`, `export_payroll`.

`admin.js` responsibilities (port interactions from the prototype):
- Global month/year/branch filter → reload with query args `?page=…&m=&y=&branch=`.
- List-table search/filter (or use `WP_List_Table` built-ins).
- **Confirm modal** before إعادة الحساب (design's modal markup).
- قواعد العمولة: live-sum the role % inputs, block **حفظ** unless total = 100, show the green «المجموع صحيح = 100%» badge.
- WP admin notices for success/error (`.notice.notice-success.is-dismissible`).

---

## 12. Export (Excel / Print)

- **Excel:** generate a real `.xlsx` with **PhpSpreadsheet** (composer `phpoffice/phpspreadsheet`) in an AJAX handler that streams `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`. A CSV fallback (`text/csv; charset=UTF-8` with a BOM for Arabic) is fine for v1.
- **Print:** a print-only view (`?page=swvt-hr-payroll&print=1`) rendering a clean table with a print stylesheet; user does browser print → PDF. (Optional: server-side PDF via **Dompdf**.)

---

## 13. Seed data (optional, activation)

Insert the 6 branches (مدينة نصر، المهندسين، سموحة، المنصورة، طنطا، أسيوط) and sample employees/roles so the admin sees populated screens on first run. Gate behind a `swvt_hr_seeded` option so it runs once. Match the role keys in §3.2.

---

## 14. Uninstall (`uninstall.php`)

```php
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
global $wpdb;
foreach ( ['payroll','commissions','attendance','sales','employees','branches'] as $t ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swvt_hr_{$t}" );
}
delete_option( 'swvt_hr_settings' );
delete_option( 'swvt_hr_db_version' );
delete_option( 'swvt_hr_seeded' );
$r = get_role( 'administrator' ); if ( $r ) $r->remove_cap( 'manage_swvt_hr' );
```

---

## 15. Build checklist (page ↔ view ↔ logic)

- [ ] `swvt-hr.php` bootstrap + constants
- [ ] Activator: 6 tables via dbDelta + default settings + capability
- [ ] Menu: parent + 9 submenus (§5)
- [ ] Assets: RTL CSS ported from approved design + Chart.js
- [ ] **Dashboard** → KPI cards, alerts, recent sales, payroll/commission summary
- [ ] **Branches** → `WP_List_Table` (name, manager, employees, sales, commission, payroll, net, status) + Add/Edit form
- [ ] **Employees** → list + filters (branch/status/title/search) + Add/Edit form
- [ ] **Monthly Sales** → entry form + auto commission preview + distribution table (§7)
- [ ] **Commission Rules** → default rate (٢ في الألف), role % table with 100% validation, per-branch overrides, employee eligibility toggles (§7)
- [ ] **Attendance** → entry + deduction calc (§8)
- [ ] **Payroll** → generate/recalc (confirm modal)/mark-paid/export (§9, §11, §12)
- [ ] **Reports** → aggregate queries + charts (§10)
- [ ] **Settings** → edit `swvt_hr_settings`
- [ ] Uninstall cleanup (§14)
- [ ] Security pass: nonce + cap + prepare + escaping on every handler/view

---

### Worked example (verifies the math end-to-end)
Branch **الجمهورية**, شهر ٣/٢٠٢٦, sales **971,181.00**, rate **٢ في الألف (0.0020)**
→ `commission_base = 971,181 × 0.0020 = 1,942.36`
→ مدير ٦٠٪ = **1,165.42** · محاسب ٢٠٪ = **388.47** · عامل توصيل ١٠٪ = **194.24** · عامل تجهيز ١٠٪ = **194.24** → الإجمالي **1,942.36** ✅
