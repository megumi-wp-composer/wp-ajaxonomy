<?php

namespace Megumi\WP;

class Ajaxonomy
{
	private $taxonomy;
	private $object_type;
	private $args;

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
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded()
	{
		add_action( 'init', array( $this, 'init' ) );

		if ( is_admin() ) {
			add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_ajax_get_taxonomies', array( $this, 'wp_ajax_get_taxonomies' ) );
		}
	}

	public function init()
	{
		register_taxonomy( $this->taxonomy, $this->object_type, $this->args );
	}

	public function get_terms_args( $args, $taxonomies )
	{
		if ( in_array( $GLOBALS['pagenow'], array( 'post.php', 'post-new.php' ) ) ) {
			if ( $taxonomies[0] === $this->taxonomy ) {
				$args['orderby'] = 'ID';
				$args['order']   = 'ASC';
				$args['parent']  = 0; // always returns parent only
			}
		}

		return $args;
	}

	public function wp_ajax_get_taxonomies()
	{
		if ( empty( $_GET['nonce'] ) || empty( $_GET['action'] ) || empty( $_GET['taxonomy'] ) || empty( $_GET['term_id'] ) || empty( $_GET['post_id'] ) ) {
			exit;
		}

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
			echo call_user_func_array( array( $walker, 'walk' ), array( $terms, 0, array(
				'taxonomy' => $this->taxonomy,
				'list_only' => false,
				'selected_cats' => $selected,
			) ) );
		}

		exit;
	}

	public function admin_enqueue_scripts()
	{
		wp_enqueue_script(
			'ajax-taxonomy',
			plugins_url( 'js/ajax-taxonomy.js', __FILE__ ),
			array( 'jquery' ),
			'0.1.0',
			true
		);
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
