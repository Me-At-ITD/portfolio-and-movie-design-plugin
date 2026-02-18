<?php
/**
 * Plugin Name:       Custom Post Frontend Display
 * Description:       Adds shortcodes to display custom frontends for the post types portfolio_item and movie.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            CPF
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-post-frontend-display
 *
 * @package CustomPostFrontendDisplay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CPF_PLUGIN_VERSION' ) ) {
	define( 'CPF_PLUGIN_VERSION', '1.0.0' );
}

if ( ! defined( 'CPF_PLUGIN_DIR' ) ) {
	define( 'CPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CPF_PLUGIN_URL' ) ) {
	define( 'CPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load plugin translations.
 *
 * @return void
 */
function cpf_load_textdomain() {
	load_plugin_textdomain( 'custom-post-frontend-display', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'cpf_load_textdomain' );

/**
 * Set shortcode flags by inspecting a content string.
 *
 * @param string $content Post content.
 * @return void
 */
function cpf_set_shortcode_flags_from_content( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return;
	}

	if (
		empty( $GLOBALS['cpf_has_portfolio_shortcode'] ) &&
		false !== strpos( $content, '[portfolio_display' ) &&
		has_shortcode( $content, 'portfolio_display' )
	) {
		$GLOBALS['cpf_has_portfolio_shortcode'] = true;
	}

	if (
		empty( $GLOBALS['cpf_has_movie_shortcode'] ) &&
		false !== strpos( $content, '[movie_display' ) &&
		has_shortcode( $content, 'movie_display' )
	) {
		$GLOBALS['cpf_has_movie_shortcode'] = true;
	}
}

/**
 * Detect shortcodes in the main query to determine if assets are needed.
 *
 * @param array    $posts Retrieved posts.
 * @param WP_Query $query Query object.
 * @return array
 */
function cpf_detect_shortcodes_in_posts( $posts, $query ) {
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || empty( $posts ) ) {
		return $posts;
	}

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		cpf_set_shortcode_flags_from_content( $post->post_content );

		if ( ! empty( $GLOBALS['cpf_has_portfolio_shortcode'] ) && ! empty( $GLOBALS['cpf_has_movie_shortcode'] ) ) {
			break;
		}
	}

	return $posts;
}
add_filter( 'the_posts', 'cpf_detect_shortcodes_in_posts', 10, 2 );

/**
 * Check whether a shortcode is present in the request context.
 *
 * @param string $shortcode_tag Shortcode name.
 * @return bool
 */
function cpf_has_shortcode_on_request( $shortcode_tag ) {
	$global_flag_key = '';

	if ( 'portfolio_display' === $shortcode_tag ) {
		$global_flag_key = 'cpf_has_portfolio_shortcode';
	} elseif ( 'movie_display' === $shortcode_tag ) {
		$global_flag_key = 'cpf_has_movie_shortcode';
	}

	if ( '' === $global_flag_key ) {
		return false;
	}

	if ( ! empty( $GLOBALS[ $global_flag_key ] ) ) {
		return true;
	}

	global $post;
	if ( $post instanceof WP_Post ) {
		cpf_set_shortcode_flags_from_content( $post->post_content );
	}

	if ( ! empty( $GLOBALS[ $global_flag_key ] ) ) {
		return true;
	}

	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Post ) {
		cpf_set_shortcode_flags_from_content( $queried_object->post_content );
	}

	return ! empty( $GLOBALS[ $global_flag_key ] );
}

/**
 * Enqueue frontend assets only when target shortcode is detected.
 *
 * @return void
 */
