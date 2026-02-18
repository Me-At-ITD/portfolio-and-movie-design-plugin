=== Custom Post Frontend Display ===
Contributors: cpf
Tags: shortcode, custom post types, portfolio, movie, frontend display
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds shortcodes to display custom frontends for the custom post types `portfolio_item` and `movie`.

== Description ==

Custom Post Frontend Display provides two frontend-only shortcodes:

* `[portfolio_display]` for the `portfolio_item` post type.
* `[movie_display]` for the `movie` post type.

Features include:

* Horizontal taxonomy tabs for category filtering.
* Responsive card grids (3 columns on desktop, 1 column on mobile).
* Accessible modals with keyboard support and escape-to-close.
* Portfolio image gallery slider with thumbnails (Swiper.js).
* Movie modal with responsive YouTube embed rendering.
* Conditional asset loading only when shortcode usage is detected.

This plugin does not register or modify post types/taxonomies; it only renders frontend displays for existing data.

== Installation ==

1. Upload the `custom-post-frontend` folder to `/wp-content/plugins/`.
2. Activate **Custom Post Frontend Display** in the WordPress admin.
3. Add one or both shortcodes to a page/post:
   * `[portfolio_display]`
   * `[movie_display]`

== Frequently Asked Questions ==

= Does this plugin require ACF? =

ACF is optional but recommended. If available, it will read:

* Portfolio fields: `img1`, `img2`, `img3`, `img4`, `img5`
* Movie field: `youtube_link`

Without ACF, the plugin attempts to read values from post meta with the same field names.

= Which taxonomies are used? =

* `portfolio_item` uses `category`.
* `movie` uses `movie-category`.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added `[portfolio_display]` and `[movie_display]`.
* Added responsive tabs, cards, modals, Swiper gallery, and YouTube modal rendering.
