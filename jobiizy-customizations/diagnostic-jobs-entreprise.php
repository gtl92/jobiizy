<?php
/**
 * SCRIPT DE DIAGNOSTIC : Jobs d'une entreprise
 * 
 * À placer dans /wp-content/plugins/jobiizy-customization/
 * Accès : https://jobiizy.com/wp-admin/admin.php?page=jobiizy_diagnostic_jobs
 * 
 * Ce script permet d'analyser en détail les jobs liés à une entreprise
 * et de corriger le compteur _active_jobs si nécessaire.
 */

// Ajouter le menu dans l'admin
// Menu reporté dans admin-menus.php 

/* add_action('admin_menu', function() {
    add_submenu_page(
        'jobiizy_settings',
        'Diagnostic Jobs Entreprise',
        '🔍 Diagnostic Jobs',
        'manage_options',
        'jobiizy_diagnostic_jobs',
        'jobiizy_render_diagnostic_jobs_page'
    );
}); */

function jobiizy_render_diagnostic_jobs_page() {
    echo '<div class="wrap">';
    echo '<h1>🔍 Diagnostic des Jobs par Entreprise</h1>';
    
    // Formulaire de recherche
    echo '<form method="GET" action="" style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">';
    echo '<input type="hidden" name="page" value="jobiizy_diagnostic_jobs">';
    echo '<h3>Rechercher une entreprise</h3>';
    echo '<p>';
    echo '<label for="company_id">ID de l\'entreprise : </label>';
    echo '<input type="number" name="company_id" id="company_id" value="' . esc_attr($_GET['company_id'] ?? '') . '" placeholder="Ex: 12661">';
    echo ' <button type="submit" class="button button-primary">🔍 Analyser</button>';
    echo '</p>';
    echo '</form>';
    
    // Si un ID est fourni
    if (!empty($_GET['company_id'])) {
        $company_id = intval($_GET['company_id']);
        jobiizy_analyze_company_jobs($company_id);
    }
    
    // Action de correction
    if (!empty($_POST['fix_company_id'])) {
        $company_id = intval($_POST['fix_company_id']);
        jobiizy_fix_company_jobs_count($company_id);
        echo '<div class="notice notice-success"><p>✅ Compteur mis à jour pour l\'entreprise #' . $company_id . '</p></div>';
        jobiizy_analyze_company_jobs($company_id);
    }
    
    echo '</div>';
}

