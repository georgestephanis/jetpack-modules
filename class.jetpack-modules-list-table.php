<?php

if ( ! class_exists( 'WP_List_Table' ) )
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Jetpack_Modules_List_Table extends WP_List_Table {
	var $jetpack;

	function __construct() {
		parent::__construct();

		$this->jetpack = Jetpack::init();

		add_filter( 'jetpack_modules_list_table_items', array( $this, 'filter_displayed_table_items' ) );
	}

	function get_modules() {
		$available_modules = $this->jetpack->get_available_modules();
		$active_modules    = $this->jetpack->get_active_modules();
		$modules           = array();

		foreach ( $available_modules as $module ) {
			if ( $module_array = $this->jetpack->get_module( $module ) ) {
				$module_array['module'] = $module;
				$module_array['activated'] = in_array( $module, $active_modules );
				$modules[ $module ] = $module_array;
			}
		}

		uasort( $modules, array( $this->jetpack, 'sort_modules' ) );

		if ( ! Jetpack::is_active() ) {
			uasort( $modules, array( __CLASS__, 'sort_requires_connection_last' ) );
		}

		return $modules;
	}

	function filter_displayed_table_items( $modules ) {
		return array_filter( $modules, array( $this, 'is_module_displayed' ) );
	}

	static function is_module_available( $module ) {
		if ( ! is_array( $module ) || empty( $module ) )
			return false;

		return ! ( $module['requires_connection'] && ! Jetpack::is_active() );
	}

	static function is_module_displayed( $module ) {
		// Handle module tag based filtering.
		if ( ! empty( $_REQUEST['module_tag'] ) ) {
			$module_tag = sanitize_text_field( $_REQUEST['module_tag'] );
			if ( ! in_array( $module_tag, $module['module_tags'] ) )
				return false;
		}

		// If nothing rejected it, include it!
		return true;
	}

	static function sort_requires_connection_last( $module1, $module2 ) {
		if ( $module1['requires_connection'] == $module2['requires_connection'] )
			return 0;
		if ( $module1['requires_connection'] )
			return 1;
		if ( $module2['requires_connection'] )
			return -1;

		return 0;
	}

	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'icon'        => '',
			'name'        => __( 'Name',        'jetpack' ),
			'module_tags' => __( 'Module Tags', 'jetpack' ),
			'description' => __( 'Description', 'jetpack' ),
		);
		return $columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'bulk-activate'   => __( 'Activate',   'jetpack' ),
			'bulk-deactivate' => __( 'Deactivate', 'jetpack' ),
		);
		return $actions;
	}

	function single_row( $item ) {
		static $row_class = '';
		$row_class = empty( $row_class ) ? ' alternate' : '';

		if ( ! empty( $item['activated'] )  )
			$row_class .= ' active';

		if ( ! $this->is_module_available( $item ) )
			$row_class .= ' unavailable';

		echo '<tr class="jetpack-module' . esc_attr( $row_class ) . '" id="' . esc_attr( $item['module'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	function get_table_classes() {
		return array( 'widefat', 'fixed', 'jetpack-modules', 'plugins' );
	}

	function column_cb( $item ) {
		if ( ! $this->is_module_available( $item ) )
			return '';

		return sprintf( '<input type="checkbox" name="modules[]" value="%s" />', $item['module'] );
	}

	function column_icon( $item ) {
		$badge_text = $free_text = '';
		ob_start();
		?>
		<a href="#TB_inline?width=600&height=550&inlineId=more-info-<?php echo $item['module']; ?>" class="thickbox">
			<div class="module-image">
				<p><span class="module-image-badge"><?php echo $badge_text; ?></span><span class="module-image-free" style="display: none"><?php echo $free_text; ?></span></p>
			</div>
		</a>
		<?php
		return ob_get_clean();

	}

	function column_name( $item ) {
		$actions = array();

		if ( empty( $item['activated'] ) && $this->is_module_available( $item ) ) {
			$url = wp_nonce_url(
				$this->jetpack->admin_url( array(
					'page'   => 'jetpack',
					'action' => 'activate',
					'module' => $item['module'],
				) ),
				'jetpack_activate-' . $item['module']
			);
			$actions['activate'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Activate', 'jetpack' ) );
		} elseif ( ! empty( $item['activated'] ) ) {
			$url = wp_nonce_url(
				$this->jetpack->admin_url( array(
					'page'   => 'jetpack',
					'action' => 'deactivate',
					'module' => $item['module'],
				) ),
				'jetpack_deactivate-' . $item['module']
			);
			$actions['delete'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Deactivate', 'jetpack' ) );
		}

		return wptexturize( $item['name'] ) . $this->row_actions( $actions );
	}

	function column_description( $item ) {
		ob_start();
		echo apply_filters( 'jetpack_short_module_description', $item['description'], $item['module'] );
		do_action( 'jetpack_learn_more_button_' . $item['module'] );
		echo '<div id="more-info-' . $item['module'] . '" class="more-info">';
		if ( $this->jetpack->is_active() && has_action( 'jetpack_module_more_info_connected_' . $item['module'] ) ) {
			do_action( 'jetpack_module_more_info_connected_' . $item['module'] );
		} else {
			do_action( 'jetpack_module_more_info_' . $item['module'] );
		}
		return ob_get_clean();
	}

	function column_module_tags( $item ) {
		$module_tags = array();
		foreach( $item['module_tags'] as $module_tag ) {
			$module_tags[] = sprintf( '<a href="%2$s">%1$s</a>', esc_html( $module_tag ), add_query_arg( 'module_tag', urlencode( $module_tag ) ) );
		}
		return implode( ', ', $module_tags );
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'icon':
			case 'name':
			case 'description':
				break;
			default:
				return print_r( $item, true );
		}
	}
}
