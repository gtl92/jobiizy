<?php
/**
 * JobiiZy AJAX Handlers - Autocomplétion et Recherche Live
 * 
 * Ajouter dans functions.php:
 * require_once get_stylesheet_directory() . '/jobiizy-ajax-search.php';
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autocomplétion des mots-clés (jobs + entreprises)
 */
add_action( 'wp_ajax_jobiizy_autocomplete_keywords', 'jobiizy_autocomplete_keywords' );
add_action( 'wp_ajax_nopriv_jobiizy_autocomplete_keywords', 'jobiizy_autocomplete_keywords' );

function jobiizy_autocomplete_keywords() {
	global $wpdb;

	$query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

	if ( strlen( $query ) < 2 ) {
		wp_send_json_success( [] );
		wp_die();
	}

	$results = [];
	$like    = '%' . $wpdb->esc_like( $query ) . '%';

	// Rechercher dans les offres d'emploi (post_title LIKE)
	$job_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'job_listing' AND post_status = 'publish' AND post_title LIKE %s LIMIT 5",
		$like
	) );
	$jobs = new WP_Query([
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'post__in'       => $job_ids ?: [0],
		'posts_per_page' => 5,
		'orderby'        => 'post__in',
	]);
	
	if ( $jobs->have_posts() ) {
		while ( $jobs->have_posts() ) {
			$jobs->the_post();
			$post_id = get_the_ID();
			
			// Type de contrat
			$type_name = '';
			$terms = get_the_terms( $post_id, 'job_listing_type' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$type_name = $terms[0]->name;
			}
			
			$results[] = [
				'type'      => 'job',
				'id'        => $post_id,
				'title'     => get_the_title(),
				'url'       => get_permalink(),
				'company'   => get_post_meta( $post_id, '_company_name', true ),
				'location'  => get_post_meta( $post_id, '_job_location', true ),
				'logo'      => get_post_meta( $post_id, '_company_logo', true ),
				'type_name' => $type_name,
			];
		}
		wp_reset_postdata();
	}
	
	// Rechercher dans les entreprises (si post type existe)
	if ( post_type_exists( 'company' ) ) {
		$company_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'company' AND post_status = 'publish' AND post_title LIKE %s LIMIT 3",
			$like
		) );
		$companies = new WP_Query([
			'post_type'      => 'company',
			'post_status'    => 'publish',
			'post__in'       => $company_ids ?: [0],
			'posts_per_page' => 3,
		]);
		
		if ( $companies->have_posts() ) {
			while ( $companies->have_posts() ) {
				$companies->the_post();
				$post_id = get_the_ID();
				
				// Compter les offres de cette entreprise
				$jobs_count = new WP_Query([
					'post_type'      => 'job_listing',
					'post_status'    => 'publish',
					'meta_query'     => [
						[
							'key'     => '_company_name',
							'value'   => get_the_title(),
							'compare' => '=',
						],
					],
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]);
				
				$results[] = [
					'type'       => 'company',
					'id'         => $post_id,
					'title'      => get_the_title(),
					'url'        => get_permalink(),
					'logo'       => get_post_meta( $post_id, '_company_logo', true ),
					'jobs_count' => $jobs_count->found_posts,
				];
			}
			wp_reset_postdata();
		}
	}
	
	// Rechercher aussi par nom d'entreprise dans les offres
	$companies_from_jobs = new WP_Query([
		'post_type'      => 'job_listing',
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'key'     => '_company_name',
				'value'   => $like,
				'compare' => 'LIKE',
			],
		],
		'posts_per_page' => 3,
	]);
	
	if ( $companies_from_jobs->have_posts() ) {
		$added_companies = [];
		while ( $companies_from_jobs->have_posts() ) {
			$companies_from_jobs->the_post();
			$company_name = get_post_meta( get_the_ID(), '_company_name', true );
			
			if ( ! in_array( $company_name, $added_companies ) ) {
				$added_companies[] = $company_name;
				
				// Compter les offres
				$count = new WP_Query([
					'post_type'      => 'job_listing',
					'post_status'    => 'publish',
					'meta_query'     => [
						[
							'key'     => '_company_name',
							'value'   => $company_name,
							'compare' => '=',
						],
					],
					'posts_per_page' => -1,
					'fields'         => 'ids',
				]);
				
				$company_q    = new WP_Query([
					'post_type'      => 'company',
					'post_status'    => 'publish',
					'title'          => $company_name,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				]);
				$company_id   = ! empty( $company_q->posts ) ? $company_q->posts[0] : null;
				$company_url  = $company_id
					? get_permalink( $company_id )
					: add_query_arg( 'search', urlencode( $company_name ), home_url( '/companies/' ) );

				$results[] = [
					'type'       => 'company',
					'id'         => $company_id,
					'title'      => $company_name,
					'url'        => $company_url,
					'logo'       => get_post_meta( get_the_ID(), '_company_logo', true ),
					'jobs_count' => $count->found_posts,
				];
			}
		}
		wp_reset_postdata();
	}
	
	wp_send_json_success( $results );
	wp_die();
}

