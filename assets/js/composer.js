/**
 * BuddyBuilder post composer (2.0): posts through BuddyPress' AJAX action "post_update" (same nonce and fields as
 * BuddyPress' own form), then puts the new item at the top of the activity list on the page. No jQuery. Without
 * JavaScript the form posts to BuddyPress' /activity/post/ handler.
 *
 * Events on the form: "bpb:composer:posted" (detail: { id, html, response }) after a post, "bpb:composer:error".
 */
( function () {
	'use strict';

	const grow = ( input ) => {
		input.style.height = 'auto';
		input.style.height = Math.min( input.scrollHeight, 320 ) + 'px';
	};

	const list = ( form ) => {
		const scope = form.closest( '#buddypress' ) || document;
		return scope.querySelector( '[data-bp-list="activity"] ul.activity-list, #activity-stream ul.activity-list, ul.activity-list' );
	};

	const submit = async ( form ) => {
		const input = form.querySelector( '.bpb-composer__input' );
		const button = form.querySelector( '.bpb-composer__submit' );
		const status = form.querySelector( '.bpb-composer__status' );
		const content = input.value.trim();

		if ( ! content || form.classList.contains( 'is-busy' ) ) {
			return;
		}

		const postIn = form.querySelector( '[data-bpb-post-in]' );
		const objectField = form.querySelector( '[data-bpb-object]' );
		let object = objectField ? objectField.dataset.bpbObject : 'user';
		let itemId = form.querySelector( 'input[name="whats-new-post-in"]' );
		itemId = itemId ? itemId.value : '0';

		if ( postIn && '0' !== postIn.value ) {
			object = 'group';
			itemId = postIn.value;
		}

		// Every field of the form (extra fields from buddy_builder/composer/fields and BuddyPress options included),
		// plus the names BuddyPress' AJAX handler reads.
		const body = new FormData( form );
		body.set( 'action', 'post_update' );
		body.set( 'content', content );
		body.set( 'object', object );
		body.set( 'item_id', itemId );

		form.classList.add( 'is-busy' );
		button.disabled = true;
		status.textContent = '';

		try {
			const response = await fetch( form.dataset.ajax, { method: 'POST', body, credentials: 'same-origin' } );
			const json = await response.json();

			if ( ! json || ! json.success ) {
				throw new Error( json && json.data && json.data.message ? json.data.message : '' );
			}

			const target = list( form );
			const html = json.data.activity || '';

			if ( target && html && ! json.data.is_private ) {
				target.insertAdjacentHTML( 'afterbegin', html );
				const empty = target.parentNode.querySelector( '.bp-feedback' );
				if ( empty ) {
					empty.remove();
				}
			} else if ( ! target && html ) {
				window.location.reload();
				return;
			}

			// The new item shows in the list; otherwise (a private group, another list) say where it went.
			if ( json.data.message && ( ! target || json.data.is_private ) ) {
				status.textContent = new DOMParser().parseFromString( json.data.message, 'text/html' ).body.textContent;
			}

			input.value = '';
			grow( input );
			form.dispatchEvent( new CustomEvent( 'bpb:composer:posted', { bubbles: true, detail: { id: json.data.id, html, response: json.data } } ) );
		} catch ( error ) {
			status.textContent = error.message || form.dataset.error || 'Sorry, the post could not be published.';
			form.dispatchEvent( new CustomEvent( 'bpb:composer:error', { bubbles: true, detail: { error } } ) );
		} finally {
			form.classList.remove( 'is-busy' );
			button.disabled = false;
		}
	};

	document.addEventListener( 'submit', ( event ) => {
		const form = event.target.closest( '[data-bpb-composer]' );
		if ( form && window.fetch ) {
			event.preventDefault();
			submit( form );
		}
	} );

	document.addEventListener( 'input', ( event ) => {
		if ( event.target.matches( '.bpb-composer__input' ) ) {
			grow( event.target );
			event.target.closest( '[data-bpb-composer]' ).classList.toggle( 'has-text', '' !== event.target.value.trim() );
		}
	} );

	// Ctrl or Cmd + Enter posts.
	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && ( event.metaKey || event.ctrlKey ) && event.target.matches( '.bpb-composer__input' ) ) {
			event.preventDefault();
			submit( event.target.closest( '[data-bpb-composer]' ) );
		}
	} );
}() );
