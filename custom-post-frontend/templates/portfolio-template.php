<?php
/**
 * Portfolio display template.
 *
 * @package CustomPostFrontendDisplay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cpf_instance_id = isset( $cpf_instance_id ) ? sanitize_html_class( $cpf_instance_id ) : cpf_generate_instance_id( 'cpf-portfolio-' );
$cpf_modal_id    = $cpf_instance_id . '-modal';
$cpf_modal_title = $cpf_instance_id . '-modal-title';
$cpf_all_posts   = isset( $cpf_all_posts ) && is_array( $cpf_all_posts ) ? $cpf_all_posts : array();
$cpf_posts_by_term = isset( $cpf_posts_by_term ) && is_array( $cpf_posts_by_term ) ? $cpf_posts_by_term : array();
$cpf_tab_panels  = array(
	array(
		'panel_key' => 'all',
		'name'      => __( 'All', 'custom-post-frontend-display' ),
		'slug'      => '',
		'posts'     => $cpf_all_posts,
	),
);

if ( isset( $cpf_terms ) && is_array( $cpf_terms ) ) {
	foreach ( $cpf_terms as $cpf_term ) {
		if ( ! $cpf_term instanceof WP_Term ) {
			continue;
		}

		$cpf_term_id     = absint( $cpf_term->term_id );
		$cpf_tab_panels[] = array(
			'panel_key' => 'term-' . $cpf_term_id,
			'name'      => $cpf_term->name,
			'slug'      => $cpf_term->slug,
			'posts' => isset( $cpf_posts_by_term[ $cpf_term_id ] ) && is_array( $cpf_posts_by_term[ $cpf_term_id ] ) ? $cpf_posts_by_term[ $cpf_term_id ] : array(),
		);
	}
}
?>
<section class="cpf-display cpf-portfolio-display" id="<?php echo esc_attr( $cpf_instance_id ); ?>">
	<div class="cpf-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Portfolio categories', 'custom-post-frontend-display' ); ?>">
		<?php foreach ( $cpf_tab_panels as $cpf_index => $cpf_panel ) : ?>
			<?php
			$cpf_is_active = ( 0 === $cpf_index );
			$cpf_key       = sanitize_html_class( (string) $cpf_panel['panel_key'] );
			$cpf_tab_id    = $cpf_instance_id . '-tab-' . $cpf_key;
			$cpf_panel_id  = $cpf_instance_id . '-panel-' . $cpf_key;
			$cpf_tab_name  = isset( $cpf_panel['name'] ) ? (string) $cpf_panel['name'] : '';
			$cpf_tab_slug  = isset( $cpf_panel['slug'] ) ? (string) $cpf_panel['slug'] : '';
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
				<span class="cpf-tab-name"><?php echo esc_html( $cpf_tab_name ); ?></span>
				<?php if ( '' !== $cpf_tab_slug ) : ?>
					<span class="cpf-tab-slug">(<?php echo esc_html( $cpf_tab_slug ); ?>)</span>
				<?php endif; ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $cpf_tab_panels as $cpf_index => $cpf_panel ) : ?>
		<?php
		$cpf_is_active = ( 0 === $cpf_index );
		$cpf_key       = sanitize_html_class( (string) $cpf_panel['panel_key'] );
		$cpf_tab_id    = $cpf_instance_id . '-tab-' . $cpf_key;
		$cpf_panel_id  = $cpf_instance_id . '-panel-' . $cpf_key;
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
				<p class="cpf-empty-message"><?php esc_html_e( 'No portfolio items found for this category.', 'custom-post-frontend-display' ); ?></p>
			<?php else : ?>
				<div class="cpf-portfolio-grid">
					<?php foreach ( $cpf_posts as $cpf_post ) : ?>
						<?php
						$cpf_post_id      = isset( $cpf_post['id'] ) ? absint( $cpf_post['id'] ) : 0;
						$cpf_post_title   = isset( $cpf_post['title'] ) ? (string) $cpf_post['title'] : '';
						$cpf_featured     = isset( $cpf_post['featured_image_url'] ) ? (string) $cpf_post['featured_image_url'] : '';
						$cpf_featured_alt = isset( $cpf_post['featured_image_alt'] ) ? (string) $cpf_post['featured_image_alt'] : '';
						$cpf_gallery      = isset( $cpf_post['gallery'] ) && is_array( $cpf_post['gallery'] ) ? $cpf_post['gallery'] : array();
						$cpf_gallery_json = wp_json_encode( $cpf_gallery );
						$cpf_gallery_json = is_string( $cpf_gallery_json ) ? $cpf_gallery_json : '[]';
						$cpf_photo_count  = count( $cpf_gallery );
						$cpf_photo_text   = $cpf_photo_count > 0
							? sprintf(
								/* translators: %d: number of photos. */
								_n( '%d photo', '%d photos', $cpf_photo_count, 'custom-post-frontend-display' ),
								$cpf_photo_count
							)
							: __( 'View Photos', 'custom-post-frontend-display' );
						$cpf_item_date = get_the_date( 'Y-m-d', $cpf_post_id );
						?>
						<article class="cpf-portfolio-card">
							<button
								type="button"
								class="cpf-card-trigger cpf-portfolio-trigger cpf-portfolio-button"
								data-cpf-post-id="<?php echo esc_attr( $cpf_post_id ); ?>"
								data-cpf-title="<?php echo esc_attr( $cpf_post_title ); ?>"
								data-cpf-images="<?php echo esc_attr( $cpf_gallery_json ); ?>"
								data-cpf-modal-id="<?php echo esc_attr( $cpf_modal_id ); ?>"
								aria-haspopup="dialog"
								aria-controls="<?php echo esc_attr( $cpf_modal_id ); ?>"
							>
								<span class="cpf-portfolio-thumb">
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
										<span class="cpf-photo-count"><?php echo esc_html( $cpf_photo_text ); ?></span>
									</span>
								</span>
								<span class="cpf-portfolio-info">
									<span class="cpf-title"><?php echo esc_html( $cpf_post_title ); ?></span>
									<span class="cpf-meta">
										<span class="cpf-date"><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo esc_html( is_string( $cpf_item_date ) ? $cpf_item_date : '' ); ?></span>
									</span>
								</span>
							</button>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</section>