/**
 * Autocomplétion des localisations
 */
add_action( 'wp_ajax_jobiizy_autocomplete_location', 'jobiizy_autocomplete_location' );
add_action( 'wp_ajax_nopriv_jobiizy_autocomplete_location', 'jobiizy_autocomplete_location' );

function jobiizy_autocomplete_location() {
	global $wpdb;

	$query = isset( $_POST['query'] ) ? sanitize_text_field( $_POST['query'] ) : '';

	if ( strlen( $query ) < 2 ) {
		wp_send_json_success( [] );
		wp_die();
	}
	
	// Récupérer les localisations uniques des offres
	$locations = $wpdb->get_results( $wpdb->prepare(
		"SELECT DISTINCT pm.meta_value as location, COUNT(*) as count
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = '_job_location'
		 AND pm.meta_value LIKE %s
		 AND p.post_type = 'job_listing'
		 AND p.post_status = 'publish'
		 GROUP BY pm.meta_value
		 ORDER BY count DESC
		 LIMIT 10",
		'%' . $wpdb->esc_like( $query ) . '%'
	) );
	
	$results = [];
	
	foreach ( $locations as $loc ) {
		if ( ! empty( $loc->location ) ) {
			$results[] = [
				'name'  => $loc->location,
				'count' => (int) $loc->count,
			];
		}
	}
	
	// Si pas de résultats dans les offres, chercher dans les régions (si plugin installé)
	if ( empty( $results ) && taxonomy_exists( 'job_listing_region' ) ) {
		$regions = get_terms([
			'taxonomy'   => 'job_listing_region',
			'name__like' => $query,
			'number'     => 10,
			'hide_empty' => false,
		]);
		
		if ( ! is_wp_error( $regions ) ) {
			foreach ( $regions as $region ) {
				$results[] = [
					'name'  => $region->name,
					'count' => $region->count,
				];
			}
		}
	}
	
	wp_send_json_success( $results );
	wp_die();
}

/**
 * Recherche Live (sans rechargement de page)
 */
add_action( 'wp_ajax_jobiizy_live_search', 'jobiizy_live_search' );
add_action( 'wp_ajax_nopriv_jobiizy_live_search', 'jobiizy_live_search' );