function cpf_enqueue_frontend_assets() {
	if ( is_admin() ) {
		return;
	}

	$has_portfolio_shortcode = cpf_has_shortcode_on_request( 'portfolio_display' );
	$has_movie_shortcode     = cpf_has_shortcode_on_request( 'movie_display' );

	if ( ! $has_portfolio_shortcode && ! $has_movie_shortcode ) {
		return;
	}

	$script_dependencies = array();

	if ( $has_portfolio_shortcode ) {
		wp_register_style(
			'cpf_swiper_style',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
			array(),
			'11.2.6'
		);

		wp_register_script(
			'cpf_swiper_script',
			'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
			array(),
			'11.2.6',
			true
		);

		wp_enqueue_style( 'cpf_swiper_style' );
		$script_dependencies[] = 'cpf_swiper_script';
	}

	wp_register_style(
		'cpf_frontend_style',
		CPF_PLUGIN_URL . 'assets/css/style.css',
		array(),
		CPF_PLUGIN_VERSION
	);

	wp_register_script(
		'cpf_frontend_script',
		CPF_PLUGIN_URL . 'assets/js/main.js',
		$script_dependencies,
		CPF_PLUGIN_VERSION,
		true
	);

	wp_localize_script(
		'cpf_frontend_script',
		'cpfFrontendData',
		array(
			'invalidVideoMessage' => esc_html__( 'The selected video URL is invalid or unavailable.', 'custom-post-frontend-display' ),
			'noGalleryMessage'    => esc_html__( 'No gallery images are available for this item.', 'custom-post-frontend-display' ),
		)
	);

	wp_enqueue_style( 'cpf_frontend_style' );
	wp_enqueue_script( 'cpf_frontend_script' );
}
add_action( 'wp_enqueue_scripts', 'cpf_enqueue_frontend_assets' );

/**
 * Get all published posts for a post type.
 *
 * @param string $post_type Post type.
 * @return array
 */
