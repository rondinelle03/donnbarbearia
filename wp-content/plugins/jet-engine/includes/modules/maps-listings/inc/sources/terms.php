<?php
namespace Jet_Engine\Modules\Maps_Listings\Source;

class Terms extends Base {

	/**
	 * Returns source ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'terms';
	}

	public function get_obj_by_id( $id ) {
		return get_term( $id );
	}

	public function get_field_value( $obj, $field ) {
		return get_term_meta( $obj->term_id, $field, true );
	}

	public function update_field_value( $obj, $key, $value ) {
		update_term_meta( $obj->term_id, $key, $value );
	}

	public function get_failure_key( $obj ) {
		return 'Term #' . $obj->term_id;
	}

	public function add_preload_hooks( $preload_fields ) {

		foreach ( $preload_fields as $field ) {
			$fields = explode( '+', $field );

			if ( 1 === count( $fields ) ) {
				$field = str_replace( 'tax::', '', $field );
				add_action( 'cx_term_meta/before_save_meta/' . $field, array( $this, 'preload' ), 10, 2 );
			} else {
				$this->field_groups[] = array_map( function ( $item ) {
					return str_replace( 'tax::', '', $item );
				}, $fields );
			}
		}

		if ( ! empty( $this->field_groups ) ) {
			add_action( 'cx_term_meta/after_save', array( $this, 'preload_groups' ) );
		}
	}

	public function filtered_preload_fields( $field ) {
		return false !== strpos( $field, 'tax::' );
	}

}
