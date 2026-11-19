/**
 * Block editor canvas of a style layout: the body gets the wrapper BuddyPress pages have on the site
 * (#buddypress.buddypress-wrap), so the style rules for the BuddyPress screens (feed, sub tabs, lists, forms) apply in
 * the canvas too. Runs only inside the canvas iframe (enqueue_block_assets also loads it in the editor page).
 */
( function () {
	if ( window.self === window.top ) {
		return;
	}

	var watched = null;
	var wrap = function () {
		var body = document.body;

		if ( ! body ) {
			return;
		}

		if ( ! body.id ) {
			body.id = 'buddypress';
		}

		if ( ! body.classList.contains( 'buddypress-wrap' ) ) {
			body.classList.add( 'buddypress-wrap' );
		}

		// The editor sets the body (and its classes) after the canvas loads: keep the wrapper on it.
		if ( watched !== body ) {
			watched = body;
			new MutationObserver( wrap ).observe( body, { attributes: true, attributeFilter: [ 'class', 'id' ] } );
		}
	};

	wrap();
	new MutationObserver( wrap ).observe( document.documentElement, { childList: true } );
}() );
