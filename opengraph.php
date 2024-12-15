<?php
/**
 * Plugin Name: Open Graph
 * Plugin URI: https://wordpress.org/plugins/opengraph
 * Description: Adds Open Graph metadata to your pages
 * Author: Will Norris & Matthias Pfefferle
 * Author URI: https://github.com/pfefferle/wordpress-opengraph
 * Version: 2.0.1
 * License: Apache License, Version 2.0
 * License URI: http://www.apache.org/licenses/LICENSE-2.0.html
 * Text Domain: opengraph
 *
 * @package opengraph
 */

// If you have the opengraph plugin running alongside jetpack, we assume you'd
// rather use our opengraph support, so disable jetpack's opengraph functionality.
add_filter( 'jetpack_enable_opengraph', '__return_false' );
add_filter( 'jetpack_enable_open_graph', '__return_false' );


// Disable strict mode by default.
defined( 'OPENGRAPH_STRICT_MODE' ) || define( 'OPENGRAPH_STRICT_MODE', false );


/**
 * Add Open Graph XML prefix to <html> element.
 *
 * @uses apply_filters calls 'opengraph_prefixes' filter on RDFa prefix array
 *
 * @param string $output The current list of prefixes.
 *
 * @return string The updated list of prefixes.
 */
function opengraph_add_prefix( $output ) {
	$prefixes = array(
		'og' => 'http://ogp.me/ns#',
	);
	$prefixes = apply_filters( 'opengraph_prefixes', $prefixes );

	$prefix_str = '';
	foreach ( $prefixes as $k => $v ) {
		$prefix_str .= $k . ': ' . $v . ' ';
	}
	$prefix_str = trim( $prefix_str );

	if ( preg_match( '/(prefix\s*=\s*[\"|\'])/i', $output ) ) {
		$output = preg_replace( '/(prefix\s*=\s*[\"|\'])/i', '${1}' . $prefix_str, $output );
	} else {
		$output .= ' prefix="' . esc_attr( $prefix_str ) . '"';
	}

	return $output;
}
add_filter( 'language_attributes', 'opengraph_add_prefix' );


/**
 * Add additional prefix namespaces that are supported by the opengraph plugin.
 *
 * @param array $prefixes The current list of prefixes.
 *
 * @return array The updated list of prefixes.
 */
function opengraph_additional_prefixes( $prefixes ) {
	if ( is_author() ) {
		$prefixes['profile'] = 'http://ogp.me/ns/profile#';
	}
	if ( is_singular() ) {
		$prefixes['article'] = 'http://ogp.me/ns/article#';
	}

	return $prefixes;
}


/**
 * Get the Open Graph metadata for the current page.
 *
 * @uses apply_filters() Calls 'opengraph_{$name}' for each property name
 * @uses apply_filters() Calls 'twitter_{$name}' for each property name
 * @uses apply_filters() Calls 'opengraph_metadata' before returning metadata array
 */
function opengraph_metadata() {
	$metadata = array();

	// Default properties defined at http://ogp.me/.
	$properties = array(
		// Required properties.
		'title'       => '',
		'type'        => '',
		'image'       => array(),
		'url'         => '',

		// Optional properties.
		'audio'       => array(),
		'description' => '',
		'determiner'  => '',
		'locale'      => '',
		'site_name'   => '',
		'video'       => array(),
	);

	foreach ( $properties as $property => $default ) {
		$filter = 'opengraph_' . $property;

		/**
		 * Filter the Open Graph metadata.
		 *
		 * @param array $default The default value.
		 */
		$metadata[ "og:$property" ] = apply_filters( $filter, $default );
	}

	$twitter_properties = array(
		'card'    => '',
		'creator' => '',
	);

	foreach ( $twitter_properties as $property => $default ) {
		$filter = 'twitter_' . $property;

		/**
		 * Filter the Twitter Card metadata.
		 *
		 * @param array $default The default value.
		 */
		$metadata[ "twitter:$property" ] = apply_filters( $filter, $default );
	}

	$fediverse_properties = array(
		'creator' => array(),
	);

	foreach ( $fediverse_properties as $property => $default ) {
		$filter = 'fediverse_' . $property;

		/**
		 * Filter the Fediverse metadata.
		 *
		 * @param array $default The default value.
		 */
		$metadata[ "fediverse:$property" ] = apply_filters( $filter, $default );
	}

	/**
	 * Filter the Open Graph metadata.
	 *
	 * @param array $metadata The metadata array.
	 */
	return apply_filters( 'opengraph_metadata', $metadata );
}


