<?php
/**
 * Block class.
 *
 * @package SiteCounts
 */

namespace XWP\SiteCounts;

use WP_Block;
use WP_Query;

/**
 * The Site Counts dynamic block.
 *
 * Registers and renders the dynamic block.
 */
class Block {

	/**
	 * The Plugin instance.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Instantiates the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Adds the action to register the block.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block.
	 */
	public function register_block() {
		register_block_type_from_metadata(
			$this->plugin->dir(),
			[
				'render_callback' => [ $this, 'render_callback' ],
			]
		);
	}

	/**
	 * Renders the block.
	 *
	 * @param array    $attributes The attributes for the block.
	 * @param string   $content    The block content, if any.
	 * @param WP_Block $block      The instance of this block.
	 * @return string The markup of the block.
	 */
	public function render_callback( $attributes, $content, $block ) {
		
		$current_post_id = get_the_ID();
		$post_types      = get_post_types( [ 'public' => true ] );
		$class_name      = $attributes['className'];

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class_name ); ?>">

			<h2><?php _e( 'Post Counts', 'site-counts' ); ?></h2>
			<ul>
				<?php
				foreach ( $post_types as $post_type_slug ) :
					$post_type_object = get_post_type_object( $post_type_slug );
					$post_type_name   = $post_type_object->labels->name;
					$post_type_count  = wp_count_posts( $post_type_slug )->publish;
					?>
					<li>
						<?php
						printf(
							/* translators: 1: post type count 2: post type name */
							__( 'There are %1$d %2$s', 'site-counts' ),
							esc_html( $post_type_count ),
							esc_html( $post_type_name )
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>

			<p>
				<?php
				printf(
					/* translators: current post ID */
					__( 'The current post ID is %d', 'site-counts' ),
					esc_html( $current_post_id )
				);
				?>
			</p>

			<?php

			// Display Query Results
			// -----------------------------------------------!

			$post_display_limit = 5; // max posts displayed that meet query criteria.
			$post_hour_start    = 9; // post date must be between these hours.
			$post_hour_end      = 17;
			$post_tag           = 'foo';
			$post_category      = 'baz';

			$query = new WP_Query(
				[
					'post_type'      => [ 'post', 'page' ],
					'post_status'    => 'any',
					'date_query'     => [
						[
							'hour'    => $post_hour_start,
							'compare' => '>=',
						],
						[
							'hour'    => $post_hour_end,
							'compare' => '<=',
						],
					],
					'tag'            => $post_tag,
					'category_name'  => $post_category,
					'post__not_in'   => [ $current_post_id ],
					'posts_per_page' => $post_display_limit,
					'no_found_rows'  => true, // prevents continuing DB search after reaching our post display limit.
					'meta_value'     => 'Accepted',
				]
			);

			$posts_heading_text = sprintf(
				/* translators: 1: display limit 2: tag 3: category 4: hour start 5: hour end */
				__( 'Latest %1$d posts with the tag %2$s and category %3$s posted between %4$s and %5$s', 'site-counts' ),
				esc_html( $post_display_limit ),
				esc_html( $post_tag ),
				esc_html( $post_category ),
				gmdate( 'g:ia', strtotime( "$post_hour_start:00" ) ),
				gmdate( 'g:ia', strtotime( "$post_hour_end:00" ) )
			);

			if ( $query->have_posts() ) :
				?>
				<h2><?php echo esc_html( $posts_heading_text ); ?></h2>
				<ul>
					<?php foreach ( $query->posts as $post ) : ?>
						<li><?php echo esc_html( $post->post_title ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php
			endif;
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
