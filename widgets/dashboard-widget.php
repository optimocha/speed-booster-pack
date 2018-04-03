<?Php

// WordPress Events and News
wp_add_dashboard_widget( 'sbp_dashboard_primary', __( 'Speed Booster Pack Overview' ), 'sbp_dashboard_events_news' );


/**
 * Renders the Events and News dashboard widget.
 *
 * @since 3.7
 */
function sbp_dashboard_events_news() {

	?>

	<div class="wordpress-news hide-if-no-js">
		<?php sbp_dashboard_widget(); ?>
	</div>


	<!--
	<p class="community-events-footer">
	see this:
	-   https://github.com/WordPress/WordPress/blob/921e131eae45801b8fdb1ecfceb5d7839fdfd509/wp-admin/includes/dashboard.php#L1124-L1159
	-   https://codex.wordpress.org/Dashboard_Widgets_API#Advanced:_Forcing_your_widget_to_the_top

	</p>
	-->

	<?php
}


/**
 * 'WordPress Events and News' dashboard widget.
 *
 * @since 3.7
 */
function sbp_dashboard_widget() {
	$feeds = array(
		'news' => array(
			'link'         => apply_filters( 'sbp_dashboard_primary_link', __( 'https://www.machothemes.com/' ) ),
			'url'          => apply_filters( 'sbp_dashboard_primary_feed', __( 'https://www.machothemes.com/blog/feed/' ) ),
			'title'        => apply_filters( 'sbp_dashboard_primary_title', __( 'Speed Booster Pack Overview' ) ),
			'items'        => 3,
			'show_summary' => 1,
			'show_author'  => 0,
			'show_date'    => 0,
		),
	);

	sbp_dashboard_primary_output( 'sbp_dashboard_primary', $feeds );
}

/**
 * Display the WordPress events and news feeds.
 *
 * @since 3.7
 *
 * @param string $widget_id Widget ID.
 * @param array  $feeds     Array of RSS feeds.
 */
function sbp_dashboard_primary_output( $widget_id, $feeds ) {
	foreach ( $feeds as $type => $args ) {
		$args['type'] = $type;
		echo '<div class="rss-widget">';
		wp_widget_rss_output( $args['url'], $args );
		echo '</div>';
	}
}