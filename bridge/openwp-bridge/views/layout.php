<?php
/**
 * App shell: sidebar + the chosen section. Variables: $tab, $tabs, $ctx (see OpenWP_Settings::render()).
 */

defined( 'ABSPATH' ) || exit;

extract( $ctx ); // phpcs:ignore WordPress.PHP.DontExtract
?>
<div class="owp owp-app" data-url="<?php echo esc_attr( $mcp_url ); ?>">
	<aside class="owp-side">
		<div class="owp-brand"><span class="owp-logo">O</span><div><b>OpenWP</b><small>v<?php echo esc_html( OPENWP_BRIDGE_VERSION ); ?></small></div></div>
		<nav>
			<?php foreach ( $tabs as $key => $t ) : ?>
				<a href="<?php echo esc_url( OpenWP_Settings::url( $key ) ); ?>" class="<?php echo $key === $tab ? 'on' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $t[1] ); ?>"></span><?php echo esc_html( $t[0] ); ?>
					<?php if ( 'dev' === $key && $on ) : ?><em class="owp-tag">ON</em><?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div class="owp-side-foot">
			<span class="owp-dot <?php echo $connected ? 'on' : ''; ?>"></span>
			<span><?php echo $connected ? 'AI connected' : 'No AI connected'; ?></span>
		</div>
	</aside>

	<main class="owp-main">
		<?php if ( isset( $_GET['done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="owp-flash ok">Saved.</div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="owp-flash bad"><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?></div>
		<?php endif; ?>
		<?php require OPENWP_BRIDGE_DIR . 'views/tab-' . $tab . '.php'; ?>
	</main>
</div>
