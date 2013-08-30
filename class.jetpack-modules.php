<?php

if( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Jetpack_Modules extends WP_List_Table {

	static $instance;

	var $jetpack;

	static function init() {
		if ( empty( self::$instance ) ) {
			self::$instance = new Jetpack_Modules;
		}
		return self::$instance;
	}

	function __construct() {
		$this->jetpack = Jetpack::init();

		if ( true || $this->jetpack->is_active() ) {
			add_action( 'jetpack_admin_menu', array( $this, 'jetpack_admin_menu' ) );
		}
	}

	function jetpack_admin_menu() {
		$hook = add_submenu_page( 'jetpack', __( 'Jetpack Modules', 'jetpack' ), __( 'Modules', 'jetpack' ), 'edit_posts', 'jetpack_modules', array( $this, 'admin_page_modules' ) );

		add_action( "load-$hook",                array( $this->jetpack, 'admin_page_load' ) );
		add_action( "load-$hook",                array( $this->jetpack, 'admin_help'      ) );
		add_action( "admin_head-$hook",          array( $this->jetpack, 'admin_head'      ) );
		add_action( "admin_print_styles-$hook",  array( $this->jetpack, 'admin_styles'    ) );
		add_action( "admin_print_scripts-$hook", array( $this->jetpack, 'admin_scripts'   ) );
		add_action( "admin_print_styles-$hook",  array( $this, 'admin_styles'             ) );
	}

	function admin_styles() {
		wp_enqueue_style( 'jetpack-modules', plugins_url( 'jetpack-modules.css', __FILE__ ) );
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

		usort( $modules, array( $this->jetpack, 'sort_modules' ) );

		return $modules;
	}

	function admin_page_modules() {
		add_filter( 'jetpack_short_module_description', 'wpautop' );
		include_once( JETPACK__PLUGIN_DIR . 'modules/module-info.php' );
		parent::__construct();
		?>

		<div class="wrap" id="jetpack-settings">

			<h2 style="display: none"></h2> <!-- For WP JS message relocation -->

			<div id="jp-header" class="small">
				<div id="jp-clouds">
					<h3><?php _e( 'Jetpack by WordPress.com', 'jetpack' ) ?></h3>
				</div>
			</div>

			<?php do_action( 'jetpack_notices' ) ?>

			<?php
				$this->items = $this->get_modules();
				$this->_column_headers = array( $this->get_columns(), array(), array() );
				$this->display();
			?>

			<!-- <pre><?php var_dump( $this->items ); ?></pre> -->

		</div>

		<?php
	}

	function get_columns() {
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'icon'        => __( 'Icon',        'jetpack' ),
			'name'        => __( 'Name',        'jetpack' ),
			'description' => __( 'Description', 'jetpack' ),
		);
		return $columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'activate'   => __( 'Activate',   'jetpack' ),
			'deactivate' => __( 'Deactivate', 'jetpack' ),
		);
		return $actions;
	}

	function single_row( $item ) {
		static $row_class = '';
		$row_class = empty( $row_class ) ? ' alternate' : '';

		$active = empty( $item['activated'] ) ? '' : ' active';

		echo '<tr class="jetpack-module' . $row_class . $active . '" id="' . $item['module'] . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	function get_table_classes() {
		return array( 'widefat', 'fixed', 'jetpack-modules', 'plugins' );
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="book[]" value="%s" />', $item['module'] );
	}

	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'icon':
				$badge_text = $free_text = '';
				ob_start();
				?>
				<div class="module-image">
					<p><span class="module-image-badge"><?php echo $badge_text; ?></span><span class="module-image-free" style="display: none"><?php echo $free_text; ?></span></p>
				</div>
				<?php
				return ob_get_clean();
			case 'name':
				return $item['name'];
			case 'description':
				$short_desc = apply_filters( 'jetpack_short_module_description', $item['description'], $item['module'] );
				ob_start();
				do_action( 'jetpack_learn_more_button_' . $item['module'] );
				echo '<div id="more-info-' . $item['module'] . '" class="more-info">';
				if ( $this->jetpack->is_active() && has_action( 'jetpack_module_more_info_connected_' . $item['module'] ) ) {
					do_action( 'jetpack_module_more_info_connected_' . $item['module'] );
				} else {
					do_action( 'jetpack_module_more_info_' . $item['module'] );
				}
				return $short_desc . ob_get_clean();
			default:
				return print_r( $item, true );
		}
	}
}

add_action( 'plugins_loaded', array( 'Jetpack_Modules', 'init' ) );