/**
 * Register filters for default Open Graph metadata.
 */
function opengraph_default_metadata() {
	// Core metadata attributes.
	add_filter( 'opengraph_title', 'opengraph_default_title', 5 );
	add_filter( 'opengraph_type', 'opengraph_default_type', 5 );
	add_filter( 'opengraph_url', 'opengraph_default_url', 5 );

	// Image metadata attributes with fallbacks.
	add_filter( 'opengraph_image', 'opengraph_default_image', 5 );
	add_filter( 'opengraph_image', 'opengraph_block_image', 15 );
	add_filter( 'opengraph_image', 'opengraph_parsed_image', 25 );
	add_filter( 'opengraph_image', 'opengraph_attached_image', 25 );
	add_filter( 'opengraph_image', 'opengraph_fallback_image', 35 );
	add_filter( 'opengraph_image', 'opengraph_ensure_max_image', 999 );

	add_filter( 'opengraph_description', 'opengraph_default_description', 5 );
	add_filter( 'opengraph_locale', 'opengraph_default_locale', 5 );
	add_filter( 'opengraph_site_name', 'opengraph_default_sitename', 5 );
	add_filter( 'opengraph_audio', 'opengraph_default_audio', 5 );
	add_filter( 'opengraph_video', 'opengraph_default_video', 5 );

	// Additional prefixes.
	add_filter( 'opengraph_prefixes', 'opengraph_additional_prefixes' );

	// Additional profile metadata.
	add_filter( 'opengraph_metadata', 'opengraph_profile_metadata' );

	// Additional article metadata.
	add_filter( 'opengraph_metadata', 'opengraph_article_metadata' );

	// twitter card metadata.
	add_filter( 'twitter_card', 'twitter_default_card', 5 );
	add_filter( 'twitter_creator', 'twitter_default_creator', 5 );

	// fediverse creator metadata.
	add_filter( 'fediverse_creator', 'fediverse_default_creator', 5 );
}
add_action( 'wp', 'opengraph_default_metadata' );


/**
 * Default title property, using the page title.
 *
 * @param string $title The current title.
 *
 * @return string The title.
 */
function opengraph_default_title( $title ) {
	if ( $title ) {
		return $title;
	}

	// Set default title, because twitter is requiring one.
	$title = __( 'Untitled', 'opengraph' );

	if ( is_home() || is_front_page() ) {
		$title = get_bloginfo( 'name' );
	} elseif ( is_singular() ) {
		$title = get_the_title( get_queried_object_id() );
		// Fall back to description.
		if ( empty( $title ) ) {
			$title = opengraph_default_description( null, 5 );
		}
	} elseif ( is_author() ) {
		$author = get_queried_object();
		$title  = $author->display_name;
	} elseif ( is_category() && single_cat_title( '', false ) ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() && single_tag_title( '', false ) ) {
		$title = single_tag_title( '', false );
	} elseif ( is_archive() && get_post_format() ) {
		$title = get_post_format_string( get_post_format() );
	} elseif (
		is_archive() &&
		function_exists( 'get_the_archive_title' ) &&
		get_the_archive_title()
	) { // New in version 4.1 to get all other archive titles.
		$title = get_the_archive_title();
	}

	return wp_strip_all_tags( $title );
}


/**
 * Default type property.
 *
 * @param string $type The current type.
 *
 * @return string The type.
 */
function opengraph_default_type( $type = '' ) {
	if ( empty( $type ) ) {
		if ( is_singular( array( 'post', 'page' ) ) ) {
			$type = 'article';
		} elseif ( is_author() ) {
			$type = 'profile';
		} else {
			$type = 'website';
		}
	}

	return $type;
}


