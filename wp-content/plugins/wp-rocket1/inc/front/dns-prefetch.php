<?php
defined( 'ABSPATH' ) or	die( 'Cheatin&#8217; uh?' );

/**
 * Launch DNS Prefetching process
 *
 * @since 2.5 Don't add CNAMES if CDN is disabled HTTPS pages or on specific posts
 * @since 2.1 Adding CNAMES fo CDN automatically in DNS Prefetch process
 * @since 2.0
 */
add_filter( 'rocket_buffer', 'rocket_dns_prefetch', 12 );
function rocket_dns_prefetch( $buffer )
{
	$dns_link_tags = '';
	$cdn_cnames    = get_rocket_cdn_cnames( array( 'all', 'images', 'css_and_js', 'css', 'js' ) );

	// Don't add CNAMES if CDN is disabled HTTPS pages or on specific posts
	if( ! is_rocket_cdn_on_ssl() || is_rocket_post_excluded_option( 'cdn' ) ) {
		$cdn_cnames = array();
	}

	$domains = array_merge( $cdn_cnames, (array) get_rocket_option( 'dns_prefetch' ) );

	/**
	 * Filter list of domains to prefetch DNS
	 *
	 * @since 1.1.0
	 *
	 * @param array $domains List of domains to prefetch DNS
	 */
	$domains = apply_filters( 'rocket_dns_prefetch', $domains );

	if ( count( $domains ) ) {
		foreach ( $domains as $domain ) {
			$dns_link_tags .= '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '" />';
		}
	}

	$old_ie_conditional_tag = '';
	
	/**
	 * Allow to print an empty IE conditional tag to speed up old IE versions to load CSS & JS files
	 *
	 * @since 2.6.5
	 *
	 * @param bool true will print the IE conditional tag
	 */
	if( apply_filters( 'do_rocket_old_ie_prefetch_conditional_tag', true ) ) {
		$old_ie_conditional_tag = '<!--[if IE]><![endif]-->';
	}

	// Insert all DNS prefecth tags in head
	$buffer = preg_replace( '/<head(.*)>/', '<head$1>' . $old_ie_conditional_tag . $dns_link_tags, $buffer, 1 );

	return $buffer;
}