<?php
/**
 * Tweaks for the <head> of the document.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 * @since      3.8
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Tweaks for the <head> of the document.
 */
class Avada_Head {

	/**
	 * Constructor.
	 *
	 * @access  public
	 */
	public function __construct() {
		/**
		 * WIP
		add_action( 'wp_head', array( $this, 'x_ua_meta' ), 1 );
		add_action( 'wp_head', array( $this, 'the_meta' ) );
		 */

		add_filter( 'language_attributes', [ $this, 'add_opengraph_doctype' ] );

		add_filter( 'document_title_separator', [ $this, 'document_title_separator' ] );

		add_filter( 'theme_color_meta', [ $this, 'theme_color' ] );

		add_action( 'wp_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'admin_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'login_head', [ $this, 'insert_favicons' ], 2 );
		add_action( 'wp_head', [ $this, 'insert_og_meta' ], 5 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

		if ( ! function_exists( '_wp_render_title_tag' ) ) {
			add_action( 'wp_head', [ $this, 'render_title' ] );
		}

		add_action( 'wp_head', [ $this, 'set_user_agent' ], 1000 );
		add_action( 'wp_head', [ $this, 'preload_fonts' ] );

		// wp_body_open function introduced in WP 5.2.
		if ( function_exists( 'wp_body_open' ) ) {
			add_action( 'avada_before_body_content', 'wp_body_open' );
		}

		/**
		 * WIP
		add_filter( 'wpseo_metadesc', array( $this, 'yoast_metadesc_helper' ) );
		*/

	}

	/**
	 * Adding the Open Graph in the Language Attributes
	 *
	 * @access public
	 * @param  string $output The output we want to process/filter.
	 * @return string The altered doctype
	 */
	public function add_opengraph_doctype( $output ) {
		if ( Avada()->settings->get( 'status_opengraph' ) ) {
			return $output . ' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"';
		}
		return $output;
	}

	/**
	 * Renders the title.
	 *
	 * @access public
	 * @since 5.0.0
	 * @return void
	 */
	public function render_title() {
		wp_title( '' );
	}

	/**
	 * Set the user agent data attribute on the HTML tag.
	 *
	 * @access public
	 * @since 6.0
	 * @return void
	 */
	public function set_user_agent() {
		?>
		<script type="text/javascript">
			var doc = document.documentElement;
			doc.setAttribute( 'data-useragent', navigator.userAgent );
		</script>
		<?php
	}

	/**
	 * Preloads font files.
	 *
	 * @static
	 * @access public
	 * @since 7.2
	 * @return void
	 */
	public function preload_fonts() {
		$fusion_settings = awb_get_fusion_settings();

		$preload_fonts = $fusion_settings->get( 'preload_fonts' );
		$tags          = '';

		if ( 'icon_fonts' === $preload_fonts || 'all' === $preload_fonts ) {
			// Icomoon.
			$font_url = FUSION_LIBRARY_URL . '/assets/fonts/icomoon';
			$font_url = set_url_scheme( $font_url ) . '/awb-icons.woff';

			$tags .= '<link rel="preload" href="' . $font_url . '" as="font" type="font/woff" crossorigin>';

			// Font Awesome.
			$tags .= ( 'local' === $fusion_settings->get( 'gfonts_load_method' ) && true === Fusion_Font_Awesome::is_fa_pro_enabled() && ! ( function_exists( 'fusion_is_preview_frame' ) && fusion_is_preview_frame() ) ) ? fusion_library()->fa->get_local_subsets_tags() : fusion_library()->fa->get_subsets_tags();

			// Custom Icons.
			$tags .= fusion_get_custom_icons_preload_tags();
		}

		if ( 'google_fonts' === $preload_fonts || 'all' === $preload_fonts ) {
			// Google fonts.
			$google_fonts = Avada_Google_Fonts::get_instance();
			$tags        .= $google_fonts->get_preload_tags();
		}

		echo $tags; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Avada extra OpenGraph tags
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access  public
	 * @return void
	 */
	public function insert_og_meta() {

		// Early exit if we don't need to continue any further.
		if ( ! Avada()->settings->get( 'status_opengraph' ) ) {
			return;
		}

		// Early exit if this is not a singular post/page/cpt.
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$settings = Avada::settings();

		$image = '';
		if ( ! has_post_thumbnail( $post->ID ) ) {
			if ( isset( $settings['logo'] ) && $settings['logo'] ) {
				$image = $settings['logo'];
			}
		} else {
			$thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$image         = esc_attr( $thumbnail_src[0] );
		}

		if ( is_array( $image ) ) {
			$image = ( isset( $image['url'] ) && ! empty( $image['url'] ) ) ? $image['url'] : '';
		}
		?>

		<meta property="og:title" content="<?php echo esc_attr( strip_tags( str_replace( [ '"', "'" ], [ '&quot;', '&#39;' ], $post->post_title ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags ?>"/>
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="<?php echo esc_url_raw( get_permalink() ); ?>"/>
		<meta property="og:site_name" content="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content ) ); ?>"/>

		<?php if ( '' != $image ) : // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison ?>
			<?php if ( is_array( $image ) ) : ?>
				<?php if ( isset( $image['url'] ) ) : ?>
					<meta property="og:image" content="<?php echo esc_url_raw( $image['url'] ); ?>"/>
				<?php endif; ?>
			<?php else : ?>
				<meta property="og:image" content="<?php echo esc_url_raw( $image ); ?>"/>
			<?php endif; ?>
		<?php endif; ?>
		<?php

	}

	/**
	 * Add X-UA-Compatible meta when needed.
	 *
	 * @access  public
	 */
	public function x_ua_meta() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( false !== strpos( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 'MSIE' ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			echo '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />';
		}
	}

	/**
	 * Set the document title separator.
	 *
	 * @access  public
	 */
	public function document_title_separator() {
		return '-';
	}

	/**
	 * Avada favicon as set in global options.
	 * These are added to the <head> of the page using the 'wp_head' action.
	 *
	 * @access  public
	 * @since   4.0
	 * @return  void
	 */
	public function insert_favicons() {
		?>
		<?php if ( '' !== Avada()->settings->get( 'fav_icon', 'url' ) ) : ?>
			<link rel="shortcut icon" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon', 'url' ) ); ?>" type="image/x-icon" />
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_apple_touch', 'url' ) ) : ?>
			<!-- Apple Touch Icon -->
			<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon_apple_touch', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_android', 'url' ) ) : ?>
			<!-- Android Icon -->
			<link rel="icon" sizes="192x192" href="<?php echo esc_url( Avada()->settings->get( 'fav_icon_android', 'url' ) ); ?>">
		<?php endif; ?>

		<?php if ( '' !== Avada()->settings->get( 'fav_icon_edge', 'url' ) ) : ?>
			<!-- MS Edge Icon -->
			<meta name="msapplication-TileImage" content="<?php echo esc_url( Avada()->settings->get( 'fav_icon_edge', 'url' ) ); ?>">
		<?php endif; ?>
		<?php

	}

