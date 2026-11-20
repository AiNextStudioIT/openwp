<?php
/**
 * MCP over HTTP (JSON-RPC, "Streamable HTTP" in its plain JSON form), so an AI client can connect straight to the site:
 * no program to install on the computer. Endpoint: POST /wp-json/openwp/v1/mcp, with an Application Password.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Mcp {

	const PROTOCOLS = array( '2025-06-18', '2025-03-26', '2024-11-05' );

	public static function init() {
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'serve_empty' ), 10, 3 );
	}

	/** A notification gets "202 Accepted" with no body, as the protocol asks. */
	public static function serve_empty( $served, $result, $request ) {
		if ( $request && '/openwp/v1/mcp' === $request->get_route() && 202 === $result->get_status() ) {
			status_header( 202 );

			return true;
		}

		return $served;
	}

	public static function handle( WP_REST_Request $request ) {
		$body = $request->get_json_params();

		if ( null === $body || ( ! is_array( $body ) ) ) {
			return self::error( null, -32700, 'Parse error' );
		}

		OpenWP_Presence::touch( 'MCP ' . mb_substr( (string) $request->get_header( 'user_agent' ), 0, 40 ), '/openwp/v1/mcp' );

		// A batch is a list of messages.
		$is_batch = array_keys( $body ) === range( 0, count( $body ) - 1 ) && isset( $body[0] );
		$messages = $is_batch ? $body : array( $body );
		$replies  = array();

		foreach ( $messages as $msg ) {
			$reply = self::one( is_array( $msg ) ? $msg : array() );

			if ( null !== $reply ) {
				$replies[] = $reply;
			}
		}

		if ( ! $replies ) {
			return new WP_REST_Response( null, 202 );
		}

		return new WP_REST_Response( $is_batch ? $replies : $replies[0], 200 );
	}

	/** One JSON-RPC message. Returns the reply, or null for a notification. */
	private static function one( array $msg ) {
		$has_id = array_key_exists( 'id', $msg );
		$id     = $has_id ? $msg['id'] : null;
		$method = isset( $msg['method'] ) ? (string) $msg['method'] : '';
		$params = isset( $msg['params'] ) && is_array( $msg['params'] ) ? $msg['params'] : array();

		if ( ! $has_id ) {
			return null; // notifications/initialized and the like.
		}

		switch ( $method ) {
			case 'initialize':
				$wanted = isset( $params['protocolVersion'] ) ? (string) $params['protocolVersion'] : '';

				return self::result(
					$id,
					array(
						'protocolVersion' => in_array( $wanted, self::PROTOCOLS, true ) ? $wanted : self::PROTOCOLS[1],
						'capabilities'    => array( 'tools' => (object) array( 'listChanged' => false ) ),
						'serverInfo'      => array( 'name' => 'openwp-bridge', 'version' => OPENWP_BRIDGE_VERSION ),
						'instructions'    => 'WordPress site tools. On a new site call detect_stack, then read the matching skills with get_skill before changing layouts or SEO data.',
					)
				);

			case 'ping':
				return self::result( $id, (object) array() );

			case 'tools/list':
				$ours = array_values( array_filter( OpenWP_Mcp_Tools::definitions(), function ( $t ) {
					return ! in_array( $t->name, OpenWP_Abilities::hidden_ours(), true );
				} ) );

				return self::result( $id, array( 'tools' => array_merge( $ours, OpenWP_Abilities::tools() ) ) );

			case 'tools/call':
				$name = isset( $params['name'] ) ? (string) $params['name'] : '';
				$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();

				if ( ! OpenWP_Abilities::is_ability_tool( $name ) && ! OpenWP_Mcp_Tools::has( $name ) ) {
					return self::error( $id, -32602, 'Unknown tool: ' . $name );
				}

				try {
					$data = OpenWP_Mcp_Tools::call( $name, $args );
				} catch ( \Throwable $e ) {
					$data = new WP_Error( 'openwp_exception', $e->getMessage() );
				}

				if ( is_wp_error( $data ) ) {
					return self::result( $id, array( 'content' => array( array( 'type' => 'text', 'text' => 'Error: ' . $data->get_error_message() ) ), 'isError' => true ) );
				}

				return self::result( $id, array( 'content' => array( array( 'type' => 'text', 'text' => wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) ) ) );
		}

		return self::error( $id, -32601, 'Method not found: ' . $method );
	}

	private static function result( $id, $result ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'result' => $result );
	}

	private static function error( $id, $code, $message ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'error' => array( 'code' => $code, 'message' => $message ) );
	}
}
