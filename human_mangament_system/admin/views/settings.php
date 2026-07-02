<?php
/**
 * Tabbed Settings View.
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

$settings = SWVT_HR::get_settings();
$default_rate = isset( $settings['default_commission_rate'] ) ? (float) $settings['default_commission_rate'] : 0.0020;
$divisor = isset( $settings['daily_salary_divisor'] ) ? (int) $settings['daily_salary_divisor'] : 30;
$currency = isset( $settings['currency'] ) ? $settings['currency'] : 'EGP';
$amount_decimals = isset( $settings['amount_decimals'] ) ? (int) $settings['amount_decimals'] : 2;
$decimal_separator = isset( $settings['decimal_separator'] ) ? (string) $settings['decimal_separator'] : '.';
$thousands_separator = isset( $settings['thousands_separator'] ) ? (string) $settings['thousands_separator'] : ',';

$job_titles = isset( $settings['job_titles'] ) ? $settings['job_titles'] : [];
$departments = isset( $settings['departments'] ) ? $settings['departments'] : [ 'Management', 'Finance', 'Operations', 'Sales' ];

$role_dist = isset( $settings['role_distribution'] ) ? $settings['role_distribution'] : [
    'manager'    => 60,
    'accountant' => 20,
    'delivery'   => 10,
    'prep'       => 10,
];

$absence_rate = isset( $settings['absence_deduction_rate'] ) ? (float) $settings['absence_deduction_rate'] : 0.00;

// Fetch active branches list to display under Branch Settings
$branches = $wpdb->get_results( "SELECT id, name, code, sales_target, commission_rate, status FROM {$p}branches ORDER BY name ASC" );

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"></path></svg>';
$page_title = __( 'HR Settings & Configuration Dashboard', 'swvt-hr' );
?>

<style>
    .swvt-hr-settings-layout {
        display: grid;
        grid-template-columns: 200px 1fr;
        gap: 20px;
        background: #ffffff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        overflow: hidden;
        margin-top: 20px;
    }
    .swvt-hr-settings-sidebar {
        background: #fafafb;
        border-right: 1px solid #dcdcde;
        padding: 10px 0;
    }
    .swvt-hr-settings-sidebar-item {
        padding: 12px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #50575e;
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    .swvt-hr-settings-sidebar-item:hover {
        background: #f0f0f1;
        color: #2271b1;
    }
    .swvt-hr-settings-sidebar-item.is-active {
        background: #ffffff;
        color: #2271b1;
        border-left-color: #2271b1;
        font-weight: 700;
    }
    .swvt-hr-settings-pane {
        padding: 24px;
        display: none;
    }
    .swvt-hr-settings-pane.is-active {
        display: block;
    }

    /* Departments Tree Styles */
    .swvt-dept-layout {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 20px;
        margin-top: 10px;
    }
    @media (max-width: 900px) {
        .swvt-dept-layout {
            grid-template-columns: 1fr;
        }
    }
    .swvt-dept-tree-card {
        border: 1px solid #dcdcde;
        border-radius: 6px;
        background: #ffffff;
        padding: 16px;
    }
    .swvt-dept-form-card {
        border: 1px solid #dcdcde;
        border-radius: 6px;
        background: #fafafb;
        padding: 16px;
        align-self: start;
    }
    .swvt-dept-tree-list {
        list-style-type: none;
        margin: 0;
        padding: 0;
    }
    .swvt-dept-tree-list ul {
        list-style-type: none;
        margin: 0;
        padding-left: 20px;
        border-left: 1px dashed #cbd5e1;
        margin-left: 10px;
        margin-top: 4px;
    }
    .rtl .swvt-dept-tree-list ul {
        padding-left: 0;
        padding-right: 20px;
        border-left: none;
        border-right: 1px dashed #cbd5e1;
        margin-left: 0;
        margin-right: 10px;
    }
    .swvt-dept-node {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        margin-bottom: 6px;
        transition: all 0.2s ease;
    }
    .swvt-dept-node:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .swvt-dept-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }
    .swvt-dept-actions {
        display: flex;
        gap: 4px;
    }
    .swvt-dept-action-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: all 0.2s;
    }
    .swvt-dept-action-btn:hover {
        background: #e2e8f0;
    }
    .swvt-dept-action-btn.delete:hover {
        background: #fee2e2;
        color: #ef4444;
    }
    .swvt-dept-action-btn.edit:hover {
        background: #dbeafe;
        color: #2563eb;
    }
</style>

