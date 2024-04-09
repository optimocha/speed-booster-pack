<?php

namespace Optimocha\SpeedBooster\Backend;

defined('ABSPATH') || exit;

class Notices
{
    /**
     * Number of notices.
     *
     * @since 5.0.0
     * @var int $notice_count
     */
    public static $notice_count = 0;

    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action('wp_ajax_sbp_dismiss_notice', [$this, 'dismiss_notice']);
        add_action('wp_ajax_sbp_remove_notice_transient', [$this, 'remove_notice_transient']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Dismisses a notice.
     *
     * @param mixed $id
     * @return void
     */
    public static function dismiss_notice($id = null)
    {
        $is_ajax = false;
        if ($id == null) {
            $is_ajax = true;
            if (isset($_GET['action']) && $_GET['action'] == 'sbp_dismiss_notice') {
                $id = $_GET['notice_id'];
            }
        }

        if ($id && current_user_can('manage_options')) { // Dismiss notice for ever
            $dismissed_notices = self::get_dismissed_notices();
            $dismissed_notices[] = $id;
            update_user_meta(get_current_user_id(), 'sbp_dismissed_notices', $dismissed_notices);
            if ($is_ajax) {
                wp_die();
            }
        }
    }

    /**
     * Removes the transient of the notice.
     *
     * @return void
     */
    public function remove_notice_transient()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'sbp_remove_notice_transient' && current_user_can('manage_options')) { // Remove notice transient for one time processes like "cache cleared"
            $id = $_GET['notice_id'];
            delete_transient($id);
            wp_die();
        }
    }

    /**
     * Displays a notice.
     *
     * If notice is like "Cache cleared" etc. set recurrent to true. If recurrent is true, notice manager will check transient.
     *
     * @param string $id
     * @param string $text
     * @param string $type error|warning|success|info
     * @param bool $is_dismissible
     * @param string $notice_type one_time|recurrent|flash
     */
    public static function display_notice(
        string $id,
        string $text,
        string $type = 'success',
        bool $is_dismissible = true,
        string $notice_type = 'one_time',
        $pages = null
    )
    {
        if (!is_admin()) {
            return;
        }

        $action = $notice_type == 'recurrent' ? 'sbp_remove_notice_transient' : 'sbp_dismiss_notice';
        if (($notice_type == 'one_time' && self::should_display($id)) || ($notice_type == 'recurrent' && get_transient($id)) || ($notice_type == 'flash' && get_transient($id))) {
            add_action('admin_notices',
                function () use ($type, $is_dismissible, $id, $text, $action, $pages, $notice_type) {
                    self::$notice_count++;
                    if ($pages !== null && !is_array($pages)) {
                        $pages = [$pages];
                    }
                    if ($pages !== null && get_current_screen() && !in_array(get_current_screen()->id, $pages)) {
                        return;
                    }
                    echo '<div class="notice sbp-notice notice-' . $type . ' ' . ($is_dismissible ? 'is-dismissible' : null) . '" data-notice-action="' . $action . '" data-notice-id="' . $id . '">' . $text . '</div>';
                    if ($notice_type == 'flash') {
                        delete_transient($id);
                    }
                });
        }
    }

    /**
     * Checks if the notice should be displayed or not.
     *
     * @param mixed $id
     * @return bool
     */
    public static function should_display($id): bool
    {
        $dismissed_notices = self::get_dismissed_notices();

        return !in_array($id, $dismissed_notices);
    }

    /**
     * Gets dismissed notices from the _usermeta table.
     *
     * @return array
     */
    public static function get_dismissed_notices(): array
    {
        $dismissed_notices = get_user_meta(get_current_user_id(), 'sbp_dismissed_notices', true);

        return is_array($dismissed_notices) ? $dismissed_notices : [];
    }

    /**
     * Enqueues the inline JS code necessary to dismiss a notice.
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        wp_add_inline_script('jquery',
            'jQuery(document).on(\'click\', \'.sbp-notice .notice-dismiss, .sbp-notice .notice-dismiss-button\', function(e) {
			if (jQuery(e.target).hasClass("notice-dismiss-button")) {
				var notice_id = jQuery(this).data(\'notice-id\');
				var $notice = jQuery("div[data-notice-id=" + notice_id + "]");
				$notice.stop().slideUp();
			} else {
				var $notice = jQuery(this).parent();
				var notice_id = $notice.data(\'notice-id\');
            }
			var action = $notice.data(\'notice-action\');
			var data = {action: action, notice_id: notice_id};
			jQuery.get(ajaxurl, data);
		});');
    }

    /**
     * Gets a number of notices.
     *
     * @return int
     */
    public static function get_notice_count(): int
    {
        return self::$notice_count;
    }

    /**
     * Checks whether the notice is dismissed or not.
     *
     * @param $id
     * @return bool
     */
    public static function has_dismissed($id): bool
    {
        $dismissed_notices = self::get_dismissed_notices();
        return in_array($id, $dismissed_notices);
    }
}
