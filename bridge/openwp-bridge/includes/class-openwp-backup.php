<?php
/**
 * Copy of the previous content, taken before every write. Kept in the post meta, last 10 per post.
 */

defined( 'ABSPATH' ) || exit;

class OpenWP_Backup {

	const META = '_openwp_backups';
	const MAX  = 10;

	/** Saves a copy. $value is whatever the builder stores (string or array). Returns the backup id. */
	public static function add( $post_id, $builder, $area, $value, $note = '' ) {
		$list = self::all( $post_id );
		$id   = (int) ( $list ? max( array_column( $list, 'id' ) ) : 0 ) + 1;

		$list[] = array(
			'id'      => $id,
			'time'    => gmdate( 'c' ),
			'user'    => get_current_user_id(),
			'builder' => $builder,
			'area'    => $area,
			'note'    => mb_substr( (string) $note, 0, 200 ),
			'value'   => $value,
		);

		update_post_meta( $post_id, self::META, array_slice( $list, -self::MAX ) );

		return $id;
	}

	public static function all( $post_id ) {
		$list = get_post_meta( $post_id, self::META, true );

		return is_array( $list ) ? $list : array();
	}

	/** List without the (large) saved values. */
	public static function summary( $post_id ) {
		return array_map(
			function ( $b ) {
				unset( $b['value'] );

				return $b;
			},
			array_reverse( self::all( $post_id ) )
		);
	}

	public static function get( $post_id, $backup_id ) {
		foreach ( self::all( $post_id ) as $b ) {
			if ( (int) $b['id'] === (int) $backup_id ) {
				return $b;
			}
		}

		return null;
	}
}