function cpf_get_posts_by_type( $post_type ) {
	$posts = get_posts(
		array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	return is_array( $posts ) ? $posts : array();
}

/**
 * Get taxonomy terms attached to a set of posts.
 *
 * @param string $taxonomy Taxonomy.
 * @param array  $post_ids Post IDs.
 * @return array
 */
function cpf_get_terms_for_posts( $taxonomy, $post_ids ) {
	$sanitized_post_ids = array_values(
		array_filter(
			array_map( 'absint', $post_ids )
		)
	);

	if ( empty( $sanitized_post_ids ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'object_ids' => $sanitized_post_ids,
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	return $terms;
}

/**
 * Build a post ID => taxonomy term ID map.
 *
 * @param array  $post_ids Post IDs.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function cpf_get_post_term_map( $post_ids, $taxonomy ) {
	$map = array();

	$sanitized_post_ids = array_values(
		array_filter(
			array_map( 'absint', $post_ids )
		)
	);

	foreach ( $sanitized_post_ids as $post_id ) {
		$map[ $post_id ] = array();
	}

	if ( empty( $sanitized_post_ids ) ) {
		return $map;
	}

	$post_terms = wp_get_object_terms(
		$sanitized_post_ids,
		$taxonomy,
		array(
			'fields' => 'all_with_object_id',
		)
	);

	if ( is_wp_error( $post_terms ) || ! is_array( $post_terms ) ) {
		return $map;
	}

	foreach ( $post_terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$post_id = isset( $term->object_id ) ? absint( $term->object_id ) : 0;
		$term_id = absint( $term->term_id );

		if ( 0 === $post_id || 0 === $term_id ) {
			continue;
		}

		if ( ! isset( $map[ $post_id ] ) ) {
			$map[ $post_id ] = array();
		}

		$map[ $post_id ][] = $term_id;
	}

	return $map;
}

/**
 * Read a custom field, with optional ACF integration.
 *
 * @param int    $post_id    Post ID.
 * @param string $field_name Field name.
 * @return mixed
 */
function cpf_get_custom_field_value( $post_id, $field_name ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $field_name, $post_id );
	}

	return get_post_meta( $post_id, $field_name, true );
}

/**
 * Get alt text for an attachment.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $fallback      Fallback alt.
 * @return string
 */
function cpf_get_attachment_alt( $attachment_id, $fallback = '' ) {
	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$alt = is_string( $alt ) ? trim( $alt ) : '';

	if ( '' === $alt ) {
		$alt = is_string( $fallback ) ? trim( $fallback ) : '';
	}

	return $alt;
}

/**
 * Resolve an image field value into a normalized URL/alt array.
 *
 * @param mixed  $field_value Field value.
 * @param string $size        Image size.
 * @return array
 */
function cpf_resolve_image_data( $field_value, $size = 'large' ) {
	$image_data    = array(
		'url' => '',
		'alt' => '',
	);
	$attachment_id = 0;

	if ( empty( $field_value ) ) {
		return $image_data;
	}

	if ( is_array( $field_value ) ) {
		if ( ! empty( $field_value['ID'] ) ) {
			$attachment_id = absint( $field_value['ID'] );
		} elseif ( ! empty( $field_value['id'] ) ) {
			$attachment_id = absint( $field_value['id'] );
		}

		if ( ! empty( $field_value['alt'] ) && is_string( $field_value['alt'] ) ) {
			$image_data['alt'] = sanitize_text_field( $field_value['alt'] );
		}

		if ( 0 === $attachment_id && ! empty( $field_value['url'] ) && is_string( $field_value['url'] ) ) {
			$image_data['url'] = esc_url_raw( $field_value['url'] );
		}
	} elseif ( $field_value instanceof WP_Post ) {
		$attachment_id = absint( $field_value->ID );
	} elseif ( is_numeric( $field_value ) ) {
		$attachment_id = absint( $field_value );
	} elseif ( is_string( $field_value ) ) {
		$image_data['url'] = esc_url_raw( $field_value );
	}

	if ( $attachment_id > 0 ) {
		$image_url = wp_get_attachment_image_url( $attachment_id, $size );
		if ( $image_url ) {
			$image_data['url'] = $image_url;
		}

		if ( '' === $image_data['alt'] ) {
			$image_data['alt'] = cpf_get_attachment_alt( $attachment_id );
		}
	}

	return $image_data;
}

/**
 * Get featured image URL and alt data.
 *
 * @param int    $post_id Post ID.
 * @param string $size    Image size.
 * @return array
 */
function cpf_get_post_thumbnail_data( $post_id, $size = 'large' ) {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( ! $thumbnail_id ) {
		return array(
			'url' => '',
			'alt' => '',
		);
	}

	$image_url = wp_get_attachment_image_url( $thumbnail_id, $size );
	$title     = get_the_title( $post_id );

	return array(
		'url' => $image_url ? $image_url : '',
		'alt' => cpf_get_attachment_alt( $thumbnail_id, is_string( $title ) ? $title : '' ),
	);
}

/**
 * Get the portfolio gallery images.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function cpf_get_portfolio_gallery_images( $post_id ) {
	$post_id  = absint( $post_id );
	$images   = array();
	$title    = get_the_title( $post_id );
	$fallback = is_string( $title ) ? $title : '';

	for ( $index = 1; $index <= 5; $index++ ) {
		if ( function_exists( 'get_field' ) ) {
			$image_field = get_field( 'img' . $index, $post_id );
		} else {
			$image_field = get_post_meta( $post_id, 'img' . $index, true );
		}

		if ( empty( $image_field ) ) {
			continue;
		}

		$image_url = '';
		$image_alt = $fallback;

		// Supports ACF Image Array and Image URL return formats.
		if ( is_array( $image_field ) && ! empty( $image_field['url'] ) && is_string( $image_field['url'] ) ) {
			$image_url = esc_url_raw( $image_field['url'] );
			if ( ! empty( $image_field['alt'] ) && is_string( $image_field['alt'] ) ) {
				$image_alt = sanitize_text_field( $image_field['alt'] );
			}
		} elseif ( is_string( $image_field ) ) {
			$image_url = esc_url_raw( $image_field );
		} elseif ( is_numeric( $image_field ) ) {
			$attachment_id = absint( $image_field );
			if ( $attachment_id > 0 ) {
				$resolved_url = wp_get_attachment_image_url( $attachment_id, 'large' );
				$image_url    = $resolved_url ? $resolved_url : '';
				$image_alt    = cpf_get_attachment_alt( $attachment_id, $fallback );
			}
		} elseif ( $image_field instanceof WP_Post ) {
			$attachment_id = absint( $image_field->ID );
			if ( $attachment_id > 0 ) {
				$resolved_url = wp_get_attachment_image_url( $attachment_id, 'large' );
				$image_url    = $resolved_url ? $resolved_url : '';
				$image_alt    = cpf_get_attachment_alt( $attachment_id, $fallback );
			}
		}

		if ( '' === $image_url ) {
			continue;
		}

		$images[] = array(
			'url' => $image_url,
			'alt' => $image_alt,
		);
	}

	return $images;
}

/**
 * Prepare portfolio card data for template rendering.
 *
 * @param WP_Post $post     Post object.
 * @param array   $term_ids Taxonomy term IDs.
 * @return array
 */
function cpf_prepare_portfolio_post_data( WP_Post $post, $term_ids = array() ) {
	$post_id        = absint( $post->ID );
	$title          = get_the_title( $post_id );
	$featured_image = cpf_get_post_thumbnail_data( $post_id, 'large' );
	$gallery_images = cpf_get_portfolio_gallery_images( $post_id );

	if ( '' === $featured_image['url'] && ! empty( $gallery_images[0] ) ) {
		$featured_image = $gallery_images[0];
	}

	return array(
		'id'                 => $post_id,
		'title'              => is_string( $title ) ? $title : '',
		'featured_image_url' => $featured_image['url'],
		'featured_image_alt' => $featured_image['alt'],
		'gallery'            => $gallery_images,
		'term_ids'           => array_values( array_unique( array_map( 'absint', $term_ids ) ) ),
	);
}

/**
 * Validate and convert a YouTube URL to an embeddable URL.
 *
 * @param string $youtube_link YouTube URL.
 * @return string
 */
function cpf_get_youtube_embed_url( $youtube_link ) {
	if ( ! is_string( $youtube_link ) ) {
		return '';
	}

	$youtube_link = trim( $youtube_link );
	if ( '' === $youtube_link ) {
		return '';
	}

	$sanitized_url = esc_url_raw( $youtube_link );
	if ( '' === $sanitized_url ) {
		return '';
	}

	$url_parts = wp_parse_url( $sanitized_url );
	if ( ! is_array( $url_parts ) ) {
		return '';
	}

	$host         = isset( $url_parts['host'] ) ? strtolower( (string) $url_parts['host'] ) : '';
	$path         = isset( $url_parts['path'] ) ? (string) $url_parts['path'] : '';
	$query_string = isset( $url_parts['query'] ) ? (string) $url_parts['query'] : '';
	$video_id     = '';

	if ( in_array( $host, array( 'youtu.be', 'www.youtu.be' ), true ) ) {
		$video_id = trim( $path, '/' );
	} elseif ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com' ), true ) ) {
		$query_args = array();
		parse_str( $query_string, $query_args );

		if ( ! empty( $query_args['v'] ) && is_string( $query_args['v'] ) ) {
			$video_id = $query_args['v'];
		} elseif ( preg_match( '#/embed/([A-Za-z0-9_-]{11})#', $path, $matches ) ) {
			$video_id = $matches[1];
		} elseif ( preg_match( '#/shorts/([A-Za-z0-9_-]{11})#', $path, $matches ) ) {
			$video_id = $matches[1];
		}
	}

	if ( ! preg_match( '/^[A-Za-z0-9_-]{11}$/', $video_id ) ) {
		return '';
	}

	return 'https://www.youtube-nocookie.com/embed/' . $video_id;
}

