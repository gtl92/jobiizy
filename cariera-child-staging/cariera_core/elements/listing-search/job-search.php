<?php
/**
 * Elementor Element: Job Search  //GTL00
 *
 * This template can be overridden by copying it to cariera-child/cariera_core/elements/listing-search/job-search.php.
 *
 * @package     cariera
 * @category    Template
 * @since       1.7.2
 * @version     1.8.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = [ 'listing-search-form', 'job-search-form', $settings['search_style'], $settings['custom_class'] ];
?>

<form method="GET" action="<?php echo esc_url( get_permalink( get_option( 'job_manager_jobs_page_id' ) ) ); ?>" class="<?php echo esc_attr( join( ' ', $classes ) ); ?>" data-listing-type="<?php echo esc_attr( $settings['listing_search'] ); ?>">
	<div class="search-keywords">
		<?php /* <label for="search_keywords"><?php esc_html_e( 'Keywords', 'cariera-core' ); ?></label> 
				<input type="text" id="search_keywords" name="search_keywords" placeholder="<?php esc_attr_e( 'Keywords', 'cariera-core' ); ?>" autocomplete="off" data-keyword-autocomplete="<?php echo esc_attr( $settings['keyword_autocomple'] ); ?>">
		*/ ?>
		<label for="search_keywords">QUOI ?</label>
		<input type="text" id="search_keywords" name="search_keywords" placeholder="Métier, entreprise, compétence..." autocomplete="off" data-keyword-autocomplete="<?php echo esc_attr( $settings['keyword_autocomple'] ); ?>">
		<div class="search-results"><div class="search-loader"><span></span></div><div class="listings cariera-scroll"></div></div>
	</div>

	<?php if ( ! empty( $settings['location'] ) ) { ?>
		<div class="search-location">
			<?php /* <label for="search_location"><?php esc_html_e( 'Location', 'cariera-core' ); ?></label>
				  <input type="text" id="search_location" name="search_location" placeholder="<?php esc_attr_e( 'Location', 'cariera-core' ); ?>" autocomplete="off"> */ ?>
			<label for="search_location">OÙ ?</label>
			<input type="text" id="search_location" name="search_location" placeholder="Ville, Région ..." autocomplete="off">
			<div class="geolocation"><i class="geolocate"></i></div>
		</div>
	<?php } ?>

	<?php if ( class_exists( 'Astoundify_Job_Manager_Regions' ) && ! empty( $settings['region'] ) ) { ?>
		<div class="search-region">
			<label for="search_region"><?php esc_html_e( 'Region', 'cariera-core' ); ?></label>
			<?php
			wp_dropdown_categories(
				apply_filters(
					'job_manager_regions_dropdown_args',
					[
						'show_option_all' => esc_html__( 'All Regions', 'cariera-core' ),
						'hierarchical'    => true,
						'orderby'         => 'name',
						'taxonomy'        => 'job_listing_region',
						'name'            => 'search_region',
						'class'           => 'search_region cariera-select2-search',
						'hide_empty'      => 0,
						'selected'        => isset( $atts['selected_region'] ) ? $atts['selected_region'] : '',
					]
				)
			);
			?>
		</div>
	<?php } ?>

	<?php if ( ! empty( $settings['categories'] ) ) {
	   // Get the option to show empty categories  // GTL
    	$show_empty_categories = get_option( 'job_manager_show_empty_categories', '0' );
    	error_log('Show Empty Categories Option: ' . $show_empty_categories);
    	// Set 'hide_empty' based on the option value
    	$hide_empty = ( '1' === $show_empty_categories ) ? 1 : 0;
	 ?>
	 	<div class="search-categories">
			<label for="search_category_jobs"><?php esc_html_e( 'Category', 'cariera-core' ); ?></label>
			<?php
			cariera_job_manager_dropdown_category(
				[
					'taxonomy'        => 'job_listing_category',
					'hierarchical'    => 1,
					'name'            => 'search_category',
					'id'              => 'search_category_jobs',
					'orderby'         => 'name',
					'selected'        => '',
					'multiple'        => false,
					'show_option_all' => true,
	                'hide_empty'      => $hide_empty, // Use the variable here
				]
			);
			?>
		</div>
	<?php } ?>
	
	<div class="search-submit GTLMOD0001">

	
	<button type="submit" class="jobiizy-search-btn" aria-label="<?php esc_attr_e( 'Search', 'cariera' ); ?>">
	  <svg class="icon-search" viewBox="0 0 24 24">
		<path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 
		6.5 6.5 0 109.5 16a6.471 6.471 0 004.23-1.57l.27.28v.79l5 
		4.99L20.49 19l-4.99-5zM9.5 14C7.01 14 5 11.99 
		5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 
		9.5 14z"/>
	  </svg>
	  <span class="jobfind-title">Trouver mon job</span>
	</button>
</div>
	
</form>