function jobiizy_analyze_company_jobs($company_id) {
    
    // Vérifier que l'entreprise existe
    $company = get_post($company_id);
    if (!$company || $company->post_type !== 'company') {
        echo '<div class="notice notice-error"><p>❌ Entreprise introuvable avec l\'ID : ' . $company_id . '</p></div>';
        return;
    }
    
    $company_name = get_post_meta($company_id, '_company_name', true) ?: get_the_title($company_id);
    
    echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">';
    echo '<h2>📊 Analyse de : ' . esc_html($company_name) . ' <small>(#' . $company_id . ')</small></h2>';
    
    // Récupérer le meta actuel
    $active_jobs_meta = get_post_meta($company_id, '_active_jobs', true);
    
    echo '<h3>📋 Meta _active_jobs actuel</h3>';
    echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
    if (is_array($active_jobs_meta)) {
        echo 'Type : Array' . "\n";
        echo 'Contenu : ';
        print_r($active_jobs_meta);
        if (count($active_jobs_meta) === 2) {
            echo "\n📌 Format détecté : [total, actifs] = [" . $active_jobs_meta[0] . ', ' . $active_jobs_meta[1] . ']';
        }
    } elseif (is_numeric($active_jobs_meta)) {
        echo 'Type : Nombre' . "\n";
        echo 'Valeur : ' . $active_jobs_meta;
    } elseif (empty($active_jobs_meta)) {
        echo 'Type : Vide / Non défini';
    } else {
        echo 'Type : Autre' . "\n";
        echo 'Valeur : ' . var_export($active_jobs_meta, true);
    }
    echo '</pre>';
    
    // Rechercher tous les jobs liés à cette entreprise
    echo '<h3>🔎 Recherche des jobs liés</h3>';
    
    // Méthode 1 : via _company_id
    $args_company_id = [
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => '_company_id',
                'value' => $company_id,
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ];
    $jobs_by_company_id = get_posts($args_company_id);
    
    // Méthode 2 : via _company_manager_id
    $args_manager_id = [
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => '_company_manager_id',
                'value' => $company_id,
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ];
    $jobs_by_manager_id = get_posts($args_manager_id);
    
    // Fusionner et dédupliquer
    $all_job_ids = array_unique(array_merge($jobs_by_company_id, $jobs_by_manager_id));
    
    echo '<p>✅ <strong>' . count($all_job_ids) . '</strong> job(s) trouvé(s) au total</p>';
    echo '<ul>';
    echo '<li>' . count($jobs_by_company_id) . ' via meta <code>_company_id</code></li>';
    echo '<li>' . count($jobs_by_manager_id) . ' via meta <code>_company_manager_id</code></li>';
    echo '</ul>';
    
    if (empty($all_job_ids)) {
        echo '<div class="notice notice-warning"><p>⚠️ Aucun job trouvé pour cette entreprise</p></div>';
        echo '</div>';
        return;
    }
    
    // Analyser chaque job en détail
    echo '<h3>📝 Détail des jobs</h3>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 60px;">ID</th>';
    echo '<th>Titre</th>';
    echo '<th style="width: 100px;">Statut</th>';
    echo '<th style="width: 120px;">Date fin</th>';
    echo '<th style="width: 80px;">Expiré ?</th>';
    echo '<th style="width: 100px;">Visibilité</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $stats = [
        'total' => count($all_job_ids),
        'publish' => 0,
        'pending' => 0,
        'draft' => 0,
        'expired' => 0,
        'visible' => 0
    ];
    
    $now = current_time('timestamp');
    
    foreach ($all_job_ids as $job_id) {
        $job = get_post($job_id);
        $job_title = get_the_title($job_id);
        $job_status = $job->post_status;
        $job_expiry = get_post_meta($job_id, '_job_expires', true);
        
        // Déterminer si expiré
        $is_expired = false;
        $expiry_display = '—';
        if ($job_expiry) {
            $expiry_timestamp = strtotime($job_expiry);
            $is_expired = ($expiry_timestamp < $now);
            $expiry_display = date('d/m/Y', $expiry_timestamp);
            if ($is_expired) {
                $stats['expired']++;
            }
        }
        
        // Compter les statuts
        if ($job_status === 'publish') $stats['publish']++;
        if ($job_status === 'pending') $stats['pending']++;
        if ($job_status === 'draft') $stats['draft']++;
        
        // Déterminer si visible sur le site
        $is_visible = ($job_status === 'publish' && !$is_expired);
        if ($is_visible) $stats['visible']++;
        
        // Couleurs
        $status_color = [
            'publish' => '#2ECC71',
            'pending' => '#F39C12',
            'draft' => '#95A5A6',
            'trash' => '#E74C3C'
        ][$job_status] ?? '#333';
        
        $expiry_color = $is_expired ? '#E74C3C' : '#2ECC71';
        $visibility_color = $is_visible ? '#2ECC71' : '#E74C3C';
        
        echo '<tr>';
        echo '<td><strong>' . $job_id . '</strong></td>';
        echo '<td><a href="' . admin_url("post.php?post={$job_id}&action=edit") . '" target="_blank">' . esc_html($job_title) . '</a></td>';
        echo '<td><span style="color: ' . $status_color . '; font-weight: bold;">' . $job_status . '</span></td>';
        echo '<td>' . $expiry_display . '</td>';
        echo '<td><span style="color: ' . $expiry_color . '; font-weight: bold;">' . ($is_expired ? '❌ Oui' : '✅ Non') . '</span></td>';
        echo '<td><span style="color: ' . $visibility_color . '; font-weight: bold;">' . ($is_visible ? '🟢 Visible' : '🔴 Caché') . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Statistiques récapitulatives
    echo '<h3>📈 Statistiques</h3>';
    echo '<table class="wp-list-table widefat" style="width: auto;">';
    echo '<tr><th>Total de jobs</th><td><strong>' . $stats['total'] . '</strong></td></tr>';
    echo '<tr><th>Status "publish"</th><td style="color: #2ECC71; font-weight: bold;">' . $stats['publish'] . '</td></tr>';
    echo '<tr><th>Status "pending"</th><td style="color: #F39C12; font-weight: bold;">' . $stats['pending'] . '</td></tr>';
    echo '<tr><th>Status "draft"</th><td style="color: #95A5A6; font-weight: bold;">' . $stats['draft'] . '</td></tr>';
    echo '<tr><th>Jobs expirés</th><td style="color: #E74C3C; font-weight: bold;">' . $stats['expired'] . '</td></tr>';
    echo '<tr><th><strong>🟢 Jobs VISIBLES sur le site</strong></th><td style="color: #2ECC71; font-weight: bold; font-size: 16px;">' . $stats['visible'] . '</td></tr>';
    echo '</table>';
    
    // Comparaison avec le meta
    echo '<h3>⚖️ Comparaison</h3>';
    echo '<table class="wp-list-table widefat" style="width: auto;">';
    
    if (is_array($active_jobs_meta) && count($active_jobs_meta) === 2) {
        $meta_total = intval($active_jobs_meta[0]);
        $meta_active = intval($active_jobs_meta[1]);
    } elseif (is_numeric($active_jobs_meta)) {
        $meta_total = intval($active_jobs_meta);
        $meta_active = 0;
    } else {
        $meta_total = 0;
        $meta_active = 0;
    }
    
    $total_match = ($meta_total === $stats['total']);
    $active_match = ($meta_active === $stats['visible']);
    
    echo '<tr>';
    echo '<th>Meta _active_jobs[0] (total)</th>';
    echo '<td><strong>' . $meta_total . '</strong></td>';
    echo '<td>' . ($total_match ? '<span style="color: #2ECC71;">✅ Correct</span>' : '<span style="color: #E74C3C;">❌ Incorrect (devrait être ' . $stats['total'] . ')</span>') . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th>Meta _active_jobs[1] (actifs)</th>';
    echo '<td><strong>' . $meta_active . '</strong></td>';
    echo '<td>' . ($active_match ? '<span style="color: #2ECC71;">✅ Correct</span>' : '<span style="color: #E74C3C;">❌ Incorrect (devrait être ' . $stats['visible'] . ')</span>') . '</td>';
    echo '</tr>';
    echo '</table>';
    
    // Bouton de correction
    if (!$total_match || !$active_match) {
        echo '<form method="POST" action="" style="margin-top: 20px;">';
        echo '<input type="hidden" name="fix_company_id" value="' . $company_id . '">';
        echo '<button type="submit" class="button button-primary button-large">🔧 Corriger le compteur _active_jobs</button>';
        echo '<p><em>Cela mettra à jour le meta pour refléter les valeurs correctes : [' . $stats['total'] . ', ' . $stats['visible'] . ']</em></p>';
        echo '</form>';
    } else {
        echo '<div class="notice notice-success inline" style="margin-top: 20px;"><p>✅ Le compteur est déjà correct !</p></div>';
    }
    
    echo '</div>';
}

