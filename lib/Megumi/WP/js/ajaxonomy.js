( function( $ ){
	$( '.ajax-taxonomy' ).on( 'click', 'input[type=checkbox]', function(){
		load_child_terms( this );
	} );

	function load_child_terms( element ) {
		if ( $( element ).prop( 'checked' ) ) {
			taxonomies_query['term_id'] = $( element ).val();
			taxonomies_query['taxonomy'] = $( element ).data( 'taxonomy' );
			$( '.loader', $( element ).closest( 'label' ) ).addClass( 'loading' );
			if ( ! $( 'ul', $( element ).closest( 'li' ) ).length ) {
				$.ajax( {
					type: "GET",
					url: taxonomies_url,
					data: taxonomies_query,
					success: function( data ){
						if ( data.length ) {
							var list = $( '<ul />' );
							$( list ).html( data );
							$( element ).closest( 'li' ).append( list );
							$( 'input[type=checkbox]', list ).each( function(){
								load_child_terms( this );
							} );
						}
						$( '.loader', $( element ).closest( 'label' ) ).removeClass( 'loading' );
					}
				} );
			} else {
				$( '.loader', $( element ).closest( 'label' ) ).removeClass( 'loading' );
			}
		}
	}

	$( document ).ready( function(){
		$( '.ajax-taxonomy input[type=checkbox]' ).each( function(){
			load_child_terms( this );
		} );
	} );
} )( jQuery );