<?php

if ( ! class_exists( 'WP_List_Table' ) )
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Jetpack_Modules_Cards extends WP_List_Table {
	var $jetpack;

	function __construct() {
		parent::__construct();

		$this->jetpack = Jetpack::init();

		$this->items = $this->all_items = $this->get_modules();
		$this->items = $this->filter_displayed_table_items( $this->items );
		$this->items = apply_filters( 'jetpack_modules_list_table_items', $this->items );
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		wp_register_script(
			'models.jetpack-modules',
			plugins_url( 'js/models.jetpack-modules.js', __FILE__ ),
			array(
				'backbone',
				'underscore',
			),
			JETPACK__VERSION
		);
		wp_register_script(
			'views.jetpack-modules',
			plugins_url( 'js/views.jetpack-modules.js', __FILE__ ),
			array(
				'backbone',
				'underscore',
			),
			JETPACK__VERSION
		);
		wp_register_script(
			'jetpack-modules-list-table',
			plugins_url( 'js/jetpack-module-list-table.js', __FILE__ ),
			array(
				'views.jetpack-modules',
				'models.jetpack-modules',
				'jquery',
			),
			JETPACK__VERSION,
			true
		);

		wp_localize_script( 'jetpack-modules-list-table', 'jetpackModulesData', $this->all_items );

		wp_enqueue_script( 'jetpack-modules-list-table' );
		add_action( 'admin_footer', array( $this, 'js_templates' ), 9 );
	}

	function get_modules() {
		$available_modules = $this->jetpack->get_available_modules();
		$active_modules    = $this->jetpack->get_active_modules();
		$modules           = array();

		foreach ( $available_modules as $module ) {
			if ( $module_array = $this->jetpack->get_module( $module ) ) {
				$module_array['module']            = $module;
				$module_array['activated']         = in_array( $module, $active_modules );
				$module_array['deactivate_nonce']  = wp_create_nonce( 'jetpack_deactivate-' . $module );
				$module_array['activate_nonce']    = wp_create_nonce( 'jetpack_activate-' . $module );
				$module_array['unavailable']       = ! self::is_module_available( $module_array );
				$module_array['short_description'] = apply_filters( 'jetpack_short_module_description', $module_array['description'], $module );

				ob_start();
				do_action( 'jetpack_learn_more_button_' . $module );
				$module_array['learn_more_button'] = ob_get_clean();
				$module_array['learn_more_button'] = str_replace( '-secondary', '', $module_array['learn_more_button'] );
				$module_array['learn_more_button'] = str_replace( 'button', 'button-small button', $module_array['learn_more_button'] );

				ob_start();
				if ( $this->jetpack->is_active() && has_action( 'jetpack_module_more_info_connected_' . $module ) ) {
					do_action( 'jetpack_module_more_info_connected_' . $module );
				} else {
					do_action( 'jetpack_module_more_info_' . $module );
				}
				$module_array['long_description']  = ob_get_clean();

				$module_array['configurable'] = false;
				if ( current_user_can( 'manage_options' ) && apply_filters( 'jetpack_module_configurable_' . $module, false ) ) {
					$module_array['configurable'] = sprintf( '<a href="%1$s" class="button button-small">%2$s</a>', esc_url( Jetpack::module_configuration_url( $module ) ), __( 'Configure', 'jetpack' ) );
				}

				$modules[ $module ] = $module_array;
			}
		}

		uasort( $modules, array( $this->jetpack, 'sort_modules' ) );

		if ( ! Jetpack::is_active() ) {
			uasort( $modules, array( __CLASS__, 'sort_requires_connection_last' ) );
		}

		return $modules;
	}

	function get_views() {
		$modules              = $this->get_modules();
		$array_of_module_tags = wp_list_pluck( $modules, 'module_tags' );
		$module_tags          = call_user_func_array( 'array_merge', $array_of_module_tags );
		$module_tags_unique   = array_count_values( $module_tags );
		ksort( $module_tags_unique );

		$format  = '<a href="%3$s"%4$s data-title="%1$s">%1$s <span class="count">(%2$s)</span></a>';
		$title   = __( 'All', 'jetpack' );
		$count   = count( $modules );
		$url     = remove_query_arg( 'module_tag' );
		$current = empty( $_GET['module_tag'] ) ? ' class="current all"' : ' class="all"';
		$views   = array(
			'all' => sprintf( $format, $title, $count, $url, $current ),
		);
		foreach ( $module_tags_unique as $title => $count ) {
			$key           = sanitize_title( $title );
			$display_title = esc_html( wptexturize( $title ) );
			$url           = add_query_arg( 'module_tag', urlencode( $title ) );
			$current       = '';
			if ( ! empty( $_GET['module_tag'] ) && $title == $_GET['module_tag'] )
				$current   = ' class="current"';
			$views[ $key ] = sprintf( $format, $display_title, $count, $url, $current );
		}
		return $views;
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

	/**
	 * Display the table
	 *
	 * @since 3.1.0
	 * @access public
	 */
	function display() {
		extract( $this->_args );

		$this->display_tablenav( 'top' );

?>
<section class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
	<div id="the-list"<?php if ( $singular ) echo " data-wp-lists='list:$singular'"; ?>>
		<?php $this->display_rows_or_placeholder(); ?>
	</div>
</table>
<?php
		$this->display_tablenav( 'bottom' );
	}

	function js_templates() {
		?>
		<script type="text/html" id="Jetpack_Modules_List_Table_Template">
			<% var item_class;
			_.each( items, function( item, key, list ) {
				item_class  = item.activated   ? ' active'      : '';
				item_class += item.unavailable ? ' unavailable' : '';
				%>
				<article class="jetpack-module <%= item_class %>" id="<%= item.module %>">
					<figure class='icon column-icon'>
						<a href="#TB_inline?width=600&height=550&inlineId=module-settings-modal" class="thickbox">
							<div class="module-image">
								<p>
									<span class="module-image-badge"></span>
									<span class="module-image-free" style="display: none"></span>
								</p>
							</div>
						</a>
					</figure>
					<h3><%= item.name %></h3>
					<ul class='module_tags column-module_tags'>
					<% _.each( item.module_tags, function( tag, tag_key, tag_list ) { %>
						<li><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack_modules&module_tag=<%= encodeURIComponent( tag ) %>" data-title="<%- tag %>"><%= tag %></a></li>
					<% } ); %>
					</ul>
					<div class='description column-description'>
						<%= item.short_description %>
					</div>
					<ul class="row-actions">
						<li class="learn-more"><%= item.learn_more_button %></li>
						<% if ( item.configurable ) { %>
							<li class='configure'><%= item.configurable %></li>
						<% } %>
						<% if ( item.activated ) { %>
							<li class='delete'><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack&#038;action=deactivate&#038;module=<%= item.module %>&#038;_wpnonce=<%= item.deactivate_nonce %>"><?php _e( 'Deactivate', 'jetpack' ); ?></a></li>
						<% } else if ( ! item.unavailable ) { %>
							<li class='activate'><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack&#038;action=activate&#038;module=<%= item.module %>&#038;_wpnonce=<%= item.activate_nonce %>"><?php _e( 'Activate', 'jetpack' ); ?></a></li>
						<% } %>
					</ul>
					<div id="more-info-<%= item.module %>" class="more-info">
						<%= item.long_description %>
					</div>
				</article>
				<%
			});
			%>
		</script>
		<?php
	}

	function single_row( $item ) {
		$row_class = '';

		if ( ! empty( $item['activated'] )  )
			$row_class .= ' active';

		if ( ! $this->is_module_available( $item ) )
			$row_class .= ' unavailable';
		?>
		<article class="jetpack-module<?php echo esc_attr( $row_class ); ?>" id="<?php echo esc_html( $item['module'] ); ?>">
			<figure class='icon column-icon'>
				<a href="#TB_inline?width=600&height=550&inlineId=module-settings-modal" class="thickbox">
					<div class="module-image">
						<p>
							<span class="module-image-badge"></span>
							<span class="module-image-free" style="display: none"></span>
						</p>
					</div>
				</a>
			</figure>
			<h3><?php echo $item['name']; ?></h3>
			<ul class='module_tags column-module_tags'>
			<?php foreach( $item['module_tags'] as $tag_key => $tag ) : ?>
				<li><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack_modules&module_tag=<?php echo urlencode( $tag ) ?>" data-title="<?php echo esc_attr( $tag ) ?>"><?php echo esc_html( $tag ) ?></a></li>
			<?php endforeach; ?>
			</ul>
			<div class='description column-description'>
				<?php echo $item['short_description'] ?>
			</div>
			<ul class="row-actions">
				<li class="learn-more"><?php echo $item['learn_more_button'] ?></li>
				<?php if ( $item['configurable'] ) : ?>
					<li class='configure'><?php echo $item['configurable']; ?></li>
				<?php endif; ?>
				<?php if ( $item['activated'] ) : ?>
					<li class='delete'><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack&#038;action=deactivate&#038;module=<?php echo $item['module'] ?>&#038;_wpnonce=<?php echo $item['deactivate_nonce']; ?>" class="button button-small"><?php _e( 'Deactivate', 'jetpack' ); ?></a></li>
				<?php elseif ( ! $item['unavailable'] ) : ?>
					<li class='activate'><a href="<?php echo admin_url( 'admin.php' ); ?>?page=jetpack&#038;action=activate&#038;module=<?php echo $item['module'] ?>&#038;_wpnonce=<?php echo $item['activate_nonce']; ?>" class="button button-small"><?php _e( 'Activate', 'jetpack' ); ?></a></li>
				<?php endif; ?>
			</ul>
			<div id="more-info-<?php echo $item['module']; ?>" class="more-info">
				<?php echo $item['long_description']; ?>
			</div>
		</article>

		<?php
	}

	function get_table_classes() {
		return array( 'widefat', 'fixed', 'jetpack-modules', 'plugins' );
	}

	function get_columns() {}

}
