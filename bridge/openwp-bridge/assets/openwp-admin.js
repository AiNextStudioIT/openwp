/* OpenWP Bridge admin page: builds the connect command, copy buttons, tabs. */
( function () {
	var root = document.querySelector( '.owp' );
	if ( ! root ) { return; }
	var url = root.getAttribute( 'data-url' );
	var u = document.getElementById( 'owp-u' ), p = document.getElementById( 'owp-p' );
	if ( ! u || ! p ) { return; } // Only the Connect section has the command box.

	function b64() { return btoa( u.value + ':' + p.value.replace( /\s+/g, ' ' ).trim() ); }

	function draw() {
		var b = b64();
		var cc = 'claude mcp add --transport http openwp ' + url + ' --header "Authorization: Basic ' + b + '"';
		var cur = JSON.stringify( { mcpServers: { openwp: { url: url, headers: { Authorization: 'Basic ' + b } } } }, null, 2 );
		var gen = 'Address: ' + url + '\nHeader:  Authorization: Basic ' + b;
		var cdx = "mkdir -p ~/.codex && touch ~/.codex/config.toml && cp ~/.codex/config.toml ~/.codex/config.toml.bak && awk '/^\\[mcp_servers\\.openwp(\\]|\\.)/{s=1;next} /^\\[/{s=0} !s' ~/.codex/config.toml.bak > ~/.codex/config.toml && cat >> ~/.codex/config.toml <<'EOF'\n\n[mcp_servers.openwp]\nurl = \"" + url + "\"\nhttp_headers = { \"Authorization\" = \"Basic " + b + "\" }\nEOF";
		document.getElementById( 'owp-cdx' ).textContent = cdx;
		document.getElementById( 'owp-cc' ).textContent = cc;
		document.getElementById( 'owp-cur' ).textContent = cur;
		document.getElementById( 'owp-gen' ).textContent = gen;
	}

	[ u, p ].forEach( function ( el ) { el.addEventListener( 'input', draw ); } );
	draw();

	// Step 1: create the Application Password from a small window and fill it in.
	var modal = document.getElementById( 'owp-modal' ), openBtn = document.getElementById( 'owp-open-pw' );
	if ( modal && openBtn && window.openwpBridge ) {
		var form = document.getElementById( 'owp-modal-form' ), done = document.getElementById( 'owp-modal-done' ), err = document.getElementById( 'owp-pw-error' ), create = document.getElementById( 'owp-pw-create' );
		function show( open ) {
			modal.hidden = ! open;
			if ( open ) { form.hidden = false; done.hidden = true; err.hidden = true; create.disabled = false; create.textContent = 'Create'; document.getElementById( 'owp-pw-name' ).focus(); }
		}
		openBtn.addEventListener( 'click', function () { show( true ); } );
		document.getElementById( 'owp-pw-cancel' ).addEventListener( 'click', function () { show( false ); } );
		document.getElementById( 'owp-pw-close' ).addEventListener( 'click', function () { show( false ); p.scrollIntoView( { behavior: 'smooth', block: 'center' } ); } );
		modal.addEventListener( 'click', function ( e ) { if ( e.target === modal ) { show( false ); } } );
		document.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' && ! modal.hidden ) { show( false ); } } );
		create.addEventListener( 'click', function () {
			create.disabled = true; create.textContent = 'Creating...'; err.hidden = true;
			var body = new URLSearchParams( { action: 'openwp_bridge_create_pw', nonce: window.openwpBridge.nonce, name: document.getElementById( 'owp-pw-name' ).value } );
			fetch( window.openwpBridge.ajax, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( r ) {
					if ( ! r.success ) { throw new Error( typeof r.data === 'string' ? r.data : 'Something went wrong.' ); }
					u.value = r.data.user; p.value = r.data.password; draw();
					document.getElementById( 'owp-pw-out' ).textContent = r.data.password;
					form.hidden = true; done.hidden = false;
				} )
				.catch( function ( e ) { err.textContent = e.message; err.hidden = false; create.disabled = false; create.textContent = 'Create'; } );
		} );
	}

	root.querySelectorAll( '.owp-tab' ).forEach( function ( t ) {
		t.addEventListener( 'click', function () {
			root.querySelectorAll( '.owp-tab,.owp-pane' ).forEach( function ( n ) { n.classList.remove( 'on' ); } );
			t.classList.add( 'on' );
			document.getElementById( t.getAttribute( 'data-pane' ) ).classList.add( 'on' );
		} );
	} );

	root.querySelectorAll( '.owp-copy' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var text = document.getElementById( btn.getAttribute( 'data-from' ) ).textContent;
			( navigator.clipboard ? navigator.clipboard.writeText( text ) : Promise.reject() ).catch( function () {
				var ta = document.createElement( 'textarea' ); ta.value = text; document.body.appendChild( ta ); ta.select(); document.execCommand( 'copy' ); ta.remove();
			} ).finally( function () {
				var old = btn.textContent; btn.textContent = 'Copied'; btn.classList.add( 'done' );
				setTimeout( function () { btn.textContent = old; btn.classList.remove( 'done' ); }, 1600 );
			} );
		} );
	} );
}() );