/**
 * Default image property, using the post-thumbnail and any attached images.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_default_image( $image = array() ) {
	// Show avatar on profile pages.
	if ( is_author() ) {
		return array( get_avatar_url( get_the_author_meta( 'ID' ), array( 'size' => 512 ) ) );
	}

	if ( count( $image ) >= opengraph_max_images() ) {
		return $image;
	}

	if ( is_attachment() && wp_attachment_is_image() ) {
		$id      = get_queried_object_id();
		$image[] = current( wp_get_attachment_image_src( $id, 'large' ) ?: array() ); // phpcs:ignore
	} elseif ( is_singular() && ! is_attachment() ) {
		$id = get_queried_object_id();

		// List post thumbnail first if this post has one.
		if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $id ) ) {
			$thumbnail_id = get_post_thumbnail_id( $id );
			$image[]      = current( wp_get_attachment_image_src( $thumbnail_id, 'large' ) ?: array() ); // phpcs:ignore
		}
	}

	return array_unique( $image );
}


/**
 * Block image property, using the first image in the post content.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_block_image( $image = array() ) {
	if (
		! opengraph_site_supports_blocks() ||
		count( $image ) >= opengraph_max_images() ||
		! is_singular() ||
		is_attachment()
	) {
		return $image;
	}

	// Get the first image in the post content.
	$blocks = parse_blocks( get_the_content( null, false ) );
	foreach ( $blocks as $block ) {
		if ( count( $image ) >= opengraph_max_images() ) {
			break;
		}

		if (
			'core/image' === $block['blockName'] ||
			'core/cover' === $block['blockName']
		) {
			if ( ! isset( $block['attrs']['id'] ) ) {
				continue;
			}

			$id      = $block['attrs']['id'];
			$image[] = current( wp_get_attachment_image_src( $id, 'large' ) ?: array() ); // phpcs:ignore
		}
	}

	return array_unique( $image );
}


/**
 * Parse images in the HTML content.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_parsed_image( $image = array() ) {
	// If someone calls that function directly, bail.
	if (
		! \class_exists( 'WP_HTML_Tag_Processor' ) ||
		! opengraph_site_supports_blocks() ||
		count( $image ) >= opengraph_max_images() ||
		! is_singular() ||
		is_attachment()
	) {
		return $image;
	}

	$post_id = get_queried_object_id();
	$base    = wp_upload_dir()['baseurl'];
	$content = get_post_field( 'post_content', $post_id );
	$tags    = new WP_HTML_Tag_Processor( $content );

	// This linter warning is a false positive - we have to re-count each time here as we modify $images.
	// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found
	while ( $tags->next_tag( 'img' ) && ( count( $image ) <= opengraph_max_images() ) ) {
		$src = $tags->get_attribute( 'src' );

		/*
		 * If the img source is in our uploads dir, get the
		 * associated ID. Note: if there's a -500x500
		 * type suffix, we remove it, but we try the original
		 * first in case the original image is actually called
		 * that. Likewise, we try adding the -scaled suffix for
		 * the case that this is a small version of an image
		 * that was big enough to get scaled down on upload:
		 * https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
		 */
		if ( null === $src || ! str_starts_with( $src, $base ) ) {
			continue;
		}

		$img_id = attachment_url_to_postid( $src );

		if ( 0 === $img_id ) {
			$count  = 0;
			$src    = strtok( $src, '?' );
			$img_id = attachment_url_to_postid( $src );
		}

		if ( 0 === $img_id ) {
			$count = 0;
			$src   = preg_replace( '/-(?:\d+x\d+)(\.[a-zA-Z]+)$/', '$1', $src, 1, $count );
			if ( $count > 0 ) {
				$img_id = attachment_url_to_postid( $src );
			}
		}

		if ( 0 === $img_id ) {
			$src    = preg_replace( '/(\.[a-zA-Z]+)$/', '-scaled$1', $src );
			$img_id = attachment_url_to_postid( $src );
		}

		if ( 0 !== $img_id ) {
			$image[] = current( wp_get_attachment_image_src( $img_id, 'large' ) ?: array() ); // phpcs:ignore
		}
	}

	return array_unique( $image );
}


/**
 * Attached images.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_attached_image( $image = array() ) {
	$max_images = opengraph_max_images();

	if (
		count( $image ) >= $max_images ||
		! is_singular() || is_attachment()
	) {
		return $image;
	}

	$id = get_queried_object_id();

	$query = new WP_Query(
		array(
			'post_parent'    => $id,
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'order'          => 'ASC',
			'orderby'        => 'menu_order ID',
			'fields'         => 'ids',
			'posts_per_page' => $max_images,
		)
	);

	$image_ids = $query->get_posts();

	// Get URLs for each image.
	foreach ( $image_ids as $id ) {
		if ( count( $image ) >= opengraph_max_images() ) {
			break;
		}

		$thumbnail = wp_get_attachment_image_src( $id, 'large' );
		if ( $thumbnail ) {
			$image[] = $thumbnail[0];
		}
	}

	return array_unique( $image );
}

/**
 * Fallback image property, using the site icon, custom logo, or header images.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_fallback_image( $image = array() ) {
	if ( $image ) {
		return $image;
	}

	$max_images = opengraph_max_images();

	// Try site icon.
	if ( function_exists( 'get_site_icon_url' ) && has_site_icon() ) {
		$image[] = get_site_icon_url( 512 );
	}

	// Try custom logo second.
	if ( empty( $image ) ) {
		$custom_logo = get_theme_mod( 'custom_logo' );
		$image[]     = wp_get_attachment_image_src( $custom_logo, 'large' );
	}

	// Try header images.
	if ( empty( $image ) && function_exists( 'get_uploaded_header_images' ) ) {
		if ( is_random_header_image() ) {
			foreach ( get_uploaded_header_images() as $header_image ) {
				$image[] = $header_image['url'];
				if ( count( $image ) >= $max_images ) {
					break;
				}
			}
		} elseif ( get_header_image() ) {
			$image[] = get_header_image();
		}
	}

	return array_unique( $image );
}

/**
 * Ensure the image count does not exceed the maximum.
 *
 * @param array $image The current list of images.
 *
 * @return array The list of images.
 */
