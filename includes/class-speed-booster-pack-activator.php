<?php

/**
 * Fired during plugin activation
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0.0
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_Activator {

	/**
	 * Plugin activation function.
	 *
	 * This function runs when the plugin is activated.
	 *
	 * @since    4.0.0
     *
	 */
	public static function activate() {

        //Check user capability to activate plugin - Added V. 4.5.8.2
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        self::dependency_check();

        add_option( 'sbp_activation_defaults', true );

        // Don't do redirects when multiple plugins are bulk activated
        if (
            ( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) &&
            ( isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) ) {
            return;
        }
        add_option( 'sbp_activation_redirect', wp_get_current_user()->ID );

	}

    /**
     *
     * Check if the plugin can be activated
     * Check PHP and WordPress version compatibility
     *
     * @since 4.5.8.2
     */
    private static function dependency_check(): void {
        global $wp_version;

        $php = SBP_PHP_VERSION;
        $wp  = SBP_WP_VERSION;

        $allowed_html = [
            'p' => [],
            'a' => [
                'href' => [],
            ],
        ];

        if ( version_compare( PHP_VERSION, $php, '<' ) ) {
            deactivate_plugins( basename( __FILE__ ) );

            $html = wp_kses(
                '<p>' .
                sprintf(
                    __( 'This plugin can not be activated because it requires a PHP version greater than %1$s. Your PHP version can be updated by your hosting company.', 'speed-booster-pack' ),
                    $php
                )
                . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Go back', 'speed-booster-pack' ) . '</a>',
                $allowed_html
            );
            wp_die( wp_kses( $html, $allowed_html ) );
        }

        if ( version_compare( $wp_version, $wp, '<' ) ) {
            deactivate_plugins( basename( __FILE__ ) );
            $html = '<p>' .
                sprintf(
                    __( 'This plugin can not be activated because it requires a WordPress version greater than %1$s. Please go to Dashboard &#9656; Updates to gran the latest version of WordPress .', 'my_plugin' ),
                    $wp
                )
                . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Go back', 'my_plugin' ) . '</a>';
            wp_die( wp_kses( $html, $allowed_html ) );
        }
    }

}