function jobiizy_fix_company_jobs_count($company_id) {
    
    // Récupérer tous les jobs
    $args = [
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => '_company_id',
                'value' => $company_id,
                'compare' => '='
            ],
            [
                'key' => '_company_manager_id',
                'value' => $company_id,
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ];
    
    $all_job_ids = get_posts($args);
    $total = count($all_job_ids);
    $visible = 0;
    
    $now = current_time('timestamp');
    
    foreach ($all_job_ids as $job_id) {
        $job = get_post($job_id);
        $job_expiry = get_post_meta($job_id, '_job_expires', true);
        
        $is_expired = false;
        if ($job_expiry) {
            $expiry_timestamp = strtotime($job_expiry);
            $is_expired = ($expiry_timestamp < $now);
        }
        
        if ($job->post_status === 'publish' && !$is_expired) {
            $visible++;
        }
    }
    
    // Mettre à jour le meta
    update_post_meta($company_id, '_active_jobs', [$total, $visible]);
    
    return ['total' => $total, 'visible' => $visible];
}

/**
 * AJOUT D'UN BOUTON "DIAGNOSTIC" DANS LA LISTE DES ENTREPRISES
 */
add_filter('post_row_actions', function($actions, $post) {
    if ($post->post_type === 'company') {
        $diagnostic_url = admin_url('admin.php?page=jobiizy_diagnostic_jobs&company_id=' . $post->ID);
        $actions['diagnostic'] = '<a href="' . esc_url($diagnostic_url) . '">🔍 Diagnostic</a>';
    }
    return $actions;
}, 10, 2);