function opengraph_ensure_max_image( $image = array() ) {
	return array_slice( $image, 0, opengraph_max_images() );
}

/**
 * Default audio property, using get_attached_media.
 *
 * @param array $audio The current list of audio files.
 *
 * @return array The list of audio files.
 */
function opengraph_default_audio( $audio = array() ) {
	$id          = get_queried_object_id();
	$attachments = get_attached_media( 'audio', $id );

	if ( empty( $attachments ) ) {
		return $audio;
	}

	foreach ( $attachments as $attachment ) {
		$audio[] = wp_get_attachment_url( $attachment->ID );
	}

	return $audio;
}


/**
 * Default video property, using get_attached_media.
 *
 * @param array $video The current list of video files.
 *
 * @return array The list of video files.
 */
function opengraph_default_video( $video = array() ) {
	$id          = get_queried_object_id();
	$attachments = get_attached_media( 'video', $id );

	if ( empty( $attachments ) ) {
		return $video;
	}

	foreach ( $attachments as $attachment ) {
		$video[] = wp_get_attachment_url( $attachment->ID );
	}

	return $video;
}


/**
 * Default url property, using the permalink for the page.
 *
 * @param string $url The current URL.
 *
 * @return string The URL.
 */
function opengraph_default_url( $url = '' ) {
	if ( empty( $url ) ) {
		if ( is_singular() ) {
			$url = get_permalink();
		} elseif ( is_author() ) {
			$url = get_author_posts_url( get_queried_object_id() );
		}
	}

	return esc_url( $url );
}


/**
 * Default site_name property, using the bloginfo name.
 *
 * @param string $name The current site name.
 *
 * @return string The site name.
 */
function opengraph_default_sitename( $name = '' ) {
	if ( empty( $name ) ) {
		$name = get_bloginfo( 'name' );
	}

	return wp_strip_all_tags( $name );
}


/**
 * Default description property, using the excerpt or content for posts, or the
 * bloginfo description.
 *
 * @param string $description The current description.
 * @param int    $length      The maximum length of the description.
 *
 * @return string The description.
 */
function opengraph_default_description( $description = '', $length = 55 ) {
	if ( $description ) {
		return $description;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( post_password_required( $post ) ) {
			$description = __( 'This content is password protected.', 'opengraph' );
		} elseif ( ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		} else {
			$description = $post->post_content;
		}
	} elseif ( is_author() ) {
		$id          = get_queried_object_id();
		$description = get_user_meta( $id, 'description', true );
	} elseif ( is_category() && category_description() ) {
		$description = category_description();
	} elseif ( is_tag() && tag_description() ) {
		$description = tag_description();
	} elseif (
		is_archive() &&
		function_exists( 'get_the_archive_description' ) &&
		get_the_archive_description()
	) { // New in version 4.1 to get all other archive descriptions.
		$description = get_the_archive_description();
	} else {
		$description = get_bloginfo( 'description' );
	}

	// strip description to first 55 words.
	$description = wp_strip_all_tags( strip_shortcodes( $description ) );
	$description = opengraph_trim_text( $description, $length );

	return wp_strip_all_tags( $description );
}


/**
 * Default locale property, using the WordPress locale.
 *
 * @param string $locale The current locale.
 *
 * @return string The locale.
 */
function opengraph_default_locale( $locale = '' ) {
	if ( empty( $locale ) ) {
		$locale = get_locale();
	}

	return $locale;
}


