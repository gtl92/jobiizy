<?php
/**
 * FILTRE DROPDOWN ENTREPRISES DANS LA LISTE DES JOBS
 * À ajouter dans votre functions.php ou plugin
 */

/**
 * Ajouter un dropdown de filtrage par entreprise dans la liste des jobs
 */
add_action('restrict_manage_posts', 'jobiizy_add_company_filter_to_jobs');

function jobiizy_add_company_filter_to_jobs($post_type) {
    // Ne s'applique que sur la page des jobs
    if ($post_type !== 'job_listing') {
        return;
    }
    
    global $wpdb;
    
    // Récupérer la valeur actuelle du filtre
    $selected_company = isset($_GET['company_filter']) ? intval($_GET['company_filter']) : 0;
    
    // Récupérer toutes les entreprises avec au moins 1 job
    $companies = $wpdb->get_results("
        SELECT DISTINCT p.ID, p.post_title, 
               COUNT(DISTINCT j.ID) as job_count
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm 
            ON pm.post_id = p.ID 
            AND pm.meta_key = '_company_name'
        INNER JOIN {$wpdb->postmeta} jm 
            ON jm.meta_value = p.ID 
            AND jm.meta_key = '_company_manager_id'
        INNER JOIN {$wpdb->posts} j 
            ON j.ID = jm.post_id 
            AND j.post_type = 'job_listing'
        WHERE p.post_type = 'company'
        AND p.post_status IN ('publish', 'pending', 'draft')
        GROUP BY p.ID, p.post_title
        ORDER BY p.post_title ASC
    ");
    
    // Compter le nombre total de jobs filtrables
    $total_jobs = $wpdb->get_var("
        SELECT COUNT(DISTINCT j.ID)
        FROM {$wpdb->posts} j
        WHERE j.post_type = 'job_listing'
    ");
    
    ?>
    <select name="company_filter" id="company-filter" style="min-width: 200px;">
        <option value="0"><?php echo __('Toutes les entreprises', 'jobiizy'); ?> (<?php echo $total_jobs; ?>)</option>
        <?php if ($companies): ?>
            <?php foreach ($companies as $company): ?>
                <option value="<?php echo esc_attr($company->ID); ?>" <?php selected($selected_company, $company->ID); ?>>
                    <?php echo esc_html($company->post_title); ?> (<?php echo $company->job_count; ?>)
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <?php
}

/**
 * Appliquer le filtre sur la requête des jobs
 */
add_filter('parse_query', 'jobiizy_filter_jobs_by_company');

function jobiizy_filter_jobs_by_company($query) {
    global $pagenow, $typenow;
    
    // Ne s'applique que sur la page d'admin des jobs
    if ($pagenow !== 'edit.php' || $typenow !== 'job_listing') {
        return;
    }
    
    // Vérifier si un filtre entreprise est appliqué
    if (isset($_GET['company_filter']) && $_GET['company_filter'] > 0) {
        $company_id = intval($_GET['company_filter']);
        
        // Ajouter la meta_query pour filtrer par entreprise
        $meta_query = array(
            array(
                'key'     => '_company_manager_id',
                'value'   => $company_id,
                'compare' => '='
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
}

/**
 * Afficher un message indiquant le filtre actif
 */
add_action('admin_notices', 'jobiizy_show_company_filter_notice');

function jobiizy_show_company_filter_notice() {
    global $pagenow, $typenow;
    
    // Ne s'applique que sur la page d'admin des jobs
    if ($pagenow !== 'edit.php' || $typenow !== 'job_listing') {
        return;
    }
    
    // Si un filtre entreprise est actif
    if (isset($_GET['company_filter']) && $_GET['company_filter'] > 0) {
        $company_id = intval($_GET['company_filter']);
        $company_name = get_the_title($company_id);
        
        // Compter les jobs de cette entreprise
        global $wpdb;
        $job_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm 
                ON pm.post_id = p.ID 
                AND pm.meta_key = '_company_manager_id'
            WHERE p.post_type = 'job_listing'
            AND pm.meta_value = %d
        ", $company_id));
        
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>🔍 Filtre actif :</strong> 
                Affichage des jobs de l'entreprise 
                <strong><?php echo esc_html($company_name); ?></strong>
                (<?php echo $job_count; ?> <?php echo $job_count > 1 ? 'jobs' : 'job'; ?>)
                
                <a href="<?php echo admin_url('edit.php?post_type=job_listing'); ?>" 
                   style="margin-left: 15px;">
                    ❌ Réinitialiser le filtre
                </a>
            </p>
        </div>
        <?php
    }
}

/**
 * BONUS : Ajouter une colonne "Entreprise" dans la liste des jobs (si pas déjà présente)
 */
// add_filter('manage_job_listing_posts_columns', 'jobiizy_add_company_column_to_jobs');

/* 
function jobiizy_add_company_column_to_jobs($columns) {
    // Vérifier si la colonne n'existe pas déjà
    if (!isset($columns['company'])) {
        // Insérer la colonne "Entreprise" après le titre
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['company'] = __('Entreprise', 'jobiizy');
            }
        }
        return $new_columns;
    }
    
    return $columns;
}
 */

/**
 * Remplir la colonne "Entreprise" avec le nom de l'entreprise
 */
// add_action('manage_job_listing_posts_custom_column', 'jobiizy_fill_company_column_in_jobs', 10, 2);

/* 
function jobiizy_fill_company_column_in_jobs($column_name, $post_id) {
    if ($column_name === 'company') {
        $company_id = get_post_meta($post_id, '_company_manager_id', true);
        
        if ($company_id) {
            $company_name = get_the_title($company_id);
            $company_url = admin_url('post.php?post=' . $company_id . '&action=edit');
            
            // Lien cliquable vers l'entreprise + lien de filtrage
            echo '<a href="' . esc_url($company_url) . '" target="_blank">';
            echo '<strong>' . esc_html($company_name) . '</strong>';
            echo '</a>';
            
            // Lien pour filtrer par cette entreprise
            $filter_url = add_query_arg(
                array(
                    'post_type' => 'job_listing',
                    'company_filter' => $company_id
                ),
                admin_url('edit.php')
            );
            
            echo '<br>';
            echo '<a href="' . esc_url($filter_url) . '" style="font-size: 11px;">';
            echo '🔍 Voir tous les jobs de cette entreprise';
            echo '</a>';
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}
 */

/**
 * Rendre la colonne "Entreprise" triable
 */
// add_filter('manage_edit-job_listing_sortable_columns', 'jobiizy_make_company_column_sortable');

/* 
function jobiizy_make_company_column_sortable($columns) {
    $columns['company'] = 'company_name';
    return $columns;
}
 */

/**
 * Gérer le tri par entreprise
 */
add_action('pre_get_posts', 'jobiizy_sort_jobs_by_company');

function jobiizy_sort_jobs_by_company($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('orderby') === 'company_name') {
        $query->set('meta_key', '_company_name');
        $query->set('orderby', 'meta_value');
    }
}

?>
