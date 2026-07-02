<?php
/**
 * Employees Registry View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$p = $wpdb->prefix . 'swvt_hr_';

// Fetch employees with branch names
$employees = $wpdb->get_results(
    "SELECT e.*, b.name as branch_name 
     FROM {$p}employees e
     LEFT JOIN {$p}branches b ON e.branch_id = b.id
     ORDER BY e.id ASC"
);

$role_labels = [
    'manager'    => __( 'Branch Manager', 'swvt-hr' ),
    'accountant' => __( 'Branch Accountant', 'swvt-hr' ),
    'delivery'   => __( 'Delivery Driver', 'swvt-hr' ),
    'prep'       => __( 'Prep Specialist', 'swvt-hr' ),
    'other'      => __( 'Other Role', 'swvt-hr' )
];

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
$page_title = __( 'Employees Registry', 'swvt-hr' );
$page_sub   = __( 'Manage your staff directory, branch assignments, basic salaries, and role groupings.', 'swvt-hr' );
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
        <button type="button" id="swvt-hr-bulk-delete-btn" class="swvt-hr-btn swvt-hr-btn-danger" style="display:none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"></path></svg>
            <?php esc_html_e( 'Delete Selected', 'swvt-hr' ); ?>
        </button>
        <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employees-edit' ); ?>" class="swvt-hr-btn swvt-hr-btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            <?php esc_html_e( 'Add Employee', 'swvt-hr' ); ?>
        </a>
    </div>

    <!-- Employees List -->
    <div class="swvt-hr-card">
        <table class="swvt-hr-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center; padding: 12px 10px;"><input type="checkbox" id="swvt-hr-select-all-employees" /></th>
                    <th style="padding:12px 18px; width:70px;"><?php esc_html_e( 'Code', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Employee', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Branch', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Role / Group', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Department', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Basic Salary', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Commission Eligible', 'swvt-hr' ); ?></th>
                    <th style="text-align:center;"><?php esc_html_e( 'Status', 'swvt-hr' ); ?></th>
                    <th style="text-align:center; width:130px;"><?php esc_html_e( 'Actions', 'swvt-hr' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $employees ) ) : ?>
                    <tr>
                        <td colspan="10" style="text-align:center; color:#787c82; padding:30px;">
                            <?php esc_html_e( 'No employees logged in the database yet. Please register staff.', 'swvt-hr' ); ?>
                        </td>
                    </tr>
                <?php else : 
                    $zebra_idx = 0;
                    foreach ( $employees as $e ) : 
                        $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                        $zebra_idx++;
                        $initials = '';
                        $parts = explode( ' ', $e->full_name );
                        if ( isset( $parts[0] ) ) $initials .= substr( $parts[0], 0, 1 );
                        if ( isset( $parts[1] ) ) $initials .= substr( $parts[1], 0, 1 );
                ?>
                    <tr class="<?php echo $zebra_class; ?>" id="swvt-hr-employee-row-<?php echo $e->id; ?>">
                        <td style="text-align: center; padding: 11px 10px;">
                            <input type="checkbox" class="swvt-hr-employee-checkbox" value="<?php echo $e->id; ?>" />
                        </td>
                        <td style="padding:11px 18px; font-weight:700; color:#2271b1;"><?php echo esc_html( $e->code ); ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="swvt-hr-emp-avatar"><?php echo esc_html( strtoupper( $initials ) ); ?></span>
                                <div style="line-height:1.35;">
                                    <div style="font-weight:600; color:#1d2327;"><?php echo esc_html( $e->full_name ); ?></div>
                                    <div style="font-size:11px; color:#787c82;"><?php echo esc_html( $e->phone ); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ( $e->branch_id ) : ?>
                                <span style="font-weight:500; color:#3c434a;"><?php echo esc_html( $e->branch_name ); ?></span>
                            <?php else : ?>
                                <span style="color:#9aa0a6; font-size:12px;"><?php esc_html_e( 'Unassigned', 'swvt-hr' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="swvt-hr-badge" style="background:#eaf2fb; color:#2271b1; font-weight:500;">
                                <?php echo isset( $role_labels[ $e->role_key ] ) ? esc_html( $role_labels[ $e->role_key ] ) : esc_html( $e->role_key ); ?>
                            </span>
                        </td>
                        <td style="color:#646970;"><?php echo esc_html( $e->department ); ?></td>
                        <td style="font-variant-numeric:tabular-nums; font-weight:600;">
                            <?php echo SWVT_HR::format_number( $e->basic_salary ); ?> EGP
                        </td>
                        <td>
                            <?php if ( $e->commission_eligible ) : ?>
                                <span class="swvt-hr-badge swvt-hr-badge-success"><?php esc_html_e( 'Eligible', 'swvt-hr' ); ?></span>
                            <?php else : ?>
                                <span class="swvt-hr-badge swvt-hr-badge-error"><?php esc_html_e( 'Ineligible', 'swvt-hr' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <span class="swvt-hr-pill swvt-hr-pill-<?php echo esc_attr( $e->status ); ?>">
                                <i></i><?php echo $e->status === 'active' ? __( 'Active', 'swvt-hr' ) : __( 'Inactive', 'swvt-hr' ); ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; gap:6px; justify-content:center;">
                                <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-employees-edit&id=' . $e->id ); ?>" class="swvt-hr-btn" style="padding:4px 8px; font-size:12px;">
                                    <?php esc_html_e( 'Edit', 'swvt-hr' ); ?>
                                </a>
                                <button type="button" class="swvt-hr-btn swvt-hr-btn-danger swvt-hr-delete-employee" data-id="<?php echo $e->id; ?>" style="padding:4px 8px; font-size:12px;">
                                    <?php esc_html_e( 'Delete', 'swvt-hr' ); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Select all checkboxes toggle
    $('#swvt-hr-select-all-employees').on('change', function() {
        var checked = $(this).is(':checked');
        $('.swvt-hr-employee-checkbox').prop('checked', checked);
        toggleBulkDeleteButton();
    });

    $('.swvt-hr-employee-checkbox').on('change', function() {
        var allChecked = $('.swvt-hr-employee-checkbox:checked').length === $('.swvt-hr-employee-checkbox').length;
        $('#swvt-hr-select-all-employees').prop('checked', allChecked);
        toggleBulkDeleteButton();
    });

    function toggleBulkDeleteButton() {
        var count = $('.swvt-hr-employee-checkbox:checked').length;
        if (count > 0) {
            $('#swvt-hr-bulk-delete-btn').fadeIn(150);
        } else {
            $('#swvt-hr-bulk-delete-btn').fadeOut(100);
        }
    }

    // Bulk Delete Action
    $('#swvt-hr-bulk-delete-btn').on('click', function() {
        var checkedBoxes = $('.swvt-hr-employee-checkbox:checked');
        var count = checkedBoxes.length;
        if (count === 0) return;

        if (!confirm('Are you sure you want to delete the selected ' + count + ' employees?')) {
            return;
        }

        var ids = [];
        checkedBoxes.each(function() {
            ids.push($(this).val());
        });

        var btn = $(this);
        btn.prop('disabled', true).text('Deleting...');

        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_bulk_delete_employees',
            nonce: SWVT_HR.nonce,
            ids: ids
        }, function(res) {
            btn.prop('disabled', false).html('<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"></path></svg> Delete Selected');
            if (res.success) {
                ids.forEach(function(id) {
                    $('#swvt-hr-employee-row-' + id).fadeOut(300, function() {
                        $(this).remove();
                    });
                });
                btn.hide();
                $('#swvt-hr-select-all-employees').prop('checked', false);
            } else {
                alert(res.data.message || 'Error executing bulk delete.');
            }
        });
    });

    // Individual Delete Employee Action
    $('.swvt-hr-delete-employee').on('click', function() {
        if (!confirm(SWVT_HR.i18n.confirmDelete)) {
            return;
        }
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true);
        
        $.post(SWVT_HR.ajax, {
            action: 'swvt_hr_delete_employee',
            nonce: SWVT_HR.nonce,
            id: id
        }, function(res) {
            btn.prop('disabled', false);
            if (res.success) {
                $('#swvt-hr-employee-row-' + id).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert(res.data.message || 'Error deleting employee.');
            }
        });
    });
});
</script>
