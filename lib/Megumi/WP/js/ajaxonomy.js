( function( $ ){
	$( '.ajax-taxonomy' ).on( 'click', 'input[type=checkbox]', function(){
		load_child_terms_for_post_admin( this );
	} );

	$( '.term-parent-wrap' ).on( 'change', 'select', function(){
		load_child_theme_for_taxonomy_admin( this );
	} );

	function load_child_theme_for_taxonomy_admin( element ) {
		if ( 'undefined' == typeof taxonomies_url || 'undefined' == typeof taxonomies_query ) {
			return;
		}

		$( '.term-parent-wrap select' ).attr( 'name', '' );
		$( '.term-parent-wrap select' ).attr( 'id', '' );
		if ( $( element ).val() ) {
			$( element ).attr( 'name', 'parent' );
		}

		if ( taxonomies_url && taxonomies_query ) {
			taxonomies_query['term_id'] = $( element ).val();
			$.ajax( {
				type: "GET",
				url: taxonomies_url,
				data: taxonomies_query,
				success: function( data ){
					var remove_flag = false;
					$( '.term-parent-wrap select' ).each( function(){
						if ( parseInt( $( element ).data( 'hierarchical' ) ) < parseInt( $( this ).data( 'hierarchical' ) ) ) {
							$( this ).remove();
						}
					} );
					if ( data.length ) {
						var select = $( '<select class="children"><option value=""></option></select>' );
						if ( ! $( element ).data( 'hierarchical' ) ) {
							$( element ).data( 'hierarchical', 1 );
						}
						$( select ).data( 'hierarchical', parseInt( $( element ).data( 'hierarchical' ) ) + 1 );
						$( element ).closest( '.term-parent-wrap' ).append( select );

						for ( var i = 0; i < data.length; i++ ) {
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
								load_child_terms_for_post_admin( this );
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
			load_child_terms_for_post_admin( this );
		} );
	} );
} )( jQuery );
