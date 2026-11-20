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
			<p>Open <a href="<?php echo esc_url( admin_url( 'profile.php#application-passwords-section' ) ); ?>">your profile</a>, name it &#8220;OpenWP&#8221; and copy the password WordPress shows you. You can delete it any time to cut the access.</p>
		</div></div>
		<div class="owp-step"><div class="owp-num">2</div><div>
			<h3>Type your user name and that password</h3>
			<p>They stay in this page, in your browser: nothing is sent or saved. They only build the command below.</p>
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
