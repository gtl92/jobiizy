<?php
/**
 * Elementor Element: Company Search
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/elements/listing-search/company-search.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.3
 * @version     1.8.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = [ 'listing-search-form', 'company-search-form', $settings['search_style'], $settings['custom_class'] ];
?>

<form method="GET" action="<?php echo esc_url( get_permalink( get_option( 'cariera_companies_page' ) ) ); ?>" class="<?php echo esc_attr( join( ' ', $classes ) ); ?>" data-listing-type="<?php echo esc_attr( $settings['listing_search'] ); ?>">
	<div class="search-keywords">
		<label for="search_keywords"><?php esc_html_e( 'Keywords', 'cariera-core' ); ?></label>
		<input type="text" id="search_keywords" name="search_keywords" placeholder="<?php esc_attr_e( 'Keywords', 'cariera-core' ); ?>" autocomplete="off" data-keyword-autocomplete="<?php echo esc_attr( $settings['keyword_autocomple'] ); ?>">
		<div class="search-results"><div class="search-loader"><span></span></div><div class="listings cariera-scroll"></div></div>
	</div>

	<?php if ( ! empty( $settings['location'] ) ) { ?>
		<div class="search-location">
			<label for="search_location"><?php esc_html_e( 'Location', 'cariera-core' ); ?></label>
			<input type="text" id="search_location" name="search_location" placeholder="<?php esc_attr_e( 'Location', 'cariera-core' ); ?>" autocomplete="off">
			<div class="geolocation"><i class="geolocate"></i></div>
		</div>
	<?php } ?>

	<?php if ( ! empty( $settings['categories'] ) ) { ?>
		<div class="search-categories">
			<label for="search_category_companies"><?php esc_html_e( 'Category', 'cariera-core' ); ?></label>                
			<?php
			cariera_job_manager_dropdown_category(
				[
					'taxonomy'        => 'company_category',
					'hierarchical'    => 1,
					'name'            => 'search_category',
					'id'              => 'search_category_companies',
					'orderby'         => 'name',
					'selected'        => '',
					'multiple'        => false,
					'show_option_all' => true,
				]
			);
			?>
		</div>
	<?php } ?>
	
	<div class="search-submit"><input type="submit" class="btn btn-main btn-effect" value="<?php esc_attr_e( 'Search', 'cariera-core' ); ?>"></div>
</form>
