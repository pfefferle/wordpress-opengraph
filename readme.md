# Open Graph

- Contributors: willnorris, pfefferle
- Tags: open graph, opengraph, social, facebook, twitter
- Requires at least: 6.2
- Tested up to: 7.1
- Stable tag: 3.0.1
- Requires PHP: 7.4
- License: Apache License, Version 2.0
- License URI: https://www.apache.org/licenses/LICENSE-2.0.html


Adds Open Graph metadata to your posts and pages so that they look great when shared on social networks.

## Description

The [Open Graph protocol][] enables any web page to become a rich object in a social graph.  Most notably, this allows for these pages to be used with Facebook's [Share Button][] and [Graph API][] as well as within Twitter posts.

The Open Graph plugin inserts the Open Graph metadata into WordPress posts and pages, and provides a simple extension mechanism for other plugins and themes to override this data, or to provide additional Open Graph data.

This plugin does not directly add social plugins like the Facebook Share Button to your pages (though they're pretty simple to add).  It will however make your pages look great when shared using those kinds of tools.

[Open Graph Protocol]: https://ogp.me/
[Share Button]: https://developers.facebook.com/documentation/plugins/share-button
[Graph API]: https://developers.facebook.com/docs/graph-api/


## Frequently Asked Questions

### How do I configure the Open Graph plugin?

You don't; there's nothing to configure and there is no admin page.  By default, it will use whatever standard WordPress data it can to populate the Open Graph data.  There are very simple yet powerful filters you can use to modify or extend the metadata returned by the plugin, described below.

### How do I extend the Open Graph plugin?

There are two main ways to provide Open Graph metadata from your plugin or theme.  First, you can implement the filter for a specific property.  These filters are of the form `opengraph_{name}` where {name} is the unqualified Open Graph property name.  For example, if you have a plugin that defines a custom post type named "movie", you could override the Open Graph `type` property for those posts using a function like:

    function my_og_type( $type ) {
        if ( get_post_type() == "movie" ) {
            $type = "movie";
        }
        return $type;
    }
    add_filter( 'opengraph_type', 'my_og_type' );

This will work for all of the core Open Graph properties.  However, if you want to add a custom property, such as `fb:admins`, then you would need to hook into the `opengraph_metadata` filter.  This filter is passed an associative array, whose keys are the qualified Open Graph property names.  For example:

    function my_og_metadata( $metadata ) {
        $metadata['fb:admins'] = '12345,67890';
        return $metadata;
    }
    add_filter( 'opengraph_metadata', 'my_og_metadata' );

Note that you may need to define the RDFa prefix for your properties.  Do this using the `opengraph_prefixes` filter.

### How does the plugin choose the images?

The plugin uses the featured image first, then the images in the post content, then the images attached to the post. If there is no image at all, it uses the site icon, the custom logo or the header image.

By default the plugin adds up to 3 images. You can change that by adding the following line to your `wp-config.php`

    define( 'OPENGRAPH_MAX_IMAGES', 1 );

or with the `opengraph_max_images` filter. To add or remove image sources, use the `opengraph_image_sources` filter. Each source is a callable that gets the post ID and returns attachment IDs:

    function my_og_image_sources( $sources ) {
        // Only use the featured image.
        return array( 'opengraph_thumbnail_image_ids' );
    }
    add_filter( 'opengraph_image_sources', 'my_og_image_sources' );

### Does it work with Jetpack?

Yes. Jetpack also adds Open Graph metadata, so the plugin disables the Jetpack output to avoid duplicate tags. To get the Jetpack output back, deactivate this plugin.

### How to enable/disable "strict mode"

The plugin populates the meta 'name' attribute alongside the 'property' attribute by default. Because both, the `og:*` and `twitter:*` names, are actually registered at https://wiki.whatwg.org/wiki/MetaExtensions, this stays compliant with the HTML5 spec. If you want to use a more strict way anyways, you can enable the strict mode by adding the following line to your `wp-config.php`

    define( 'OPENGRAPH_STRICT_MODE', true );


## Changelog

Project maintained on github at [pfefferle/wordpress-opengraph](https://github.com/pfefferle/wordpress-opengraph).

### 3.0.1 (Sep 25, 2026)

 - fixed a fatal error on ClassicPress, which does not ship the HTML API (#50)

### 3.0.0 (Sep 18, 2026)

 - simpler image handling: one pass over the post content, catches gallery, media & text and nested block images
 - new `opengraph_image_sources` filter to add or remove image sources
 - the property filters get the metadata collected so far as second argument
 - twitter card is `summary_large_image` whenever the post has an image, `summary` for the fallback images
 - use the current page of a multipage post and the `<!--more-->` teaser for the description (#9)
 - fixed merging into an existing `prefix` attribute
 - fixed a fatal error with The Events Calendar (#39)
 - **breaking:** removed `opengraph_block_image`, `opengraph_parsed_image`, `opengraph_attached_image`, `opengraph_ensure_max_image` and `opengraph_site_supports_blocks` (and its filter), use the `opengraph_image_sources` filter instead
 - minimum WordPress version is now 6.2
 - added phpunit tests

### 2.0.2 (Feb 25, 2025)

 - Add a constant to easily change the max number of images to be included in the OpenGraph meta tags.

### 2.0.1 (Dec 16, 2024)

 - Fixed a warning
 - Improved `opengraph_max_images` handling

### 2.0.0 (Dec 01, 2024)

 - complete rewrite of image handling
 - added support for block images
 - added parsing of HTML `<img>` tags
 - improved WordPress Coding Standards compliance

### 1.12.2 (Nov 17, 2024)
 - optimized readme and updated dependencies

### 1.12.1 (Jul 15, 2024)
 - fix fediverse account normalization

### 1.12.0 (Jul 3, 2024)
 - support `<meta name="fediverse:creator" />`

### 1.11.3 (Jun 4, 2024)
 - don't return description for password protected posts

### 1.11.2 (Nov 13, 2023)
 - stript tags from title, site-name and description

### 1.11.1 (Apr 03, 2023)
 - fixed a typo

### 1.11.0 (Oct 21, 2021)
 - fixed attachment issue
 - fixed PHP 7.4 issue

### 1.10.0 (Apr 20, 2020)
 - basic video support
 - basic audio support

### 1.9.0 (Mai 14, 2019)
 - show only featured image if available
 - prefer header images over site-icon
 - use avatar for profile pages
 - fallback to description if title is empty
 - better twitter `card` handling

### 1.8.3 (Jan 27, 2019)
 - added escaping for the missing attributes

### 1.8.2 (Nov 21, 2018)
 - fixed PHP warning issue: <https://wordpress.org/support/topic/php-warning-count-parameter-must-be-an-array-or-an-object-that-implements-c/>

### 1.8.1 (Nov 19, 2016)
 - change `og:image` to use the full size of image (props @torenord)

### 1.8.0 (Jan 29, 2016)
 - fixed `article:author` property
 - added `article:modified_time`
 - added first category as `article:section`

### 1.7.0 (Jan 18, 2016)
 - added "strict mode" setting
 - better twitter:card handling
 - basic twitter:creator support
 - WordPress coding standard

### 1.6 (Dec 30, 2014)
 - implemented `get_the_archive_title` and `get_the_archive_description` (new in WordPress 4.1)
 - basic twitter cards support (thanks to elroyjetson)
 - replace `$post->post_title` with `get_the_title()` (see #[17][] for details)

[17]: https://github.com/pfefferle/wordpress-opengraph/issues/17

### 1.5.1 (Nov 13, 2012)
 - fix duplicate opengraph markup when used with jetpack plugin (for real)

### 1.5 (Nov 13, 2012)
 - include descriptions on tag and category pages
 - include profile metadata on author pages
 - fix bug with 404 pages include extra og:image values
 - general code cleanup (including removal of dependency on global vars)
 - fix duplicate opengraph markup when used with jetpack plugin

### 1.4 (Aug 24, 2012)
 - better default description
 - include all images that are attached to a post, so that users can choose
   which to use when sharing the page.  If the post has a post thumbnail, that
   is still used as the primary image.

### 1.3 (May 21, 2012)
 - add 'opengraph_prefixes' filter for defining additional prefixes
 - add new basic properties, and remove some old ones.  This is a breaking
   change for anyone that was using the old properties, but they can always be
   added using the 'opengraph_metadata' filter. (see [f476552][] for details)
 - updates to many default values, particularly for individual posts and pages
   (thanks pfefferle)
 - add basic support for array values (see [d987eb7][])

[f476552]: https://github.com/willnorris/wordpress-opengraph/commit/f47655202d59c0e5b5032b4b86764f7a87813640
[d987eb7]: https://github.com/willnorris/wordpress-opengraph/commit/d987eb76e2da1431e5df3311fde3d9c2407b06f5

### 1.2 (Feb 21, 2012)
 - switch to newer RDFa prefix syntax rather than XML namespaces (props
   pfefferle)

### 1.1 (Nov 7, 2011)
 - fix function undefined error when theme doesn't support post thumbnails

### 1.0 (Apr 24, 2010)
 - initial public release