/**
 * Prepare movie card data for template rendering.
 *
 * @param WP_Post $post     Post object.
 * @param array   $term_ids Taxonomy term IDs.
 * @return array
 */
function cpf_prepare_movie_post_data( WP_Post $post, $term_ids = array() ) {
	$post_id        = absint( $post->ID );
	$title          = get_the_title( $post_id );
	$featured_image = cpf_get_post_thumbnail_data( $post_id, 'large' );
	$youtube_field  = cpf_get_custom_field_value( $post_id, 'youtube_link' );
	$youtube_url    = '';

	if ( is_string( $youtube_field ) ) {
		$youtube_url = cpf_get_youtube_embed_url( $youtube_field );
	} elseif ( is_array( $youtube_field ) && ! empty( $youtube_field['url'] ) && is_string( $youtube_field['url'] ) ) {
		$youtube_url = cpf_get_youtube_embed_url( $youtube_field['url'] );
	}

	return array(
		'id'                 => $post_id,
		'title'              => is_string( $title ) ? $title : '',
		'featured_image_url' => $featured_image['url'],
		'featured_image_alt' => $featured_image['alt'],
		'youtube_embed_url'  => $youtube_url,
		'term_ids'           => array_values( array_unique( array_map( 'absint', $term_ids ) ) ),
	);
}

/**
 * Generate a unique and safe HTML ID.
 *
 * @param string $prefix Prefix.
 * @return string
 */
function cpf_generate_instance_id( $prefix ) {
	$id = function_exists( 'wp_unique_id' ) ? wp_unique_id( $prefix ) : uniqid( $prefix, false );
	return sanitize_html_class( $id );
}