function jobiizy_live_search() {
	$keywords = isset( $_POST['search_keywords'] ) ? sanitize_text_field( $_POST['search_keywords'] ) : '';
	$location = isset( $_POST['search_location'] ) ? sanitize_text_field( $_POST['search_location'] ) : '';
	$category = isset( $_POST['search_categories'] ) ? sanitize_text_field( $_POST['search_categories'] ) : '';
	$job_types = isset( $_POST['filter_job_type'] ) ? array_map( 'sanitize_text_field', (array) $_POST['filter_job_type'] ) : [];
	
	// Construire la query
	$args = [
		'post_type'           => 'job_listing',
		'post_status'         => 'publish',
		'posts_per_page'      => 20,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => 1,
	];
	
	// Recherche par mots-clés
	if ( ! empty( $keywords ) ) {
		$args['s'] = $keywords;
	}
	
	// Filtre par localisation
	if ( ! empty( $location ) ) {
		$args['meta_query'][] = [
			'key'     => '_job_location',
			'value'   => $location,
			'compare' => 'LIKE',
		];
	}
	
	// Filtre par catégorie
	if ( ! empty( $category ) ) {
		$args['tax_query'][] = [
			'taxonomy' => 'job_listing_category',
			'field'    => 'slug',
			'terms'    => $category,
		];
	}
	
	// Filtre par type de contrat
	if ( ! empty( $job_types ) ) {
		$args['tax_query'][] = [
			'taxonomy' => 'job_listing_type',
			'field'    => 'slug',
			'terms'    => $job_types,
		];
	}
	
	// Relations
	if ( isset( $args['meta_query'] ) && count( $args['meta_query'] ) > 1 ) {
		$args['meta_query']['relation'] = 'AND';
	}
	if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
		$args['tax_query']['relation'] = 'AND';
	}
	
	$query = new WP_Query( $args );
	
	// Générer le HTML des cards
	ob_start();
	
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			
			$location_val = get_post_meta( $post_id, '_job_location', true );
			$terms = get_the_terms( $post_id, 'job_listing_type' );
			$job_type = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
			$is_featured = get_post_meta( $post_id, '_featured', true );
			$company = get_post_meta( $post_id, '_company_name', true );
			$company_logo = get_post_meta( $post_id, '_company_logo', true );
			?>
			<article class="jobiizy-modern-card job-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-post-url="<?php the_permalink(); ?>">
				<?php if ( $is_featured ) : ?>
					<span class="featured-badge"><i class="las la-star"></i> À la une</span>
				<?php endif; ?>
				<div class="job-card-content">
					<div class="job-card-header">
						<?php if ( $company_logo ) : ?>
							<div class="company-logo-wrapper">
								<img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company ); ?>" class="company-logo">
							</div>
						<?php endif; ?>
						<?php if ( $company ) : ?>
							<div class="company-name"><i class="las la-building"></i> <?php echo esc_html( $company ); ?></div>
						<?php endif; ?>
					</div>
					<h3 class="job-title"><?php the_title(); ?></h3>
					<div class="job-meta">
						<?php if ( $location_val ) : ?>
							<span class="job-location"><i class="las la-map-marker"></i> <?php echo esc_html( $location_val ); ?></span>
						<?php endif; ?>
						<?php if ( $job_type ) : ?>
							<span class="job-type"><i class="las la-clock"></i> <?php echo esc_html( $job_type ); ?></span>
						<?php endif; ?>
						<span class="job-date"><i class="las la-calendar"></i> Il y a <?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ); ?></span>
					</div>
					<?php if ( $excerpt = get_the_excerpt() ) : ?>
						<div class="job-excerpt"><?php echo wp_trim_words( $excerpt, 15, '...' ); ?></div>
					<?php endif; ?>
					<div class="job-card-footer">
						<button class="btn-view-job">Voir l'offre <i class="las la-arrow-right"></i></button>
					</div>
				</div>
			</article>
			<?php
		}
		wp_reset_postdata();
	} else {
		?>
		<div class="no-jobs-found">
			<i class="las la-search" style="font-size:48px;color:#64748b;display:block;margin-bottom:16px;"></i>
			<p>Aucune offre trouvée pour cette recherche.</p>
			<p style="font-size:13px;color:#64748b;">Essayez de modifier vos critères de recherche.</p>
		</div>
		<?php
	}
	
	$html = ob_get_clean();
	
	wp_send_json_success([
		'count' => $query->found_posts,
		'html'  => $html,
	]);
	wp_die();
}