<?php
/**
 * Assets Enqueue Class (English LTR).
 *
 * @package    SWVT_HR
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class SWVT_HR_Assets {

    public function enqueue( $hook ) {
        // Enqueue only on plugin pages
        if ( strpos( $hook, 'swvt-hr' ) === false ) {
            return;
        }

        // Google Fonts
        wp_enqueue_style(
            'swvt-hr-fonts',
            'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;800&display=swap',
            [],
            null
        );

        // Core CSS
        wp_enqueue_style(
            'swvt-hr-admin',
            SWVT_HR_URL . 'assets/css/admin.css',
            [],
            SWVT_HR_VERSION
        );

        // Chart.js library (enqueued from local script path)
        wp_enqueue_script(
            'chartjs',
            SWVT_HR_URL . 'assets/js/chart.min.js',
            [],
            '4.4.0',
            true
        );

        // Custom admin JS actions
        wp_enqueue_script(
            'swvt-hr-admin',
            SWVT_HR_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            SWVT_HR_VERSION,
            true
        );

        // Localize script with WP AJAX URLs and localized strings in English
        wp_localize_script(
            'swvt-hr-admin',
            'SWVT_HR',
            [
                'ajax'  => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'swvt_hr_nonce' ),
                'i18n'  => [
                    'confirmRecalc' => __( 'Are you sure you want to recalculate payroll for this month?', 'swvt-hr' ),
                    'confirmDelete' => __( 'Are you sure you want to delete this record?', 'swvt-hr' ),
                ],
            ]
        );
    }
}