	/**
	 * Fixes YOAST SEO plugin issues.
	 *
	 * @access public
	 * @since 5.0.3
	 * @param string $metadesc The description.
	 * @return string
	 */
	public function yoast_metadesc_helper( $metadesc ) {
		if ( '' === $metadesc ) {
			global $post;

			$metadesc = Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content );
		}

		return $metadesc;
	}

	/**
	 * Echoes the viewport.
	 *
	 * @access public
	 * @since 5.1.0
	 * @return void
	 */
	public function the_viewport() {

		$is_ipad = (bool) ( isset( $_SERVER['HTTP_USER_AGENT'] ) && false !== strpos( $_SERVER['HTTP_USER_AGENT'], 'iPad' ) ); // phpcs:ignore WordPress.Security

		$viewport = '';
		if ( fusion_get_option( 'responsive' ) && $is_ipad ) {
			$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
		} elseif ( fusion_get_option( 'responsive' ) ) {
			if ( Avada()->settings->get( 'mobile_zoom' ) ) {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1" />';
			} else {
				$viewport .= '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
			}
		}

		$viewport = apply_filters( 'avada_viewport_meta', $viewport );

		echo $viewport; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Prints the theme-color meta.
	 *
	 * @access public
	 * @since 5.8
	 * @param string $theme_color The theme-color we want to use.
	 * @return string
	 */
	public function theme_color( $theme_color ) {

		// Exit early if PWA is not enabled.
		$pwa_enabled = Fusion_Settings::get_instance()->get( 'pwa_enable' );
		if ( true === $pwa_enabled || '1' !== $pwa_enabled ) {
			$settings    = Fusion_Settings::get_instance();
			$theme_color = $settings->get( 'pwa_theme_color' );
			return Fusion_Color::new_color( $theme_color )->get_new( 'alpha', 1 )->to_css( 'hex' );
		}
		return $theme_color;
	}
}
