<?php
/**
 * Employee Add/Edit View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$employee = null;

if ( $id ) {
    $employee = SWVT_HR_Employee::get( $id );
}

$branches = SWVT_HR_Branch::get_active();

$settings = SWVT_HR::get_settings();
$job_titles = isset( $settings['job_titles'] ) ? $settings['job_titles'] : [
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
];

$role_labels = [
    'manager'    => __( 'Branch Manager', 'swvt-hr' ),
    'accountant' => __( 'Branch Accountant', 'swvt-hr' ),
    'delivery'   => __( 'Delivery Driver', 'swvt-hr' ),
    'prep'       => __( 'Prep Specialist', 'swvt-hr' ),
    'other'      => __( 'Other Role', 'swvt-hr' )
];

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
$page_title = $id ? __( 'Edit Employee Profile', 'swvt-hr' ) : __( 'Register New Employee', 'swvt-hr' );
$page_sub   = $id ? __( 'Update employee registry details, salary configurations, and branch assignments.', 'swvt-hr' ) : __( 'Create a new employee file in the central HR directory.', 'swvt-hr' );
?>

<div class="wrap swvt-hr-wrap">
    <div class="swvt-hr-card" style="background:#fff; border-bottom:1px solid #e2e4e7; padding:12px 26px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom: 20px;">
        <div style="display:flex; align-items:center; gap:11px; margin-right:auto;">
            <div style="width:38px; height:38px; border-radius:9px; background:#fdf4dd; display:flex; align-items:center; justify-content:center; color:#b78a00;">
                <?php echo $page_icon; ?>
            </div>
            <div style="line-height:1.3;">
                <div style="font-size:18px; font-weight:600;"><?php echo esc_html( $page_title ); ?></div>
                <div style="font-size:12px; color:#787c82;"><?php echo esc_html( $page_sub ); ?></div>
            </div>
        </div>
        <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employees' ); ?>" class="swvt-hr-btn">
            <?php esc_html_e( 'Back to List', 'swvt-hr' ); ?>
        </a>
    </div>

    <div class="swvt-hr-card-padded" style="max-width: 720px;">
        <form id="swvt-hr-employee-form" method="post" action="">
            <?php if ( $id ) : ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>" />
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Full Name', 'swvt-hr' ); ?></label>
                    <input type="text" name="full_name" value="<?php echo $employee ? esc_attr( $employee->full_name ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. Ahmed Selim" required />
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Employee Code / Badge ID', 'swvt-hr' ); ?></label>
                    <input type="text" name="code" value="<?php echo $employee ? esc_attr( $employee->code ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. EMP-101" required />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch Assignment', 'swvt-hr' ); ?></label>
                    <select name="branch_id" class="swvt-hr-select" required>
                        <option value=""><?php esc_html_e( 'Select Branch', 'swvt-hr' ); ?></option>
                        <?php foreach ( $branches as $b ) : ?>
                            <option value="<?php echo $b->id; ?>" <?php selected( $employee ? $employee->branch_id : 0, $b->id ); ?>><?php echo esc_html( $b->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Functional Role Group', 'swvt-hr' ); ?></label>
                    <select name="role_key" class="swvt-hr-select" required>
                        <option value=""><?php esc_html_e( 'Select Role Group', 'swvt-hr' ); ?></option>
                        <?php foreach ( $role_labels as $key => $lbl ) : ?>
                            <option value="<?php echo $key; ?>" <?php selected( $employee ? $employee->role_key : '', $key ); ?>><?php echo esc_html( $lbl ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Job Title', 'swvt-hr' ); ?></label>
                    <select name="job_title" class="swvt-hr-select" required>
                        <option value=""><?php esc_html_e( 'Select Job Title', 'swvt-hr' ); ?></option>
                        <?php 
                        $selected_title = $employee ? $employee->job_title : '';
                        $titles = isset( $settings['job_titles'] ) ? $settings['job_titles'] : [];
                        $hierarchical_titles = SWVT_HR::get_hierarchical_job_titles( $titles );
                        
                        $matched = false;
                        foreach ( $hierarchical_titles as $t_node ) : 
                            $val = $t_node['name'];
                            $prefix = str_repeat( '&mdash;&nbsp;', $t_node['depth'] );
                            if ( $selected_title === $val ) {
                                $matched = true;
                            }
                            ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected_title, $val ); ?>>
                                <?php echo $prefix . esc_html( $t_node['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                        
                        <?php if ( ! $matched && ! empty( $selected_title ) ) : ?>
                            <option value="<?php echo esc_attr( $selected_title ); ?>" selected>
                                <?php echo esc_html( $selected_title ) . ' (' . __( 'Legacy', 'swvt-hr' ) . ')'; ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Department', 'swvt-hr' ); ?></label>
                    <select name="department" class="swvt-hr-select" required>
                        <option value=""><?php esc_html_e( 'Select Department', 'swvt-hr' ); ?></option>
                        <?php 
                        $selected_dept = $employee ? $employee->department : 'Operations';
                        $depts = isset( $settings['departments'] ) ? $settings['departments'] : [];
                        $hierarchical_depts = SWVT_HR::get_hierarchical_departments( $depts );
                        
                        $matched = false;
                        foreach ( $hierarchical_depts as $dept ) : 
                            $val = $dept['name'];
                            $prefix = str_repeat( '&mdash;&nbsp;', $dept['depth'] );
                            if ( $selected_dept === $val ) {
                                $matched = true;
                            }
                            ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected_dept, $val ); ?>>
                                <?php echo $prefix . esc_html( $dept['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                        
                        <?php if ( ! $matched && ! empty( $selected_dept ) ) : ?>
                            <option value="<?php echo esc_attr( $selected_dept ); ?>" selected>
                                <?php echo esc_html( $selected_dept ) . ' (' . __( 'Legacy', 'swvt-hr' ) . ')'; ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Phone Number', 'swvt-hr' ); ?></label>
                    <input type="text" name="phone" value="<?php echo $employee ? esc_attr( $employee->phone ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. +2010000000" />
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Email Address', 'swvt-hr' ); ?></label>
                    <input type="email" name="email" value="<?php echo $employee ? esc_attr( $employee->email ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. employee@example.com" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'National ID / Iqama Number', 'swvt-hr' ); ?></label>
                    <input type="text" name="national_id" value="<?php echo $employee ? esc_attr( $employee->national_id ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. 29012345678901" />
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Date of Hire', 'swvt-hr' ); ?></label>
                    <input type="date" name="hire_date" value="<?php echo $employee ? esc_attr( $employee->hire_date ) : ''; ?>" class="swvt-hr-input" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:16px; border-top:1px solid #f0f1f2; padding-top:15px; margin-top:10px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Basic Monthly Salary', 'swvt-hr' ); ?></label>
                    <input type="number" step="50" min="0" name="basic_salary" value="<?php echo $employee ? (float) $employee->basic_salary : ''; ?>" class="swvt-hr-input" placeholder="e.g. 5000" required />
                    <div style="font-size:11px; color:#787c82; margin-top:4px;">
                        <?php esc_html_e( 'Basic salary represents the base wage used to compute attendance/absence deductions.', 'swvt-hr' ); ?>
                    </div>
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Status', 'swvt-hr' ); ?></label>
                    <select name="status" class="swvt-hr-select">
                        <option value="active" <?php selected( $employee ? $employee->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'swvt-hr' ); ?></option>
                        <option value="inactive" <?php selected( $employee ? $employee->status : 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'swvt-hr' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="swvt-hr-field-group" style="background:#f6f7f8; border-radius:8px; padding:12px 14px; margin-top: 5px;">
                <label style="display:flex; align-items:center; gap:8px; font-weight:600; font-size:13px; cursor:pointer;">
                    <input type="checkbox" name="commission_eligible" value="1" <?php checked( $employee ? (int) $employee->commission_eligible : 1, 1 ); ?> />
                    <?php esc_html_e( 'Eligible for Branch Commission Splits', 'swvt-hr' ); ?>
                </label>
                <div style="font-size:11.5px; color:#787c82; margin-top:4px; margin-left:22px;">
                    <?php esc_html_e( 'If checked, this employee will receive their share of branch commission payouts according to their role percentage.', 'swvt-hr' ); ?>
                </div>
            </div>

            <div class="swvt-hr-field-group">
                <label class="swvt-hr-label"><?php esc_html_e( 'Staff Notes / Details', 'swvt-hr' ); ?></label>
                <textarea name="notes" class="swvt-hr-input" rows="2"><?php echo $employee ? esc_textarea( $employee->notes ) : ''; ?></textarea>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                    <?php esc_html_e( 'Save Employee Details', 'swvt-hr' ); ?>
                </button>
                <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employees' ); ?>" class="swvt-hr-btn"><?php esc_html_e( 'Cancel', 'swvt-hr' ); ?></a>
            </div>
        </form>
    </div>
</div>