<div class="wrap swvt-hr-wrap">
    
    <!-- Header banner -->
    <div class="swvt-hr-card" style="background:#fff; border-bottom:1px solid #e2e4e7; padding:12px 20px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:11px; margin-right:auto;">
            <div style="width:38px; height:38px; border-radius:9px; background:#f3edfb; display:flex; align-items:center; justify-content:center; color:#7c3aed;">
                <?php echo $page_icon; ?>
            </div>
            <div style="line-height:1.3;">
                <div style="font-size:18px; font-weight:600;"><?php echo esc_html( $page_title ); ?></div>
                <div style="font-size:12px; color:#787c82;"><?php esc_html_e( 'Manage branch sales configurations, default wages formulas, commission role weights, and attendance parameters.', 'swvt-hr' ); ?></div>
            </div>
        </div>
        <span class="swvt-hr-badge" style="background:#eaf2fb; color:#2271b1; font-weight:600;"><?php printf( __( 'Version %s', 'swvt-hr' ), SWVT_HR_VERSION ); ?></span>
    </div>

    <!-- Main Tabbed Settings Layout -->
    <form id="swvt-hr-settings-form" method="post" action="">
        <div class="swvt-hr-inline-notice" aria-live="polite" style="margin-top:15px; margin-bottom:-10px;" hidden></div>

        <div class="swvt-hr-settings-layout">
            
            <!-- Tab Side navigation -->
            <div class="swvt-hr-settings-sidebar" id="settings-sidebar-tabs">
                <div class="swvt-hr-settings-sidebar-item is-active" data-settings-tab="general">⚙️ General Settings</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="branches">🏢 Branch Targets</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="departments">📂 Departments</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="job-titles">💼 Job Titles</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="salary-rules">💵 Salary Rules</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="commission-rules">🏆 Commission Rules</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="attendance-rules">📅 Attendance Rules</div>
                <div class="swvt-hr-settings-sidebar-item" data-settings-tab="roles-permissions">🔒 Roles & Access</div>
            </div>

            <!-- Tab Panels -->
            <div class="swvt-hr-settings-content">
                
                <!-- 1. General Settings Pane -->
                <div class="swvt-hr-settings-pane is-active" id="settings-pane-general">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">General Configuration</h4>
                    
                    <div class="swvt-hr-field-group">
                        <label class="swvt-hr-label"><?php esc_html_e( 'Currency Code', 'swvt-hr' ); ?></label>
                        <input type="text" name="currency" value="<?php echo esc_attr( $currency ); ?>" class="swvt-hr-input" style="max-width:220px;" required />
                    </div>

                    <div class="swvt-hr-field-group">
                        <label class="swvt-hr-label"><?php esc_html_e( 'Amount Decimal Places', 'swvt-hr' ); ?></label>
                        <input type="number" min="0" max="4" name="amount_decimals" value="<?php echo $amount_decimals; ?>" class="swvt-hr-input" style="max-width:220px;" required />
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; max-width: 460px; margin-bottom:14px;">
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label"><?php esc_html_e( 'Decimal Separator', 'swvt-hr' ); ?></label>
                            <select name="decimal_separator" class="swvt-hr-select">
                                <option value="." <?php selected( $decimal_separator, '.' ); ?>>.</option>
                                <option value="," <?php selected( $decimal_separator, ',' ); ?>>,</option>
                            </select>
                        </div>
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label"><?php esc_html_e( 'Thousands Separator', 'swvt-hr' ); ?></label>
                            <select name="thousands_separator" class="swvt-hr-select">
                                <option value="," <?php selected( $thousands_separator, ',' ); ?>>,</option>
                                <option value="." <?php selected( $thousands_separator, '.' ); ?>>.</option>
                                <option value=" " <?php selected( $thousands_separator, ' ' ); ?>>Space</option>
                                <option value="" <?php selected( $thousands_separator, '' ); ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. Branch Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-branches">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Branch Commission Defaults & Threshold Targets</h4>
                    
                    <div class="swvt-hr-field-group">
                        <label class="swvt-hr-label"><?php esc_html_e( 'Default Commission Rate (as decimal, e.g., 0.002)', 'swvt-hr' ); ?></label>
                        <input type="number" step="0.0001" min="0" max="1" name="default_commission_rate" value="<?php echo $default_rate; ?>" class="swvt-hr-input" style="max-width:220px;" required />
                    </div>

                    <table class="swvt-hr-table" style="font-size:12px; margin-top:20px;">
                        <thead>
                            <tr>
                                <th style="padding:9px 12px;">Branch</th>
                                <th>Code</th>
                                <th>Sales Target</th>
                                <th>Rate Override</th>
                                <th>Status</th>
                                <th style="text-align:center;">Edit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $branches as $b ) : ?>
                                <tr>
                                    <td style="padding:8px 12px;"><strong><?php echo esc_html($b->name); ?></strong></td>
                                    <td><?php echo esc_html($b->code); ?></td>
                                    <td><?php echo number_format($b->sales_target, 0); ?> EGP</td>
                                    <td><?php echo $b->commission_rate ? number_format($b->commission_rate * 100, 2) . '%' : 'Default'; ?></td>
                                    <td><span class="swvt-hr-pill <?php echo $b->status === 'active' ? 'swvt-hr-pill-active' : 'swvt-hr-pill-inactive'; ?>" style="transform:scale(0.85);"><?php echo $b->status; ?></span></td>
                                    <td style="text-align:center;"><a href="<?php echo admin_url('admin.php?page=swvt-hr-branches-edit&id=' . $b->id); ?>" class="swvt-hr-btn" style="padding:2px 8px; font-size:11px;">Edit</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Annual Target Increase Section -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dcdcde;">
                        <h4 style="margin: 0 0 8px; font-size:14px; font-weight:700; color:#1d2327;"><?php esc_html_e( 'Annual Target Increase & Monthly Distribution', 'swvt-hr' ); ?></h4>
                        <p style="font-size: 12px; color: #646970; margin-bottom: 16px;">
                            <?php esc_html_e( 'Specify a target year and apply a percentage target increase for each branch. This will distribute the increase across all 12 months for that year, relative to existing monthly targets or the branch default baseline.', 'swvt-hr' ); ?>
                        </p>

                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 15px;">
                            <label class="swvt-hr-label" style="margin-bottom:0; font-weight:600;"><?php esc_html_e( 'Target Year:', 'swvt-hr' ); ?></label>
                            <select id="swvt-target-increase-year" class="swvt-hr-select" style="max-width: 120px;">
                                <?php 
                                $current_year = (int) date('Y');
                                for ($yr = $current_year; $yr <= $current_year + 3; $yr++) {
                                    echo '<option value="' . $yr . '">' . $yr . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <table class="swvt-hr-table" style="font-size:12px; margin-top:10px;">
                            <thead>
                                <tr>
                                    <th style="padding:9px 12px;"><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                                    <th><?php esc_html_e( 'Baseline Monthly Target', 'swvt-hr' ); ?></th>
                                    <th style="width: 220px;"><?php esc_html_e( 'Annual Target Increase (%)', 'swvt-hr' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $branches as $b ) : ?>
                                    <tr>
                                        <td style="padding:8px 12px;"><strong><?php echo esc_html($b->name); ?></strong></td>
                                        <td><?php echo number_format($b->sales_target, 0); ?> EGP</td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <input type="number" step="0.1" min="-100" max="1000" class="swvt-hr-input swvt-target-increase-input" data-branch-id="<?php echo $b->id; ?>" placeholder="<?php esc_attr_e( 'e.g. 5', 'swvt-hr' ); ?>" style="padding: 4px 8px; font-size:12px; max-width: 90px;" />
                                                <span style="font-weight:600; color:#64748b;">%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button type="button" id="swvt-apply-target-increase-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="margin-top: 14px; padding: 6px 16px; font-size:12px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:-2px; margin-right:4px;"><path d="M12 5v14M5 12h14"></path></svg>
                            <?php esc_html_e( 'Apply & Distribute Increase', 'swvt-hr' ); ?>
                        </button>
                    </div>
                </div>

                <!-- 3. Departments Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-departments">
                    <h4 style="margin: 0 0 8px; font-size:14px; font-weight:700; color:#1d2327;"><?php esc_html_e( 'Company Departments Structure', 'swvt-hr' ); ?></h4>
                    <p style="font-size: 12.5px; color: #646970; margin-bottom: 20px;"><?php esc_html_e( 'Manage the hierarchical structure of company departments. You can add nested departments and map them to employees.', 'swvt-hr' ); ?></p>
                    
                    <input type="hidden" name="departments_json" id="swvt-departments-json" value="" />

                    <div class="swvt-dept-layout">
                        <!-- Tree View Card -->
                        <div class="swvt-dept-tree-card">
                            <h5 style="margin: 0 0 12px; font-size:13px; font-weight:600; color:#475569;"><?php esc_html_e( 'Departments Tree', 'swvt-hr' ); ?></h5>
                            <div id="swvt-dept-tree-container">
                                <!-- Tree rendered dynamically here -->
                            </div>
                        </div>

                        <!-- Form Card -->
                        <div class="swvt-dept-form-card">
                            <h5 id="swvt-dept-form-title" style="margin: 0 0 12px; font-size:13px; font-weight:600; color:#475569;"><?php esc_html_e( 'Add Department', 'swvt-hr' ); ?></h5>
                            
                            <div class="swvt-hr-field-group" style="margin-bottom: 12px;">
                                <label class="swvt-hr-label" style="font-size: 11.5px;"><?php esc_html_e( 'Department Name', 'swvt-hr' ); ?></label>
                                <input type="text" id="swvt-dept-name-input" class="swvt-hr-input" placeholder="<?php esc_attr_e( 'e.g. Bar / Kitchen', 'swvt-hr' ); ?>" />
                            </div>

                            <div class="swvt-hr-field-group" style="margin-bottom: 16px;">
                                <label class="swvt-hr-label" style="font-size: 11.5px;"><?php esc_html_e( 'Parent Department', 'swvt-hr' ); ?></label>
                                <select id="swvt-dept-parent-input" class="swvt-hr-select">
                                    <option value=""><?php esc_html_e( 'None (Root)', 'swvt-hr' ); ?></option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <button type="button" id="swvt-dept-submit-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="padding: 6px 14px; font-size:12px;">
                                    <?php esc_html_e( 'Add Department', 'swvt-hr' ); ?>
                                </button>
                                <button type="button" id="swvt-dept-cancel-btn" class="swvt-hr-btn" style="padding: 6px 14px; font-size:12px; display: none;">
                                    <?php esc_html_e( 'Cancel', 'swvt-hr' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Job Titles Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-job-titles">
                    <h4 style="margin: 0 0 8px; font-size:14px; font-weight:700; color:#1d2327;"><?php esc_html_e( 'Predefined Job Titles Structure', 'swvt-hr' ); ?></h4>
                    <p style="font-size: 12.5px; color: #646970; margin-bottom: 20px;"><?php esc_html_e( 'Manage the hierarchical structure of predefined job titles. You can add nested job titles and map them to employees.', 'swvt-hr' ); ?></p>
                    
                    <input type="hidden" name="job_titles_json" id="swvt-job-titles-json" value="" />

                    <div class="swvt-dept-layout">
                        <!-- Tree View Card -->
                        <div class="swvt-dept-tree-card">
                            <h5 style="margin: 0 0 12px; font-size:13px; font-weight:600; color:#475569;"><?php esc_html_e( 'Job Titles Tree', 'swvt-hr' ); ?></h5>
                            <div id="swvt-job-titles-tree-container">
                                <!-- Tree rendered dynamically here -->
                            </div>
                        </div>

                        <!-- Form Card -->
                        <div class="swvt-dept-form-card">
                            <h5 id="swvt-job-titles-form-title" style="margin: 0 0 12px; font-size:13px; font-weight:600; color:#475569;"><?php esc_html_e( 'Add Job Title', 'swvt-hr' ); ?></h5>
                            
                            <div class="swvt-hr-field-group" style="margin-bottom: 12px;">
                                <label class="swvt-hr-label" style="font-size: 11.5px;"><?php esc_html_e( 'Job Title Name', 'swvt-hr' ); ?></label>
                                <input type="text" id="swvt-job-title-name-input" class="swvt-hr-input" placeholder="<?php esc_attr_e( 'e.g. Sales Manager / Agent', 'swvt-hr' ); ?>" />
                            </div>

                            <div class="swvt-hr-field-group" style="margin-bottom: 16px;">
                                <label class="swvt-hr-label" style="font-size: 11.5px;"><?php esc_html_e( 'Parent Job Title', 'swvt-hr' ); ?></label>
                                <select id="swvt-job-title-parent-input" class="swvt-hr-select">
                                    <option value=""><?php esc_html_e( 'None (Root)', 'swvt-hr' ); ?></option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <button type="button" id="swvt-job-title-submit-btn" class="swvt-hr-btn swvt-hr-btn-primary" style="padding: 6px 14px; font-size:12px;">
                                    <?php esc_html_e( 'Add Job Title', 'swvt-hr' ); ?>
                                </button>
                                <button type="button" id="swvt-job-title-cancel-btn" class="swvt-hr-btn" style="padding: 6px 14px; font-size:12px; display: none;">
                                    <?php esc_html_e( 'Cancel', 'swvt-hr' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Salary Rules Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-salary-rules">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Salary & Wage Calculation Rules</h4>
                    
                    <div class="swvt-hr-field-group">
                        <label class="swvt-hr-label"><?php esc_html_e( 'Daily Salary Divisor (Default: 30 days)', 'swvt-hr' ); ?></label>
                        <input type="number" min="1" max="365" name="daily_salary_divisor" value="<?php echo $divisor; ?>" class="swvt-hr-input" style="max-width:220px;" required />
                        <div style="font-size:11.5px; color:#787c82; margin-top:4px;">
                            <?php esc_html_e( 'Used to divide base salary for daily absence deductions. E.g. daily = basic_salary / divisor', 'swvt-hr' ); ?>
                        </div>
                    </div>
                </div>

                <!-- 6. Commission Rules Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-commission-rules">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Role-based Commission Distribution Weights</h4>
                    <p style="font-size: 12px; color: #646970; margin-top:-10px; margin-bottom: 20px;">Allocate weights for the 4 core staff roles. The sum of all roles MUST add up to exactly 100%.</p>
                    
                    <div style="max-width: 400px; display:flex; flex-direction:column; gap:14px;" id="swvt-role-dist-container">
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label">Branch Manager Share (%)</label>
                            <input type="number" step="1" min="0" max="100" name="role_dist_manager" value="<?php echo $role_dist['manager']; ?>" class="swvt-hr-input swvt-role-input" style="max-width:120px;" />
                        </div>
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label">Branch Accountant Share (%)</label>
                            <input type="number" step="1" min="0" max="100" name="role_dist_accountant" value="<?php echo $role_dist['accountant']; ?>" class="swvt-hr-input swvt-role-input" style="max-width:120px;" />
                        </div>
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label">Delivery Staff Share (%)</label>
                            <input type="number" step="1" min="0" max="100" name="role_dist_delivery" value="<?php echo $role_dist['delivery']; ?>" class="swvt-hr-input swvt-role-input" style="max-width:120px;" />
                        </div>
                        <div class="swvt-hr-field-group" style="margin-bottom:0;">
                            <label class="swvt-hr-label">Preparation Workers Share (%)</label>
                            <input type="number" step="1" min="0" max="100" name="role_dist_prep" value="<?php echo $role_dist['prep']; ?>" class="swvt-hr-input swvt-role-input" style="max-width:120px;" />
                        </div>
                        <div style="background:#f6f7f7; padding:10px; border-radius:4px; font-weight:700; font-size:12.5px; border:1px solid #dcdcde;">
                            Distribution Sum Total: <span id="swvt-role-sum-val">100%</span>
                        </div>
                    </div>
                </div>

                <!-- 7. Attendance Rules Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-attendance-rules">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Attendance & Absence Penalty Rules</h4>
                    
                    <div class="swvt-hr-field-group">
                        <label class="swvt-hr-label"><?php esc_html_e( 'Absence Deduction Fine Multiplier (e.g. 1.0 = normal daily wage, 2.0 = double fine)', 'swvt-hr' ); ?></label>
                        <input type="number" step="0.1" min="0" max="5" name="absence_deduction_rate" value="<?php echo $absence_rate > 0 ? $absence_rate : 1.0; ?>" class="swvt-hr-input" style="max-width:220px;" required />
                    </div>
                </div>

                <!-- 8. Roles & Permissions Settings Pane -->
                <div class="swvt-hr-settings-pane" id="settings-pane-roles-permissions">
                    <h4 style="margin: 0 0 16px; font-size:14px; font-weight:700; color:#1d2327;">Security Roles & Cap Permissions Visualizer</h4>
                    
                    <div style="background:#f6f7f7; border:1px solid #dcdcde; border-radius:6px; padding:15px; font-size:13px; max-width: 500px;">
                        <p>The capability slug required to access this plugin is: <code>manage_swvt_hr</code></p>
                        <ul style="margin:10px 0 0 10px; padding:0; list-style:square;">
                            <li style="margin-bottom:6px;"><strong>Administrator</strong> role: <span style="color:#137333; font-weight:700;">🟢 GRANTED</span></li>
                            <li style="margin-bottom:6px;"><strong>Editor</strong> role: <span style="color:#c5221f;">🔴 DENIED</span></li>
                            <li style="margin-bottom:6px;"><strong>Author</strong> role: <span style="color:#c5221f;">🔴 DENIED</span></li>
                            <li style="margin-bottom:6px;"><strong>HR Manager</strong> (custom role mapped): <span style="color:#c5221f;">🔴 DENIED</span></li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

        <!-- Submit Buttons -->
        <div style="display:flex; gap:10px; margin-top:20px; padding-left: 20px;">
            <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary" id="swvt-save-settings-submit-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                <?php esc_html_e( 'Save Settings Panel', 'swvt-hr' ); ?>
            </button>
            <button type="button" onclick="window.location.reload();" class="swvt-hr-btn"><?php esc_html_e( 'Reset Settings', 'swvt-hr' ); ?></button>
        </div>
    </form>

</div>

<script>
jQuery(document).ready(function($) {
    // 1. Tab Navigation Toggles
    $('#settings-sidebar-tabs .swvt-hr-settings-sidebar-item').on('click', function() {
        var clicked = $(this);
        var tabKey = clicked.data('settings-tab');

        $('#settings-sidebar-tabs .swvt-hr-settings-sidebar-item').removeClass('is-active');
        clicked.addClass('is-active');

        $('.swvt-hr-settings-pane').removeClass('is-active');
        $('#settings-pane-' + tabKey).addClass('is-active');
    });

    // 2. Validate Sum of Commissions Distributions
    function checkCommissionsDistributionSum() {
        var sum = 0;
        $('.swvt-role-input').each(function() {
            var val = parseFloat($(this).val()) || 0;
            sum += val;
        });
        $('#swvt-role-sum-val').text(sum + '%');
        if (sum !== 100) {
            $('#swvt-role-sum-val').css('color', '#c5221f');
            return false;
        } else {
            $('#swvt-role-sum-val').css('color', '#137333');
            return true;
        }
    }

    $('.swvt-role-input').on('input change', checkCommissionsDistributionSum);
    checkCommissionsDistributionSum();

    // 3. Departments Hierarchy Manager
    var departmentsList = <?php echo json_encode( $departments ); ?>;
    var editingDeptId = null;

    function renderDepartmentsTree() {
        // Build nested tree structure
        var treeContainer = $('#swvt-dept-tree-container');
        treeContainer.empty();

        if (departmentsList.length === 0) {
            treeContainer.append('<div style="color:#64748b; font-size:12px; font-style:italic; padding:10px 0;">No departments defined yet.</div>');
            return;
        }

        // Helper recursive render function
        function buildTreeHTML(parentId) {
            var html = '';
            var children = departmentsList.filter(function(d) {
                var parent = d.parent;
                var hasParentInList = departmentsList.some(function(item) { return item.id === parent; });
                if (parent && !hasParentInList) {
                    parent = '';
                }
                return parent === parentId;
            });

            if (children.length > 0) {
                html += '<ul class="swvt-dept-tree-list">';
                children.forEach(function(child) {
                    html += '<li data-id="' + child.id + '">';
                    html += '  <div class="swvt-dept-node">';
                    html += '    <span class="swvt-dept-title">';
                    html += '      📁 ' + escapeHTML(child.name);
                    html += '    </span>';
                    html += '    <div class="swvt-dept-actions">';
                    html += '      <button type="button" class="swvt-dept-action-btn edit" data-id="' + child.id + '" title="Edit">';
                    html += '        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>';
                    html += '      </button>';
                    html += '      <button type="button" class="swvt-dept-action-btn delete" data-id="' + child.id + '" title="Delete">';
                    html += '        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>';
                    html += '      </button>';
                    html += '    </div>';
                    html += '  </div>';
                    html += buildTreeHTML(child.id);
                    html += '</li>';
                });
                html += '</ul>';
            }
            return html;
        }

        var rootHTML = buildTreeHTML('');
        if (rootHTML) {
            treeContainer.append(rootHTML);
        } else {
            var flatHTML = '<ul class="swvt-dept-tree-list">';
            departmentsList.forEach(function(d) {
                flatHTML += '<li data-id="' + d.id + '">';
                flatHTML += '  <div class="swvt-dept-node">';
                flatHTML += '    <span class="swvt-dept-title">📁 ' + escapeHTML(d.name) + '</span>';
                flatHTML += '    <div class="swvt-dept-actions">';
                flatHTML += '      <button type="button" class="swvt-dept-action-btn edit" data-id="' + d.id + '" title="Edit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></button>';
                flatHTML += '      <button type="button" class="swvt-dept-action-btn delete" data-id="' + d.id + '" title="Delete"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg></button>';
                flatHTML += '    </div>';
                flatHTML += '  </div>';
                flatHTML += '</li>';
            });
            flatHTML += '</ul>';
            treeContainer.append(flatHTML);
        }

        // Sync hidden value
        $('#swvt-departments-json').val(JSON.stringify(departmentsList));

        // Update dropdown list
        updateParentDropdown();
    }

    function updateParentDropdown() {
        var parentSelect = $('#swvt-dept-parent-input');
        var currentSelected = parentSelect.val();

        parentSelect.find('option:not(:first)').remove();

        function populateDropdownOptions(parentId, depth) {
            var prefix = '&mdash; '.repeat(depth);
            var children = departmentsList.filter(function(d) {
                var parent = d.parent;
                var hasParentInList = departmentsList.some(function(item) { return item.id === parent; });
                if (parent && !hasParentInList) parent = '';
                return parent === parentId;
            });

            children.forEach(function(child) {
                if (editingDeptId && (child.id === editingDeptId || isDescendantOf(child.id, editingDeptId))) {
                    return;
                }
                parentSelect.append('<option value="' + child.id + '">' + prefix + escapeHTML(child.name) + '</option>');
                populateDropdownOptions(child.id, depth + 1);
            });
        }

        populateDropdownOptions('', 0);

        if (currentSelected && parentSelect.find('option[value="' + currentSelected + '"]').length > 0) {
            parentSelect.val(currentSelected);
        } else {
            parentSelect.val('');
        }
    }

    function isDescendantOf(nodeId, parentId) {
        var node = departmentsList.find(function(d) { return d.id === nodeId; });
        if (!node || !node.parent) return false;
        if (node.parent === parentId) return true;
        return isDescendantOf(node.parent, parentId);
    }

    function escapeHTML(str) {
        return $('<div/>').text(str).html();
    }

    // Submit action (Add/Edit)
    $('#swvt-dept-submit-btn').on('click', function() {
        var nameInput = $('#swvt-dept-name-input');
        var parentInput = $('#swvt-dept-parent-input');

        var name = $.trim(nameInput.val());
        var parent = parentInput.val();

        if (!name) {
            alert('Please enter a department name.');
            return;
        }

        if (editingDeptId) {
            var dept = departmentsList.find(function(d) { return d.id === editingDeptId; });
            if (dept) {
                dept.name = name;
                dept.parent = parent;
            }
            editingDeptId = null;
            $('#swvt-dept-form-title').text('Add Department');
            $('#swvt-dept-submit-btn').text('Add Department');
            $('#swvt-dept-cancel-btn').hide();
        } else {
            var id = 'dept_' + Math.random().toString(36).substr(2, 9);
            departmentsList.push({
                id: id,
                name: name,
                parent: parent
            });
        }

        nameInput.val('');
        parentInput.val('');

        renderDepartmentsTree();
    });

    // Edit trigger
    $(document).on('click', '#swvt-dept-tree-container .swvt-dept-action-btn.edit', function() {
        var id = $(this).data('id');
        var dept = departmentsList.find(function(d) { return d.id === id; });
        if (dept) {
            editingDeptId = id;
            $('#swvt-dept-name-input').val(dept.name);
            
            updateParentDropdown();
            $('#swvt-dept-parent-input').val(dept.parent);

            $('#swvt-dept-form-title').text('Edit Department: ' + dept.name);
            $('#swvt-dept-submit-btn').text('Update Department');
            $('#swvt-dept-cancel-btn').show();
        }
    });

    // Cancel editing
    $('#swvt-dept-cancel-btn').on('click', function() {
        editingDeptId = null;
        $('#swvt-dept-name-input').val('');
        $('#swvt-dept-parent-input').val('');
        $('#swvt-dept-form-title').text('Add Department');
        $('#swvt-dept-submit-btn').text('Add Department');
        $(this).hide();
        updateParentDropdown();
    });

    // Delete trigger
    $(document).on('click', '#swvt-dept-tree-container .swvt-dept-action-btn.delete', function() {
        var id = $(this).data('id');
        var dept = departmentsList.find(function(d) { return d.id === id; });
        if (!dept) return;

        var children = departmentsList.filter(function(d) { return d.parent === id; });
        var confirmMsg = 'Are you sure you want to delete this department?';
        if (children.length > 0) {
            confirmMsg = 'Warning: This department has sub-departments. If you delete it, they will be moved up to its parent department level. Proceed?';
        }

        if (confirm(confirmMsg)) {
            children.forEach(function(child) {
                child.parent = dept.parent;
            });
            departmentsList = departmentsList.filter(function(d) { return d.id !== id; });

            if (editingDeptId === id) {
                $('#swvt-dept-cancel-btn').trigger('click');
            }

            renderDepartmentsTree();
        }
    });

    // Initialize Tree
    renderDepartmentsTree();

    // 4. Job Titles Hierarchy Manager
    var jobTitlesList = <?php echo json_encode( $job_titles ); ?>;
    var editingJobTitleId = null;

    function renderJobTitlesTree() {
        var treeContainer = $('#swvt-job-titles-tree-container');
        treeContainer.empty();

        if (jobTitlesList.length === 0) {
            treeContainer.append('<div style="color:#64748b; font-size:12px; font-style:italic; padding:10px 0;">No job titles defined yet.</div>');
            return;
        }

        function buildJobTitleTreeHTML(parentId) {
            var html = '';
            var children = jobTitlesList.filter(function(d) {
                var parent = d.parent;
                var hasParentInList = jobTitlesList.some(function(item) { return item.id === parent; });
                if (parent && !hasParentInList) {
                    parent = '';
                }
                return parent === parentId;
            });

            if (children.length > 0) {
                html += '<ul class="swvt-dept-tree-list">';
                children.forEach(function(child) {
                    html += '<li data-id="' + child.id + '">';
                    html += '  <div class="swvt-dept-node">';
                    html += '    <span class="swvt-dept-title">';
                    html += '      💼 ' + escapeHTML(child.name);
                    html += '    </span>';
                    html += '    <div class="swvt-dept-actions">';
                    html += '      <button type="button" class="swvt-job-title-action-btn edit swvt-dept-action-btn" data-id="' + child.id + '" title="Edit">';
                    html += '        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>';
                    html += '      </button>';
                    html += '      <button type="button" class="swvt-job-title-action-btn delete swvt-dept-action-btn" data-id="' + child.id + '" title="Delete">';
                    html += '        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>';
                    html += '      </button>';
                    html += '    </div>';
                    html += '  </div>';
                    html += buildJobTitleTreeHTML(child.id);
                    html += '</li>';
                });
                html += '</ul>';
            }
            return html;
        }

        var rootHTML = buildJobTitleTreeHTML('');
        if (rootHTML) {
            treeContainer.append(rootHTML);
        } else {
            var flatHTML = '<ul class="swvt-dept-tree-list">';
            jobTitlesList.forEach(function(d) {
                flatHTML += '<li data-id="' + d.id + '">';
                flatHTML += '  <div class="swvt-dept-node">';
                flatHTML += '    <span class="swvt-dept-title">💼 ' + escapeHTML(d.name) + '</span>';
                flatHTML += '    <div class="swvt-dept-actions">';
                flatHTML += '      <button type="button" class="swvt-job-title-action-btn edit swvt-dept-action-btn" data-id="' + d.id + '" title="Edit"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></button>';
                flatHTML += '      <button type="button" class="swvt-job-title-action-btn delete swvt-dept-action-btn" data-id="' + d.id + '" title="Delete"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg></button>';
                flatHTML += '    </div>';
                flatHTML += '  </div>';
                flatHTML += '</li>';
            });
            flatHTML += '</ul>';
            treeContainer.append(flatHTML);
        }

        // Sync hidden value
        $('#swvt-job-titles-json').val(JSON.stringify(jobTitlesList));

        // Update dropdown list
        updateJobTitleParentDropdown();
    }

    function updateJobTitleParentDropdown() {
        var parentSelect = $('#swvt-job-title-parent-input');
        var currentSelected = parentSelect.val();

        parentSelect.find('option:not(:first)').remove();

        function populateJobTitleDropdownOptions(parentId, depth) {
            var prefix = '&mdash; '.repeat(depth);
            var children = jobTitlesList.filter(function(d) {
                var parent = d.parent;
                var hasParentInList = jobTitlesList.some(function(item) { return item.id === parent; });
                if (parent && !hasParentInList) parent = '';
                return parent === parentId;
            });

            children.forEach(function(child) {
                if (editingJobTitleId && (child.id === editingJobTitleId || isJobTitleDescendantOf(child.id, editingJobTitleId))) {
                    return;
                }
                parentSelect.append('<option value="' + child.id + '">' + prefix + escapeHTML(child.name) + '</option>');
                populateJobTitleDropdownOptions(child.id, depth + 1);
            });
        }

        populateJobTitleDropdownOptions('', 0);

        if (currentSelected && parentSelect.find('option[value="' + currentSelected + '"]').length > 0) {
            parentSelect.val(currentSelected);
        } else {
            parentSelect.val('');
        }
    }

    function isJobTitleDescendantOf(nodeId, parentId) {
        var node = jobTitlesList.find(function(d) { return d.id === nodeId; });
        if (!node || !node.parent) return false;
        if (node.parent === parentId) return true;
        return isJobTitleDescendantOf(node.parent, parentId);
    }

    // Submit action (Add/Edit)
    $('#swvt-job-title-submit-btn').on('click', function() {
        var nameInput = $('#swvt-job-title-name-input');
        var parentInput = $('#swvt-job-title-parent-input');

        var name = $.trim(nameInput.val());
        var parent = parentInput.val();

        if (!name) {
            alert('Please enter a job title name.');
            return;
        }

        if (editingJobTitleId) {
            var title = jobTitlesList.find(function(d) { return d.id === editingJobTitleId; });
            if (title) {
                title.name = name;
                title.parent = parent;
            }
            editingJobTitleId = null;
            $('#swvt-job-titles-form-title').text('Add Job Title');
            $('#swvt-job-title-submit-btn').text('Add Job Title');
            $('#swvt-job-title-cancel-btn').hide();
        } else {
            var id = 'title_' + Math.random().toString(36).substr(2, 9);
            jobTitlesList.push({
                id: id,
                name: name,
                parent: parent
            });
        }

        nameInput.val('');
        parentInput.val('');

        renderJobTitlesTree();
    });

    // Edit trigger
    $(document).on('click', '#swvt-job-titles-tree-container .swvt-job-title-action-btn.edit', function() {
        var id = $(this).data('id');
        var title = jobTitlesList.find(function(d) { return d.id === id; });
        if (title) {
            editingJobTitleId = id;
            $('#swvt-job-title-name-input').val(title.name);
            
            updateJobTitleParentDropdown();
            $('#swvt-job-title-parent-input').val(title.parent);

            $('#swvt-job-titles-form-title').text('Edit Job Title: ' + title.name);
            $('#swvt-job-title-submit-btn').text('Update Job Title');
            $('#swvt-job-title-cancel-btn').show();
        }
    });

    // Cancel editing
    $('#swvt-job-title-cancel-btn').on('click', function() {
        editingJobTitleId = null;
        $('#swvt-job-title-name-input').val('');
        $('#swvt-job-title-parent-input').val('');
        $('#swvt-job-titles-form-title').text('Add Job Title');
        $('#swvt-job-title-submit-btn').text('Add Job Title');
        $(this).hide();
        updateJobTitleParentDropdown();
    });

    // Delete trigger
    $(document).on('click', '#swvt-job-titles-tree-container .swvt-job-title-action-btn.delete', function() {
        var id = $(this).data('id');
        var title = jobTitlesList.find(function(d) { return d.id === id; });
        if (!title) return;

        var children = jobTitlesList.filter(function(d) { return d.parent === id; });
        var confirmMsg = 'Are you sure you want to delete this job title?';
        if (children.length > 0) {
            confirmMsg = 'Warning: This job title has sub-titles. If you delete it, they will be moved up to its parent level. Proceed?';
        }

        if (confirm(confirmMsg)) {
            children.forEach(function(child) {
                child.parent = title.parent;
            });
            jobTitlesList = jobTitlesList.filter(function(d) { return d.id !== id; });

            if (editingJobTitleId === id) {
                $('#swvt-job-title-cancel-btn').trigger('click');
            }

            renderJobTitlesTree();
        }
    });

    // Initialize Tree
    renderJobTitlesTree();

    // 5. Apply Annual Target Increase
    $('#swvt-apply-target-increase-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var year = $('#swvt-target-increase-year').val();
        var increases = {};
        var hasValue = false;

        $('.swvt-target-increase-input').each(function() {
            var branchId = $(this).data('branch-id');
            var val = parseFloat($(this).val()) || 0;
            if (val !== 0) {
                increases[branchId] = val;
                hasValue = true;
            }
        });

        if (!hasValue) {
            alert('Please enter at least one non-zero increase percentage.');
            return;
        }

        if (!confirm('Are you sure you want to apply the annual target increase for the year ' + year + '? This will recalculate and overwrite targets for all 12 months.')) {
            return;
        }

        btn.prop('disabled', true).css('opacity', 0.6);

        var data = {
            action: 'swvt_hr_apply_annual_target_increase',
            nonce: SWVT_HR.nonce,
            year: year,
            increases: increases
        };

        $.post(SWVT_HR.ajax, data, function(res) {
            btn.prop('disabled', false).css('opacity', 1);
            if (res.success) {
                showToast(res.data.message, 'success');
                $('.swvt-target-increase-input').val('');
            } else {
                showToast(res.data.message || 'Error applying target increases.', 'error');
            }
        });
    });

    // Prevent submission if commission sum is not exactly 100%
    $('#swvt-hr-settings-form').on('submit', function(e) {
        if (!checkCommissionsDistributionSum()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            alert('Error: The sum of role-based commission distribution percentages must be exactly 100%. Please adjust the parameters.');
            return false;
        }
    });
});
</script>
