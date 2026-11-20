<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="owp-card">
	<h2>Activity</h2>
	<p class="lead">The last 50 things your AI did on this site.</p>
	<?php $rows = array_slice( OpenWP_Audit::all(), 0, 50 ); require OPENWP_BRIDGE_DIR . 'views/part-log.php'; ?>
</div>
