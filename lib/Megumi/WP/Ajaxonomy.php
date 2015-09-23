<?php

namespace Megumi\WP;

class Ajaxonomy
{
	private $taxonomy;
	private $object_type;
	private $args;

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $taxonomy The name of the taxonomy.
	 * @param stinrg $object_type A name of the object type for the taxonomy object like `post`.
	 * @param array $args An array of Arguments. See https://codex.wordpress.org/Function_Reference/register_taxonomy#Arguments.
	 * @return none
	 */
	public function __construct( $taxonomy, $object_type, $args )
	{
		$default = array(
			'meta_box_cb'           => array( $this, 'meta_box_cb' ),
			'hierarchical'          => true,
			'show_admin_column'     => true,
			'show_tagcloud'         => false,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => $taxonomy ),
		);

		$this->taxonomy    = $taxonomy;
		$this->object_type = $object_type;
		$this->args        = wp_parse_args( $args, $default );
	}

	public function register()
	{
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Fires on `init` hook.
	 *
	 * @access public
	 * @param none
	 * @return none
	 */
	public function init()
	{
		if ( is_admin() ) {
			add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_get_taxonomies', array( $this, 'wp_ajax_get_taxonomies' ) );
			add_filter( 'taxonomy_parent_dropdown_args', array( $this, 'taxonomy_parent_dropdown_args' ), 10, 2 );
			add_action( $this->taxonomy . '_add_form', array( $this, 'add_script_to_taxonomy_admin' ) );
			add_action( $this->taxonomy . '_edit_form', array( $this, 'add_script_to_taxonomy_admin' ) );
		}

		register_taxonomy( $this->taxonomy, $this->object_type, $this->args );
	}

	/**
	 * Add scripts variables to the end of form.
	 *
	 * Fires `<taxonomy>_add_form` or `<taxonomy>_edit_form` hooks.
	 *
	 * @access public
	 * @param int|object $taxonomy Taxonomy object
	 * @return none
	 */
	public function add_script_to_taxonomy_admin( $taxonomy )
	{
		if ( is_object( $taxonomy ) ) { // on the edit term form
			$term_id = $taxonomy->term_id;

			$parents = array();
			$parent = $taxonomy->parent;
			while ( $parent ) {
				$parents[] = intval( $parent );
				$term = get_term( $parent, $taxonomy->taxonomy );
				$parent = $term->parent;
			}

			$query = array(
				'nonce'    => wp_create_nonce( 'wp-ajax-get-taxonomies' ),
				'action'   => 'get_taxonomies',
				'taxonomy' => $taxonomy->taxonomy,
				'parents'  => array_reverse( $parents ),
				'current_term_id'  => $taxonomy->term_id,
			);
		} else { // on the add new term form
			$query = array(
				'nonce'    => wp_create_nonce( 'wp-ajax-get-taxonomies' ),
				'action'   => 'get_taxonomies',
				'taxonomy' => $taxonomy,
			);
		}

		?>
		<script type="text/javascript">
			var taxonomies_url = '<?php echo esc_url( admin_url( '/admin-ajax.php' ) ); ?>';
			var taxonomies_query = <?php echo json_encode( $query ); ?>;
		</script>
		<?php
	}

	public function taxonomy_parent_dropdown_args( $args, $taxonomy )
	{
		if ( $this->taxonomy === $taxonomy ) {
			$args['parent'] = 0;
		}

		return $args;
	}

	public function get_terms_args( $args, $taxonomies )
	{
		if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
			if ( $taxonomies[0] === $this->taxonomy ) {
				$args['orderby'] = 'ID';
				$args['order']   = 'ASC';
				$args['parent']  = 0; // always returns parent only
			}
		} elseif ( in_array( $GLOBALS['pagenow'], array( 'edit-tags.php' ) ) ) {
			if ( $taxonomies[0] === $this->taxonomy ) {
				$args['orderby'] = 'ID';
				$args['order']   = 'ASC';
			}
		}

		return $args;
	}

	public function wp_ajax_get_taxonomies()
	{
		if ( empty( $_GET['nonce'] ) || empty( $_GET['action'] ) || empty( $_GET['taxonomy'] ) || empty( $_GET['term_id'] ) ) {
			exit;
		}

		if ( ! empty( $_GET['post_id'] ) ) {
			if ( wp_verify_nonce( $_GET['nonce'], 'wp-ajax-get-taxonomies' ) ) {
				$args = array(
					'orderby'           => 'ID',
					'order'             => 'ASC',
					'hide_empty'        => false,
					'parent'            => intval( $_GET['term_id'] ),
					'hierarchical'      => true,
					'child_of'          => 0,
				);
				$terms = (array) get_terms( $_GET['taxonomy'], $args );
				$selected = array();
				foreach ( $terms as $term ) {
					if ( has_term( $term->term_id, $_GET['taxonomy'], $_GET['post_id'] ) ) {
						$selected[] = $term->term_id;
					}
				}

				$walker = new Ajaxonomy_Walker;
				nocache_headers();
				echo call_user_func_array( array( $walker, 'walk' ), array( $terms, 0, array(
					'taxonomy' => $_GET['taxonomy'],
					'list_only' => false,
					'selected_cats' => $selected,
				) ) );
			}
		} else {
			if ( wp_verify_nonce( $_GET['nonce'], 'wp-ajax-get-taxonomies' ) ) {
				$args = array(
					'orderby'           => 'ID',
					'order'             => 'ASC',
					'hide_empty'        => false,
					'parent'            => intval( $_GET['term_id'] ),
					'hierarchical'      => true,
					'child_of'          => 0,
				);
				$terms = (array) get_terms( $_GET['taxonomy'], $args );

				nocache_headers();
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode( $terms );
			}
		}

		exit;
	}

	public function admin_enqueue_scripts()
	{
		wp_enqueue_style(
			'ajaxonomy',
			plugins_url( 'css/ajaxonomy.css', __FILE__ ),
			array(),
			'0.3.0'
		);

		wp_register_script(
			'ajaxonomy',
			plugins_url( 'js/ajaxonomy.js', __FILE__ ),
			array( 'jquery' ),
			'0.3.0',
			true
		);

		wp_localize_script( 'ajaxonomy', 'ajaxonomy_lang', array(
			'none' => esc_html__( 'None' ),
		) );

		wp_enqueue_script( 'ajaxonomy' );
	}

	public function admin_print_footer_scripts()
	{
		if ( ! in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		$query = array(
			'nonce'   => wp_create_nonce( 'wp-ajax-get-taxonomies' ),
			'action'  => 'get_taxonomies',
			'post_id' => get_the_ID(),
		);

		?>
		<script type="text/javascript">
			var taxonomies_url = '<?php echo esc_url( admin_url( '/admin-ajax.php' ) ); ?>';
			var taxonomies_query = <?php echo json_encode( $query ); ?>;
		</script>
		<?php
	}

	public function meta_box_cb( $post, $box )
	{
		?>
		<div id="taxonomy-<?php echo esc_attr( $this->taxonomy ); ?>" class="categorydiv">
			<?php
			$name = ( $this->taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $this->taxonomy . ']';
			// Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			echo "<input type='hidden' name='{$name}[]' value='0' />";
			?>
			<ul id="<?php echo esc_attr( $this->taxonomy ); ?>checklist" data-wp-lists="list:<?php echo esc_attr( $this->taxonomy ); ?>" class="categorychecklist form-no-clear ajax-taxonomy">
				<?php
					wp_terms_checklist( $post->ID, array(
						'taxonomy'      => $this->taxonomy,
						'checked_ontop' => false,
						'walker'        => new Ajaxonomy_Walker(),
					) );
				?>
			</ul>
		</div>
		<?php
	}
}