<div class="cpf-modal cpf-portfolio-modal" id="<?php echo esc_attr( $cpf_modal_id ); ?>" hidden aria-hidden="true">
	<button type="button" class="cpf-modal-overlay" data-cpf-modal-close="1" aria-label="<?php esc_attr_e( 'Close modal', 'custom-post-frontend-display' ); ?>"></button>
	<div class="cpf-modal-dialog cpf-portfolio-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $cpf_modal_title ); ?>" tabindex="-1">
		<button type="button" class="cpf-modal-close" data-cpf-modal-close="1" aria-label="<?php esc_attr_e( 'Close modal', 'custom-post-frontend-display' ); ?>">
			&times;
		</button>
		<h2 class="cpf-modal-title" id="<?php echo esc_attr( $cpf_modal_title ); ?>"></h2>
		<div class="cpf-portfolio-gallery cpf-gallery-popup">
			<div class="cpf-slider-main-wrap">
				<button type="button" class="swiper-button-prev cpf-slider-button cpf-slider-prev" aria-label="<?php esc_attr_e( 'Previous image', 'custom-post-frontend-display' ); ?>"></button>
				<div class="swiper cpf-slider-main">
					<div class="swiper-wrapper"></div>
					<div class="swiper-pagination"></div>
				</div>
				<button type="button" class="swiper-button-next cpf-slider-button cpf-slider-next" aria-label="<?php esc_attr_e( 'Next image', 'custom-post-frontend-display' ); ?>"></button>
			</div>
			<div class="swiper cpf-slider-thumbs">
				<div class="swiper-wrapper"></div>
			</div>
		</div>
	</div>
</div>
