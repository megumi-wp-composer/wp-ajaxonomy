( function( $ ){

	/**
	 * Should fires only on /wp-admin/edit-tags.php?taxonomy=<taxonomy>
	 */
	$( '.term-parent-wrap' ).on( 'change', 'select', function(){
		load_child_trems_for_taxonomy_admin( this );
	} );

	/**
	 * Should fires only on /wp-admin/post-new.php
	 */
	$( '.ajax-taxonomy' ).on( 'click', 'input[type=checkbox]', function(){
		load_child_terms_for_post_admin( this );
	} );

	/**
	 * Should fires only on /wp-admin/post-new.php
	 */
	$( '.ajax-taxonomy a.ajax-taxonomy-clear' ).click( function( e ){
		$( '.ajax-taxonomy input[type=checkbox]' ).prop( 'checked', false );
		e.preventDefault();
		return false;
	} );

	/**
	 * Adds loader animation to the postbox title
	 */
	$( 'h3.hndle', $( '.ajax-taxonomy' ).closest( '.postbox' ) ).append(
		$( '<span class="ajaxonomy-loader"></span>' )
	);

	/**
	 * Should fires only on /wp-admin/post-new.php
	 */
	$( window ).on( 'load', function(){
		$( '.ajax-taxonomy input[type=checkbox]' ).each( function(){
			load_child_terms_for_post_admin( this );
		} );
		if ( $( '.term-parent-wrap select' ).length && taxonomies_query.parents ) {
			for ( var i = 0; i < taxonomies_query.parents.length; i++ ) {
				$( '.term-parent-wrap select' ).eq( i ).val( taxonomies_query.parents[i] );
				$( '.term-parent-wrap select' ).eq( i ).trigger( 'change' );
			}
		}
	} );

	function load_child_trems_for_taxonomy_admin( element ) {
		if ( 'undefined' == typeof taxonomies_url || 'undefined' == typeof taxonomies_query ) {
			return;
		}

		$( '.term-parent-wrap select' ).attr( 'name', '' );
		$( '.term-parent-wrap select' ).attr( 'id', '' );
		if ( $( element ).val() ) {
			$( element ).attr( 'name', 'parent' );
		} else {
			$( element ).prev().attr( 'name', 'parent' );
		}

		if ( taxonomies_url && taxonomies_query ) {
			taxonomies_query['term_id'] = $( element ).val();
			$.ajax( {
				type: "GET",
				url: taxonomies_url,
				data: taxonomies_query,
				async: false,
				success: function( data ){
					var remove_flag = false;
					$( '.term-parent-wrap select' ).each( function(){
						if ( parseInt( $( element ).data( 'hierarchical' ) ) < parseInt( $( this ).data( 'hierarchical' ) ) ) {
							$( this ).remove();
						}
					} );
					if ( data.length ) {
						var select = $( '<select class="children"><option value="">' + ajaxonomy_lang.none + '</option></select>' );
						if ( ! $( element ).data( 'hierarchical' ) ) {
							$( element ).data( 'hierarchical', 1 );
						}
						$( select ).data( 'hierarchical', parseInt( $( element ).data( 'hierarchical' ) ) + 1 );
						$( element ).parent().append( select );

						for ( var i = 0; i < data.length; i++ ) {
							// editing panel only
							if ( 'undefined' != typeof taxonomies_query['current_term_id'] && taxonomies_query['current_term_id'] == data[i].term_id ) {
								continue;
							}
							var option = $( '<option />' ).val( data[i].term_id ).text( data[i].name );
							$( select ).append( option );
						}
					}
				}
			} );
		}
	}

	function load_child_terms_for_post_admin( element ) {
		if ( $( element ).prop( 'checked' ) ) {
			taxonomies_query['term_id'] = $( element ).val();
			taxonomies_query['taxonomy'] = $( element ).data( 'taxonomy' );
			if ( ! $( 'ul', $( element ).closest( 'li' ) ).length ) { // prevent duplicate
				$.ajax( {
					type: "GET",
					url: taxonomies_url,
					data: taxonomies_query,
					beforeSend: function(){
						$( 'h3 .ajaxonomy-loader' ).addClass( 'loading' );
						$( '#publish' ).prop( 'disabled', true );
						$( '.ajaxonomy-loader', $( element ).closest( 'label' ) ).addClass( 'loading' );
					}
				} ).done( function( data ){
					if ( data.length ) {
						var list = $( '<ul />' );
						$( list ).html( data );
						$( element ).closest( 'li' ).append( list );
						$( 'input[type=checkbox]', list ).each( function(){
							load_child_terms_for_post_admin( this ); // `this` is this!!
						} );
					}
				} ).then( function(){
					$( '.ajaxonomy-loader', $( element ).closest( 'label' ) ).removeClass( 'loading' );
					if ( ! $( '.ajax-taxonomy .ajaxonomy-loader.loading' ).length ) { // run after all ajax complete
						$( 'h3 .ajaxonomy-loader' ).removeClass( 'loading' );
						$( '#publish' ).prop( 'disabled', false );
					}
				} );
			}
		}
	}

} )( jQuery );