/**
 * Default twitter-card type.
 *
 * @param string $card The current card type.
 *
 * @return string The card type.
 */
function twitter_default_card( $card = '' ) {
	if ( $card ) {
		return $card;
	}

	$card   = 'summary';
	$images = apply_filters( 'opengraph_image', array() );

	// Show large image on...
	if ( is_singular() ) {
		if (
			// Gallery and image posts.
			in_array( get_post_format(), array( 'image', 'gallery' ), true ) ||
			// Posts with more than one image.
			( is_array( $images ) && count( $images ) > 1 ) ||
			// Posts with a post-thumbnail.
			( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() )
		) {
			$card = 'summary_large_image';
		}
	}

	return $card;
}


/**
 * Default twitter-card creator.
 *
 * @see https://developer.twitter.com/en/docs/twitter-for-websites/cards/guides/getting-started
 *
 * @param string $creator The current creator.
 *
 * @return string The creator.
 */
function twitter_default_creator( $creator = '' ) {
	if ( $creator || ! is_singular() ) {
		return $creator;
	}

	$post    = get_queried_object();
	$author  = $post->post_author;
	$twitter = get_the_author_meta( 'twitter', $author );

	if ( ! $twitter ) {
		return $creator;
	}

	// Check if twitter-account matches "http://twitter.com/username".
	if ( preg_match( '/^http:\/\/twitter\.com\/(#!\/)?(\w+)/i', $twitter, $matches ) ) {
		$creator = '@' . $matches[2];
	} elseif ( preg_match( '/^@?(\w+)$/i', $twitter, $matches ) ) { // Check if twitter-account matches "(@)username".
		$creator = '@' . $matches[1];
	}

	return $creator;
}


/**
 * Default fediverse creator.
 *
 * @see https://github.com/mastodon/mastodon/pull/30398
 *
 * @param string $creator The current creator.
 *
 * @return string The creator.
 */
function fediverse_default_creator( $creator = '' ) {
	if ( ! is_singular() ) {
		return $creator;
	}

	$post      = get_queried_object();
	$author    = $post->post_author;
	$webfinger = get_the_author_meta( 'fediverse', $author );

	if ( ! $webfinger ) {
		return $creator;
	}

	$webfinger = ltrim( $webfinger, '@' );
	$webfinger = str_replace( 'acct:', '', $webfinger );

	return $webfinger;
}


/**
 * Output Open Graph <meta> tags in the page header.
 */
function opengraph_meta_tags() {
	$metadata = opengraph_metadata();
	foreach ( $metadata as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) {
			continue;
		}
		$value = (array) $value;

		foreach ( $value as $v ) {
			// Skip empty values.
			if ( empty( $v ) ) {
				continue;
			}

			// Check if "strict mode" is enabled.
			if ( OPENGRAPH_STRICT_MODE === true ) {
				if ( // Use "name" attribute for Twitter Cards.
					str_starts_with( $key, 'twitter:' ) ||
					str_starts_with( $key, 'fediverse:' )
				) {
					printf(
						'<meta name="%1$s" content="%2$s" />' . PHP_EOL,
						esc_attr( $key ),
						esc_attr( $v )
					);
				} else { // Use "property" attribute for Open Graph.
					printf(
						'<meta property="%1$s" content="%2$s" />' . PHP_EOL,
						esc_attr( $key ),
						esc_attr( $v )
					);
				}
			} else {
				// Use the "property" and "name" attributes.
				printf(
					'<meta property="%1$s" name="%1$s" content="%2$s" />' . PHP_EOL,
					esc_attr( $key ),
					esc_attr( $v )
				);
			}
		}
	}
}
add_action( 'wp_head', 'opengraph_meta_tags' );


/**
 * Include profile metadata for author pages.
 *
 * @link http://ogp.me/#type_profile
 *
 * @param array $metadata The current metadata.
 *
 * @return array The updated metadata.
 */
function opengraph_profile_metadata( $metadata ) {
	if ( is_author() ) {
		$id = get_queried_object_id();

		$metadata['profile:first_name'] = get_the_author_meta( 'first_name', $id );
		$metadata['profile:last_name']  = get_the_author_meta( 'last_name', $id );
		$metadata['profile:username']   = get_the_author_meta( 'nicename', $id );
	}

	return $metadata;
}


/**
 * Include article metadata for posts and pages.
 *
 * @link http://ogp.me/#type_article
 *
 * @param array $metadata The current metadata.
 *
 * @return array The updated metadata.
 */
