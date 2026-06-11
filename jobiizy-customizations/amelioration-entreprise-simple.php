<?php
/**
 * ============================================================
 * AMÉLIORATION AFFICHAGE ENTREPRISE - LISTE DES JOBS
 * ============================================================
 * 
 * Ajoute des actions rapides pour chaque entreprise :
 * - 👁 Voir l'entreprise (page publique)
 * - ✏️ Éditer l'entreprise (admin)
 * - 🔍 Jobs (X) - Filtrer les jobs de l'entreprise
 */

/**
 * Ajouter les actions entreprise dans les row actions
 */
add_filter('post_row_actions', 'jobiizy_add_company_actions_enhanced', 10, 2);

function jobiizy_add_company_actions_enhanced($actions, $post) {
    // Ne s'applique qu'aux jobs
    if ($post->post_type !== 'job_listing') {
        return $actions;
    }
    
    // Récupérer l'ID de l'entreprise
    $company_id = get_post_meta($post->ID, '_company_manager_id', true);
    
    if (!$company_id) {
        return $actions;
    }
    
    // Récupérer le nom de l'entreprise
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
    
    // URLs
    $company_edit_url = admin_url('post.php?post=' . $company_id . '&action=edit');
    $company_view_url = get_permalink($company_id);
    $filter_url = add_query_arg(
        array(
            'post_type' => 'job_listing',
            'company_filter' => $company_id
        ),
        admin_url('edit.php')
    );
    
    // Insérer les nouvelles actions
    $new_actions = array();
    
    foreach ($actions as $key => $action) {
        $new_actions[$key] = $action;
        
        // Après "Voir", ajouter nos actions entreprise
        if ($key === 'view') {
            // Groupe "Entreprise" avec voir + éditer
            $new_actions['company_group'] = sprintf(
                '<span style="color: #666;">Entreprise</span> ' .
                '<a href="%s" target="_blank" title="%s" style="text-decoration: none;">👁</a> ' .
                '<a href="%s" target="_blank" title="%s" style="text-decoration: none;">✏️</a>',
                esc_url($company_view_url),
                esc_attr('Voir la page publique de ' . $company_name),
                esc_url($company_edit_url),
                esc_attr('Éditer l\'entreprise ' . $company_name)
            );
            
            // Action : Filtrer les jobs
            $new_actions['filter_company_jobs'] = sprintf(
                '<a href="%s" title="%s" style="font-weight: 500;">🔍 Jobs (%d)</a>',
                esc_url($filter_url),
                esc_attr(sprintf('Voir les %d jobs de %s', $job_count, $company_name)),
                $job_count
            );
        }
    }
    
    return $new_actions;
}

/**
 * Transformer le nom d'entreprise en lien cliquable via JavaScript
 */
add_action('admin_footer', 'jobiizy_make_company_name_clickable_js_v2');

function jobiizy_make_company_name_clickable_js_v2() {
    global $pagenow, $typenow;
    
    // Seulement sur la page de liste des jobs
    if ($pagenow !== 'edit.php' || $typenow !== 'job_listing') {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Pour chaque ligne du tableau
        $('.wp-list-table tbody tr').each(function() {
            var $row = $(this);
            
            // Trouver les liens entreprise dans les row actions
            var $companyActions = $row.find('.row-actions .company_group');
            
            if ($companyActions.length > 0) {
                // Extraire l'URL d'édition
                var $editLink = $companyActions.find('a[href*="post.php"]').eq(1); // Deuxième lien (✏️)
                
                if ($editLink.length > 0) {
                    var companyEditUrl = $editLink.attr('href');
                    var companyTitle = $editLink.attr('title');
                    
                    // Extraire le nom de l'entreprise du title
                    var companyName = companyTitle.replace('Éditer l\'entreprise ', '');
                    
                    // Trouver le texte du nom d'entreprise sous le titre
                    // Chercher dans plusieurs formats possibles
                    var $titleColumn = $row.find('.column-title');
                    
                    // Pattern 1 : Chercher un élément avec la classe company_name
                    var $companySpan = $titleColumn.find('.company_name');
                    
                    if ($companySpan.length > 0) {
                        var currentText = $companySpan.text().trim();
                        
                        // Remplacer par un lien cliquable
                        $companySpan.html(
                            '<a href="' + companyEditUrl + '" target="_blank" ' +
                            'style="color: #2271b1; text-decoration: none; font-weight: 500;">' +
                            '<span class="dashicons dashicons-building" style="font-size: 16px; vertical-align: middle; width: 16px; height: 16px;"></span> ' +
                            '<strong>' + companyName + '</strong>' +
                            '</a>'
                        );
                    } else {
                        // Pattern 2 : Chercher le texte directement
                        $titleColumn.contents().filter(function() {
                            return this.nodeType === 3; // Text nodes
                        }).each(function() {
                            var text = $(this).text().trim();
                            if (text.indexOf('🏢') === 0 || text === companyName) {
                                $(this).replaceWith(
                                    '<a href="' + companyEditUrl + '" target="_blank" ' +
                                    'class="company-link" ' +
                                    'style="color: #2271b1; text-decoration: none; font-weight: 500; display: inline-block; margin-top: 5px;">' +
                                    '<span class="dashicons dashicons-building" style="font-size: 16px; vertical-align: middle;"></span> ' +
                                    '<strong>' + companyName + '</strong>' +
                                    '</a>'
                                );
                            }
                        });
                    }
                }
            }
        });
    });
    </script>
    
    <style>
    /* Style pour les liens d'entreprise */
    .column-title .company-link:hover,
    .column-title .company_name a:hover {
        color: #135e96 !important;
        text-decoration: underline !important;
    }
    
    /* Améliorer le groupe "Entreprise" */
    .row-actions .company_group {
        display: inline-block;
    }
    
    .row-actions .company_group a {
        font-size: 16px;
        margin: 0 2px;
        transition: transform 0.2s;
        display: inline-block;
    }
    
    .row-actions .company_group a:hover {
        transform: scale(1.2);
    }
    
    /* Mettre en évidence l'action de filtrage */
    .row-actions a[href*="company_filter"] {
        color: #2271b1 !important;
        font-weight: 500;
    }
    
    .row-actions a[href*="company_filter"]:hover {
        color: #135e96 !important;
    }
    
    /* Espacer les groupes d'actions */
    .row-actions .company_group,
    .row-actions .filter_company_jobs {
        margin: 0 5px;
    }
    </style>
    <?php
}

?>