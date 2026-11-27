<?php
/**
 * The tools the MCP endpoint offers. Most of them are a call to the WordPress REST API made inside the site,
 * so the rights of the logged-in user (the Application Password) apply exactly as for any REST client.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Mcp_Tools {

	/** Compact profile: the essential tools, for AI services that refuse a long tool list (GLM/z.ai stops at about 128 in total). */
	const LITE = array(
		'detect_stack', 'get_skill', 'get_content_raw', 'memory_write', 'memory_read', 'memory_search',
		'get_posts', 'get_post', 'create_post', 'update_post', 'get_pages', 'create_page', 'update_page', 'search_content', 'get_media',
		'get_plugins', 'activate_plugin', 'deactivate_plugin', 'get_site_info',
		'get_builder_data', 'set_builder_data', 'list_builder_backups', 'restore_builder_backup', 'set_page_meta', 'bridge_status',
		'get_woo_products', 'create_woo_product', 'update_woo_product', 'get_woo_orders',
		'dev_run_php', 'dev_files',
	);

	/** Two tools that reach every ability of the other plugins without listing them one by one. */
	public static function meta_tools() {
		return array(
			(object) array(
				'name'        => 'list_abilities',
				'description' => 'List the tools that the other plugins of this site publish for AI (WooCommerce, SEO plugins, forms...). Returns name, description and input fields. Run one with run_ability.',
				'inputSchema' => (object) array( 'type' => 'object', 'properties' => new stdClass() ),
			),
			(object) array(
				'name'        => 'our_plugins',
				'description' => 'Products by the makers of OpenWP that are NOT installed on this site yet, with a note on whether each fits the site. Use it only when the request would really be easier with one of them, and say they are the makers\' products.',
				'inputSchema' => (object) array( 'type' => 'object', 'properties' => new stdClass() ),
			),
			(object) array(
				'name'        => 'run_ability',
				'description' => 'Run one ability of another plugin. Use the name from list_abilities, for example "woocommerce/products-query", and the fields it asks for in "input".',
				'inputSchema' => (object) array(
					'type'       => 'object',
					'properties' => (object) array(
						'name'  => (object) array( 'type' => 'string', 'description' => 'Ability name, for example woocommerce/products-query' ),
						'input' => (object) array( 'type' => 'object', 'description' => 'The fields the ability asks for', 'additionalProperties' => true ),
					),
					'required'   => array( 'name' ),
				),
			),
		);
	}

	/** Tools of our plugins (one module per product), only for the ones that are active. */
	public static function adapter_tools() {
		return OpenWP_Fluxtore::active() ? OpenWP_Fluxtore::tools() : array();
	}

	/** The compact list: LITE tools plus the two meta tools. */
	public static function lite_definitions() {
		$out = array();

		foreach ( self::definitions() as $d ) {
			if ( in_array( $d->name, self::LITE, true ) ) {
				$out[] = $d;
			}
		}

		return array_merge( $out, self::meta_tools(), self::adapter_tools() );
	}

	/** Tool definitions (names, descriptions, input schemas), generated from the OpenWP server by bridge/build.sh. */
	public static function definitions() {
		$file = OPENWP_BRIDGE_DIR . 'mcp-tools.json';
		$list = is_file( $file ) ? json_decode( (string) file_get_contents( $file ) ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions

		return is_array( $list ) ? $list : array();
	}

	/**
	 * name => [ method, path, default query, query keys from the arguments, fields to return ('' = all) ]
	 * {id} in the path is replaced by the "id" argument. For POST and PUT all the other arguments are sent as the body.
	 */
	private static function table() {
		return array(
			'get_posts'          => array( 'GET', '/wp/v2/posts', array( 'per_page' => 10, 'page' => 1, 'status' => 'publish' ), array( 'per_page', 'page', 'status', 'search', 'categories', 'tags' ), 'id,title,status,date,link,excerpt' ),
			'get_post'           => array( 'GET', '/wp/v2/posts/{id}', array(), array(), 'id,title,content,status,date,link,categories,tags' ),
			'create_post'        => array( 'POST', '/wp/v2/posts', array(), array(), 'id,title,status,link', array( 'status' => 'draft' ) ),
			'update_post'        => array( 'POST', '/wp/v2/posts/{id}', array(), array(), 'id,title,status,link' ),
			'get_pages'          => array( 'GET', '/wp/v2/pages', array( 'per_page' => 10, 'status' => 'publish' ), array( 'per_page', 'status', 'search', 'parent' ), 'id,title,status,link,parent' ),
			'create_page'        => array( 'POST', '/wp/v2/pages', array(), array(), 'id,title,status,link', array( 'status' => 'draft' ) ),
			'update_page'        => array( 'POST', '/wp/v2/pages/{id}', array(), array(), 'id,title,status,link' ),
			'get_categories'     => array( 'GET', '/wp/v2/categories', array( 'per_page' => 50, 'hide_empty' => false ), array( 'per_page', 'hide_empty', 'search' ), 'id,name,slug,count,parent' ),
			'create_category'    => array( 'POST', '/wp/v2/categories', array(), array(), 'id,name,slug' ),
			'get_tags'           => array( 'GET', '/wp/v2/tags', array( 'per_page' => 50 ), array( 'per_page', 'search' ), 'id,name,slug,count' ),
			'create_tag'         => array( 'POST', '/wp/v2/tags', array(), array(), 'id,name,slug' ),
			'get_users'          => array( 'GET', '/wp/v2/users', array( 'per_page' => 20 ), array( 'per_page', 'search', 'roles' ), 'id,slug,name,email,roles' ),
			'create_user'        => array( 'POST', '/wp/v2/users', array(), array(), 'id,slug,name,email,roles' ),
			'update_user'        => array( 'POST', '/wp/v2/users/{id}', array(), array(), 'id,name,email,roles' ),
			'get_media'          => array( 'GET', '/wp/v2/media', array( 'per_page' => 20 ), array( 'per_page', 'search', 'media_type' ), 'id,title,source_url,media_type,mime_type,date' ),
			'get_plugins'        => array( 'GET', '/wp/v2/plugins', array(), array( 'status' ), 'name,plugin,status,version,author' ),
			'get_themes'         => array( 'GET', '/wp/v2/themes', array(), array(), 'stylesheet,name,status,version' ),
			'install_theme'      => array( 'POST', '/wp/v2/themes', array(), array(), 'stylesheet,name,status,version' ),
			'get_site_info'      => array( 'GET', '/wp/v2/settings', array(), array(), '' ),
			'update_site_settings' => array( 'POST', '/wp/v2/settings', array(), array(), '' ),
			'get_comments'       => array( 'GET', '/wp/v2/comments', array( 'per_page' => 20, 'status' => 'approved' ), array( 'per_page', 'status', 'post', 'search' ), 'id,post,author_name,content,status,date' ),
			'moderate_comment'   => array( 'POST', '/wp/v2/comments/{id}', array(), array(), 'id,status' ),
			'get_post_types'     => array( 'GET', '/wp/v2/types', array(), array(), '' ),
			'get_woo_products'   => array( 'GET', '/wc/v3/products', array( 'per_page' => 10, 'status' => 'publish' ), array( 'per_page', 'status', 'search', 'category' ), 'id,name,status,price,regular_price,stock_status,sku' ),
			'create_woo_product' => array( 'POST', '/wc/v3/products', array(), array(), 'id,name,status,price,permalink' ),
			'update_woo_product' => array( 'PUT', '/wc/v3/products/{id}', array(), array(), 'id,name,status,price' ),
			'get_woo_orders'     => array( 'GET', '/wc/v3/orders', array( 'per_page' => 10 ), array( 'per_page', 'status', 'customer', 'after' ), 'id,status,total,currency,date_created,billing,line_items' ),
			'update_woo_order'   => array( 'PUT', '/wc/v3/orders/{id}', array(), array(), 'id,status,total' ),
			'get_woo_customers'  => array( 'GET', '/wc/v3/customers', array( 'per_page' => 10, 'role' => 'customer' ), array( 'per_page', 'role', 'search' ), 'id,first_name,last_name,email,orders_count,total_spent' ),
		);
	}

	public static function has( $name ) {
		if ( OpenWP_Fluxtore::has( $name ) ) {
			return true;
		}

		if ( in_array( $name, array( 'list_abilities', 'run_ability', 'our_plugins' ), true ) ) {
			return true;
		}

		foreach ( self::definitions() as $d ) {
			if ( $d->name === $name ) {
				return true;
			}
		}

		return false;
	}

	/** @return mixed|WP_Error The data to send back, or an error. */
	public static function call( $name, array $args ) {
		if ( OpenWP_Abilities::is_ability_tool( $name ) ) {
			return OpenWP_Abilities::call( $name, $args );
		}

		if ( OpenWP_Fluxtore::has( $name ) ) {
			return OpenWP_Fluxtore::call( $name, $args );
		}

		$refused = OpenWP_Abilities::refuse_ours( $name, $args );

		if ( $refused ) {
			return new WP_Error( 'openwp_choice', $refused );
		}

		if ( 'update_woo_order' === $name || 'update_woo_product' === $name ) {
			return self::woo_update( $name, $args );
		}

		$table = self::table();

		if ( isset( $table[ $name ] ) ) {
			return self::from_table( $table[ $name ], $args );
		}

		switch ( $name ) {
			case 'our_plugins':
				return OpenWP_Ours::for_ai();
			case 'list_abilities':
				$out = array();

				foreach ( OpenWP_Abilities::all_for_listing() as $row ) {
					$out[] = $row;
				}

				return $out;
			case 'run_ability':
				$ab = isset( $args['name'] ) ? (string) $args['name'] : '';

				return OpenWP_Abilities::call( OpenWP_Abilities::PREFIX . str_replace( '/', '__', $ab ), isset( $args['input'] ) && is_array( $args['input'] ) ? $args['input'] : array() );
			case 'delete_post':
				return self::rest( 'DELETE', '/wp/v2/posts/' . (int) $args['id'], array( 'force' => ! empty( $args['force'] ) ), array(), '' );
			case 'delete_comment':
				return self::rest( 'DELETE', '/wp/v2/comments/' . (int) $args['id'], array( 'force' => true ), array(), '' );
			case 'search_content':
				$q = array( 'search' => isset( $args['query'] ) ? $args['query'] : '', 'per_page' => isset( $args['per_page'] ) ? (int) $args['per_page'] : 10 );
				if ( ! empty( $args['type'] ) && 'all' !== $args['type'] ) {
					$q['type'] = $args['type'];
				}
				return self::rest( 'GET', '/wp/v2/search', $q, array(), 'id,title,type,url' );
			case 'install_plugin':
				return self::rest( 'POST', '/wp/v2/plugins', array(), array( 'slug' => $args['slug'], 'status' => ! empty( $args['activate'] ) ? 'active' : 'inactive' ), 'plugin,name,status,version' );
			case 'activate_plugin':
			case 'deactivate_plugin':
				$pid = self::plugin_id( $args['plugin'] );
				return is_wp_error( $pid ) ? $pid : self::rest( 'PUT', '/wp/v2/plugins/' . $pid, array(), array( 'status' => 'activate_plugin' === $name ? 'active' : 'inactive' ), 'plugin,name,status' );
			case 'delete_plugin':
				$pid = self::plugin_id( $args['plugin'] );
				return is_wp_error( $pid ) ? $pid : self::rest( 'DELETE', '/wp/v2/plugins/' . $pid, array(), array(), '' );
			case 'activate_theme':
				return self::rest( 'PUT', '/wp/v2/themes/' . rawurlencode( $args['stylesheet'] ), array(), array( 'status' => 'active' ), 'stylesheet,name,status' );
			case 'get_custom_posts':
				return self::custom_posts( $args );
			case 'bulk_update_posts':
				$out = array();
				foreach ( (array) $args['ids'] as $id ) {
					$r     = self::rest( 'POST', '/wp/v2/posts/' . (int) $id, array(), array( 'status' => $args['status'] ), 'id,status' );
					$out[] = is_wp_error( $r ) ? array( 'id' => $id, 'error' => $r->get_error_message() ) : $r;
				}
				return $out;
			case 'upload_media_from_url':
				return self::upload_from_url( $args );

			// Skills, memory and raw content.
			case 'detect_stack':
				return OpenWP_Skills::detect();
			case 'get_skill':
				return empty( $args['name'] ) ? OpenWP_Skills::all() : OpenWP_Skills::get( (string) $args['name'] );
			case 'get_content_raw':
				$type = preg_replace( '/[^a-z0-9_-]/i', '', isset( $args['type'] ) ? $args['type'] : 'pages' );
				$p    = self::rest( 'GET', '/wp/v2/' . $type . '/' . (int) $args['id'], array( 'context' => 'edit' ), array(), '' );
				return is_wp_error( $p ) ? $p : array( 'id' => $p['id'], 'type' => $p['type'], 'status' => $p['status'], 'title' => isset( $p['title']['raw'] ) ? $p['title']['raw'] : null, 'content' => isset( $p['content']['raw'] ) ? $p['content']['raw'] : null );
			case 'memory_write':
				return OpenWP_Memory::write( isset( $args['key'] ) ? $args['key'] : '', isset( $args['note'] ) ? $args['note'] : '' );
			case 'memory_read':
				return OpenWP_Memory::read( isset( $args['key'] ) ? $args['key'] : '' );
			case 'memory_search':
				return OpenWP_Memory::search( isset( $args['query'] ) ? $args['query'] : '' );
			case 'memory_delete':
				return OpenWP_Memory::delete( isset( $args['key'] ) ? $args['key'] : '' );

			// Bridge.
			case 'bridge_status':
				return OpenWP_Rest::status();
			case 'get_builder_data':
				return self::need_post( (int) $args['id'], false ) ?: OpenWP_Builders::read( (int) $args['id'], isset( $args['area'] ) ? sanitize_key( $args['area'] ) : 'content', isset( $args['builder'] ) ? sanitize_key( $args['builder'] ) : '' );
			case 'set_builder_data':
				$id = (int) $args['id'];
				unset( $args['id'] );
				$note = isset( $args['note'] ) ? (string) $args['note'] : '';
				return self::need_post( $id, true ) ?: OpenWP_Builders::write( $id, $args, $note );
			case 'list_builder_backups':
				return self::need_post( (int) $args['id'], false ) ?: array( 'backups' => OpenWP_Backup::summary( (int) $args['id'] ) );
			case 'restore_builder_backup':
				return self::need_post( (int) $args['id'], true ) ?: OpenWP_Builders::restore( (int) $args['id'], (int) $args['backup'] );
			case 'set_page_meta':
				return self::need_post( (int) $args['id'], true ) ?: OpenWP_Meta::write( (int) $args['id'], (array) $args['meta'] );

			// Developer mode.
			case 'dev_run_php':
			case 'dev_run_sql':
			case 'dev_files':
				$ok = OpenWP_Rest::can_dev();

				if ( true !== $ok ) {
					return is_wp_error( $ok ) ? $ok : new WP_Error( 'openwp_forbidden', 'Administrators only.' );
				}

				if ( 'dev_run_php' === $name ) {
					return OpenWP_Dev::run_php( isset( $args['code'] ) ? $args['code'] : '' );
				}

				if ( 'dev_run_sql' === $name ) {
					return OpenWP_Dev::run_sql( isset( $args['sql'] ) ? $args['sql'] : '', isset( $args['limit'] ) ? (int) $args['limit'] : 200 );
				}

				return OpenWP_Dev::file( isset( $args['action'] ) ? sanitize_key( $args['action'] ) : '', isset( $args['path'] ) ? $args['path'] : '', isset( $args['content'] ) ? $args['content'] : null );
		}

		return new WP_Error( 'openwp_unknown_tool', 'Unknown tool: ' . $name );
	}

	/**
	 * Safe WooCommerce updates. Reads the current values first, then:
	 * - dry_run: returns before/after and writes nothing;
	 * - orders: a status change (it can email the customer and change stock) needs confirm=true;
	 * - the answer always carries the previous values, so a change can be undone by sending them back.
	 */
	private static function woo_update( $name, array $args ) {
		$is_order = 'update_woo_order' === $name;
		$path     = ( $is_order ? '/wc/v3/orders/' : '/wc/v3/products/' ) . (int) ( isset( $args['id'] ) ? $args['id'] : 0 );
		$dry      = ! empty( $args['dry_run'] );
		$confirm  = ! empty( $args['confirm'] );
		unset( $args['id'], $args['dry_run'], $args['confirm'] );

		$current = self::rest( 'GET', $path, array(), array(), '' );

		if ( is_wp_error( $current ) ) {
			return $current;
		}

		$before = array();
		$after  = array();

		foreach ( $args as $k => $v ) {
			$old = isset( $current[ $k ] ) ? $current[ $k ] : null;

			if ( $old !== $v && ( ! is_scalar( $old ) || ! is_scalar( $v ) || (string) $old !== (string) $v ) ) {
				$before[ $k ] = $old;
				$after[ $k ]  = $v;
			}
		}

		if ( ! $before ) {
			return array( 'id' => $current['id'], 'changed' => false, 'message' => 'Nothing to change: the values are already the same.' );
		}

		$status_change = $is_order && isset( $before['status'] );
		$warning       = $status_change ? 'Changing the status of an order can send an email to the customer, change stock and run hooks of other plugins.' : '';

		if ( $dry || ( $status_change && ! $confirm ) ) {
			return array(
				'id'                => $current['id'],
				'written'           => false,
				'needs_confirm'     => $status_change && ! $dry,
				'before'            => $before,
				'after'             => $after,
				'warning'           => $warning,
				'how_to_apply'      => 'Call again without dry_run' . ( $status_change ? ' and with confirm=true' : '' ) . '.',
			);
		}

		$res = self::rest( 'PUT', $path, array(), $after, $is_order ? 'id,status,total' : 'id,name,status,price' );

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$res['previous'] = $before;

		if ( $warning ) {
			$res['warning'] = $warning;
		}

		return $res;
	}

	/** Returns an error if the post does not exist or the user may not edit it, otherwise null. */
	private static function need_post( $id, $write ) {
		if ( ! get_post( $id ) ) {
			return new WP_Error( 'openwp_no_post', 'Post not found.' );
		}

		return current_user_can( 'edit_post', $id ) ? null : new WP_Error( 'openwp_forbidden', 'You are not allowed to edit this post.' );
	}

	private static function from_table( array $row, array $args ) {
		list( $method, $path, $defaults, $query_keys, $fields ) = $row;
		$body_defaults = isset( $row[5] ) ? $row[5] : array();

		if ( false !== strpos( $path, '{id}' ) ) {
			$path = str_replace( '{id}', (string) (int) ( isset( $args['id'] ) ? $args['id'] : 0 ), $path );
			unset( $args['id'] );
		}

		if ( 'GET' === $method ) {
			$query = $defaults;

			foreach ( $query_keys as $k ) {
				if ( isset( $args[ $k ] ) && '' !== $args[ $k ] ) {
					$query[ $k ] = $args[ $k ];
				}
			}

			return self::rest( 'GET', $path, $query, array(), $fields );
		}

		return self::rest( $method, $path, array(), array_merge( $body_defaults, $args ), $fields );
	}

	/** One call to the REST API of this site, from inside PHP. */
	private static function rest( $method, $path, array $query, array $body, $fields ) {
		$req = new WP_REST_Request( $method, $path );

		if ( $fields ) {
			$query['_fields'] = $fields;
		}

		$req->set_query_params( $query );

		if ( $body ) {
			$req->set_body_params( $body );
			$req->set_header( 'content-type', 'application/json' );
			$req->set_body( wp_json_encode( $body ) );
		}

		$res = rest_do_request( $req );

		if ( $res->is_error() ) {
			$e = $res->as_error();

			return new WP_Error( $e->get_error_code(), $e->get_error_message() );
		}

		return rest_get_server()->response_to_data( $res, false );
	}

	/** "elementor" or "Elementor" or "elementor/elementor": the plugin id the REST API wants. */
	private static function plugin_id( $input ) {
		$list = self::rest( 'GET', '/wp/v2/plugins', array(), array(), 'name,plugin' );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$q = strtolower( (string) $input );

		foreach ( $list as $p ) {
			if ( strtolower( $p['plugin'] ) === $q ) {
				return $p['plugin'];
			}
		}

		foreach ( $list as $p ) {
			if ( false !== strpos( strtolower( $p['name'] ), $q ) || false !== strpos( strtolower( $p['plugin'] ), $q ) ) {
				return $p['plugin'];
			}
		}

		return new WP_Error( 'openwp_no_plugin', 'Plugin not found: "' . $input . '". Use get_plugins to see the list.' );
	}

	private static function custom_posts( array $args ) {
		$types = self::rest( 'GET', '/wp/v2/types', array(), array(), '' );

		if ( is_wp_error( $types ) ) {
			return $types;
		}

		foreach ( $types as $t ) {
			if ( isset( $t['slug'] ) && $t['slug'] === $args['post_type'] ) {
				$q = array( 'per_page' => isset( $args['per_page'] ) ? (int) $args['per_page'] : 10, 'status' => isset( $args['status'] ) ? $args['status'] : 'publish' );

				if ( ! empty( $args['search'] ) ) {
					$q['search'] = $args['search'];
				}

				return self::rest( 'GET', '/wp/v2/' . $t['rest_base'], $q, array(), 'id,title,status,date,link' );
			}
		}

		return new WP_Error( 'openwp_no_type', 'Post type "' . $args['post_type'] . '" not found. Use get_post_types to see the list.' );
	}

	private static function upload_from_url( array $args ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error( 'openwp_forbidden', 'You are not allowed to upload files.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( esc_url_raw( $args['url'] ) );

		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$file = array( 'name' => basename( (string) wp_parse_url( $args['url'], PHP_URL_PATH ) ) ?: 'upload', 'tmp_name' => $tmp );
		$id   = media_handle_sideload( $file, 0, isset( $args['title'] ) ? $args['title'] : null );

		if ( is_wp_error( $id ) ) {
			@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

			return $id;
		}

		if ( ! empty( $args['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $args['alt_text'] ) );
		}

		return array( 'id' => $id, 'url' => wp_get_attachment_url( $id ), 'title' => get_the_title( $id ), 'type' => get_post_mime_type( $id ) );
	}
}
