<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: backup
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'SBPOPT_Field_backup' ) ) {
  class SBPOPT_Field_backup extends SBPOPT_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $unique = $this->unique;
      $nonce  = wp_create_nonce( 'sbpopt_backup_nonce' );
      $export = add_query_arg( array( 'action' => 'sbpopt-export', 'unique' => $unique, 'nonce' => $nonce ), admin_url( 'admin-ajax.php' ) );

      echo $this->field_before();

      echo '<textarea name="sbpopt_import_data" class="sbpopt-import-data"></textarea>';
      echo '<button type="submit" class="button button-primary sbpopt-confirm sbpopt-import" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Import', 'sbpopt' ) .'</button>';
      echo '<hr />';
      echo '<textarea readonly="readonly" class="sbpopt-export-data">'. esc_attr( json_encode( get_option( $unique ) ) ) .'</textarea>';
      echo '<a href="'. esc_url( $export ) .'" class="button button-primary sbpopt-export" target="_blank">'. esc_html__( 'Export & Download', 'sbpopt' ) .'</a>';
      echo '<hr />';
      echo '<button type="submit" name="sbpopt_transient[reset]" value="reset" class="button sbpopt-warning-primary sbpopt-confirm sbpopt-reset" data-unique="'. esc_attr( $unique ) .'" data-nonce="'. esc_attr( $nonce ) .'">'. esc_html__( 'Reset', 'sbpopt' ) .'</button>';

      echo $this->field_after();

    }

  }
}
