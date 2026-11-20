<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="owp-card">
	<h2>Connect your AI</h2>
	<p class="lead">No program to install on your computer.</p>
	<div class="owp-flow"><span class="owp-node">Your AI (Claude, Cursor...)</span><span class="owp-arrow">&rarr;</span><span class="owp-node me">This site &middot; OpenWP</span></div>
	<div class="owp-steps">
		<div class="owp-step"><div class="owp-num">1</div><div>
			<h3>Create an Application Password</h3>
			<p>A key that lets your AI work on this site. We create it here for you and fill it in below. You can delete it any time in <a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">your profile</a> to cut the access.</p>
			<p style="margin:10px 0 0"><button type="button" class="owp-btn" id="owp-open-pw">Create it for me</button></p>
		</div></div>
		<div class="owp-step"><div class="owp-num">2</div><div>
			<h3>Your user name and password</h3>
			<p>Filled in for you after step 1 (or paste a password you already have). They stay in this page, in your browser, and only build the command below.</p>
			<div class="owp-fields">
				<input type="text" id="owp-u" placeholder="user name" value="<?php echo esc_attr( wp_get_current_user()->user_login ); ?>" autocomplete="off">
				<input type="text" id="owp-p" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" autocomplete="off">
			</div>
		</div></div>
		<div class="owp-step"><div class="owp-num">3</div><div style="min-width:0;flex:1">
			<h3>Copy the command for your AI</h3>
			<div class="owp-tabs">
				<button type="button" class="owp-tab on" data-pane="owp-p1">Claude Code</button>
				<button type="button" class="owp-tab" data-pane="owp-p2">Cursor</button>
				<button type="button" class="owp-tab" data-pane="owp-p3">Other</button>
			</div>
			<div id="owp-p1" class="owp-pane on"><div style="position:relative"><pre class="owp-code" id="owp-cc"></pre><button type="button" class="owp-copy" data-from="owp-cc">Copy</button></div></div>
			<div id="owp-p2" class="owp-pane"><div style="position:relative"><pre class="owp-code" id="owp-cur"></pre><button type="button" class="owp-copy" data-from="owp-cur">Copy</button></div><p class="owp-note">Paste it in Cursor&#8217;s MCP settings (<code>mcp.json</code>).</p></div>
			<div id="owp-p3" class="owp-pane"><div style="position:relative"><pre class="owp-code" id="owp-gen"></pre><button type="button" class="owp-copy" data-from="owp-gen">Copy</button></div><p class="owp-note">Any client that connects to an MCP address with a header works. Claude Desktop: not confirmed, use the OpenWP program from the README.</p></div>
		</div></div>
	</div>
</div>

<div class="owp-modal" id="owp-modal" hidden>
	<div class="owp-modal-box" role="dialog" aria-modal="true" aria-labelledby="owp-modal-title">
		<h2 id="owp-modal-title">Create an Application Password</h2>
		<div id="owp-modal-form">
			<p class="lead" style="margin:0 0 14px">Give it a name, so you recognise it later in your profile.</p>
			<div class="owp-fields" style="margin:0 0 6px"><input type="text" id="owp-pw-name" value="OpenWP" autocomplete="off"></div>
			<p class="owp-note" id="owp-pw-error" style="color:#b91c1c" hidden></p>
			<div class="owp-modal-actions"><button type="button" class="owp-btn ghost" id="owp-pw-cancel">Cancel</button><button type="button" class="owp-btn" id="owp-pw-create">Create</button></div>
		</div>
		<div id="owp-modal-done" hidden>
			<p class="lead" style="margin:0 0 12px"><strong>Done.</strong> The password is already filled in below. WordPress shows it only once, so copy it if you want to keep it.</p>
			<div style="position:relative"><pre class="owp-code" id="owp-pw-out" style="padding-right:92px"></pre><button type="button" class="owp-copy" data-from="owp-pw-out">Copy</button></div>
			<div class="owp-modal-actions"><button type="button" class="owp-btn" id="owp-pw-close">Continue</button></div>
		</div>
	</div>
</div>
