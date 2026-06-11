<?php
/**
 * ============================================================
 * CORRECTIF LOGOS ENTREPRISES - CARIERA THEME
 * ============================================================
 * 
 * Problème : Certaines entreprises ont un logo enregistré mais 
 * il ne s'affiche pas dans la liste des companies.
 * 
 * Cause : Cariera utilise _thumbnail_id mais le logo est dans _company_logo
 * 
 * Solution : Synchroniser _thumbnail_id avec _company_logo
 */

/**
 * OPTION 1 : Corriger les logos existants (une seule fois)
 * Synchroniser _thumbnail_id avec _company_logo pour toutes les entreprises
 */
add_action('admin_init', 'jobiizy_fix_company_logos_sync');

function jobiizy_fix_company_logos_sync() {
    // N'exécuter qu'une seule fois ou sur demande
    if (!isset($_GET['fix_company_logos']) || get_option('jobiizy_logos_fixed_v1')) {
        return;
    }
    
    global $wpdb;
    
    // Récupérer toutes les entreprises
    $companies = $wpdb->get_results("
        SELECT ID, post_title 
        FROM {$wpdb->posts} 
        WHERE post_type = 'company'
        AND post_status IN ('publish', 'draft', 'pending')
    ");
    
    $fixed = 0;
    $errors = array();
    
    foreach ($companies as $company) {
        $company_id = $company->ID;
        
        // Vérifier si _company_logo existe
        $company_logo = get_post_meta($company_id, '_company_logo', true);
        
        if (empty($company_logo)) {
            continue;
        }
        
        // Cas 1 : _company_logo contient une URL complète
        if (filter_var($company_logo, FILTER_VALIDATE_URL)) {
            // Chercher l'attachment ID correspondant à cette URL
            $attachment_id = attachment_url_to_postid($company_logo);
            
            if ($attachment_id) {
                update_post_meta($company_id, '_thumbnail_id', $attachment_id);
                $fixed++;
            } else {
                $errors[] = "Company {$company_id} ({$company->post_title}) : URL trouvée mais pas d'attachment ID";
            }
        }
        // Cas 2 : _company_logo contient déjà un ID
        elseif (is_numeric($company_logo)) {
            update_post_meta($company_id, '_thumbnail_id', $company_logo);
            $fixed++;
        }
    }
    
    // Marquer comme fait
    update_option('jobiizy_logos_fixed_v1', true);
    
    // Afficher le résultat
    echo '<div class="notice notice-success">';
    echo '<h2>✅ Synchronisation des logos terminée</h2>';
    echo '<p><strong>' . $fixed . '</strong> entreprises corrigées sur ' . count($companies) . ' total.</p>';
    
    if (!empty($errors)) {
        echo '<h3>⚠️ Erreurs :</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
    }
    
    echo '<p><a href="' . admin_url('edit.php?post_type=company') . '">Voir les entreprises</a></p>';
    echo '</div>';
    
    exit;
}

/**
 * OPTION 2 : Synchronisation automatique lors de la sauvegarde
 * Quand une entreprise est sauvegardée, synchroniser automatiquement
 */
add_action('save_post_company', 'jobiizy_sync_company_logo_on_save', 20, 1);

function jobiizy_sync_company_logo_on_save($post_id) {
    // Éviter les auto-saves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Récupérer _company_logo
    $company_logo = get_post_meta($post_id, '_company_logo', true);
    
    if (empty($company_logo)) {
        return;
    }
    
    // Si c'est une URL, trouver l'attachment ID
    if (filter_var($company_logo, FILTER_VALIDATE_URL)) {
        $attachment_id = attachment_url_to_postid($company_logo);
        
        if ($attachment_id) {
            update_post_meta($post_id, '_thumbnail_id', $attachment_id);
        }
    }
    // Si c'est déjà un ID
    elseif (is_numeric($company_logo)) {
        update_post_meta($post_id, '_thumbnail_id', $company_logo);
    }
}

/**
 * OPTION 3 : Filtrer l'affichage du logo dans les listes
 * Forcer le bon logo même si _thumbnail_id n'est pas défini
 */
add_filter('get_the_company_logo', 'jobiizy_force_correct_company_logo', 10, 2);

function jobiizy_force_correct_company_logo($logo_url, $post_id = null) {
    // Si on a déjà un logo valide, ne rien changer
    if (!empty($logo_url) && $logo_url !== apply_filters('job_manager_default_company_logo', '')) {
        return $logo_url;
    }
    
    // Sinon, chercher dans _company_logo
    if ($post_id) {
        $company_logo_meta = get_post_meta($post_id, '_company_logo', true);
        
        if (!empty($company_logo_meta)) {
            // Si c'est une URL, la retourner directement
            if (filter_var($company_logo_meta, FILTER_VALIDATE_URL)) {
                return $company_logo_meta;
            }
            
            // Si c'est un attachment ID, récupérer l'URL
            if (is_numeric($company_logo_meta)) {
                $attachment_url = wp_get_attachment_url($company_logo_meta);
                if ($attachment_url) {
                    return $attachment_url;
                }
            }
        }
    }
    
    return $logo_url;
}

/**
 * OPTION 4 : Ajouter une colonne dans l'admin pour diagnostiquer
 */
add_filter('manage_company_posts_columns', 'jobiizy_add_logo_debug_column');

function jobiizy_add_logo_debug_column($columns) {
    // Insérer après le titre
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'title') {
            $new_columns['logo_status'] = '🖼️ Logo';
        }
    }
    
    return $new_columns;
}

