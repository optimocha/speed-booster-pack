<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: group
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'SBPOPT_Field_group' ) ) {
  class SBPOPT_Field_group extends SBPOPT_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = wp_parse_args( $this->field, array(
        'max'                       => 0,
        'min'                       => 0,
        'fields'                    => array(),
        'button_title'              => esc_html__( 'Add New', 'sbpopt' ),
        'accordion_title_prefix'    => '',
        'accordion_title_number'    => false,
        'accordion_title_auto'      => true,
        'accordion_title_by'        => array(),
        'accordion_title_by_prefix' => ' ',
      ) );

      $title_prefix    = ( ! empty( $args['accordion_title_prefix'] ) ) ? $args['accordion_title_prefix'] : '';
      $title_number    = ( ! empty( $args['accordion_title_number'] ) ) ? true : false;
      $title_auto      = ( ! empty( $args['accordion_title_auto'] ) ) ? true : false;
      $title_first     = ( isset( $this->field['fields'][0]['id'] ) ) ? $this->field['fields'][0]['id'] : $this->field['fields'][1]['id'];
      $title_by        = ( is_array( $args['accordion_title_by'] ) ) ? $args['accordion_title_by'] : (array) $args['accordion_title_by'];
      $title_by        = ( empty( $title_by ) ) ? array( $title_first ) : $title_by;
      $title_by_prefix = ( ! empty( $args['accordion_title_by_prefix'] ) ) ? $args['accordion_title_by_prefix'] : '';

      if ( preg_match( '/'. preg_quote( '['. $this->field['id'] .']' ) .'/', $this->unique ) ) {

        echo '<div class="sbpopt-notice sbpopt-notice-danger">'. esc_html__( 'Error: Field ID conflict.', 'sbpopt' ) .'</div>';

      } else {

        echo $this->field_before();

        echo '<div class="sbpopt-cloneable-item sbpopt-cloneable-hidden" data-depend-id="'. esc_attr( $this->field['id'] ) .'">';

          echo '<div class="sbpopt-cloneable-helper">';
          echo '<i class="sbpopt-cloneable-sort fas fa-arrows-alt"></i>';
          echo '<i class="sbpopt-cloneable-clone far fa-clone"></i>';
          echo '<i class="sbpopt-cloneable-remove sbpopt-confirm fas fa-times" data-confirm="'. esc_html__( 'Are you sure to delete this item?', 'sbpopt' ) .'"></i>';
          echo '</div>';

          echo '<h4 class="sbpopt-cloneable-title">';
          echo '<span class="sbpopt-cloneable-text">';
          echo ( $title_number ) ? '<span class="sbpopt-cloneable-title-number"></span>' : '';
          echo ( $title_prefix ) ? '<span class="sbpopt-cloneable-title-prefix">'. esc_attr( $title_prefix ) .'</span>' : '';
          echo ( $title_auto ) ? '<span class="sbpopt-cloneable-value"><span class="sbpopt-cloneable-placeholder"></span></span>' : '';
          echo '</span>';
          echo '</h4>';

          echo '<div class="sbpopt-cloneable-content">';
          foreach ( $this->field['fields'] as $field ) {

            $field_default = ( isset( $field['default'] ) ) ? $field['default'] : '';
            $field_unique  = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .'][0]' : $this->field['id'] .'[0]';

            SBPOPT::field( $field, $field_default, '___'. $field_unique, 'field/group' );

          }
          echo '</div>';

        echo '</div>';

        echo '<div class="sbpopt-cloneable-wrapper sbpopt-data-wrapper" data-title-by="'. esc_attr( json_encode( $title_by ) ) .'" data-title-by-prefix="'. esc_attr( $title_by_prefix ) .'" data-title-number="'. esc_attr( $title_number ) .'" data-field-id="['. esc_attr( $this->field['id'] ) .']" data-max="'. esc_attr( $args['max'] ) .'" data-min="'. esc_attr( $args['min'] ) .'">';

        if ( ! empty( $this->value ) ) {

          $num = 0;

          foreach ( $this->value as $value ) {

            $title = '';

            if ( ! empty( $title_by ) ) {

              $titles = array();

              foreach ( $title_by as $title_key ) {
                if ( isset( $value[ $title_key ] ) ) {
                  $titles[] = $value[ $title_key ];
                }
              }

              $title = join( $title_by_prefix, $titles );

            }

            $title = ( is_array( $title ) ) ? reset( $title ) : $title;

            echo '<div class="sbpopt-cloneable-item">';

              echo '<div class="sbpopt-cloneable-helper">';
              echo '<i class="sbpopt-cloneable-sort fas fa-arrows-alt"></i>';
              echo '<i class="sbpopt-cloneable-clone far fa-clone"></i>';
              echo '<i class="sbpopt-cloneable-remove sbpopt-confirm fas fa-times" data-confirm="'. esc_html__( 'Are you sure to delete this item?', 'sbpopt' ) .'"></i>';
              echo '</div>';

              echo '<h4 class="sbpopt-cloneable-title">';
              echo '<span class="sbpopt-cloneable-text">';
              echo ( $title_number ) ? '<span class="sbpopt-cloneable-title-number">'. esc_attr( $num+1 ) .'.</span>' : '';
              echo ( $title_prefix ) ? '<span class="sbpopt-cloneable-title-prefix">'. esc_attr( $title_prefix ) .'</span>' : '';
              echo ( $title_auto ) ? '<span class="sbpopt-cloneable-value">' . esc_attr( $title ) .'</span>' : '';
              echo '</span>';
              echo '</h4>';

              echo '<div class="sbpopt-cloneable-content">';

              foreach ( $this->field['fields'] as $field ) {

                $field_unique = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .']['. $num .']' : $this->field['id'] .'['. $num .']';
                $field_value  = ( isset( $field['id'] ) && isset( $value[$field['id']] ) ) ? $value[$field['id']] : '';

                SBPOPT::field( $field, $field_value, $field_unique, 'field/group' );

              }

              echo '</div>';

            echo '</div>';

            $num++;

          }

        }

        echo '</div>';

        echo '<div class="sbpopt-cloneable-alert sbpopt-cloneable-max">'. esc_html__( 'You cannot add more.', 'sbpopt' ) .'</div>';
        echo '<div class="sbpopt-cloneable-alert sbpopt-cloneable-min">'. esc_html__( 'You cannot remove more.', 'sbpopt' ) .'</div>';
        echo '<a href="#" class="button button-primary sbpopt-cloneable-add">'. $args['button_title'] .'</a>';

        echo $this->field_after();

      }

    }

    public function enqueue() {

      if ( ! wp_script_is( 'jquery-ui-accordion' ) ) {
        wp_enqueue_script( 'jquery-ui-accordion' );
      }

      if ( ! wp_script_is( 'jquery-ui-sortable' ) ) {
        wp_enqueue_script( 'jquery-ui-sortable' );
      }

    }

  }
}
