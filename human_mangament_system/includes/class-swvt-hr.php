<?php
/**
 * Core plugin class.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_settings() {
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
                [ 'id' => 'branches_manager', 'name' => 'Branches Manager', 'parent' => '' ],
                [ 'id' => 'sales_manager', 'name' => 'Sales Manager', 'parent' => 'branches_manager' ],
                [ 'id' => 'sales_representative', 'name' => 'Sales Representative', 'parent' => 'sales_manager' ],
                [ 'id' => 'branch_manager', 'name' => 'Branch Manager', 'parent' => 'branches_manager' ],
                [ 'id' => 'branch_accountant', 'name' => 'Branch Accountant', 'parent' => 'branch_manager' ],
                [ 'id' => 'assistant_worker', 'name' => 'Assistant Worker', 'parent' => 'branch_manager' ],
                [ 'id' => 'cleaning_worker', 'name' => 'Cleaning Worker', 'parent' => 'branch_manager' ],
                [ 'id' => 'delivery_driver', 'name' => 'Delivery Driver', 'parent' => 'branch_manager' ],
            ],
            'departments' => [
                [ 'id' => 'management', 'name' => 'Management', 'parent' => '' ],
                [ 'id' => 'finance', 'name' => 'Finance', 'parent' => '' ],
                [ 'id' => 'operations', 'name' => 'Operations', 'parent' => '' ],
                [ 'id' => 'sales', 'name' => 'Sales', 'parent' => '' ],
            ]
        ];

        $settings = get_option( 'swvt_hr_settings', [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        $merged = array_replace_recursive( $defaults, $settings );

        // Normalize departments: if flat list of strings, convert to hierarchy
        if ( isset( $merged['departments'] ) && is_array( $merged['departments'] ) ) {
            $normalized = [];
            foreach ( $merged['departments'] as $dept ) {
                if ( is_string( $dept ) ) {
                    $normalized[] = [
                        'id' => sanitize_title( $dept ),
                        'name' => $dept,
                        'parent' => ''
                    ];
                } elseif ( is_array( $dept ) && isset( $dept['id'], $dept['name'] ) ) {
                    $normalized[] = [
                        'id' => sanitize_key( $dept['id'] ),
                        'name' => sanitize_text_field( $dept['name'] ),
                        'parent' => isset( $dept['parent'] ) ? sanitize_key( $dept['parent'] ) : ''
                    ];
                }
            }
            if ( ! empty( $normalized ) ) {
                $merged['departments'] = $normalized;
            }
        }

        // Normalize job_titles: if flat list of strings, convert to hierarchy
        if ( isset( $merged['job_titles'] ) && is_array( $merged['job_titles'] ) ) {
            $normalized = [];
            foreach ( $merged['job_titles'] as $title ) {
                if ( is_string( $title ) ) {
                    $normalized[] = [
                        'id' => sanitize_title( $title ),
                        'name' => $title,
                        'parent' => ''
                    ];
                } elseif ( is_array( $title ) && isset( $title['id'], $title['name'] ) ) {
                    $normalized[] = [
                        'id' => sanitize_key( $title['id'] ),
                        'name' => sanitize_text_field( $title['name'] ),
                        'parent' => isset( $title['parent'] ) ? sanitize_key( $title['parent'] ) : ''
                    ];
                }
            }
            if ( ! empty( $normalized ) ) {
                $merged['job_titles'] = $normalized;
            }
        }

        return $merged;
    }

    public static function get_hierarchical_items( $items, $parent = '', $depth = 0 ) {
        if ( ! is_array( $items ) ) {
            return [];
        }
        // Collect all IDs to check for orphans
        $ids = array_column( $items, 'id' );
        
        $tree = [];
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
                continue;
            }
            $current_parent = isset( $item['parent'] ) ? $item['parent'] : '';
            // If parent is not in the list of IDs, treat as root when sorting for root
            if ( ! empty( $current_parent ) && ! in_array( $current_parent, $ids, true ) ) {
                $current_parent = '';
            }
            
            if ( (string) $current_parent === (string) $parent ) {
                $item['depth'] = $depth;
                $tree[] = $item;
                $children = self::get_hierarchical_items( $items, $item['id'], $depth + 1 );
                $tree = array_merge( $tree, $children );
            }
        }
        return $tree;
    }

    public static function get_hierarchical_departments( $departments, $parent = '', $depth = 0 ) {
        return self::get_hierarchical_items( $departments, $parent, $depth );
    }

    public static function get_hierarchical_job_titles( $job_titles, $parent = '', $depth = 0 ) {
        return self::get_hierarchical_items( $job_titles, $parent, $depth );
    }

    public static function format_number( $number, $decimals = null ) {
        $settings = self::get_settings();
        $resolved_decimals = is_null( $decimals ) ? (int) $settings['amount_decimals'] : (int) $decimals;

        return number_format(
            (float) $number,
            max( 0, $resolved_decimals ),
            (string) $settings['decimal_separator'],
            (string) $settings['thousands_separator']
        );
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Models
        require_once SWVT_HR_DIR . 'includes/models/class-branch.php';
        require_once SWVT_HR_DIR . 'includes/models/class-employee.php';
        require_once SWVT_HR_DIR . 'includes/models/class-sales.php';
        require_once SWVT_HR_DIR . 'includes/models/class-commission.php';
        require_once SWVT_HR_DIR . 'includes/models/class-attendance.php';
        require_once SWVT_HR_DIR . 'includes/models/class-payroll.php';

        // Services
        require_once SWVT_HR_DIR . 'includes/services/class-commission-service.php';
        require_once SWVT_HR_DIR . 'includes/services/class-payroll-service.php';
        require_once SWVT_HR_DIR . 'includes/services/class-report-service.php';
        require_once SWVT_HR_DIR . 'includes/services/class-erp-service.php';

        // Core Components
        require_once SWVT_HR_DIR . 'includes/class-menu.php';
        require_once SWVT_HR_DIR . 'includes/class-assets.php';
        require_once SWVT_HR_DIR . 'includes/class-ajax.php';
    }

    private function init_hooks() {
        // Menu hooks
        $menu = new SWVT_HR_Menu();
        add_action( 'admin_menu', [ $menu, 'register_menus' ] );

        // Assets hooks
        $assets = new SWVT_HR_Assets();
        add_action( 'admin_enqueue_scripts', [ $assets, 'enqueue' ] );

        // AJAX hooks
        $ajax = new SWVT_HR_Ajax();
        $ajax->register_handlers();
    }
}