add_action('manage_company_posts_custom_column', 'jobiizy_fill_logo_debug_column', 10, 2);

function jobiizy_fill_logo_debug_column($column_name, $post_id) {
    if ($column_name !== 'logo_status') {
        return;
    }
    
    $company_logo = get_post_meta($post_id, '_company_logo', true);
    $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);
    
    // Récupérer le logo affiché
    $displayed_logo = '';
    if (function_exists('get_the_company_logo')) {
        $displayed_logo = get_the_company_logo($post_id);
    }
    
    // Vérifier si le logo par défaut est affiché
    $is_default = (strpos($displayed_logo, 'company.png') !== false);
    
    echo '<div style="display: flex; align-items: center; gap: 10px;">';
    
    // Preview du logo
    if ($displayed_logo && !$is_default) {
        echo '<img src="' . esc_url($displayed_logo) . '" style="width: 40px; height: 40px; object-fit: contain; border: 1px solid #ddd; border-radius: 3px;" />';
        echo '<span style="color: green; font-weight: bold;">✅</span>';
    } else {
        echo '<div style="width: 40px; height: 40px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 3px; display: flex; align-items: center; justify-content: center;">';
        echo '🏢';
        echo '</div>';
        
        if ($company_logo) {
            echo '<span style="color: orange; font-weight: bold;" title="Logo défini mais pas affiché">⚠️</span>';
        } else {
            echo '<span style="color: red;" title="Pas de logo">❌</span>';
        }
    }
    
    echo '</div>';
    
    // Debug info (au survol)
    echo '<div style="font-size: 11px; color: #666; margin-top: 3px;">';
    if ($company_logo) {
        echo 'Meta: ✅';
    }
    if ($thumbnail_id) {
        echo ' | Thumb: ✅';
    }
    if ($is_default) {
        echo ' | <strong style="color: orange;">Défaut affiché</strong>';
    }
    echo '</div>';
}

/**
 * OPTION 5 : Ajouter un bouton de correction dans l'admin
 */
add_action('admin_notices', 'jobiizy_show_logo_fix_notice');

function jobiizy_show_logo_fix_notice() {
    global $pagenow, $typenow;
    
    // Seulement sur la page des companies
    if ($pagenow !== 'edit.php' || $typenow !== 'company') {
        return;
    }
    
    // Si déjà corrigé, ne rien afficher
    if (get_option('jobiizy_logos_fixed_v1')) {
        return;
    }
    
    // Compter les entreprises avec problème
    global $wpdb;
    $problem_count = $wpdb->get_var("
        SELECT COUNT(DISTINCT p.ID)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm1 ON pm1.post_id = p.ID AND pm1.meta_key = '_company_logo'
        LEFT JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_thumbnail_id'
        WHERE p.post_type = 'company'
        AND pm1.meta_value != ''
        AND (pm2.meta_value IS NULL OR pm2.meta_value = '')
    ");
    
    if ($problem_count > 0) {
        ?>
        <div class="notice notice-warning">
            <h3>🖼️ Problème de logos détecté</h3>
            <p>
                <strong><?php echo $problem_count; ?> entreprise(s)</strong> ont un logo défini mais il ne s'affiche pas correctement.
            </p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=company&fix_company_logos=1'); ?>" 
                   class="button button-primary">
                    🔧 Corriger automatiquement les logos
                </a>
            </p>
            <p style="font-size: 12px; color: #666;">
                Cette opération synchronisera _thumbnail_id avec _company_logo pour toutes les entreprises.
            </p>
        </div>
        <?php
    }
}

/**
 * BONUS : Fonction utilitaire pour vérifier un logo spécifique
 */
function jobiizy_debug_company_logo($company_id) {
    $company_logo = get_post_meta($company_id, '_company_logo', true);
    $thumbnail_id = get_post_meta($company_id, '_thumbnail_id', true);
    
    echo '<h3>Debug Logo - Entreprise #' . $company_id . '</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Meta Key</th><th>Valeur</th></tr>';
    echo '<tr><td>_company_logo</td><td>' . esc_html($company_logo) . '</td></tr>';
    echo '<tr><td>_thumbnail_id</td><td>' . esc_html($thumbnail_id) . '</td></tr>';
    
    if (function_exists('get_the_company_logo')) {
        $displayed = get_the_company_logo($company_id);
        echo '<tr><td>Logo affiché</td><td>' . esc_html($displayed) . '</td></tr>';
        echo '<tr><td>Preview</td><td><img src="' . esc_url($displayed) . '" style="max-width: 200px;" /></td></tr>';
    }
    
    echo '</table>';
}

// Activer le debug avec ?debug_logo=12088
add_action('init', function() {
    if (isset($_GET['debug_logo']) && current_user_can('manage_options')) {
        jobiizy_debug_company_logo(intval($_GET['debug_logo']));
        exit;
    }
});

?>
