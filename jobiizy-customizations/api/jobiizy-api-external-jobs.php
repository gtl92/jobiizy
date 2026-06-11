<?php
/**
 * ============================================================================
 * JOBIIZY CUSTOMIZATIONS – MODULE API OFFRES EXTERNES (OPTIONCARRIERE)
 * ============================================================================
 *
 * Module complet et autonome permettant :
 *   - La configuration des identifiants API OptionCarriere
 *   - Le mode démo (prévisualisation d'offres simulées)
 *   - Le test de connexion API
 *   - La recherche d'offres en direct
 *   - L’affichage des résultats dans une table WP propre
 *
 * Chargé depuis Jobiizy-function.php :
 *     require_once __DIR__ . '/api/jobiizy-api-external-jobs.php';
 *
 * Menus déclarés dans admin-menus.php.
 *
 * ============================================================================
 */

if (!defined('ABSPATH')) exit;

/*=============================================================================
 * PAGE ADMIN : OFFRES EXTERNES (API)
 *============================================================================*/
/*
function jobiizy_render_external_jobs_page() {
    ?>
    <div class="wrap">
        <h1 style="margin-bottom:10px;">🌐 Offres externes — API OptionCarriere</h1>
        <p style="max-width:800px;">
            Cette page vous permet de configurer l’accès à l’API OptionCarriere,
            tester la connexion et récupérer des offres en direct avec un affichage
            clair et structuré.
        </p>

        <hr>

        <!-- ============================================================
             SECTION : Réglages (Partner ID / Partner Key)
        ============================================================ -->
        <h2>🔑 Paramètres API</h2>

        <form method="post" action="options.php" style="margin-top:20px;">
            <?php
            settings_fields('jobiizy_external_jobs_group');
            do_settings_sections('jobiizy-external-jobs');
            submit_button('Enregistrer les clés API');
            ?>
        </form>

        <hr>

        <!-- ============================================================
             SECTION : Mode Démo
        ============================================================ -->
        <h2>🧰 Mode démo</h2>

        <form method="post" style="margin-top:10px;">
            <input type="hidden" name="jobiizy_toggle_demo" value="1">
            <label>
                <input type="checkbox" name="demo_mode" value="1"
                    <?php checked('1', get_option('jobiizy_demo_mode', '0')); ?>>
                Activer le mode démo (affiche des offres simulées)
            </label>
            <?php submit_button('Mettre à jour', 'secondary', 'submit', false); ?>
        </form>

        <?php if (!empty($_POST['jobiizy_toggle_demo'])) : ?>
            <div class="updated notice"><p>⚙️ Mode démo mis à jour.</p></div>
        <?php endif; ?>

        <hr>

        <!-- ============================================================
             SECTION : Test de connexion API
        ============================================================ -->
        <h2>🔄 Tester la connexion API</h2>

        <form method="post">
            <input type="hidden" name="jobiizy_test_connection" value="1">
            <?php submit_button('Tester maintenant', 'secondary'); ?>
        </form>

        <?php
        if (!empty($_POST['jobiizy_test_connection'])) {
            jobiizy_test_api_connection();
        }
        ?>

        <hr>

        <!-- ============================================================
             SECTION : Récupération des offres
        ============================================================ -->
        <h2>📥 Récupérer des offres</h2>

        <form method="post" style="margin-top:20px;">
            <input type="hidden" name="jobiizy_fetch_jobs" value="1">

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="search_keyword">Mot-clé</label></th>
                    <td><input class="regular-text" type="text" name="search_keyword" id="search_keyword"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="search_location">Localisation</label></th>
                    <td><input class="regular-text" type="text" name="search_location" id="search_location"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_results">Nombre max</label></th>
                    <td><input type="number" name="max_results" id="max_results" value="10" min="1" max="50"></td>
                </tr>
            </table>

            <?php submit_button('Obtenir les offres', 'primary'); ?>
        </form>

        <?php
        if (!empty($_POST['jobiizy_fetch_jobs'])) {
            jobiizy_fetch_and_display_jobs();
        }
        ?>
    </div>
    <?php
}
*/
function jobiizy_render_external_jobs_page() {
    echo '<div class="wrap">';
    echo '<h1>🌐 Offres externes (API OptionCarriere)</h1>';
    echo '<p>Configurez vos identifiants API et testez la récupération d’offres en direct.</p>';

    // --- Formulaire d'enregistrement des clés API ---
    echo '<h2>🔑 Clés API</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields('jobiizy_external_jobs_group');
    do_settings_sections('jobiizy-external-jobs');
    submit_button('Enregistrer les clés');
    echo '</form>';

    // --- Mode démo ---
    $demo_mode = get_option('jobiizy_demo_mode', '0');
    echo '<hr><h2>🧰 Mode Démo</h2>';
    echo '<form method="post">';
    echo '<input type="hidden" name="jobiizy_toggle_demo" value="1">';
    echo '<label><input type="checkbox" name="demo_mode" value="1" ' . checked('1', $demo_mode, false) . '> Activer le mode démo (utiliser des données simulées)</label>';
    submit_button('Mettre à jour', 'secondary');
    echo '</form>';

    if (!empty($_POST['jobiizy_toggle_demo'])) {
        update_option('jobiizy_demo_mode', isset($_POST['demo_mode']) ? '1' : '0');
        echo '<div class="updated notice"><p>⚙️ Mode démo mis à jour.</p></div>';
    }

    // --- Test de connexion API ---
    echo '<hr><h2>🔄 Tester la connexion API</h2>';
    echo '<form method="post">';
    echo '<input type="hidden" name="jobiizy_test_connection" value="1">';
    submit_button('Tester la connexion API OptionCarriere', 'secondary');
    echo '</form>';

    if (!empty($_POST['jobiizy_test_connection'])) {
        $partner_id = get_option('jobiizy_api_partner_id');
        $api_key = get_option('jobiizy_api_key');
        $demo_mode = get_option('jobiizy_demo_mode', '0');

        if ($demo_mode === '1') {
            echo '<div class="notice notice-success"><p>✅ Mode démo actif — les résultats affichés sont simulés.</p></div>';
        } elseif (!$partner_id || !$api_key) {
            echo '<div class="notice notice-error"><p>⚠️ Vous devez d’abord renseigner vos identifiants API avant de tester la connexion.</p></div>';
        } else {
            $test_url = "https://api.optioncarriere.com/api/v2/jobs?partnerid={$partner_id}&key={$api_key}&q=test&l=France&limit=1";
            $response = wp_remote_get($test_url);
            $code = wp_remote_retrieve_response_code($response);

            if (is_wp_error($response)) {
                echo '<div class="notice notice-error"><p>❌ Erreur de connexion : ' . esc_html($response->get_error_message()) . '</p></div>';
            } elseif ($code === 403) {
                $server_ip = jobiizy_get_server_ip();
                echo '<div class="notice notice-error"><p>🚫 Accès refusé (403). Vérifiez que l’adresse IP du serveur est bien autorisée chez OptionCarriere : <code>' . esc_html($server_ip ?? 'IP inconnue') . '</code></p></div>';
            } elseif ($code === 200) {
                echo '<div class="notice notice-success"><p>✅ Connexion réussie à l’API OptionCarriere !</p></div>';
            } else {
                echo '<div class="notice notice-warning"><p>⚠️ Réponse inattendue (' . esc_html($code) . ').</p></div>';
            }
        }
    }

    // --- Formulaire de test API ou simulation ---
    echo '<hr><h2>🧪 Test de récupération d’offres</h2>';
    echo '<form method="post">';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th scope="row"><label for="keyword">Mot-clé</label></th>';
    echo '<td><input type="text" name="keyword" id="keyword" value="' . esc_attr($_POST['keyword'] ?? '') . '" class="regular-text" placeholder="ex : développeur, comptable..."></td></tr>';
    echo '<tr><th scope="row"><label for="location">Localisation</label></th>';
    echo '<td><input type="text" name="location" id="location" value="' . esc_attr($_POST['location'] ?? 'France') . '" class="regular-text" placeholder="ex : Paris, Lyon, France..."></td></tr>';
    echo '</tbody></table>';
    submit_button('Tester l’API OptionCarriere');
    echo '</form>';

    if (!empty($_POST['keyword']) && !empty($_POST['location'])) {
        $keyword = sanitize_text_field($_POST['keyword']);
        $location = sanitize_text_field($_POST['location']);
        $partner_id = get_option('jobiizy_api_partner_id');
        $api_key = get_option('jobiizy_api_key');
        $demo_mode = get_option('jobiizy_demo_mode', '0');

        if ($demo_mode === '1') {
            $body = jobiizy_get_fake_jobs();
        } else {
            $url = "https://api.optioncarriere.com/api/v2/jobs?partnerid={$partner_id}&key={$api_key}&q=" . urlencode($keyword) . "&l=" . urlencode($location) . "&limit=5";
            $response = wp_remote_get($url);
            $body = is_wp_error($response) ? [] : json_decode(wp_remote_retrieve_body($response), true);
        }

        if (empty($body['jobs'])) {
            echo '<div class="notice notice-warning"><p>Aucune offre trouvée — affichage des données simulées.</p></div>';
            $body = jobiizy_get_fake_jobs();
        }

        echo '<h3>Résultats :</h3><table class="widefat fixed striped">';
        echo '<thead><tr><th>Titre</th><th>Entreprise</th><th>Lieu</th><th>Source</th></tr></thead><tbody>';
        foreach ($body['jobs'] as $job) {
            echo '<tr>';
            echo '<td><a href="' . esc_url($job['url']) . '" target="_blank">' . esc_html($job['title']) . '</a></td>';
            echo '<td>' . esc_html($job['company'] ?? '-') . '</td>';
            echo '<td>' . esc_html($job['location'] ?? '-') . '</td>';
            echo '<td>' . esc_html($job['source'] ?? '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    // --- IP publique du serveur ---
    $server_ip = jobiizy_get_server_ip();
    echo '<hr><h2>🌍 Adresse IP du serveur</h2>';
    echo '<p>Adresse IP publique utilisée pour les appels sortants :<br>';
    echo '<code style="font-size:16px;color:#2271b1;">' . esc_html($server_ip ?: 'Indéterminée') . '</code></p>';
    echo '<p><em>C’est cette adresse que vous devez transmettre à OptionCarriere pour autoriser les requêtes API.</em></p>';

    echo '</div>';
} 

// --- Fonction utilitaire : récupérer IP publique ---
function jobiizy_get_server_ip() {
    $response = wp_remote_get('https://api.ipify.org?format=json');
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data['ip'] ?? '';
    }
    return '';
}

// --- Fonction utilitaire : fausses offres pour le mode démo ---
function jobiizy_get_fake_jobs() {
    return [
        'jobs' => [
            ['title'=>'Développeur Full Stack (F/H)','company'=>'TechNova','location'=>'Paris (75)','url'=>'#','source'=>'OptionCarriere (simulé)'],
            ['title'=>'Chef de projet digital','company'=>'Innova Conseil','location'=>'Lyon (69)','url'=>'#','source'=>'OptionCarriere (simulé)'],
            ['title'=>'Data Analyst Junior','company'=>'DataCorp','location'=>'Toulouse (31)','url'=>'#','source'=>'OptionCarriere (simulé)'],
            ['title'=>'UX/UI Designer','company'=>'Creative Minds','location'=>'Bordeaux (33)','url'=>'#','source'=>'OptionCarriere (simulé)'],
            ['title'=>'Administrateur Systèmes & Réseaux','company'=>'InfraTech','location'=>'Lille (59)','url'=>'#','source'=>'OptionCarriere (simulé)']
        ]
    ];
}
/*=============================================================================
 * TEST API
 *============================================================================*/
function jobiizy_test_api_connection() {
    $partner_id = get_option('jobiizy_api_partner_id');
    $partner_key = get_option('jobiizy_api_partner_key');
if (empty($partner_id)) {
    // Si l’API ne requiert que la clé, on peut réutiliser la clé comme “publisher”
    $params['publisher'] = $partner_key;
} else {
    $params['publisher'] = $partner_id;
}
$params['partner_key'] = $partner_key;
    if (get_option('jobiizy_demo_mode') === '1') {
        echo '<div class="notice notice-info"><p>🧰 Mode démo activé — test simulé.</p></div>';
        echo '<div class="updated notice"><p>✅ Connexion API simulée : OK</p></div>';
        return;
    }

    if (empty($partner_id) || empty($partner_key)) {
        echo '<div class="notice notice-error"><p>❌ Identifiants API manquants.</p></div>';
        return;
    }

    $params = [
        'publisher'   => $partner_id,
        'partner_key' => $partner_key,
        'what'        => 'test',
        'format'      => 'json',
        'limit'       => 1
    ];

    $response = wp_remote_get(
        add_query_arg($params, "https://api.optioncarriere.com/jobs/1.0/search/jobs"),
        ['timeout' => 15]
    );

    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>❌ Connexion impossible : ' .
            esc_html($response->get_error_message()) . '</p></div>';
        return;
    }

    if (wp_remote_retrieve_response_code($response) === 200) {
        echo '<div class="notice notice-success"><p>✅ Connexion API réussie !</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Erreur API — code : ' .
            wp_remote_retrieve_response_code($response) . '</p></div>';
    }
}

/*=============================================================================
 * RÉCUPÉRATION D’OFFRES
 *============================================================================*/
function jobiizy_fetch_and_display_jobs() {

    $keyword     = sanitize_text_field($_POST['search_keyword'] ?? '');
    $location    = sanitize_text_field($_POST['search_location'] ?? '');
    $max_results = absint($_POST['max_results'] ?? 10);

    if (get_option('jobiizy_demo_mode') === '1') {
        return jobiizy_display_demo_jobs($keyword, $location, $max_results);
    }

    $partner_id  = get_option('jobiizy_api_partner_id');
    $partner_key = get_option('jobiizy_api_partner_key');

    if (!$partner_id || !$partner_key) {
        echo '<div class="notice notice-error"><p>❌ Identifiants API manquants.</p></div>';
        return;
    }

    $params = [
        'publisher'   => $partner_id,
        'partner_key' => $partner_key,
        'what'        => $keyword,
        'where'       => $location,
        'format'      => 'json',
        'limit'       => $max_results
    ];

    $response = wp_remote_get(
        add_query_arg($params, "https://api.optioncarriere.com/jobs/1.0/search/jobs"),
        ['timeout' => 15]
    );

    if (is_wp_error($response)) {
        echo '<div class="notice notice-error"><p>❌ Erreur API : ' .
            esc_html($response->get_error_message()) . '</p></div>';
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['results'])) {
        echo '<div class="notice notice-warning"><p>⚠️ Aucune offre trouvée.</p></div>';
        return;
    }

    jobiizy_display_jobs_table($data['results']);
}

