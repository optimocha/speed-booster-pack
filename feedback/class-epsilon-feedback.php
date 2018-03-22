<?php

class Epsilon_Feedback {

	private $plugin_file = '';
	private $plugin_name = '';

	function __construct( $_plugin_file ) {

		$this->plugin_file = $_plugin_file;
		$this->plugin_name = basename( $this->plugin_file, '.php' );

		// Deactivation
		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array(
			$this,
			'filter_action_links',
		) );
		add_action( 'admin_footer-plugins.php', array( $this, 'goodbye_ajax' ) );
		add_action( 'wp_ajax_epsilon_deactivate_plugin', array( $this, 'epsilon_deactivate_plugin_callback' ) );

	}

	/**
	 * Filter the deactivation link to allow us to present a form when the user deactivates the plugin
	 *
	 * @since 1.0.0
	 */
	public function filter_action_links( $links ) {

		if ( isset( $links['deactivate'] ) ) {
			$deactivation_link = $links['deactivate'];
			// Insert an onClick action to allow form before deactivating
			$deactivation_link   = str_replace( '<a ', '<div class="epsilon-deactivate-form-wrapper"><span class="epsilon-deactivate-form" id="epsilon-deactivate-form-' . esc_attr( $this->plugin_name ) . '"></span></div><a onclick="javascript:event.preventDefault();" id="epsilon-deactivate-link-' . esc_attr( $this->plugin_name ) . '" ', $deactivation_link );
			$links['deactivate'] = $deactivation_link;
		}

		return $links;
	}

	/**
	 * Form text strings
	 * These can be filtered
	 *
	 * @since 1.0.0
	 */
	public function goodbye_ajax() {
		// Get our strings for the form
		$form = $this->get_form_info();

		// Build the HTML to go in the form
		$html = '<div class="epsilon-deactivate-form-head"><strong>' . esc_html( $form['heading'] ) . '</strong></div>';
		$html .= '<div class="epsilon-deactivate-form-body"><p>' . esc_html( $form['body'] ) . '</p>';
		if ( is_array( $form['options'] ) ) {
			$html .= '<div class="epsilon-deactivate-options"><p>';
			foreach ( $form['options'] as $key => $option ) {
				if ( 'features' == $key ) {
					$html .= '<input type="radio" name="epsilon-deactivate-reason" checked="checked" id="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"> <label for="' . esc_attr( $key ) . '">' . esc_attr( $option ) . '</label><br>';
				} else {
					$html .= '<input type="radio" name="epsilon-deactivate-reason" id="' . esc_attr( $key ) . '" value="' . esc_attr( $key ) . '"> <label for="' . esc_attr( $key ) . '">' . esc_attr( $option ) . '</label><br>';
				}
			}
			$html .= '</p><label id="epsilon-deactivate-details-label" for="epsilon-deactivate-reasons"><strong>' . esc_html( $form['details'] ) . '</strong></label><textarea name="epsilon-deactivate-details" id="epsilon-deactivate-details" rows="2" style="width:100%"></textarea>';
			$html .= '<input type="checkbox" name="epsilon-deactivate-tracking" checked="" id="allow-tracking" value="yes"> <label for="allow-tracking">' . esc_html__( 'Allow us to get more information in order to improve our plugin', 'sb-pack' ) . '</label><br>';
			$html .= '</div><!-- .epsilon-deactivate-options -->';
		}
		$html .= '</div><!-- .epsilon-deactivate-form-body -->';
		$html .= '<p class="deactivating-spinner"><span class="spinner"></span> ' . __( 'Submitting form', 'sb-pack' ) . '</p>';
		$html .= '<div class="epsilon-deactivate-form-footer"><p><a id="epsilon-deactivate-plugin" href="#">' . __( 'Just Deactivate', 'sb-pack' ) . '</a><a id="epsilon-deactivate-submit-form" class="button button-primary" href="#">' . __( 'Submit and Deactivate', 'sb-pack' ) . '</a></p></div>'
		?>
		<div class="epsilon-deactivate-form-bg"></div>
		<style type="text/css">
			.epsilon-deactivate-form-active .epsilon-deactivate-form-bg {
				background: rgba(0, 0, 0, .5);
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
			}

			.epsilon-deactivate-form-wrapper {
				position: relative;
				z-index: 999;
				display: none;
			}

			.epsilon-deactivate-form-active .epsilon-deactivate-form-wrapper {
				display: block;
			}

			.epsilon-deactivate-form {
				display: none;
			}

			.epsilon-deactivate-form-active .epsilon-deactivate-form {
				position: absolute;
				bottom: 30px;
				left: 0;
				max-width: 400px;
				background: #fff;
				white-space: normal;
			}

			.epsilon-deactivate-form-head {
				background: #272754;
				color: #fff;
				padding: 8px 18px;
			}

			.epsilon-deactivate-form-body {
				padding: 8px 18px;
				color: #444;
			}

			.deactivating-spinner {
				display: none;
			}

			.deactivating-spinner .spinner {
				float: none;
				margin: 4px 4px 0 18px;
				vertical-align: bottom;
				visibility: visible;
			}

			.epsilon-deactivate-form-footer {
				padding: 8px 18px;
			}

			.epsilon-deactivate-form-footer p {
				display: flex;
				align-items: center;
				justify-content: space-between;
			}

			.epsilon-deactivate-form.process-response .epsilon-deactivate-form-body,
			.epsilon-deactivate-form.process-response .epsilon-deactivate-form-footer {
				position: relative;
			}

			.epsilon-deactivate-form.process-response .epsilon-deactivate-form-body:after,
			.epsilon-deactivate-form.process-response .epsilon-deactivate-form-footer:after {
				content: "";
				display: block;
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(255, 255, 255, .5);
			}
		</style>
		<script>
			jQuery( document ).ready( function( $ ) {
				var deactivateURL = $( "#epsilon-deactivate-link-<?php echo esc_attr( $this->plugin_name ); ?>" ),
					formContainer = $( '#epsilon-deactivate-form-<?php echo esc_attr( $this->plugin_name ); ?>' ),
					detailsStrings = {
						'setup': '<?php echo __( 'What was the dificult part ?', 'sb-pack' ) ?>',
						'documentation': '<?php echo __( 'What can we describe more ?', 'sb-pack' ) ?>',
						'features': '<?php echo __( 'How could we improve ?', 'sb-pack' ) ?>',
						'better-plugin': '<?php echo __( 'Can you mention it ?', 'sb-pack' ) ?>',
						'incompatibility': '<?php echo __( 'With what plugin or theme is incompatible ?', 'sb-pack' ) ?>',
					};

				$( deactivateURL ).on( "click", function() {
					// We'll send the user to this deactivation link when they've completed or dismissed the form
					var url = deactivateURL.attr( 'href' );
					$( 'body' ).toggleClass( 'epsilon-deactivate-form-active' );
					formContainer.fadeIn();
					formContainer.html( '<?php echo $html; ?>' );

					formContainer.on( 'change', 'input[name="epsilon-deactivate-reason"]', function() {
						var detailsLabel = formContainer.find( '#epsilon-deactivate-details-label strong' );
						var value = formContainer.find( 'input[name="epsilon-deactivate-reason"]:checked' ).val();
						detailsLabel.text( detailsStrings[ value ] );
					} );

					formContainer.on( 'click', '#epsilon-deactivate-submit-form', function( e ) {
						var data = {
							'action': 'epsilon_deactivate_plugin',
							'security': "<?php echo wp_create_nonce( 'epsilon_deactivate_plugin' ); ?>",
							'dataType': "json"
						};
						e.preventDefault();
						// As soon as we click, the body of the form should disappear
						formContainer.addClass( 'process-response' );
						// Fade in spinner
						formContainer.find( ".deactivating-spinner" ).fadeIn();

						data[ 'reason' ] = formContainer.find( 'input[name="epsilon-deactivate-reason"]:checked' ).val();
						data[ 'details' ] = formContainer.find( '#epsilon-deactivate-details' ).val();
						data[ 'tracking' ] = formContainer.find( '#allow-tracking:checked' ).length;

						$.post(
							ajaxurl,
							data,
							function( response ) {
								// Redirect to original deactivation URL
								window.location.href = url;
							}
						);
					} );

					formContainer.on( 'click', '#epsilon-deactivate-plugin', function( e ) {
						e.preventDefault();
						window.location.href = url;
					} );

					// If we click outside the form, the form will close
					$( '.epsilon-deactivate-form-bg' ).on( 'click', function() {
						formContainer.fadeOut();
						$( 'body' ).removeClass( 'epsilon-deactivate-form-active' );
					} );
				} );
			} );
		</script>
	<?php }

	/*
	 * Form text strings
	 * These are non-filterable and used as fallback in case filtered strings aren't set correctly
	 * @since 1.0.0
	 */
	public function get_form_info() {
		$form            = array();
		$form['heading'] = __( 'Sorry to see you go', 'sb-pack' );
		$form['body']    = __( 'Before you deactivate the plugin, would you quickly give us your reason for doing so?', 'sb-pack' );
		$form['options'] = array(
			'setup'           => __( 'Set up is too difficult', 'sb-pack' ),
			'documentation'   => __( 'Lack of documentation', 'sb-pack' ),
			'features'        => __( 'Not the features I wanted', 'sb-pack' ),
			'better-plugin'   => __( 'Found a better plugin', 'sb-pack' ),
			'incompatibility' => __( 'Incompatible with theme or plugin', 'sb-pack' ),
		);
		$form['details'] = __( 'How could we improve ?', 'sb-pack' );

		return $form;
	}

	public function epsilon_deactivate_plugin_callback() {

		check_ajax_referer( 'epsilon_deactivate_plugin', 'security' );

		if ( isset( $_POST['reason'] ) && isset( $_POST['details'] ) && isset( $_POST['tracking'] ) ) {
			require_once 'class-epsilon-plugin-request.php';
			$args    = array(
				'reason'   => $_POST['reason'],
				'details'  => $_POST['details'],
				'tracking' => $_POST['tracking'],
			);
			$request = new Epsilon_Plugin_Request( $this->plugin_file, $args );
			if ( $request->request_successful ) {
				echo json_encode( array(
					'status' => 'ok',
				) );
			} else {
				echo json_encode( array(
					'status' => 'nok',
				) );
			}
		} else {
			echo json_encode( array(
				'status' => 'ok',
			) );
		}

		die();

	}

}