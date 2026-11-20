/* OpenWP Bridge admin page: builds the connect command, copy buttons, tabs. */
( function () {
	var root = document.querySelector( '.owp' );
	if ( ! root ) { return; }
	var url = root.getAttribute( 'data-url' );
	var u = document.getElementById( 'owp-u' ), p = document.getElementById( 'owp-p' );

	function b64() { return btoa( u.value + ':' + p.value.replace( /\s+/g, ' ' ).trim() ); }

	function draw() {
		var b = b64();
		var cc = 'claude mcp add --transport http openwp ' + url + ' --header "Authorization: Basic ' + b + '"';
		var cur = JSON.stringify( { mcpServers: { openwp: { url: url, headers: { Authorization: 'Basic ' + b } } } }, null, 2 );
		var gen = 'Address: ' + url + '\nHeader:  Authorization: Basic ' + b;
		document.getElementById( 'owp-cc' ).textContent = cc;
		document.getElementById( 'owp-cur' ).textContent = cur;
		document.getElementById( 'owp-gen' ).textContent = gen;
	}

	[ u, p ].forEach( function ( el ) { el.addEventListener( 'input', draw ); } );
	draw();

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
