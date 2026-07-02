<?php
/**
 * Branch Add/Edit View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$branch = null;

if ( $id ) {
    $branch = SWVT_HR_Branch::get( $id );
}

// Fetch all employees to assign managers
$employees = SWVT_HR_Employee::get_all();

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 9h7a1 1 0 0 1 1 1v11"></path><path d="M22 21H2"></path></svg>';
$page_title = $id ? __( 'Edit Branch Details', 'swvt-hr' ) : __( 'Add New Branch', 'swvt-hr' );
$page_sub   = $id ? __( 'Modify operational data and commission rates for this branch.', 'swvt-hr' ) : __( 'Establish a new business node and set targets.', 'swvt-hr' );
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
        <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-branches' ); ?>" class="swvt-hr-btn">
            <?php esc_html_e( 'Back to List', 'swvt-hr' ); ?>
        </a>
    </div>

    <div class="swvt-hr-card-padded" style="max-width: 680px;">
        <form id="swvt-hr-branch-form" method="post" action="">
            <?php if ( $id ) : ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>" />
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch Name', 'swvt-hr' ); ?></label>
                    <input type="text" name="name" value="<?php echo $branch ? esc_attr( $branch->name ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. Republic Branch" required />
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch Code', 'swvt-hr' ); ?></label>
                    <input type="text" name="code" value="<?php echo $branch ? esc_attr( $branch->code ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. REP" style="text-transform:uppercase;" required />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'City', 'swvt-hr' ); ?></label>
                    <input type="text" name="city" value="<?php echo $branch ? esc_attr( $branch->city ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. Mansoura" required />
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Region', 'swvt-hr' ); ?></label>
                    <input type="text" name="region" value="<?php echo $branch && isset($branch->region) ? esc_attr( $branch->region) : ''; ?>" class="swvt-hr-input" placeholder="e.g. Delta Region" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch Type', 'swvt-hr' ); ?></label>
                    <select name="type" class="swvt-hr-select">
                        <option value="retail" <?php selected( $branch && isset($branch->type) ? $branch->type : 'retail', 'retail' ); ?>><?php esc_html_e( 'Retail Shop', 'swvt-hr' ); ?></option>
                        <option value="warehouse" <?php selected( $branch && isset($branch->type) ? $branch->type : 'retail', 'warehouse' ); ?>><?php esc_html_e( 'Warehouse Depot', 'swvt-hr' ); ?></option>
                        <option value="office" <?php selected( $branch && isset($branch->type) ? $branch->type : 'retail', 'office' ); ?>><?php esc_html_e( 'Office HQ', 'swvt-hr' ); ?></option>
                        <option value="factory" <?php selected( $branch && isset($branch->type) ? $branch->type : 'retail', 'factory' ); ?>><?php esc_html_e( 'Factory Plant', 'swvt-hr' ); ?></option>
                    </select>
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Opening Date', 'swvt-hr' ); ?></label>
                    <input type="date" name="opening_date" value="<?php echo $branch && isset($branch->opening_date) ? esc_attr( $branch->opening_date ) : ''; ?>" class="swvt-hr-input" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Branch Manager', 'swvt-hr' ); ?></label>
                    <select name="manager_id" class="swvt-hr-select">
                        <option value=""><?php esc_html_e( 'Select Manager', 'swvt-hr' ); ?></option>
                        <?php foreach ( $employees as $emp ) : ?>
                            <option value="<?php echo $emp->id; ?>" <?php selected( $branch ? $branch->manager_id : 0, $emp->id ); ?>>
                                <?php echo esc_html( $emp->full_name ) . ' (' . esc_html( $emp->job_title ) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Working Hours', 'swvt-hr' ); ?></label>
                    <input type="text" name="working_hours" value="<?php echo $branch && isset($branch->working_hours) ? esc_attr( $branch->working_hours ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. 09:00 AM - 10:00 PM" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Phone Number', 'swvt-hr' ); ?></label>
                    <input type="text" name="phone" value="<?php echo $branch ? esc_attr( $branch->phone ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. +2010000000" />
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Email Address', 'swvt-hr' ); ?></label>
                    <input type="email" name="email" value="<?php echo $branch && isset($branch->email) ? esc_attr( $branch->email ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. branch@company.com" />
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Google Maps Link', 'swvt-hr' ); ?></label>
                    <input type="url" name="google_maps_url" value="<?php echo $branch && isset($branch->google_maps_url) ? esc_url( $branch->google_maps_url ) : ''; ?>" class="swvt-hr-input" placeholder="https://maps.google.com/..." />
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Status', 'swvt-hr' ); ?></label>
                    <select name="status" class="swvt-hr-select">
                        <option value="active" <?php selected( $branch ? $branch->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'swvt-hr' ); ?></option>
                        <option value="inactive" <?php selected( $branch ? $branch->status : 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'swvt-hr' ); ?></option>
                        <option value="maintenance" <?php selected( $branch ? $branch->status : 'active', 'maintenance' ); ?>><?php esc_html_e( 'Under Maintenance', 'swvt-hr' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="swvt-hr-field-group">
                <label class="swvt-hr-label"><?php esc_html_e( 'Full Address', 'swvt-hr' ); ?></label>
                <textarea name="address" class="swvt-hr-input" rows="2" placeholder="Street, Building, Area Details..."><?php echo $branch ? esc_textarea( $branch->address ) : ''; ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:16px; border-top:1px solid #f0f1f2; padding-top:15px; margin-top:10px;">
                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Custom Commission Rate (Optional override)', 'swvt-hr' ); ?></label>
                    <input type="number" step="0.0001" min="0" max="1" name="commission_rate" value="<?php echo $branch ? esc_attr( $branch->commission_rate ) : ''; ?>" class="swvt-hr-input" placeholder="e.g. 0.0025 (or leave empty for default)" />
                    <div style="font-size:11px; color:#787c82; margin-top:4px;">
                        <?php esc_html_e( 'Overrides default company rate. Example: 0.0025 = 2.5 per thousand.', 'swvt-hr' ); ?>
                    </div>
                </div>

                <div class="swvt-hr-field-group">
                    <label class="swvt-hr-label"><?php esc_html_e( 'Monthly Sales Target', 'swvt-hr' ); ?></label>
                    <input type="number" step="100" min="0" name="sales_target" value="<?php echo $branch ? (float) $branch->sales_target : ''; ?>" class="swvt-hr-input" placeholder="e.g. 1000000" />
                </div>
            </div>

            <div class="swvt-hr-field-group">
                <label class="swvt-hr-label"><?php esc_html_e( 'Operational Notes / Details', 'swvt-hr' ); ?></label>
                <textarea name="notes" class="swvt-hr-input" rows="2"><?php echo $branch ? esc_textarea( $branch->notes ) : ''; ?></textarea>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="swvt-hr-btn swvt-hr-btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>
                    <?php esc_html_e( 'Save Branch Details', 'swvt-hr' ); ?>
                </button>
                <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-branches' ); ?>" class="swvt-hr-btn"><?php esc_html_e( 'Cancel', 'swvt-hr' ); ?></a>
            </div>
        </form>
    </div>
</div>
