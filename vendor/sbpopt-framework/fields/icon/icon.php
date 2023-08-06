<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: icon
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'SBPOPT_Field_icon' ) ) {
  class SBPOPT_Field_icon extends SBPOPT_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'button_title' => esc_html__( 'Add Icon', 'sbpopt' ),
        'remove_title' => esc_html__( 'Remove Icon', 'sbpopt' ),
      ) );

      echo $this->field_before();

      $nonce  = wp_create_nonce( 'sbpopt_icon_nonce' );
      $hidden = ( empty( $this->value ) ) ? ' hidden' : '';

      echo '<div class="sbpopt-icon-select">';
      echo '<span class="sbpopt-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="button button-primary sbpopt-icon-add" data-nonce="'. esc_attr( $nonce ) .'">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="button sbpopt-warning-primary sbpopt-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="hidden" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="sbpopt-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      add_action( 'admin_footer', array( 'SBPOPT_Field_icon', 'add_footer_modal_icon' ) );
      add_action( 'customize_controls_print_footer_scripts', array( 'SBPOPT_Field_icon', 'add_footer_modal_icon' ) );
    }

    public static function add_footer_modal_icon() {
    ?>
      <div id="sbpopt-modal-icon" class="sbpopt-modal sbpopt-modal-icon hidden">
        <div class="sbpopt-modal-table">
          <div class="sbpopt-modal-table-cell">
            <div class="sbpopt-modal-overlay"></div>
            <div class="sbpopt-modal-inner">
              <div class="sbpopt-modal-title">
                <?php esc_html_e( 'Add Icon', 'sbpopt' ); ?>
                <div class="sbpopt-modal-close sbpopt-icon-close"></div>
              </div>
              <div class="sbpopt-modal-header">
                <input type="text" placeholder="<?php esc_html_e( 'Search...', 'sbpopt' ); ?>" class="sbpopt-icon-search" />
              </div>
              <div class="sbpopt-modal-content">
                <div class="sbpopt-modal-loading"><div class="sbpopt-loading"></div></div>
                <div class="sbpopt-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
