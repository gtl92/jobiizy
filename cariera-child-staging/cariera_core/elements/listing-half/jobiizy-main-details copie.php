<?php
/**
 * Template: JobiiZy Split View - Version 4.0 REFACTORISÉ
 * Architecture modulaire : PHP/HTML séparé du CSS/JS
 * 
 * Emplacement: /cariera-child/cariera-core/elements/listing-half/jobiizy-main-details.php
 * 
 * Fichiers associés:
 * - CSS: /cariera-child/assets/css/jobiizy-split-view.css
 * - JS:  /cariera-child/assets/js/jobiizy-split-view.js
 * 
 * @version 4.0
 * @author JobiiZy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// CONFIGURATION ET QUERY
// ============================================================================

// Variables depuis les settings Elementor
$listing_type = isset($settings['listing_type']) ? $settings['listing_type'] : 'job_listing';
$per_page = isset( $per_page ) ? (int) str_replace( ['per_page="', '"'], '', $per_page ) : 10;
$orderby = isset( $orderby ) ? str_replace( ['orderby="', '"'], '', $orderby ) : 'featured';
$order = isset( $order ) ? str_replace( ['order="', '"'], '', $order ) : 'DESC';
$featured = isset( $featured ) ? $featured : '';

// Arguments de la query WordPress
$args = [
	'post_type'           => $listing_type,
	'post_status'         => 'publish',
	'ignore_sticky_posts' => 1,
	'posts_per_page'      => $per_page,
	'orderby'             => $orderby,
	'order'               => $order,
	'paged'               => 1,
];

// Filtre featured
if ( strpos( $featured, 'true' ) !== false ) {
	$args['meta_query'] = [['key' => '_featured', 'value' => '1', 'compare' => '=']];
} elseif ( strpos( $featured, 'false' ) !== false ) {
	$args['meta_query'] = [['key' => '_featured', 'value' => '1', 'compare' => '!=']];
}

// Initialiser meta_query si nécessaire
if ( ! isset( $args['meta_query'] ) ) {
	$args['meta_query'] = [];
}

// Filtre : Mots-clés
if ( ! empty( $_GET['search_keywords'] ) ) {
	$args['s'] = sanitize_text_field( $_GET['search_keywords'] );
}

// Filtre : Localisation
if ( ! empty( $_GET['search_location'] ) ) {
	$args['meta_query'][] = [
		'key'     => '_job_location',
		'value'   => sanitize_text_field( $_GET['search_location'] ),
		'compare' => 'LIKE',
	];
}

// Filtre : Catégories
if ( ! empty( $_GET['search_categories'] ) ) {
	$args['tax_query'] = [[
		'taxonomy' => 'job_listing_category',
		'field'    => 'slug',
		'terms'    => sanitize_text_field( $_GET['search_categories'] ),
	]];
}

// Filtre : Types de contrat
if ( ! empty( $_GET['filter_job_type'] ) ) {
	$job_types = is_array( $_GET['filter_job_type'] ) 
		? array_map( 'sanitize_text_field', $_GET['filter_job_type'] )
		: explode( ',', sanitize_text_field( $_GET['filter_job_type'] ) );
	
	if ( ! isset( $args['tax_query'] ) ) {
		$args['tax_query'] = [];
	}
	$args['tax_query'][] = [
		'taxonomy' => 'job_listing_type',
		'field'    => 'slug',
		'terms'    => $job_types,
	];
}

// Relation AND pour les taxonomies multiples
if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
	$args['tax_query']['relation'] = 'AND';
}

// Exécuter la query
$query = new WP_Query( $args );
$total_jobs = $query->found_posts;
$max_pages = $query->max_num_pages;

// ============================================================================
// ENQUEUE DES ASSETS (CSS ET JAVASCRIPT)
// ============================================================================

// Cette fonction doit aussi être ajoutée dans functions.php pour une meilleure performance
// Mais on l'ajoute ici aussi pour compatibilité
add_action( 'wp_footer', function() use ( $total_jobs, $max_pages, $per_page ) {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	
	// CSS
	$css_file = $theme_dir . '/assets/css/jobiizy-split-view.css';
	if ( file_exists( $css_file ) ) {
		wp_enqueue_style(
			'jobiizy-split-view',
			$theme_uri . '/assets/css/jobiizy-split-view.css',
			[],
			filemtime( $css_file )
		);
	}
	
	// JavaScript
	$js_file = $theme_dir . '/assets/js/jobiizy-split-view.js';
	if ( file_exists( $js_file ) ) {
		wp_enqueue_script(
			'jobiizy-split-view',
			$theme_uri . '/assets/js/jobiizy-split-view.js',
			['jquery'],
			filemtime( $js_file ),
			true
		);
		
		// Passer les données PHP au JavaScript
		wp_localize_script( 'jobiizy-split-view', 'jobiizyData', [
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'jobiizy_nonce' ),
			'currentPage' => 1,
			'totalJobs'   => $total_jobs,
			'maxPages'    => $max_pages,
			'perPage'     => $per_page,
		]);
	}
}, 5);

?>

<div id="jobiizy-search-wrapper" class="jobiizy-search-wrapper">
    <div id="jobiizy-sticky-bar" class="jobiizy-sticky-bar">
        <div class="container">
            <form class="jobiizy-search-form" action="<?php echo get_post_type_archive_link('job_listing'); ?>" method="GET">
                
                <div class="jobiizy-inputs-container">
                    <div class="jobiizy-input-group icon-search">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search_keywords" placeholder="Métier, compétences..." value="<?php echo get_query_var('search_keywords'); ?>">
                    </div>

                    <div class="jobiizy-input-group icon-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="search_location" placeholder="Ville, région..." value="<?php echo get_query_var('search_location'); ?>">
                    </div>
                </div>

                <div class="jobiizy-actions">
                     <button type="submit" class="jobiizy-btn-search">
                        <span>Rechercher</span>
                        <i class="fas fa-arrow-right mobile-only"></i>
                     </button>
                </div>

            </form>
        </div>
    </div>
</div>
<div class="jobiizy-split-view-outer ...">
<!-- ============================================================================
     WRAPPER PRINCIPAL
     ============================================================================ -->
<div class="jobiizy-split-view-outer">

<!-- ============================================================================
     BARRE DE RECHERCHE STICKY
     ============================================================================ -->
     
<!-- Barre sticky mobile (visible uniquement en mobile < 1024px) -->
<div class="jobiizy-mobile-sticky-bar" id="jobiizy-mobile-sticky">
	<div class="mobile-sticky-content">
		<!-- Bouton recherche principal -->
		<button type="button" class="mobile-search-trigger" id="mobile-search-trigger">
			<i class="las la-search"></i>
			<span>Rechercher une offre</span>
		</button>
		
		<!-- Badge filtres actifs (caché si aucun filtre) -->
		<button type="button" class="mobile-filters-badge" id="mobile-filters-badge" data-count="0" style="display: none;">
			<i class="las la-filter"></i>
			<span class="badge-count">0</span>
		</button>
		
		<!-- Bouton réinitialiser (visible si filtres actifs) -->
		<button type="button" class="mobile-reset-btn" id="mobile-reset-btn" style="display: none;" title="Réinitialiser les filtres">
			<i class="las la-redo"></i>
		</button>
	</div>
	
	<!-- Ligne de filtres actifs (visible si présents) -->
	<div class="mobile-active-filters" id="mobile-active-filters" style="display: none;">
		<div class="mobile-filters-tags"></div>
	</div>
</div>

<div class="jobiizy-search-bar-sticky" id="jobiizy-sticky-bar">
	<div class="jobiizy-search-bar-container">
		<div class="jobiizy-search-form" id="jobiizy-live-search">
			
			<!-- Champ "Quoi ?" -->
			<div class="jobiizy-search-field jobiizy-search-what">
				<label>QUOI ?</label>
				<input type="text" id="jobiizy-keywords" name="search_keywords" placeholder="Métier, entreprise, compétence..." value="<?php echo esc_attr( isset($_GET['search_keywords']) ? $_GET['search_keywords'] : '' ); ?>" autocomplete="off">
				<div class="jobiizy-autocomplete-dropdown jobiizy-keywords-dropdown">
					<div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
					<div class="jobiizy-autocomplete-results"></div>
				</div>
			</div>
			
			<div class="jobiizy-search-separator"></div>
			
			<!-- Champ "Où ?" -->
			<div class="jobiizy-search-field jobiizy-search-where">
				<label>OÙ ?</label>
				<input type="text" id="jobiizy-location" name="search_location" placeholder="Ville, département, code postal..." value="<?php echo esc_attr( isset($_GET['search_location']) ? $_GET['search_location'] : '' ); ?>" autocomplete="off">
				<?php if ( get_option( 'cariera_auto_geolocate' ) ) : ?>
					<button type="button" class="jobiizy-geolocate" title="Ma position"><i class="las la-crosshairs"></i></button>
				<?php endif; ?>
				<div class="jobiizy-autocomplete-dropdown jobiizy-location-dropdown">
					<div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
					<div class="jobiizy-autocomplete-results"></div>
				</div>
			</div>
			
			<!-- Bouton recherche -->
			<button type="button" class="jobiizy-search-submit" id="jobiizy-search-btn"><i class="las la-search"></i></button>
		</div>
		
		<div class="jobiizy-search-actions">
			<button type="button" class="jobiizy-btn-filters" id="jobiizy-open-filters" data-active-filters="0">
				<i class="las la-sliders-h"></i><span>Filtres</span><span class="jobiizy-filter-badge">0</span>
			</button>
			<button type="button" class="jobiizy-btn-alert">
				<i class="las la-bell"></i><span>Créer mon alerte</span>
			</button>
		</div>
	</div>
	<div class="jobiizy-active-filters" style="display: none;">
		<div class="jobiizy-filters-tags"></div>
		<button type="button" class="jobiizy-clear-all"><i class="las la-times"></i> Réinitialiser tous les filtres</button>
	</div>
</div>

<!-- ============================================================================
     HEADER COMPTEUR
     ============================================================================ -->
<div class="jobiizy-split-header">
	<h2 class="jobiizy-job-count">
		<i class="las la-briefcase"></i>
		<?php printf( esc_html( _n( 'Nous avons trouvé %s offre d\'emploi pour vous !', 'Nous avons trouvé %s offres d\'emploi pour vous !', $total_jobs, 'cariera' ) ), '<strong>' . number_format_i18n( $total_jobs ) . '</strong>' ); ?>
	</h2>
	
	<!-- Bouton mobile de recherche (visible uniquement en mobile) -->
<!-- 
	<button type="button" class="jobiizy-mobile-search-in-header" id="jobiizy-mobile-search-btn">
		<i class="las la-search"></i>
	</button>
 -->
</div>

<!-- ============================================================================
     SPLIT VIEW WRAPPER
     ============================================================================ -->
<div class="jobiizy-split-view-wrapper">
	
	<!-- ========================================================================
	     COLONNE LISTINGS
	     ======================================================================== -->
	<div class="jobiizy-listings-column" id="jobiizy-listings-scroll" 
		 data-page="1" 
		 data-max-pages="<?php echo esc_attr( $max_pages ); ?>" 
		 data-total="<?php echo esc_attr( $total_jobs ); ?>"
		 data-per-page="<?php echo esc_attr( $per_page ); ?>">
		
		<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
			$post_id = get_the_ID();
			$location = get_post_meta( $post_id, '_job_location', true );
			$terms = get_the_terms( $post_id, 'job_listing_type' );
			$job_type = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
			$is_featured = get_post_meta( $post_id, '_featured', true );
			$company = get_post_meta( $post_id, '_company_name', true );
			$company_logo = get_post_meta( $post_id, '_company_logo', true );
		?>
			<article class="jobiizy-modern-card job-listing" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-post-url="<?php the_permalink(); ?>">
				<?php if ( $is_featured ) : ?><span class="featured-badge"><i class="las la-star"></i> À la une</span><?php endif; ?>
				<div class="job-card-content">
					<div class="job-card-header">
						<?php if ( $company_logo ) : ?><div class="company-logo-wrapper"><img src="<?php echo esc_url( $company_logo ); ?>" alt="<?php echo esc_attr( $company ); ?>" class="company-logo"></div><?php endif; ?>
						<?php if ( $company ) : ?><div class="company-name"><i class="las la-building"></i> <?php echo esc_html( $company ); ?></div><?php endif; ?>
					</div>
					<h3 class="job-title"><?php the_title(); ?></h3>
					<div class="job-meta">
						<?php if ( $location ) : ?><span class="job-location"><i class="las la-map-marker"></i> <?php echo esc_html( $location ); ?></span><?php endif; ?>
						<?php if ( $job_type ) : ?><span class="job-type"><i class="las la-clock"></i> <?php echo esc_html( $job_type ); ?></span><?php endif; ?>
						<span class="job-date"><i class="las la-calendar"></i> Il y a <?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ); ?></span>
					</div>
					<?php if ( $excerpt = get_the_excerpt() ) : ?><div class="job-excerpt"><?php echo wp_trim_words( $excerpt, 15, '...' ); ?></div><?php endif; ?>
					<div class="job-card-footer"><button class="btn-view-job">Voir l'offre <i class="las la-arrow-right"></i></button></div>
				</div>
			</article>
		<?php endwhile; wp_reset_postdata(); else : ?>
			<div class="no-jobs-found"><p>Aucune offre trouvée.</p></div>
		<?php endif; ?>
		
		<!-- Zone de chargement (infinite scroll) - SEULEMENT si plus d'une page -->
		<?php if ( $max_pages > 1 ) : ?>
		<div class="jobiizy-load-more-container" id="jobiizy-load-more">
			<div class="jobiizy-progress-container">
				<div class="jobiizy-progress-info">
					<span class="jobiizy-progress-count">
						<span id="jobiizy-loaded-count"><?php echo min( $per_page, $total_jobs ); ?></span> / <?php echo $total_jobs; ?> offres
					</span>
					<span class="jobiizy-progress-percent" id="jobiizy-progress-percent"><?php echo round( min( $per_page, $total_jobs ) / $total_jobs * 100 ); ?>%</span>
				</div>
				<div class="jobiizy-progress-bar">
					<div class="jobiizy-progress-fill" id="jobiizy-progress-fill" style="width: <?php echo round( min( $per_page, $total_jobs ) / $total_jobs * 100 ); ?>%;"></div>
				</div>
			</div>
			
			<button type="button" class="jobiizy-load-more-btn" id="jobiizy-load-more-btn">
				<span class="btn-spinner"></span>
				<span class="btn-text"><i class="las la-plus-circle"></i> Charger plus d'offres</span>
			</button>
		</div>
		<?php endif; ?>
		
		<!-- Message fin de liste -->
		<div class="jobiizy-end-message" id="jobiizy-end-message" style="display: none;">
			<i class="las la-check-circle"></i>
			<span>Vous avez vu toutes les offres disponibles !</span>
		</div>
	</div>

	<!-- ========================================================================
	     TOAST EMPTY STATE
	     ======================================================================== -->
	<div class="jobiizy-empty-state">
		<div class="jobiizy-empty-box">
			<i class="las la-hand-pointer"></i>
			<div class="jobiizy-empty-text">
				<strong>Sélectionnez une offre</strong><br>
				<small>pour voir les détails</small>
			</div>
		</div>
	</div>
	
	<!-- ========================================================================
	     COLONNE DÉTAILS
	     ======================================================================== -->
	<div class="jobiizy-detail-column" id="jobiizy-detail-panel">
		<div class="jobiizy-drawer-handle"></div>
		<button type="button" class="jobiizy-drawer-close" id="jobiizy-drawer-close">
			<i class="las la-times"></i>
		</button>
		<div class="jobiizy-detail-content" style="display:none;"></div>
	</div>
</div>

</div><!-- /.jobiizy-split-view-outer -->

<!-- ============================================================================
     OVERLAY DRAWER MOBILE
     ============================================================================ -->
<div class="jobiizy-drawer-overlay" id="jobiizy-drawer-overlay"></div>

<!-- ============================================================================
     BOUTON TOGGLE RECHERCHE (apparaît quand la barre est cachée)
     ============================================================================ -->
<button type="button" class="jobiizy-toggle-search-btn" id="jobiizy-toggle-search" title="Afficher la recherche">
	<i class="las la-search"></i>
</button>

<!-- ============================================================================
     MODAL RECHERCHE MOBILE
     ============================================================================ -->
<div class="jobiizy-mobile-search-modal" id="jobiizy-mobile-search-modal">
	<div class="jobiizy-mobile-search-header">
		<h3><i class="las la-search"></i> Rechercher</h3>
		<button type="button" class="jobiizy-mobile-close-search" id="jobiizy-mobile-close-search">
			<i class="las la-times"></i>
		</button>
	</div>
	
	<div class="jobiizy-mobile-search-body">
		<div class="jobiizy-mobile-search-field">
			<label><i class="las la-briefcase"></i> Quoi ?</label>
			<input type="text" id="jobiizy-mobile-keywords" placeholder="Métier, entreprise, compétence...">
			        <!-- AJOUTER LE DROPDOWN -->
        <div class="jobiizy-autocomplete-dropdown">
            <div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
            <div class="jobiizy-autocomplete-results"></div>
        </div>

		</div>
		
		<div class="jobiizy-mobile-search-field">
			<label><i class="las la-map-marker"></i> Où ?</label>
			<input type="text" id="jobiizy-mobile-location" placeholder="Ville, département...">
			        <!-- AJOUTER LE DROPDOWN -->
        <div class="jobiizy-autocomplete-dropdown">
            <div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
            <div class="jobiizy-autocomplete-results"></div>
        </div>

		</div>
		
		<div class="jobiizy-mobile-search-field">
			<label><i class="las la-sliders-h"></i> Filtres avancés</label>
            <button type="button" class="jobiizy-mobile-open-filters" style="width:100%; padding:14px; background:rgba(59,130,246,0.2); border:1px solid rgba(59,130,246,0.4); color:#93c5fd; border-radius:10px; display:flex; align-items:center; justify-content:space-between;">
                <span>Catégories, types de contrat...</span>
                <i class="las la-chevron-right"></i>
            </button>
		</div>
	</div>
	
	<div class="jobiizy-mobile-search-footer">
		<button type="button" class="jobiizy-mobile-search-submit" id="jobiizy-mobile-search-submit">
			<i class="las la-search"></i>
			<span>Lancer la recherche</span>
		</button>
	</div>
</div>

<!-- ============================================================================
     POPUP FILTRES
     ============================================================================ -->
<div class="jobiizy-filters-overlay" id="jobiizy-filters-popup">
	<div class="jobiizy-filters-modal">
		<div class="jobiizy-filters-header">
			<h3><i class="las la-sliders-h"></i> Recherche avancée</h3>
			<button type="button" class="jobiizy-close-filters"><i class="las la-times"></i></button>
		</div>
		
		<div class="jobiizy-filters-body">
			<form class="jobiizy-filter-form">
				
				<div class="jobiizy-filter-field">
					<label><i class="las la-search"></i> Mots-clés</label>
					<input type="text" name="search_keywords" placeholder="Poste, compétences..." value="<?php echo esc_attr( isset($_GET['search_keywords']) ? $_GET['search_keywords'] : '' ); ?>">
				</div>
				
				<div class="jobiizy-filter-field">
					<label><i class="las la-map-marker"></i> Localisation</label>
					<input type="text" name="search_location" placeholder="Ville, région..." value="<?php echo esc_attr( isset($_GET['search_location']) ? $_GET['search_location'] : '' ); ?>">
				</div>
				
				<div class="jobiizy-filter-field">
					<label><i class="las la-folder-open"></i> Catégorie</label>
					<select name="search_categories">
						<option value="">Toutes les catégories</option>
						<?php
						$categories = get_terms(['taxonomy' => 'job_listing_category', 'hide_empty' => true]);
						$selected_cat = isset($_GET['search_categories']) ? $_GET['search_categories'] : '';
						if ($categories && !is_wp_error($categories)) {
							foreach ($categories as $cat) {
								$selected = ($selected_cat === $cat->slug) ? 'selected' : '';
								echo '<option value="' . esc_attr($cat->slug) . '" ' . $selected . '>' . esc_html($cat->name) . '</option>';
							}
						}
						?>
					</select>
				</div>
				
				<div class="jobiizy-filter-field">
					<label><i class="las la-briefcase"></i> Type de contrat</label>
					<div class="jobiizy-checkbox-group">
						<?php
						$job_types = get_terms(['taxonomy' => 'job_listing_type', 'hide_empty' => true]);
						$selected_types = isset($_GET['filter_job_type']) ? (array) $_GET['filter_job_type'] : [];
						if ($job_types && !is_wp_error($job_types)) {
							foreach ($job_types as $type) {
								$checked = in_array($type->slug, $selected_types) ? 'checked' : '';
								echo '<label class="jobiizy-checkbox-label">';
								echo '<input type="checkbox" name="filter_job_type[]" value="' . esc_attr($type->slug) . '" ' . $checked . '>';
								echo '<span>' . esc_html($type->name) . '</span>';
								echo '</label>';
							}
						}
						?>
					</div>
				</div>
				
			</form>
		</div>
		
		<div class="jobiizy-filters-footer">
			<button type="button" class="jobiizy-btn-reset">
				<i class="las la-redo"></i> Réinitialiser
			</button>
			<button type="button" class="jobiizy-btn-apply">
				<i class="las la-check"></i> Appliquer
			</button>
		</div>
	</div>
</div>