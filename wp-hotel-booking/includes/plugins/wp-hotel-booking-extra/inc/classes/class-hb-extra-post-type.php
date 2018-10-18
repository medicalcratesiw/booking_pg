<?php

class HB_Extra_Post_Type {

	static $_instance = null;

	/**
	 * initialize class register post type, insert, update post
	 * with post_type = 'hb_extra_room'
	 */
	function __construct() {
		add_action( 'init', array( $this, 'init' ) );

		// update admin extra columns
		add_filter( 'manage_hb_extra_room_posts_columns', array( $this, 'extra_columns' ) );
		add_action( 'manage_hb_extra_room_posts_custom_column', array( $this, 'extra_columns_content' ) );

		add_action( 'admin_init', array( $this, 'settings_meta_box' ) );


		add_action( 'wp_ajax_tp_extra_package_remove', array( $this, 'tp_extra_package_remove' ) );
	}

	/**
	 * Registers a new post type
	 * @uses $wp_post_types Inserts new post type object into the list
	 *
	 * @param string  Post type key, must not exceed 20 characters
	 * @param array|string  See optional args description above.
	 *
	 * @return object|WP_Error the registered post type object, or an error object
	 */
	function init() {
		$labels = array(
			'name'               => __( 'Extra Room', 'wp-hotel-booking' ),
			'singular_name'      => __( 'Extra Room', 'wp-hotel-booking' ),
			'add_new'            => _x( 'Add New Extra Room', 'wp-hotel-booking', 'wp-hotel-booking' ),
			'add_new_item'       => __( 'Add New Extra Room', 'wp-hotel-booking' ),
			'edit_item'          => __( 'Edit Extra Room', 'wp-hotel-booking' ),
			'new_item'           => __( 'New Extra Room', 'wp-hotel-booking' ),
			'view_item'          => __( 'View Extra Room', 'wp-hotel-booking' ),
			'search_items'       => __( 'Search Extra Room', 'wp-hotel-booking' ),
			'not_found'          => __( 'No Extra Room found', 'wp-hotel-booking' ),
			'not_found_in_trash' => __( 'No Extra Room found in Trash', 'wp-hotel-booking' ),
			'parent_item_colon'  => __( 'Parent Singular Extra Room:', 'wp-hotel-booking' ),
			'menu_name'          => __( 'Extra Options', 'wp-hotel-booking' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'query_var'          => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'has_archive'        => false,
			'capability_type'    => 'hb_booking',
			'map_meta_cap'       => true,
			'show_in_menu'       => 'tp_hotel_booking',
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'supports'           => array( 'title', 'editor', 'custom-fields' ),
			'hierarchical'       => false
		);

		register_post_type( 'hb_extra_room', $args );
	}

	/**
	 * @param $column
	 */
	public function extra_columns_content( $column ) {
		global $post;
		$post_id = $post->ID;

		switch ( $column ) {
			case 'price':
				echo hb_format_price( get_post_meta( $post_id, 'tp_hb_extra_room_price', true ) );
				break;
			case 'unit':
				echo get_post_meta( $post_id, 'tp_hb_extra_room_respondent_name', true );
				break;
			case 'type':
				echo get_post_meta( $post_id, 'tp_hb_extra_room_respondent', true );
				break;
			case 'required':
				?>
				<input type="checkbox" name="required-extra"
				       data-extra-id="<?php echo esc_attr( $post_id ); ?>" <?php checked( get_post_meta( $post_id, 'tp_hb_extra_room_required', true ), 1 ); ?> disabled>
				<?php break;
			default:
				break;
		}
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function extra_columns( $columns ) {
		//			unset( $columns['author'] );
		unset( $columns['date'] );
		$columns['price']    = __( 'Price', 'wp-hotel-booking' );
		$columns['unit']     = __( 'Unit', 'wp-hotel-booking' );
		$columns['type']     = __( 'Type', 'wp-hotel-booking' );
		$columns['required'] = __( 'Required', 'wp-hotel-booking' );

		return $columns;
	}


	function add_extra( $post_id, $post = array() ) {
		global $wpdb;
		$query = $wpdb->prepare( "
				SELECT * FROM $wpdb->posts WHERE `ID` = %d AND `post_type` = %s
			", $post_id, 'hb_extra_room' );

		$results = $wpdb->get_results( $query, OBJECT );

		$args = array(
			'post_title'   => isset( $post['name'] ) ? $post['name'] : '',
			'post_content' => isset( $post['desc'] ) ? $post['desc'] : '',
			'post_type'    => 'hb_extra_room',
			'post_status'  => 'publish'
		);

		if ( ! $results ) {
			$post_id = wp_insert_post( $args );
		} else {
			$args['ID'] = $post_id;
			wp_update_post( $args );
		}

		if ( isset( $post['price'] ) ) {
			$price = (float) $post['price'];
		} else {
			$price = 0;
		}

		if ( get_post_meta( $post_id, 'tp_hb_extra_room_price', true ) || get_post_meta( $post_id, 'tp_hb_extra_room_price', true ) == 0 ) {
			update_post_meta( $post_id, 'tp_hb_extra_room_price', $price );
		} else {
			add_post_meta( $post_id, 'tp_hb_extra_room_price', $price );
		}

		unset( $post['name'] );
		unset( $post['desc'] );
		unset( $post['price'] );

		foreach ( $post as $key => $value ) {
			if ( get_post_meta( $post_id, 'tp_hb_extra_room_' . $key, true )
			     || get_post_meta( $post_id, 'tp_hb_extra_room_' . $key, true ) === ''
			     || get_post_meta( $post_id, 'tp_hb_extra_room_' . $key, true ) == 0 ) {
				update_post_meta( $post_id, 'tp_hb_extra_room_' . $key, $value );
			} else {
				add_post_meta( $post_id, 'tp_hb_extra_room_' . $key, $value );
			}
		}

		return $post_id;
	}

	/**
	 * Extra room meta box
	 */
	public function settings_meta_box() {
		WPHB_Meta_Box::instance(
			'extra_settings',
			array(
				'title'           => __( 'Extra Settings', 'wp-hotel-booking' ),
				'post_type'       => 'hb_extra_room',
				'meta_key_prefix' => 'tp_hb_extra_room_',
				'priority'        => 'high'
			),
			array()
		)->add_field(
			array(
				'name'  => 'price',
				'label' => __( 'Price', 'wp-hotel-booking' ),
				'type'  => 'number',
				'std'   => '10',
				'desc'  => __( 'Price of extra room option', 'wp-hotel-booking' ),
				'min'   => 0,
				'step'  => 0.01,
			),
			array(
				'name'    => 'respondent_name',
				'label'   => __( 'Unit', 'wp-hotel-booking' ),
				'desc'    => __( 'Unit of extra room option', 'wp-hotel-booking' ),
				'type'    => 'text',
				'default' => __( 'Package', 'wp-hotel-booking' )
			),
			array(
				'name'    => 'respondent',
				'label'   => __( 'Type', 'wp-hotel-booking' ),
				'desc'    => __( 'Type of extra room option', 'wp-hotel-booking' ),
				'type'    => 'select',
				'options' => hb_extra_types()
			),
			array(
				'name'  => 'required',
				'label' => __( 'Required', 'wp-hotel-booking' ),
				'desc'  => __( 'Required include for all booking', 'wp-hotel-booking' ),
				'type'  => 'checkbox',
				'std'   => ''
			)
		);
	}

	function tp_extra_package_remove() {
		if ( ! isset( $_POST ) ) {
			return;
		}

		if ( ! isset( $_POST['package_id'] ) ) {
			return;
		}

		if ( wp_delete_post( $_POST['package_id'] ) || ! get_post( $_POST['package_id'] ) ) {
			wp_send_json( array( 'status' => 'success' ) );
		}
	}

	/**
	 * get instance return self instead of new Class()
	 * @return object class
	 */
	static function instance() {
		if ( self::$_instance ) {
			return self::$_instance;
		}

		return new self();
	}


}

HB_Extra_Post_Type::instance();