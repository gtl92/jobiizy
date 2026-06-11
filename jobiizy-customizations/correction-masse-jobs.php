<?php
/**
 * SCRIPT DE CORRECTION EN MASSE : Toutes les entreprises
 * 
 * Ce script analyse et corrige les compteurs _active_jobs pour TOUTES les entreprises
 * du site en une seule opération.
 * 
 * ⚠️ À utiliser avec précaution - fait des modifications en base de données
 */

// Ajouter un sous-menu dans JobiiZy
/* 
add_action('admin_menu', function() {
    add_submenu_page(
        'jobiizy_settings',
        'Correction Masse Jobs',
        '🔧 Correction Masse',
        'manage_options',
        'jobiizy_fix_all_companies',
        'jobiizy_render_fix_all_companies_page'
    );
});
 */

function jobiizy_render_fix_all_companies_page() {
    
    echo '<div class="wrap">';
    echo '<h1>🔧 Correction en masse des compteurs _active_jobs</h1>';
    echo '<p>Cet outil va analyser <strong>toutes les entreprises</strong> et corriger leurs compteurs de jobs si nécessaire.</p>';
    
    // Bouton de lancement
    if (empty($_POST['jobiizy_run_mass_fix'])) {
        echo '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; margin: 20px 0;">';
        echo '<h3>⚠️ Attention</h3>';
        echo '<p>Cette action va :</p>';
        echo '<ul>';
        echo '<li>✅ Scanner toutes les entreprises du site</li>';
        echo '<li>✅ Recalculer le nombre de jobs (total et actifs) pour chaque entreprise</li>';
        echo '<li>✅ Mettre à jour le meta <code>_active_jobs</code> si nécessaire</li>';
        echo '<li>✅ Afficher un rapport détaillé</li>';
        echo '</ul>';
        echo '<p><strong>Durée estimée :</strong> ~1-2 secondes par entreprise</p>';
        echo '</div>';
        
        echo '<form method="POST" action="">';
        echo '<input type="hidden" name="jobiizy_run_mass_fix" value="1">';
        echo '<p><button type="submit" class="button button-primary button-hero" onclick="return confirm(\'Êtes-vous sûr de vouloir lancer la correction en masse ?\')">🚀 Lancer la correction</button></p>';
        echo '</form>';
        
    } else {
        // Lancement de la correction
        echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">';
        echo '<h2>⚙️ Correction en cours...</h2>';
        
        $start_time = microtime(true);
        
        // Récupérer toutes les entreprises
        $companies = get_posts([
            'post_type' => 'company',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $total_companies = count($companies);
        echo '<p>📊 <strong>' . $total_companies . '</strong> entreprise(s) trouvée(s)</p>';
        
        // Stats
        $stats = [
            'processed' => 0,
            'corrected' => 0,
            'already_correct' => 0,
            'no_jobs' => 0,
            'errors' => 0
        ];
        
        $corrections = [];
        
        // Traiter chaque entreprise
        foreach ($companies as $company_id) {
            $stats['processed']++;
            
            try {
                $result = jobiizy_fix_company_jobs_count_v2($company_id);
                
                if ($result['total'] === 0) {
                    $stats['no_jobs']++;
                } elseif ($result['was_corrected']) {
                    $stats['corrected']++;
                    $corrections[] = [
                        'id' => $company_id,
                        'name' => get_the_title($company_id),
                        'old' => $result['old_value'],
                        'new' => $result['new_value']
                    ];
                } else {
                    $stats['already_correct']++;
                }
                
            } catch (Exception $e) {
                $stats['errors']++;
            }
        }
        
        $end_time = microtime(true);
        $duration = round($end_time - $start_time, 2);
        
        echo '<h3>✅ Correction terminée !</h3>';
        echo '<p>⏱️ Durée : <strong>' . $duration . '</strong> secondes</p>';
        
        // Afficher les statistiques
        echo '<h3>📈 Statistiques</h3>';
        echo '<table class="wp-list-table widefat" style="width: auto;">';
        echo '<tr><th>Total d\'entreprises traitées</th><td><strong>' . $stats['processed'] . '</strong></td></tr>';
        echo '<tr><th style="color: #2ECC71;">✅ Compteurs corrigés</th><td style="color: #2ECC71; font-weight: bold;">' . $stats['corrected'] . '</td></tr>';
        echo '<tr><th>✔️ Déjà corrects</th><td>' . $stats['already_correct'] . '</td></tr>';
        echo '<tr><th>➖ Sans jobs</th><td>' . $stats['no_jobs'] . '</td></tr>';
        echo '<tr><th style="color: #E74C3C;">❌ Erreurs</th><td style="color: #E74C3C; font-weight: bold;">' . $stats['errors'] . '</td></tr>';
        echo '</table>';
        
        // Afficher les corrections effectuées
        if (!empty($corrections)) {
            echo '<h3>🔧 Corrections effectuées (' . count($corrections) . ')</h3>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th style="width: 60px;">ID</th>';
            echo '<th>Nom entreprise</th>';
            echo '<th style="width: 150px;">Ancienne valeur</th>';
            echo '<th style="width: 150px;">Nouvelle valeur</th>';
            echo '<th style="width: 100px;">Action</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($corrections as $correction) {
                echo '<tr>';
                echo '<td><strong>' . $correction['id'] . '</strong></td>';
                echo '<td>' . esc_html($correction['name']) . '</td>';
                echo '<td><code>' . esc_html($correction['old']) . '</code></td>';
                echo '<td><code style="color: #2ECC71; font-weight: bold;">' . esc_html($correction['new']) . '</code></td>';
                echo '<td><a href="' . admin_url('admin.php?page=jobiizy_diagnostic_jobs&company_id=' . $correction['id']) . '" class="button button-small">🔍 Détails</a></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '<p style="margin-top: 30px;"><a href="' . admin_url('admin.php?page=jobiizy_fix_all_companies') . '" class="button">🔄 Relancer une analyse</a></p>';
        
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Version améliorée de la fonction de correction qui retourne plus d'infos
 */
function jobiizy_fix_company_jobs_count_v2($company_id) {
    
    // Récupérer la valeur actuelle
    $old_meta = get_post_meta($company_id, '_active_jobs', true);
    
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
    
    // Compter les jobs visibles
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
    
    // Comparer avec l'ancienne valeur
    $old_total = 0;
    $old_visible = 0;
    
    if (is_array($old_meta) && count($old_meta) === 2) {
        $old_total = intval($old_meta[0]);
        $old_visible = intval($old_meta[1]);
    } elseif (is_numeric($old_meta)) {
        $old_total = intval($old_meta);
    }
    
    $was_corrected = ($old_total !== $total || $old_visible !== $visible);
    
    // Mettre à jour uniquement si nécessaire
    if ($was_corrected) {
        update_post_meta($company_id, '_active_jobs', [$total, $visible]);
    }
    
    return [
        'total' => $total,
        'visible' => $visible,
        'was_corrected' => $was_corrected,
        'old_value' => is_array($old_meta) ? '[' . implode(', ', $old_meta) . ']' : $old_meta,
        'new_value' => '[' . $total . ', ' . $visible . ']'
    ];
}

/**
 * Ajouter un lien dans le menu principal
 */
/*
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!current_user_can('manage_options')) return;
    
    $wp_admin_bar->add_node([
        'id' => 'jobiizy-fix-all',
        'title' => '🔧 Corriger tous les compteurs',
        'href' => admin_url('admin.php?page=jobiizy_fix_all_companies'),
        'parent' => false
    ]);
}, 999);
*/
