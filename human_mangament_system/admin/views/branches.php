<?php
/**
 * Branches Directory View (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$branches = SWVT_HR_Branch::get_all();

$page_icon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16M12 9h7a1 1 0 0 1 1 1v11"></path><path d="M22 21H2"></path></svg>';
$page_title = __( 'Branches Directory', 'swvt-hr' );
$page_sub   = __( 'Configure business branches, code allocations, local sales targets, and overrides.', 'swvt-hr' );
?>

<div class="wrap swvt-hr-wrap swvt-hr-branches-compact">
    <div class="swvt-hr-card swvt-hr-branches-header" style="background:#fff; border-bottom:1px solid #e2e4e7; padding:12px 26px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom: 20px;">
        <div style="display:flex; align-items:center; gap:11px; margin-right:auto;">
            <div class="swvt-hr-branches-header-icon" style="width:38px; height:38px; border-radius:9px; background:#fdf4dd; display:flex; align-items:center; justify-content:center; color:#b78a00;">
                <?php echo $page_icon; ?>
            </div>
            <div style="line-height:1.3;">
                <div class="swvt-hr-branches-title" style="font-size:18px; font-weight:600;"><?php echo esc_html( $page_title ); ?></div>
                <div class="swvt-hr-branches-subtitle" style="font-size:12px; color:#787c82;"><?php echo esc_html( $page_sub ); ?></div>
            </div>
        </div>
        <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-branches-edit' ); ?>" class="swvt-hr-btn swvt-hr-btn-primary swvt-hr-branches-add-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            <?php esc_html_e( 'Add Branch', 'swvt-hr' ); ?>
        </a>
    </div>

    <!-- Branches List -->
    <div class="swvt-hr-card swvt-hr-branches-table-card">
        <table class="swvt-hr-table swvt-hr-branches-table">
            <thead>
                <tr>
                    <th style="padding:12px 18px; width:70px;"><?php esc_html_e( 'Code', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Branch Name', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'City', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Manager', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Commission Rate', 'swvt-hr' ); ?></th>
                    <th><?php esc_html_e( 'Sales Target', 'swvt-hr' ); ?></th>
                    <th style="text-align:center;"><?php esc_html_e( 'Status', 'swvt-hr' ); ?></th>
                    <th style="text-align:center; width:130px;"><?php esc_html_e( 'Actions', 'swvt-hr' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $branches ) ) : ?>
                    <tr>
                        <td colspan="9" style="text-align:center; color:#787c82; padding:30px;">
                            <?php esc_html_e( 'No branches found. Please add a branch.', 'swvt-hr' ); ?>
                        </td>
                    </tr>
                <?php else : 
                    $zebra_idx = 0;
                    foreach ( $branches as $b ) : 
                        $manager_name = $b->manager_id ? SWVT_HR_Employee::get( $b->manager_id )->full_name : __( 'Not Assigned', 'swvt-hr' );
                        $has_override = ! is_null( $b->commission_rate ) && $b->commission_rate > 0;
                        
                        $zebra_class = ( $zebra_idx % 2 === 1 ) ? 'zebra' : '';
                        $zebra_idx++;
                ?>
                    <tr class="<?php echo $zebra_class; ?>" id="swvt-hr-branch-row-<?php echo $b->id; ?>">
                        <td style="padding:11px 18px;">
                            <span class="swvt-hr-branch-code"><?php echo esc_html( $b->code ); ?></span>
                        </td>
                        <td class="swvt-hr-branches-name-cell" style="font-weight:600; color:#1d2327;">
                            <?php echo esc_html( $b->name ); ?>
                        </td>
                        <td><?php echo esc_html( $b->city ); ?></td>
                        <td><?php echo esc_html( $manager_name ); ?></td>
                        <td style="font-variant-numeric:tabular-nums; color:#646970;"><?php echo esc_html( $b->phone ); ?></td>
                        <td>
                            <?php if ( $has_override ) : ?>
                                <span class="swvt-hr-badge swvt-hr-badge-success" style="font-variant-numeric:tabular-nums;">
                                    <?php echo ( $b->commission_rate * 1000 ) . ' ‰'; ?>
                                </span>
                            <?php else : ?>
                                <span style="color:#9aa0a6; font-size:12px;"><?php esc_html_e( 'Default', 'swvt-hr' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-variant-numeric:tabular-nums; font-weight:500;">
                            <?php echo SWVT_HR::format_number( $b->sales_target ); ?> EGP
                        </td>
                        <td style="text-align:center;">
                            <span class="swvt-hr-pill swvt-hr-pill-<?php echo esc_attr( $b->status ); ?>">
                                <i></i><?php echo $b->status === 'active' ? __( 'Active', 'swvt-hr' ) : __( 'Inactive', 'swvt-hr' ); ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div class="swvt-hr-branches-actions" style="display:flex; gap:6px; justify-content:center;">
                                <a href="<?php echo admin_url( 'admin.php?page=swvt-hr-branches-edit&id=' . $b->id ); ?>" class="swvt-hr-btn swvt-hr-branches-action-btn" style="padding:4px 8px; font-size:12px;">
                                    <?php esc_html_e( 'Edit', 'swvt-hr' ); ?>
                                </a>
                                <button type="button" class="swvt-hr-btn swvt-hr-btn-danger swvt-hr-delete-branch swvt-hr-branches-action-btn" data-id="<?php echo $b->id; ?>" style="padding:4px 8px; font-size:12px;">
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
