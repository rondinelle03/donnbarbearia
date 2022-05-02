<?php
/**
 * Meta oxes mamager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Engine_CPT_Meta' ) ) {
	require jet_engine()->plugin_path( 'includes/components/meta-boxes/post.php' );
}

if ( ! class_exists( 'Jet_Engine_Options_Page_Factory' ) ) {

	/**
	 * Define Jet_Engine_Options_Page_Factory class
	 */
	class Jet_Engine_Options_Page_Factory extends Jet_Engine_CPT_Meta {

		/**
		 * Current page data
		 *
		 * @var null
		 */
		public $page = null;

		/**
		 * Current page slug
		 *
		 * @var null
		 */
		public $slug = null;

		/**
		 * Prepared fields array
		 *
		 * @var null
		 */
		public $prepared_fields = null;

		/**
		 * Holder for is page or not is page now prop
		 *
		 * @var null
		 */
		public $is_page_now = null;

		/**
		 * Inerface builder instance
		 *
		 * @var null
		 */
		public $builder = null;

		/**
		 * Saved options holder
		 *
		 * @var null
		 */
		public $options = null;

		/**
		 * Save trigger
		 *
		 * @var string
		 */
		public $save_action = 'jet-engine-op-save-settings';

		public $layout_now        = false;
		public $current_component = false;
		public $current_panel     = false;
		public $custom_css        = array();

		/**
		 * Constructor for the class
		 */
		public function __construct( $page ) {

			$this->page     = $page;
			$this->slug     = $page['slug'];
			$this->meta_box = $page['fields'];

			if ( empty( $this->page['position'] ) && 0 !== $this->page['position'] ) {
				$this->page['position'] = null;
			}

			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );

			if ( $this->is_page_now() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'init_builder' ), 0 );
				add_action( 'admin_init', array( $this, 'save' ), 40 );
				add_action( 'admin_notices', array( $this, 'saved_notice' ) );
			}

		}

		/**
		 * Check if current options page is processed now
		 *
		 * @return boolean [description]
		 */
		public function is_page_now() {

			if ( null !== $this->is_page_now ) {
				return $this->is_page_now;
			}

			if ( isset( $_GET['page'] ) && $this->slug === $_GET['page'] ) {
				$this->is_page_now = true;
			} else {
				$this->is_page_now = false;
			}

			return $this->is_page_now;

		}

		/**
		 * Register avalable menu pages
		 *
		 * @return [type] [description]
		 */
		public function register_menu_page() {

			if ( ! empty( $this->page['parent'] ) ) {
				add_submenu_page(
					$this->page['parent'],
					$this->page['labels']['name'],
					$this->page['labels']['menu_name'],
					$this->page['capability'],
					$this->page['slug'],
					array( $this, 'render_page' )
				);
			} else {
				add_menu_page(
					$this->page['labels']['name'],
					$this->page['labels']['menu_name'],
					$this->page['capability'],
					$this->page['slug'],
					array( $this, 'render_page' ),
					$this->page['icon'],
					$this->page['position']
				);

			}
		}

		/**
		 * Process options saving
		 *
		 * @return [type] [description]
		 */
		public function save() {

			if ( ! isset( $_REQUEST['action'] ) || $this->save_action !== $_REQUEST['action'] ) {
				return;
			}

			$capability = $this->page['capability'];

			if ( ! current_user_can( $capability ) ) {
				return;
			}

			$current = get_option( $this->slug, array() );
			$data    = $_REQUEST;

			$fields = $this->get_prepared_fields();

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $field ) {

					if ( isset( $data[ $key ] ) ) {

						$value = $data[ $key ];
						$value = $this->maybe_apply_sanitize_callback( $value, $field );

						if ( $this->to_timestamp( $field ) ) {
							$value = strtotime( $value );
						}

						$current[ $key ] = $value;

					} else {
						$current[ $key ] = null;
					}
				}
			}

			update_option( $this->slug, $current );

			$redirect = add_query_arg(
				array(
					'page'         => $this->slug,
					'dialog-saved' => true,
				),
				esc_url( admin_url( 'admin.php' ) )
			);

			wp_redirect( $redirect );
			die();

		}

		/**
		 * Is date field
		 *
		 * @param  [type]  $input_type [description]
		 * @return boolean             [description]
		 */
		public function to_timestamp( $field ) {

			if ( empty( $field['input_type'] ) ) {
				return false;
			}

			if ( empty( $field['is_timestamp'] ) ) {
				return false;
			}


			if ( ! in_array( $field['input_type'], array( 'date', 'datetime-local' ) ) ) {
				return false;
			}

			return ( true === $field['is_timestamp'] );

		}

		/**
		 * Maybe apply sanitize callback
		 *
		 * @param mixed $value
		 * @param array $field
		 *
		 * @return mixed
		 */
		public function maybe_apply_sanitize_callback( $value, $field ) {

			if ( is_array( $value ) && 'repeater' === $field['type'] && ! empty( $field['fields'] ) ) {
				foreach ( $value as $item_id => $item ) {
					foreach ( $item as $sub_item_id => $sub_item_value ) {
						$value[ $item_id ][ $sub_item_id ] = $this->maybe_apply_sanitize_callback( $sub_item_value, $field['fields'][ $sub_item_id ] );
					}
				}
			}

			if ( 'checkbox' === $field['type'] && ! empty( $field['is_array'] ) ) {
				$raw    = ! empty( $value ) ? $value : array();
				$result = array();

				if ( is_array( $raw ) ) {
					foreach ( $raw as $raw_key => $raw_value ) {
						$bool_value = filter_var( $raw_value, FILTER_VALIDATE_BOOLEAN );
						if ( $bool_value ) {
							$result[] = $raw_key;
						}
					}
				}

				return $result;
			}

			if ( ! empty( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
				$value = call_user_func( $field['sanitize_callback'], $value, $field['name'], $field );
			}

			return $value;
		}

		/**
		 * Show saved notice
		 *
		 * @return bool
		 */
		public function saved_notice() {

			if ( ! isset( $_GET['dialog-saved'] ) ) {
				return false;
			}

			$message = __( 'Saved', 'jet-engine' );

			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', $message );

			return true;

		}

		/**
		 * Initialize page builder
		 *
		 * @return [type] [description]
		 */
		public function init_builder() {

			$builder_data = jet_engine()->framework->get_included_module_data( 'cherry-x-interface-builder.php' );

			$this->builder = new \CX_Interface_Builder(
				array(
					'path' => $builder_data['path'],
					'url'  => $builder_data['url'],
				)
			);

			$slug = $this->page['slug'];

			$this->builder->register_section(
				array(
					$slug => array(
						'type'   => 'section',
						'scroll' => false,
						'title'  => apply_filters( 'jet-engine/compatibility/translate-string', $this->page['labels']['name'] ),
					),
				)
			);

			$this->builder->register_form(
				array(
					$slug . '_form' => array(
						'type'   => 'form',
						'parent' => $slug,
						'action' => add_query_arg(
							array(
								'page'   => $slug,
								'action' => $this->save_action,
							),
							esc_url( admin_url( 'admin.php' ) )
						),
					),
				)
			);

			$this->builder->register_settings(
				array(
					'settings_top' => array(
						'type'   => 'settings',
						'parent' => $slug . '_form',
					),
					'settings_bottom' => array(
						'type'   => 'settings',
						'parent' => $slug . '_form',
					),
				)
			);

			if ( ! empty( $this->page['fields'] ) ) {

				$this->builder->register_control( $this->get_prepared_fields() );

			}

			$label = __( 'Save', 'jet-engine' );

			$this->builder->register_html(
				array(
					'save_button' => array(
						'type'   => 'html',
						'parent' => 'settings_bottom',
						'class'  => 'cx-component dialog-save',
						'html'   => '<button type="submit" class="cx-button cx-button-primary-style">' . $label . '</button>',
					),
				)
			);

			$this->print_custom_css();

		}

		/**
		 * Print custom CSS
		 *
		 * @return void
		 */
		public function print_custom_css() {

			if ( ! empty( $this->custom_css ) ) {

				// Modifying selectors.
				$selectors = array_map( function ( $selector ) {
					return '#settings_top.cx-settings__content ' . $selector;
				}, array_keys( $this->custom_css ) );

				$values = array_values( $this->custom_css );

				$this->custom_css = array_combine( $selectors, $values );
			}

			$this->maybe_enqueue_custom_css( null );

		}

		/**
		 * Get saved options
		 *
		 * @param  [type]  $option [description]
		 * @param  boolean $default [description]
		 * @return [type]           [description]
		 */
		public function get( $option = '', $default = false, $field = array() ) {

			if ( null === $this->options ) {
				$this->options = get_option( $this->slug, array() );
			}

			return isset( $this->options[ $option ] ) ? wp_unslash( $this->options[ $option ] ) : $default;

		}

		/**
		 * Render options page
		 *
		 * @return [type] [description]
		 */
		public function render_page() {
			echo '<div class="jet-engine-options-page-wrap">';
			$this->builder->render();
			echo '</div>';
		}

		/**
		 * Prepare stored fields array to be registered in interface builder
		 *
		 * @return array
		 */
		public function get_prepared_fields() {

			if ( null !== $this->prepared_fields ) {
				return $this->prepared_fields;
			}

			$result = $this->prepare_meta_fields( $this->page['fields'] );

			// Prepare fields array to use in Options Page.
			foreach ( $result as $field_name => $field_args ) {

				if ( empty( $field_args['parent'] ) ) {
					$result[ $field_name ]['parent'] = 'settings_top';
				}

				if ( ! empty( $field_args['element'] ) && 'control' === $field_args['element'] ) {
					$result[ $field_name ]['id']   = $field_name;
					$result[ $field_name ]['name'] = $field_name;

					$result[ $field_name ]['value'] = $this->get(
						$field_name,
						false,
						$result[ $field_name ]
					);

					$result[ $field_name ]['value'] = $this->prepare_field_value(
						$result[ $field_name ],
						$result[ $field_name ]['value']
					);
				}
			}

			$this->prepared_fields = $result;

			return $result;

		}

		/**
		 * Prepare field value.
		 *
		 * @param $field
		 * @param $value
		 *
		 * @return array
		 */
		public function prepare_field_value( $field, $value ) {

			switch ( $field['type'] ) {
				case 'repeater':

					if ( is_array( $value ) && ! empty( $field['fields'] ) ) {

						$repeater_fields =  $field['fields'];

						foreach ( $value as $item_id => $item_value ) {
							foreach ( $item_value as $repeater_field_id => $repeater_field_value ) {
								$value[ $item_id ][ $repeater_field_id ] = $this->prepare_field_value( $repeater_fields[ $repeater_field_id ], $repeater_field_value );
							}
						}
					}

					break;

				case 'checkbox':
				case 'checkbox-raw':

					if ( ! empty( $field['is_array'] ) && ! empty( $field['options'] ) && ! empty( $value ) ) {

						$adjusted = array();

						if ( ! is_array( $value ) ) {
							$value = array( $value );
						}

						foreach ( $value as $val ) {
							$adjusted[ $val ] = 'true';
						}

						foreach ( $field['options'] as $opt_val => $opt_label ) {
							if ( ! in_array( $opt_val, $value ) ) {
								$adjusted[ $opt_val ] = 'false';
							}
						}

						$value = $adjusted;
					}

					break;

				case 'text':

					if ( ! empty( $field['input_type'] ) && ! empty( $field['is_timestamp'] ) ) {

						if ( is_numeric( $value ) ) {
							switch ( $field['input_type'] ) {
								case 'date':
									$value = date( 'Y-m-d', $value );
									break;

								case 'datetime-local':
									$value = date( 'Y-m-d\TH:i', $value );
									break;
							}
						}

					}

					break;

			}

			return $value;
		}

		public function is_allowed_on_current_admin_hook( $hook ) {
			return true;
		}

		/**
		 * Get options list for use as select options
		 *
		 * @return [type] [description]
		 */
		public function get_options_for_select() {

			$fields = array();

			if ( ! empty( $this->page['fields'] ) ) {
				foreach ( $this->page['fields'] as $field ) {

					$key = $this->slug . '::' . $field['name'];

					$fields[ $key ] = array(
						'title' => $field['title'],
						'type'  => ( 'field' === $field['object_type'] ) ? $field['type'] : $field['object_type'],
					);
				}
			}

			return array(
				'label'   => $this->page['labels']['name'],
				'options' => $fields,
			);

		}

	}

}
