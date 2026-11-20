<?php
/**
 * Developer mode: run PHP, read the database and read/write files, for staging and test sites.
 *
 * - OFF by default. The owner turns it on in the OpenWP menu (Developer mode), for 1 hour, 8 hours, 24 hours or until turned off.
 * - It turns itself off when the time ends.
 * - Only administrators can use it, and every call is written in the log.
 * - To forbid it on a site for good, add define( 'OPENWP_BRIDGE_DISABLE_DEV', true ); to wp-config.php.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Dev {

	const UNTIL   = 'openwp_bridge_dev_until';
	const MAX_READ = 204800; // 200 KB.

	public static function forbidden_for_good() {
		return defined( 'OPENWP_BRIDGE_DISABLE_DEV' ) && OPENWP_BRIDGE_DISABLE_DEV;
	}

	/** Seconds left, 0 when off. A very large value means "until I turn it off". */
	public static function seconds_left() {
		if ( self::forbidden_for_good() ) {
			return 0;
		}

		return max( 0, (int) get_option( self::UNTIL, 0 ) - time() );
	}

	public static function enabled() {
		return self::seconds_left() > 0;
	}

	public static function turn_on( $hours ) {
		$until = $hours > 0 ? time() + (int) $hours * HOUR_IN_SECONDS : time() + 10 * YEAR_IN_SECONDS;
		update_option( self::UNTIL, $until, false );
		OpenWP_Audit::add( 'dev_mode_on', $hours > 0 ? $hours . ' hours' : 'until turned off' );
	}

	public static function turn_off() {
		update_option( self::UNTIL, 0, false );
		OpenWP_Audit::add( 'dev_mode_off' );
	}

	/** Runs PHP code (without the opening tag) and returns what it printed and what it returned. */
	public static function run_php( $code ) {
		$code = trim( (string) $code );
		$code = preg_replace( '/^<\?php\s*/i', '', $code );

		OpenWP_Audit::add( 'dev_php', 'sha1 ' . sha1( $code ) . ': ' . mb_substr( $code, 0, 120 ) );

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 30 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		// A fatal error ends the request: this catches it and still answers with JSON.
		$GLOBALS['openwp_dev_running'] = true;
		register_shutdown_function(
			function () {
				if ( empty( $GLOBALS['openwp_dev_running'] ) ) {
					return;
				}

				$e = error_get_last();

				if ( $e && in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
					while ( ob_get_level() ) {
						ob_end_clean();
					}

					if ( ! headers_sent() ) {
						header( 'Content-Type: application/json; charset=utf-8' );
					}

					echo wp_json_encode( array( 'ok' => false, 'error' => $e['message'] . ' (line ' . $e['line'] . ')', 'fatal' => true ) );
				}
			}
		);

		ob_start();
		$result = null;
		$error  = null;

		try {
			$result = eval( $code ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Developer mode, by design.
		} catch ( \Throwable $e ) {
			$error = get_class( $e ) . ': ' . $e->getMessage() . ' (line ' . $e->getLine() . ')';
		}

		$output = ob_get_clean();
		$GLOBALS['openwp_dev_running'] = false;

		return array(
			'ok'     => null === $error,
			'output' => mb_substr( (string) $output, 0, self::MAX_READ ),
			'result' => $result,
			'error'  => $error,
		);
	}

	/** Read-only SQL: SELECT, SHOW, DESCRIBE, EXPLAIN. */
	public static function run_sql( $sql, $limit = 200 ) {
		global $wpdb;

		$sql = trim( (string) $sql );
		OpenWP_Audit::add( 'dev_sql', mb_substr( $sql, 0, 200 ) );

		if ( ! preg_match( '/^(select|show|describe|desc|explain)\b/i', $sql ) || false !== strpos( rtrim( $sql, "; \t\n" ), ';' ) || preg_match( '/into\s+(outfile|dumpfile)/i', $sql ) ) {
			return new WP_Error( 'openwp_sql_readonly', 'Only a single read-only query (SELECT, SHOW, DESCRIBE, EXPLAIN) is allowed. To change data, use run PHP.', array( 'status' => 400 ) );
		}

		$rows = $wpdb->get_results( rtrim( $sql, "; \t\n" ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $wpdb->last_error ) {
			return new WP_Error( 'openwp_sql_error', $wpdb->last_error, array( 'status' => 400 ) );
		}

		$total = is_array( $rows ) ? count( $rows ) : 0;

		return array( 'ok' => true, 'total' => $total, 'prefix' => $wpdb->prefix, 'rows' => array_slice( (array) $rows, 0, max( 1, (int) $limit ) ) );
	}

	/** Files, only inside wp-content. */
	public static function file( $action, $path, $content = null ) {
		$base = realpath( WP_CONTENT_DIR );
		$path = ltrim( str_replace( '\\', '/', (string) $path ), '/' );
		$full = $base . '/' . $path;

		if ( false !== strpos( $path, '..' ) ) {
			return new WP_Error( 'openwp_path', 'The path must stay inside wp-content.', array( 'status' => 400 ) );
		}

		OpenWP_Audit::add( 'dev_file_' . $action, $path );

		if ( 'list' === $action ) {
			$dir = is_dir( $full ) ? realpath( $full ) : false;

			if ( ! $dir || 0 !== strpos( $dir, $base ) ) {
				return new WP_Error( 'openwp_path', 'Folder not found.', array( 'status' => 404 ) );
			}

			$out = array();

			foreach ( array_slice( (array) scandir( $dir ), 0, 500 ) as $name ) {
				if ( '.' !== $name && '..' !== $name ) {
					$out[] = array( 'name' => $name, 'type' => is_dir( $dir . '/' . $name ) ? 'dir' : 'file', 'size' => is_file( $dir . '/' . $name ) ? filesize( $dir . '/' . $name ) : null );
				}
			}

			return array( 'ok' => true, 'path' => $path, 'items' => $out );
		}

		if ( 'read' === $action ) {
			$real = realpath( $full );

			if ( ! $real || 0 !== strpos( $real, $base ) || ! is_file( $real ) ) {
				return new WP_Error( 'openwp_path', 'File not found.', array( 'status' => 404 ) );
			}

			return array( 'ok' => true, 'path' => $path, 'size' => filesize( $real ), 'content' => file_get_contents( $real, false, null, 0, self::MAX_READ ), 'truncated' => filesize( $real ) > self::MAX_READ ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		if ( 'write' === $action ) {
			if ( ! is_string( $content ) ) {
				return new WP_Error( 'openwp_bad_payload', 'Send "content" as a string.', array( 'status' => 400 ) );
			}

			$dir = realpath( dirname( $full ) );

			if ( ! $dir || 0 !== strpos( $dir, $base ) ) {
				return new WP_Error( 'openwp_path', 'The folder must already exist inside wp-content.', array( 'status' => 400 ) );
			}

			$full = $dir . '/' . basename( $full );
			$bak  = null;

			if ( is_file( $full ) ) {
				$bak = $full . '.openwp-bak-' . gmdate( 'Ymd-His' );
				copy( $full, $bak ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}

			$bytes = file_put_contents( $full, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions

			return array( 'ok' => false !== $bytes, 'path' => $path, 'bytes' => $bytes, 'backup' => $bak ? basename( $bak ) : null );
		}

		return new WP_Error( 'openwp_bad_action', 'Action must be list, read or write.', array( 'status' => 400 ) );
	}
}