/**
 * Render a template file from the templates directory.
 *
 * @param string $template_name Template filename.
 * @param array  $template_vars Template variables.
 * @return string
 */
function cpf_render_template( $template_name, $template_vars = array() ) {
	$template_file = CPF_PLUGIN_DIR . 'templates/' . sanitize_file_name( $template_name );
	if ( ! file_exists( $template_file ) ) {
		return '';
	}

	if ( ! is_array( $template_vars ) ) {
		$template_vars = array();
	}

	ob_start();
	extract( $template_vars, EXTR_SKIP );
	include $template_file;
	return (string) ob_get_clean();
}

/**
 * Render [portfolio_display].
 *
 * @return string
 */
function cpf_shortcode_portfolio_display() {
	$portfolio_posts = cpf_get_posts_by_type( 'portfolio_item' );
	$post_ids        = wp_list_pluck( $portfolio_posts, 'ID' );
	$term_map        = cpf_get_post_term_map( $post_ids, 'portfolio' );
	$terms           = get_terms(
		array(
			'taxonomy'   => 'portfolio',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		$terms = array();
	}
	$all_posts       = array();

	foreach ( $portfolio_posts as $portfolio_post ) {
		if ( ! $portfolio_post instanceof WP_Post ) {
			continue;
		}

		$post_id = absint( $portfolio_post->ID );
		$all_posts[] = cpf_prepare_portfolio_post_data(
			$portfolio_post,
			isset( $term_map[ $post_id ] ) ? $term_map[ $post_id ] : array()
		);
	}

	$posts_by_term = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$term_id               = absint( $term->term_id );
		$posts_by_term[ $term_id ] = array_values(
			array_filter(
				$all_posts,
				static function ( $portfolio_post ) use ( $term_id ) {
					if ( ! isset( $portfolio_post['term_ids'] ) || ! is_array( $portfolio_post['term_ids'] ) ) {
						return false;
					}

					return in_array( $term_id, $portfolio_post['term_ids'], true );
				}
			)
		);
	}

	return cpf_render_template(
		'portfolio-template.php',
		array(
			'cpf_instance_id'  => cpf_generate_instance_id( 'cpf-portfolio-' ),
			'cpf_terms'        => $terms,
			'cpf_all_posts'    => $all_posts,
			'cpf_posts_by_term' => $posts_by_term,
		)
	);
}

/**
 * Render [movie_display].
 *
 * @return string
 */
function cpf_shortcode_movie_display() {
	$movie_posts = cpf_get_posts_by_type( 'movie' );
	$post_ids    = wp_list_pluck( $movie_posts, 'ID' );
	$term_map    = cpf_get_post_term_map( $post_ids, 'movie-category' );
	$terms       = cpf_get_terms_for_posts( 'movie-category', $post_ids );
	$all_posts   = array();

	foreach ( $movie_posts as $movie_post ) {
		if ( ! $movie_post instanceof WP_Post ) {
			continue;
		}

		$post_id = absint( $movie_post->ID );
		$all_posts[] = cpf_prepare_movie_post_data(
			$movie_post,
			isset( $term_map[ $post_id ] ) ? $term_map[ $post_id ] : array()
		);
	}

	$posts_by_term = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$term_id               = absint( $term->term_id );
		$posts_by_term[ $term_id ] = array_values(
			array_filter(
				$all_posts,
				static function ( $movie_post ) use ( $term_id ) {
					if ( ! isset( $movie_post['term_ids'] ) || ! is_array( $movie_post['term_ids'] ) ) {
						return false;
					}

					return in_array( $term_id, $movie_post['term_ids'], true );
				}
			)
		);
	}

	return cpf_render_template(
		'movie-template.php',
		array(
			'cpf_instance_id'  => cpf_generate_instance_id( 'cpf-movie-' ),
			'cpf_terms'        => $terms,
			'cpf_all_posts'    => $all_posts,
			'cpf_posts_by_term' => $posts_by_term,
		)
	);
}

/**
 * Register plugin shortcodes.
 *
 * @return void
 */
function cpf_register_shortcodes() {
	add_shortcode( 'portfolio_display', 'cpf_shortcode_portfolio_display' );
	add_shortcode( 'movie_display', 'cpf_shortcode_movie_display' );
}
add_action( 'init', 'cpf_register_shortcodes' );
