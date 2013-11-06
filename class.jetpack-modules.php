<?php

require_once 'class.jetpack-form-elements.php';

class Jetpack_Modules {

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

		add_action( 'jetpack_admin_menu',            array( $this, 'jetpack_admin_menu' ) );
		add_action( 'jetpack_admin_menu',            array( $this, 'jetpack_settings_menu' ) );
		add_action( 'jetpack_pre_activate_module',   array( $this, 'fix_redirect' ) );
		add_action( 'jetpack_pre_deactivate_module', array( $this, 'fix_redirect' ) );
		add_action( 'jetpack_unrecognized_action',   array( $this, 'handle_unrecognized_action' ) );
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

	function admin_page_modules() {
		add_filter( 'jetpack_short_module_description', 'wpautop' );
		include_once( JETPACK__PLUGIN_DIR . 'modules/module-info.php' );
		add_thickbox();

		include_once( 'class.jetpack-modules-list-table.php' );
		$list_table = new Jetpack_Modules_List_Table;
		?>
		<div class="wrap" id="jetpack-settings">
			<div id="module-settings-modal" style="display:none;">
			     <!-- Here goes the settings -->
			</div>
			<div id="jp-header" class="small">
				<div id="jp-clouds">
					<h3><?php _e( 'Jetpack by WordPress.com', 'jetpack' ) ?></h3>
				</div>
			</div>

			<h2 style="display: none"></h2> <!-- For WP JS message relocation -->

			<?php do_action( 'jetpack_notices' ); ?>

			<?php $list_table->views(); ?>
			<form method="get">
				<input type="hidden" name="page" value="jetpack_modules" />
				<?php if ( ! empty( $_GET['module_tag'] ) ) : ?>
					<input type="hidden" name="module_tag" value="<?php echo esc_attr( $_GET['module_tag'] ); ?>" />
				<?php endif; ?>
				<?php $list_table->search_box( __( 'Search', 'jetpack' ), 'search_modules' ); ?>
				<?php $list_table->display(); ?>
			</form>

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

}
