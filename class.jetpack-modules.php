<?php

require_once 'class.jetpack-form-elements.php';

if ( ! class_exists( 'WP_List_Table' ) )
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

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

		add_action( 'jetpack_admin_menu',               array( $this, 'jetpack_admin_menu' ) );
		add_action( 'jetpack_admin_menu',               array( $this, 'jetpack_settings_menu' ) );
		add_filter( 'jetpack_modules_list_table_items', array( $this, 'filter_displayed_table_items' ) );
		add_action( 'jetpack_pre_activate_module',      array( $this, 'fix_redirect' ) );
		add_action( 'jetpack_pre_deactivate_module',    array( $this, 'fix_redirect' ) );
		add_action( 'jetpack_unrecognized_action',      array( $this, 'handle_unrecognized_action' ) );
	}

	function handle_unrecognized_action( $action ) {
		switch( $action ) {
			case 'bulk-activate' :
				if ( ! current_user_can( 'manage_options' ) )
					break;

				$modules = (array) $_GET['modules'];
				$modules = array_map( 'sanitize_key', $modules );
				check_admin_referer( 'bulk-jetpack_page_jetpack_modules' );
				foreach( $modules as $module ) {
					Jetpack::log( 'activate', $module );
					Jetpack::activate_module( $module, false );
				}
				// The following two lines will rarely happen, as Jetpack::activate_module normally exits at the end.
				wp_safe_redirect( wp_get_referer() );
				exit;
			case 'bulk-deactivate' :
				if ( ! current_user_can( 'manage_options' ) )
					break;

				$modules = (array) $_GET['modules'];
				$modules = array_map( 'sanitize_key', $modules );
				check_admin_referer( 'bulk-jetpack_page_jetpack_modules' );
				foreach ( $modules as $module ) {
					Jetpack::log( 'deactivate', $module );
					Jetpack::deactivate_module( $module );
					Jetpack::state( 'message', 'module_deactivated' );
				}
				Jetpack::state( 'module', $modules );
				wp_safe_redirect( wp_get_referer() );
				exit;
			default:
				return;
		}
	}

	function fix_redirect() {
		if ( wp_get_referer() ) {
			add_filter( 'wp_redirect', 'wp_get_referer' );
		}
	}

	function jetpack_admin_menu() {
		$hook = add_submenu_page( 'jetpack', __( 'Jetpack Modules', 'jetpack' ), __( 'Modules', 'jetpack' ), 'manage_options', 'jetpack_modules', array( $this, 'admin_page_modules' ) );

		add_action( "load-$hook",                array( $this->jetpack, 'admin_page_load' ) );
		add_action( "load-$hook",                array( $this->jetpack, 'admin_help'      ) );
		add_action( "admin_head-$hook",          array( $this->jetpack, 'admin_head'      ) );
		add_action( "admin_print_styles-$hook",  array( $this->jetpack, 'admin_styles'    ) );
		add_action( "admin_print_scripts-$hook", array( $this->jetpack, 'admin_scripts'   ) );
		add_action( "admin_print_styles-$hook",  array( $this, 'admin_styles'             ) );
	}

	function jetpack_settings_menu() {
		$hook = add_submenu_page( 'jetpack', __( 'Jetpack Settings', 'jetpack' ), __( 'Settings', 'jetpack' ), 'manage_options', 'jetpack_settings', array( $this, 'admin_page_settings' ) );

		add_action( "load-$hook",                array( $this->jetpack, 'admin_page_load' ) );
		add_action( "load-$hook",                array( $this->jetpack, 'admin_help'      ) );
		add_action( "admin_head-$hook",          array( $this->jetpack, 'admin_head'      ) );
		add_action( "admin_print_styles-$hook",  array( $this->jetpack, 'admin_styles'    ) );
		add_action( "admin_print_scripts-$hook", array( $this->jetpack, 'admin_scripts'   ) );

		register_setting( 'jetpack_settings', 'videopress' );

		$videopress = Jetpack_Options::get_option( 'videopress', array(
			'blogs' => array(),
			'blog_id' => 0,
			'access' => '',
			'allow-upload' => false,
			'freedom' => false,
			'hd' => false,
			'meta' => array(
				'max_upload_size' => 0,
			),
		) );
		add_settings_section( 'videopress', __( 'VideoPress', 'jetpack'), array( $this, 'videopress_description' ), 'jetpack_settings' );

		$blog_id_choices = array( 0 => __( 'None', 'jetpack' ) );
		foreach ( $videopress['blogs'] as $blog ) {
			$blog_id_choices[ $blog['blog_id'] ] = $blog['name'];
		}
		add_settings_field( 'videopress-blog-id', __( 'Connected WordPress.com Blog', 'jetpack' ), array( 'Jetpack_Form_Elements', 'select' ), 'jetpack_settings', 'videopress', array(
			'name'    => 'blog_id',
			'value'   => $videopress['blog_id'],
			'choices' => $blog_id_choices,
		) );
		add_settings_field( 'video-library-access', __( 'Video Library Access', 'jetpack' ), array( 'Jetpack_Form_Elements', 'radio' ), 'jetpack_settings', 'videopress', array(
			'name'    => 'videopress-access',
			'value'   => $videopress['access'],
			'choices' => array(
				''       => __( 'Do not allow other users to access my VideoPress library', 'jetpack' ),
				'read'   => __( 'Allow users to access my videos', 'jetpack' ),
				'edit'   => __( 'Allow users to access and edit my videos', 'jetpack' ),
				'delete' => __( 'Allow users to access, edit, and delete my videos', 'jetpack' ),
			),
		) );
		add_settings_field( 'videopress-upload', '', array( 'Jetpack_Form_Elements', 'checkbox' ), 'jetpack_settings', 'videopress', array(
			'name'  => 'videopress-upload',
			'value' => $videopress['allow-upload'],
			'label' => __( 'Allow users to upload videos', 'jetpack' ),
		) );
		add_settings_field( 'videopress-freedom', __( 'Free formats', 'jetpack' ), array( 'Jetpack_Form_Elements', 'checkbox' ), 'jetpack_settings', 'videopress', array(
			'name'        => 'videopress-freedom',
			'value'       => $videopress['freedom'],
			'label'       => __( 'Only display videos in free software formats.', 'jetpack' ),
			'description' => __( 'Ogg file container with Theora video and Vorbis audio. Note that some browsers are unable to play free software video formats, including Internet Explorer and Safari.', 'jetpack' ),
		) );
		add_settings_field( 'videopress-hd', __( 'Default quality', 'jetpack' ), array( 'Jetpack_Form_Elements', 'checkbox' ), 'jetpack_settings', 'videopress', array(
			'name'        => 'videopress-hd',
			'value'       => $videopress['hd'],
			'label'       => __( 'Display higher quality video by default.', 'jetpack' ),
			'description' => __( 'This setting may be overridden for individual videos.', 'jetpack' ),
		) );

	}

	function videopress_description() {
		_e( 'Please note that the VideoPress module requires a WordPress.com account with an active <a href="http://store.wordpress.com/premium-upgrades/videopress/" target="_blank">VideoPress subscription</a>.</p>', 'jetpack' );
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

		uasort( $modules, array( $this->jetpack, 'sort_modules' ) );

		if ( ! Jetpack::is_active() ) {
			uasort( $modules, array( __CLASS__, 'sort_requires_connection_last' ) );
		}

		return $modules;
	}

	function admin_page_modules() {
		add_filter( 'jetpack_short_module_description', 'wpautop' );
		include_once( JETPACK__PLUGIN_DIR . 'modules/module-info.php' );
		parent::__construct();
		add_thickbox();
		?>

		<div class="wrap" id="jetpack-settings">

			<div id="jp-header" class="small">
				<div id="jp-clouds">
					<h3><?php _e( 'Jetpack by WordPress.com', 'jetpack' ) ?></h3>
				</div>
			</div>

			<h2 style="display: none"></h2> <!-- For WP JS message relocation -->

			<?php
				do_action( 'jetpack_notices' );
				$this->items = $this->all_items = $this->get_modules();
				$this->items = apply_filters( 'jetpack_modules_list_table_items', $this->items );
				$this->_column_headers = array( $this->get_columns(), array(), array() );
			?>

			<form method="get">
				<input type="hidden" name="page" value="jetpack_modules" />
				<?php if ( ! empty( $_GET['module_tag'] ) ) : ?>
					<input type="hidden" name="module_tag" value="<?php echo esc_attr( $_GET['module_tag'] ); ?>" />
				<?php endif; ?>
				<?php $this->search_box( __( 'Search', 'jetpack' ), 'search_modules' ); ?>
				<?php $this->display(); ?>
			</form>

			<script>
			var jetpackModules = <?php echo json_encode( $this->all_items ); ?>;
			jQuery(document).ready(function($){
				$('.more-info-link').click(function(e){
					e.preventDefault();
					$(this).siblings('.more-info').toggle();
				});
			});
			</script>

		</div>

		<?php
	}

	function admin_page_settings() {
		?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>
			<h2><?php _e( 'Jetpack Settings', 'jetpack' ); ?></h2>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'jetpack_settings' );
					do_settings_sections( 'jetpack_settings' );
					submit_button();
				?>
				<input type="hidden" name="_wp_http_referer" value="<?php menu_page_url( 'jetpack' ); ?>" />
			</form>
		</div>
		<?php
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