function opengraph_article_metadata( $metadata ) {
	if ( ! is_singular() ) {
		return $metadata;
	}

	$post   = get_queried_object();
	$author = $post->post_author;

	// Check if page/post has tags.
	$tags = wp_get_object_terms( $post->ID, 'post_tag' );
	if ( $tags && is_array( $tags ) ) {
		foreach ( $tags as $tag ) {
			$metadata['article:tag'][] = $tag->name;
		}
	}

	// Check if page/post has categories.
	$categories = wp_get_object_terms( $post->ID, 'category' );
	if ( $categories && is_array( $categories ) ) {
		$metadata['article:section'][] = current( $categories )->name;
	}

	$metadata['article:published_time'] = get_the_time( 'c', $post->ID );
	$metadata['article:modified_time']  = get_the_modified_time( 'c', $post->ID );
	$metadata['article:author'][]       = get_author_posts_url( $author );

	$facebook = get_the_author_meta( 'facebook', $author );

	if ( ! empty( $facebook ) ) {
		$metadata['article:author'][] = $facebook;
	}

	return $metadata;
}


/**
 * Add "twitter" as a contact method
 *
 * @param array $user_contactmethods The current list of contact methods.
 *
 * @return array The updated list of contact methods.
 */
function opengraph_user_contactmethods( $user_contactmethods = array() ) {
	$user_contactmethods['twitter']   = __( 'Twitter', 'opengraph' );
	$user_contactmethods['facebook']  = __( 'Facebook (Profile URL)', 'opengraph' );
	$user_contactmethods['fediverse'] = __( 'Fediverse (username@host.tld)', 'opengraph' );

	return $user_contactmethods;
}
add_filter( 'user_contactmethods', 'opengraph_user_contactmethods', 1 );


/**
 * Add 512x512 icon size
 *
 * @param array $sizes Sizes available for the site icon.
 *
 * @return array updated list of icons.
 */
function opengraph_site_icon_image_sizes( $sizes ) {
	$sizes[] = 512;

	return array_unique( $sizes );
}
add_filter( 'site_icon_image_sizes', 'opengraph_site_icon_image_sizes' );


/**
 * Helper function to trim text using the same default values for length and
 * 'more' text as wp_trim_excerpt.
 *
 * @param string $text The text to trim.
 * @param int    $length The maximum number of words to include.
 *
 * @return string The trimmed text.
 */
function opengraph_trim_text( $text, $length = 55 ) {
	$excerpt_length = apply_filters( 'excerpt_length', $length );
	$excerpt_more   = apply_filters( 'excerpt_more', ' [...]' );

	return wp_trim_words( $text, $excerpt_length, $excerpt_more );
}

/**
 * Get the maximum number of images to include in Open Graph metadata.
 *
 * @return int The maximum number of images to include.
 */
function opengraph_max_images() {
	/**
	 * Filter the maximum number of images to include in Open Graph metadata.
	 *
	 * As of July 2014, Facebook seems to only let you select from the first 3 images.
	 *
	 * @param int $max_images The maximum number of images to include.
	 */
	$max_images = apply_filters( 'opengraph_max_images', 3 );

	// Max images can't be negative or zero.
	if ( $max_images <= 0 ) {
		$max_images = 1;
	}

	return $max_images;
}

/**
 * Check if a site supports the block editor.
 *
 * @return boolean True if the site supports the block editor, false otherwise.
 */
function opengraph_site_supports_blocks() {
	$return = true;

	if ( version_compare( get_bloginfo( 'version' ), '5.9', '<' ) ) {
		$return = false;
	} elseif ( function_exists( 'classicpress_version' ) ) {
		$return = false;
	} elseif (
		! function_exists( 'register_block_type_from_metadata' ) ||
		! function_exists( 'do_blocks' )
	) {
		$return = false;
	}

	/**
	 * Allow plugins to disable block editor support,
	 * thus disabling blocks registered by the OpenGraph plugin.
	 *
	 * @param boolean $supports_blocks True if the site supports the block editor, false otherwise.
	 */
	return apply_filters( 'opengraph_site_supports_blocks', $return );
}


if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * `str_starts_with` function for PHP < 8.0.
	 *
	 * @see https://www.php.net/manual/en/function.str-starts-with
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The string to search for.
	 *
	 * @return bool True if the string starts with the needle, false otherwise.
	 */
	function str_starts_with( $haystack, $needle ) {
		return 0 === strncmp( $haystack, $needle, \strlen( $needle ) );
	}
}
