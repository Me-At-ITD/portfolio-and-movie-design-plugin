<?php
/**
 * Movie display template.
 *
 * @package CustomPostFrontendDisplay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cpf_instance_id   = isset( $cpf_instance_id ) ? sanitize_html_class( $cpf_instance_id ) : cpf_generate_instance_id( 'cpf-movie-' );
$cpf_modal_id      = $cpf_instance_id . '-modal';
$cpf_modal_title   = $cpf_instance_id . '-modal-title';
$cpf_all_posts     = isset( $cpf_all_posts ) && is_array( $cpf_all_posts ) ? $cpf_all_posts : array();
$cpf_posts_by_term = isset( $cpf_posts_by_term ) && is_array( $cpf_posts_by_term ) ? $cpf_posts_by_term : array();
$cpf_tab_panels    = array(
	array(
		'slug'  => 'all',
		'label' => __( 'All', 'custom-post-frontend-display' ),
		'posts' => $cpf_all_posts,
	),
);

if ( isset( $cpf_terms ) && is_array( $cpf_terms ) ) {
	foreach ( $cpf_terms as $cpf_term ) {
		if ( ! $cpf_term instanceof WP_Term ) {
			continue;
		}

		$cpf_term_id      = absint( $cpf_term->term_id );
		$cpf_tab_panels[] = array(
			'slug'  => (string) $cpf_term_id,
			'label' => $cpf_term->name,
			'posts' => isset( $cpf_posts_by_term[ $cpf_term_id ] ) && is_array( $cpf_posts_by_term[ $cpf_term_id ] ) ? $cpf_posts_by_term[ $cpf_term_id ] : array(),
		);
	}
}
?>
<section class="cpf-display cpf-movie-display" id="<?php echo esc_attr( $cpf_instance_id ); ?>">
	<div class="cpf-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Movie categories', 'custom-post-frontend-display' ); ?>">
		<?php foreach ( $cpf_tab_panels as $cpf_index => $cpf_panel ) : ?>
			<?php
			$cpf_is_active = ( 0 === $cpf_index );
			$cpf_slug      = sanitize_html_class( (string) $cpf_panel['slug'] );
			$cpf_tab_id    = $cpf_instance_id . '-tab-' . $cpf_slug;
			$cpf_panel_id  = $cpf_instance_id . '-panel-' . $cpf_slug;
			?>
			<button
				type="button"
				class="cpf-tab<?php echo $cpf_is_active ? ' is-active' : ''; ?>"
				id="<?php echo esc_attr( $cpf_tab_id ); ?>"
				role="tab"
				aria-selected="<?php echo $cpf_is_active ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( $cpf_panel_id ); ?>"
				data-cpf-target="<?php echo esc_attr( $cpf_panel_id ); ?>"
				tabindex="<?php echo $cpf_is_active ? '0' : '-1'; ?>"
			>
				<?php echo esc_html( $cpf_panel['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $cpf_tab_panels as $cpf_index => $cpf_panel ) : ?>
		<?php
		$cpf_is_active = ( 0 === $cpf_index );
		$cpf_slug      = sanitize_html_class( (string) $cpf_panel['slug'] );
		$cpf_tab_id    = $cpf_instance_id . '-tab-' . $cpf_slug;
		$cpf_panel_id  = $cpf_instance_id . '-panel-' . $cpf_slug;
		$cpf_posts     = isset( $cpf_panel['posts'] ) && is_array( $cpf_panel['posts'] ) ? $cpf_panel['posts'] : array();
		?>
		<div
			id="<?php echo esc_attr( $cpf_panel_id ); ?>"
			class="cpf-tab-panel<?php echo $cpf_is_active ? ' is-active' : ''; ?>"
			role="tabpanel"
			aria-labelledby="<?php echo esc_attr( $cpf_tab_id ); ?>"
			<?php echo $cpf_is_active ? '' : 'hidden'; ?>
		>
			<?php if ( empty( $cpf_posts ) ) : ?>
				<p class="cpf-empty-message"><?php esc_html_e( 'No movies found for this category.', 'custom-post-frontend-display' ); ?></p>
			<?php else : ?>
				<div class="cpf-grid-wrapper">
					<div class="cpf-movie-grid">
						<?php foreach ( $cpf_posts as $cpf_post ) : ?>
							<?php
							$cpf_post_id        = isset( $cpf_post['id'] ) ? absint( $cpf_post['id'] ) : 0;
							$cpf_post_title     = isset( $cpf_post['title'] ) ? (string) $cpf_post['title'] : '';
							$cpf_featured       = isset( $cpf_post['featured_image_url'] ) ? (string) $cpf_post['featured_image_url'] : '';
							$cpf_featured_alt   = isset( $cpf_post['featured_image_alt'] ) ? (string) $cpf_post['featured_image_alt'] : '';
							$cpf_video_url      = isset( $cpf_post['youtube_embed_url'] ) ? (string) $cpf_post['youtube_embed_url'] : '';
							$cpf_movie_date     = get_the_date( 'Y-m-d', $cpf_post_id );
							$cpf_duration_value = '';

							if ( function_exists( 'get_field' ) ) {
								$cpf_duration_field = get_field( 'duration', $cpf_post_id );
								if ( is_scalar( $cpf_duration_field ) ) {
									$cpf_duration_value = sanitize_text_field( (string) $cpf_duration_field );
								}
							}

							if ( '' === $cpf_duration_value ) {
								$cpf_duration_meta = get_post_meta( $cpf_post_id, 'duration', true );
								if ( is_scalar( $cpf_duration_meta ) ) {
									$cpf_duration_value = sanitize_text_field( (string) $cpf_duration_meta );
								}
							}

							$cpf_excerpt_raw = get_the_excerpt( $cpf_post_id );
							$cpf_excerpt     = wp_trim_words(
								wp_strip_all_tags( is_string( $cpf_excerpt_raw ) ? $cpf_excerpt_raw : '' ),
								18,
								'...'
							);

							if ( '' !== $cpf_video_url ) {
								$cpf_video_url = add_query_arg(
									array(
										'autoplay'       => '1',
										'modestbranding' => '1',
										'rel'            => '0',
										'controls'       => '1',
										'showinfo'       => '0',
									),
									$cpf_video_url
								);
							}
							?>
							<article class="cpf-movie-card">
								<button
									type="button"
									class="cpf-card-trigger cpf-movie-trigger cpf-movie-button"
									data-cpf-post-id="<?php echo esc_attr( $cpf_post_id ); ?>"
									data-cpf-title="<?php echo esc_attr( $cpf_post_title ); ?>"
									data-cpf-video-url="<?php echo esc_url( $cpf_video_url ); ?>"
									data-cpf-modal-id="<?php echo esc_attr( $cpf_modal_id ); ?>"
									aria-haspopup="dialog"
									aria-controls="<?php echo esc_attr( $cpf_modal_id ); ?>"
								>
									<span class="cpf-movie-thumb">
										<?php if ( '' !== $cpf_featured ) : ?>
											<img
												src="<?php echo esc_url( $cpf_featured ); ?>"
												alt="<?php echo esc_attr( $cpf_featured_alt ); ?>"
												loading="lazy"
												decoding="async"
											/>
										<?php else : ?>
											<span class="cpf-card-media-placeholder"><?php esc_html_e( 'Image unavailable', 'custom-post-frontend-display' ); ?></span>
										<?php endif; ?>
										<span class="cpf-overlay">
											<span class="cpf-play-icon"><i class="fa fa-play-circle" aria-hidden="true"></i></span>
										</span>
									</span>
									<span class="cpf-movie-info">
										<span class="cpf-title"><?php echo esc_html( $cpf_post_title ); ?></span>
										<span class="cpf-meta">
											<span class="cpf-date"><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo esc_html( is_string( $cpf_movie_date ) ? $cpf_movie_date : '' ); ?></span>
											<?php if ( '' !== $cpf_duration_value ) : ?>
												<span class="cpf-time"><i class="fa fa-clock" aria-hidden="true"></i> <?php echo esc_html( $cpf_duration_value ); ?></span>
											<?php endif; ?>
										</span>
										<?php if ( '' !== $cpf_excerpt ) : ?>
											<span class="cpf-desc"><?php echo esc_html( $cpf_excerpt ); ?></span>
										<?php endif; ?>
									</span>
								</button>
							</article>
						<?php endforeach; ?>
					</div>
					<div class="cpf-load-more-wrap">
						<button type="button" class="cpf-load-more"><?php esc_html_e( 'Load More', 'custom-post-frontend-display' ); ?></button>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</section>

<div class="cpf-modal cpf-movie-modal" id="<?php echo esc_attr( $cpf_modal_id ); ?>" hidden aria-hidden="true">
	<div class="cpf-modal-overlay" data-cpf-modal-close="1" aria-hidden="true"></div>
	<div class="cpf-modal-content cpf-modal-dialog cpf-movie-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $cpf_modal_title ); ?>" tabindex="-1">
		<button type="button" class="cpf-modal-close" data-cpf-modal-close="1" aria-label="<?php esc_attr_e( 'Close modal', 'custom-post-frontend-display' ); ?>">
			&times;
		</button>
		<h2 class="cpf-modal-title" id="<?php echo esc_attr( $cpf_modal_title ); ?>"></h2>
		<div class="cpf-video-wrapper" data-cpf-video-container></div>
		<p class="cpf-video-message" data-cpf-video-message hidden></p>
	</div>
</div>
