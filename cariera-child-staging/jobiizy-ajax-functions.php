<?php
/**
 * JobiiZy - AJAX Handler pour Infinite Scroll
 * Ajouter ce code dans le fichier functions.php de cariera-child
 * Ou dans un fichier séparé inclus par functions.php
 */

/**
 * Handler AJAX pour charger plus d'offres d'emploi
 */
add_action( 'wp_ajax_jobiizy_load_more_jobs', 'jobiizy_load_more_jobs_handler' );
add_action( 'wp_ajax_nopriv_jobiizy_load_more_jobs', 'jobiizy_load_more_jobs_handler' );

function jobiizy_load_more_jobs_handler() {
    // Vérifier le nonce
 // TEST TEMPORAIRE - désactiver nonce
// if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'jobiizy_nonce' ) ) {
//        wp_send_json_error( ['message' => 'Nonce invalide'] );
//        return;
//    }
	
	// Vérifier le nonce (GET ou POST)
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : (isset($_GET['nonce']) ? $_GET['nonce'] : '');
    if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'jobiizy_nonce' ) ) {
        wp_send_json_error( ['message' => 'Nonce invalide'] );
        return;
    }
    
    // Récupérer les paramètres
    $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
    $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;
    $search_keywords = isset( $_POST['search_keywords'] ) ? sanitize_text_field( $_POST['search_keywords'] ) : '';
    $search_location = isset( $_POST['search_location'] ) ? sanitize_text_field( $_POST['search_location'] ) : '';
    $search_categories = isset( $_POST['search_categories'] ) ? sanitize_text_field( $_POST['search_categories'] ) : '';
    $filter_job_type = isset( $_POST['filter_job_type'] ) ? sanitize_text_field( $_POST['filter_job_type'] ) : '';
    
    // Construire la query
    $args = [
        'post_type'           => 'job_listing',
        'post_status'         => 'publish',
        'ignore_sticky_posts' => 1,
        'posts_per_page'      => $per_page,
        'paged'               => $page,
        'orderby'             => 'featured',
        'order'               => 'DESC',
    ];
    
    // Filtre mots-clés
    if ( ! empty( $search_keywords ) ) {
        $args['s'] = $search_keywords;
    }
    
    // Filtre localisation
    if ( ! empty( $search_location ) ) {
        $args['meta_query'][] = [
            'key'     => '_job_location',
            'value'   => $search_location,
            'compare' => 'LIKE',
        ];
    }
    
    // Filtre catégories
    if ( ! empty( $search_categories ) ) {
        $args['tax_query'][] = [
            'taxonomy' => 'job_listing_category',
            'field'    => 'slug',
            'terms'    => $search_categories,
        ];
    }
    
    // Filtre types de contrat
    if ( ! empty( $filter_job_type ) ) {
        $job_types = explode( ',', $filter_job_type );
        $args['tax_query'][] = [
            'taxonomy' => 'job_listing_type',
            'field'    => 'slug',
            'terms'    => $job_types,
        ];
    }
    
    if ( isset( $args['tax_query'] ) && count( $args['tax_query'] ) > 1 ) {
        $args['tax_query']['relation'] = 'AND';
    }
    
    $query = new WP_Query( $args );
    
    if ( ! $query->have_posts() ) {
        wp_send_json_error( ['message' => 'Aucune offre supplémentaire'] );
        return;
    }
    
    // Générer le HTML des cards
    ob_start();
    $count = 0;
    
    while ( $query->have_posts() ) : $query->the_post();
        $count++;
        $post_id = get_the_ID();
        $location = get_post_meta( $post_id, '_job_location', true );
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
                    <?php if ( $location ) : ?>
                        <span class="job-location"><i class="las la-map-marker"></i> <?php echo esc_html( $location ); ?></span>
                    <?php endif; ?>
                    <?php if ( $job_type ) : ?>
                        <span class="job-type"><i class="las la-clock"></i> <?php echo esc_html( $job_type ); ?></span>
                    <?php endif; ?>
                    <span class="job-date"><i class="las la-calendar"></i> Il y a <?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ); ?></span>
                </div>
                <?php 
                $excerpt = get_the_excerpt();
                if ( $excerpt ) : ?>
                    <div class="job-excerpt"><?php echo wp_trim_words( $excerpt, 15, '...' ); ?></div>
                <?php endif; ?>
                <div class="job-card-footer">
                    <button class="btn-view-job">Voir l'offre <i class="las la-arrow-right"></i></button>
                </div>
            </div>
        </article>
        <?php
    endwhile;
    wp_reset_postdata();
    
    $html = ob_get_clean();
    
    wp_send_json_success([
        'html'  => $html,
        'count' => $count,
        'page'  => $page,
        'max'   => $query->max_num_pages,
    ]);
}

/**
 * Script pour forcer overflow visible sur les conteneurs Elementor
 * (Backup JavaScript si le CSS ne suffit pas)
 */
add_action( 'wp_footer', 'jobiizy_fix_sticky_overflow', 999 );

function jobiizy_fix_sticky_overflow() {
    // Seulement sur la page des jobs
    if ( ! is_page( 'les-jobs' ) && ! is_page( 'emplois' ) ) {
        return;
    }
    ?>
    <script>
    (function() {
        // Attendre que le DOM soit prêt
        function fixStickyOverflow() {
            var stickyBar = document.querySelector('.jobiizy-search-bar-sticky');
            if (!stickyBar) return;
            
            // Remonter tous les parents et forcer overflow visible
            var parent = stickyBar.parentElement;
            while (parent && parent !== document.body) {
                var style = window.getComputedStyle(parent);
                if (style.overflow !== 'visible' || style.overflowY !== 'visible' || style.overflowX !== 'visible') {
                    parent.style.overflow = 'visible';
                    parent.style.overflowX = 'visible';
                    parent.style.overflowY = 'visible';
                }
                parent = parent.parentElement;
            }
            
            console.log('✅ [JobiiZy] Fix sticky overflow appliqué');
        }
        
        // Exécuter après chargement complet
        if (document.readyState === 'complete') {
            fixStickyOverflow();
        } else {
            window.addEventListener('load', fixStickyOverflow);
        }
        
        // Re-exécuter après Elementor (au cas où)
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(fixStickyOverflow, 500);
            setTimeout(fixStickyOverflow, 1500);
        });
    })();
    </script>
    <?php
}