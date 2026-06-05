/**
 * MBSS Theme — frontend JS (scaffold)
 */
( function () {
	'use strict';

	var form = document.querySelector( '[data-mbss-course-finder]' );
	if ( ! form ) {
		return;
	}

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		var result = form.parentElement.querySelector( '.mbss-course-finder__result' );
		if ( ! result ) {
			return;
		}
		result.hidden = false;
		result.innerHTML =
			'<p><strong>Recommended:</strong> ASA 101 — <a href="/learn-to-sail/asa-101/">Learn more</a> · <a href="/book/">Reserve Your Spot</a></p>';
	} );
} )();