/*=============================================================================
 * MODE DÉMO — OFFRES FACTICES
 *============================================================================*/
function jobiizy_display_demo_jobs($keyword, $location, $max_results) {

    $jobs = [
        [
            'title'    => 'Développeur Full Stack',
            'company'  => 'TechCorp FR',
            'location' => $location ?: 'Paris',
            'url'      => 'https://example.com/job1',
            'date'     => date('Y-m-d'),
        ],
        [
            'title'    => 'Chef de Projet Digital',
            'company'  => 'Agence Web Lyon',
            'location' => $location ?: 'Lyon',
            'url'      => 'https://example.com/job2',
            'date'     => date('Y-m-d', strtotime('-1 day')),
        ]
    ];

    jobiizy_display_jobs_table(array_slice($jobs, 0, $max_results));
}

/*=============================================================================
 * TABLEAU DES OFFRES
 *============================================================================*/
function jobiizy_display_jobs_table($jobs) {
    ?>
    <h3 style="margin-top:30px;">Résultats</h3>

    <table class="wp-list-table widefat fixed striped" style="margin-top:15px;">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Entreprise</th>
                <th>Localisation</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($jobs as $job) : ?>
            <tr>
                <td><?php echo esc_html($job['title'] ?? ''); ?></td>
                <td><?php echo esc_html($job['company'] ?? ''); ?></td>
                <td><?php echo esc_html($job['location'] ?? ''); ?></td>
                <td><?php echo esc_html($job['date'] ?? ''); ?></td>
                <td>
                    <?php if (!empty($job['url'])) : ?>
                        <a class="button button-small" target="_blank"
                           href="<?php echo esc_url($job['url']); ?>">
                            Voir
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/*=============================================================================
 * RÉGLAGES API (OPTIONS WP)
 *============================================================================*/
add_action('admin_init', 'jobiizy_register_external_jobs_settings');

function jobiizy_register_external_jobs_settings() {

    // Enregistrement des options
    register_setting('jobiizy_external_jobs_group', 'jobiizy_api_partner_id');
    register_setting('jobiizy_external_jobs_group', 'jobiizy_api_partner_key');
    register_setting('jobiizy_external_jobs_group', 'jobiizy_demo_mode');

    // Section
    add_settings_section(
        'jobiizy_api_section',
        'Configuration API OptionCarriere',
        null,
        'jobiizy-external-jobs'
    );

    // Champ Partner ID
    add_settings_field(
        'jobiizy_api_partner_id',
        'Partner ID',
        'jobiizy_render_partner_id_field',
        'jobiizy-external-jobs',
        'jobiizy_api_section'
    );

    // Champ Partner Key
    add_settings_field(
        'jobiizy_api_partner_key',
        'Partner Key',
        'jobiizy_render_partner_key_field',
        'jobiizy-external-jobs',
        'jobiizy_api_section'
    );
}
/**
 * Champ Partner ID
 */
function jobiizy_render_partner_id_field() {
    ?>
    <input type="text"
           name="jobiizy_api_partner_id"
           value="<?php echo esc_attr(get_option('jobiizy_api_partner_id', '')); ?>"
           class="regular-text">
    <?php
}
/**
 * Champ Partner Key
 */
function jobiizy_render_partner_key_field() {
    ?>
    <input type="text"
           name="jobiizy_api_partner_key"
           value="<?php echo esc_attr(get_option('jobiizy_api_partner_key', '')); ?>"
           class="regular-text">
    <?php
}
// --- Enregistrement des options ---
add_action('admin_init', function() {
    register_setting('jobiizy_external_jobs_group', 'jobiizy_api_partner_id');
    register_setting('jobiizy_external_jobs_group', 'jobiizy_api_key');
    register_setting('jobiizy_external_jobs_group', 'jobiizy_demo_mode');

    add_settings_section(
        'jobiizy_external_jobs_section',
        'Clés API OptionCarriere',
        function() {
            echo '<p>Entre ici tes identifiants API pour accéder à OptionCarriere.</p>';
        },
        'jobiizy-external-jobs'
    );

    add_settings_field(
        'jobiizy_api_partner_id',
        'Partner ID',
        function() {
            echo '<input type="text" name="jobiizy_api_partner_id" value="' . esc_attr(get_option('jobiizy_api_partner_id', '')) . '" class="regular-text">';
        },
        'jobiizy-external-jobs',
        'jobiizy_external_jobs_section'
    );

    add_settings_field(
        'jobiizy_api_key',
        'Clé API',
        function() {
            echo '<input type="text" name="jobiizy_api_key" value="' . esc_attr(get_option('jobiizy_api_key', '')) . '" class="regular-text">';
        },
        'jobiizy-external-jobs',
        'jobiizy_external_jobs_section'
    );
});