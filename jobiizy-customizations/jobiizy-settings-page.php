<?php
/**
 * Fichier : jobiizy-settings-page.php V2
 * Description : Réglages avancés centralisés pour JobiiZy (à intégrer dans un plugin).
 */
// Crée une page "JobiiZy > Accès protégé"
add_action('admin_menu', function () {
    add_menu_page(
        'Paramètres JobiiZy',
        'Jobiizy GTL',
        'manage_options',
        'jobiizy_settings',
        'jobiizy_render_settings_page',
        'dashicons-lock',
        66
    );
});

// Enregistre les réglages
add_action('admin_init', function () {
    register_setting('jobiizy_settings_group', 'job_manager_skip_moderation_roles');
    register_setting('jobiizy_settings_group', 'jobiizy_private_access_roles');
    register_setting('jobiizy_settings_group', 'jobiizy_hide_empty_categories');
});

// Affiche la page de réglages
function jobiizy_render_settings_page()
{
    $all_roles = wp_roles()->roles;
    $role_names = array_map(function ($r) {
        return $r['name'];
    }, $all_roles);

    // Nettoyage des valeurs
    $moderation_roles = (array) get_option('job_manager_skip_moderation_roles', []);
    $access_roles = (array) get_option('jobiizy_private_access_roles', []);

    ?>
    <div class="wrap">
        <h1>🔒 Paramètres d'accès JobiiZy</h1>
        <form method="post" action="options.php">
            <?php settings_fields('jobiizy_settings_group'); ?>
            <?php do_settings_sections('jobiizy_settings'); ?>
            <h2 style="margin-top:32px;">✍️ Rôles exemptés de validation des offres</h2>
            <p>Ces rôles peuvent publier ou modifier une offre sans validation manuelle.</p>
            <select multiple name="job_manager_skip_moderation_roles[]" style="height:auto; min-width:300px;">
                <?php foreach ($role_names as $key => $label): ?>
                    <option value="<?= esc_attr($key); ?>" <?= in_array($key, $moderation_roles) ? 'selected' : ''; ?>>
                        <?= esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <h2 style="margin-top:32px;">👁️ Rôles autorisés à voir les pages privées (ex: CVThèque)</h2>
            <p>Seuls les utilisateurs avec ces rôles pourront accéder à certaines pages protégées comme
                <code>/jobiizy-profils/</code>.
            </p>
            <select multiple name="jobiizy_private_access_roles[]" style="height:auto; min-width:300px;">
                <option value="aucun" <?= in_array('aucun', $access_roles) ? 'selected' : ''; ?>>🚫 Aucun</option>
                <?php foreach ($role_names as $key => $label): ?>
                    <option value="<?= esc_attr($key); ?>" <?= in_array($key, $access_roles) ? 'selected' : ''; ?>>
                        <?= esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <h2 style="margin-top:32px;">🗂️ Visibilité des catégories (GTL)</h2>
            <p>Masquer les catégories sans offres (sitemap, recherche, flux...)</p>

            <label>
                <input type="checkbox" name="jobiizy_hide_empty_categories" value="1"
                    <?= checked(get_option('jobiizy_hide_empty_categories'), '1', false); ?>>
                Masquer les catégories vides
            </label>
            <p class="submit" style="margin-top:32px;">
                <button type="submit" class="button button-primary">💾 Enregistrer les réglages</button>
            </p>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const select = document.querySelector('select[name="jobiizy_private_access_roles[]"]');
            if (!select) return;

            select.addEventListener('change', () => {
                const options = [...select.options];
                const aucunSelected = options.find(opt => opt.value === 'aucun' && opt.selected);

                options.forEach(opt => {
                    if (opt.value !== 'aucun') {
                        opt.disabled = aucunSelected;
                    } else {
                        opt.disabled = options.some(o => o.value !== 'aucun' && o.selected);
                    }
                });
            });

            select.dispatchEvent(new Event('change'));
        });
    </script>
    <?php
}


// === SECTION : Correctif du carrousel des entreprises ===
add_action('admin_init', function () {

    add_settings_section(
        'jobiizy_carousel_section',
        '<span style="color:blue">⚠️  Surcharge du carrousel "Top Companies"</span>',
        null,
        'jobiizy_settings'
    );

    add_settings_field(
        'jobiizy_enable_company_carousel_override',
        'Activer la surcharge du carrousel Company',
        function () {
            $value = get_option('jobiizy_enable_company_carousel_override', '1');

            // Chemins possibles
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/company-carousel.php';

            // Recherche dans child ou parent
            $child_template = get_stylesheet_directory() . '/wp-job-manager-companies/company-templates/company-carousel.php';
            $parent_template = get_template_directory() . '/wp-job-manager-companies/company-templates/company-carousel.php';
            $theme_template = file_exists($child_template) ? $child_template : $parent_template;

            // Détermine le fichier actif selon l’option
            $active_template = ($value === '1' && file_exists($plugin_template))
                ? $plugin_template
                : $theme_template;

            ?>
        <div style="
    background: #f9fafc;
    border: 1px solid #ccd0d4;
    border-left: 4px solid #2271b1;
    border-radius: 6px;
    padding: 15px 20px;
    max-width: 750px;
    margin-top: 15px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
">
            <h3 style="margin-top: 0; color: #2271b1;">⚙️ Correctif du carrousel “Top Companies”</h3>

            <p style="margin-bottom: 10px; color: #333;">
                Cette option permet d’activer la version corrigée du template
                <code id="jobiizy-template-path" style="user-select: all;"><?php echo esc_html($active_template); ?></code><br>

                <!-- 📁 Chemin dynamique -->
            <p style="margin-top: 15px; font-size:13px; color:#555;">
                📄 <b>Template actuellement utilisé :</b><br>
            </p>

            <?php
                if (file_exists($active_template)):
                    if ($active_template === $plugin_template) {
                        echo '<span id="jobiizy-template-label" style="color:green; font-weight:bold;">✔️ Fichier de surcharge (plugin JobiiZy)</span>';
                    } else {
                        echo '<span id="jobiizy-template-label" style="color:#0073aa; font-weight:bold;">🎨 Fichier du thème (Cariera ou enfant)</span>';
                    }
                else:
                    echo '<span id="jobiizy-template-label" style="color:red; font-weight:bold;">❌ Fichier introuvable</span>';
                endif;
                ?>
            </p>

            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="jobiizy_enable_company_carousel_override" value="1" <?php checked($value, '1'); ?>>
                <span>Activer la correction du nombre d’emplois affiché</span>
            </label>

            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                Si désactivé, le thème Cariera utilisera sa version native du carrousel.
            </p>


            <button type="button" class="button" id="toggle-carousel-code" style="margin-top: 10px;">Afficher le code source du
                template surchargé</button>

            <pre id="carousel-code"
                style="display: none; background: #fff; border: 1px solid #ccd0d4; padding: 12px; margin-top: 10px; font-size: 13px; color: #333; overflow-x: auto;">
        <?php
            $source_file = $active_template;
            if (file_exists($source_file)) {
                echo esc_html(file_get_contents($source_file));
            } else {
                echo '⚠️ Fichier non trouvé : ' . esc_html($source_file);
            }
            ?>
            </pre>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const checkbox = document.querySelector('input[name="jobiizy_enable_company_carousel_override"]');
                    const pathCode = document.getElementById('jobiizy-template-path');
                    const labelSpan = document.getElementById('jobiizy-template-label');
                    const btn = document.getElementById('toggle-carousel-code');
                    const codePre = document.getElementById('carousel-code');

                    if (!checkbox || !pathCode || !labelSpan) return;

                    // Chemins calculés côté PHP
                    const pluginPath = "<?php echo esc_js($plugin_template); ?>";
                    const themePath = "<?php echo esc_js($theme_template); ?>";

                    // Contenu des fichiers (échappé côté PHP)
                    const pluginSource = <?php
                        echo json_encode(file_exists($plugin_template) ? file_get_contents($plugin_template) : '');
                        ?>;
                    const themeSource = <?php
                        echo json_encode(file_exists($theme_template) ? file_get_contents($theme_template) : '');
                        ?>;

                    function updateTemplateUI() {
                        const usingPlugin = checkbox.checked;

                        // Met à jour le chemin
                        pathCode.textContent = usingPlugin ? pluginPath : themePath;

                        // Met à jour le label
                        labelSpan.innerHTML = usingPlugin
                            ? '✔️ <b>Fichier de surcharge (plugin JobiiZy)</b>'
                            : '🎨 <b>Fichier du thème (Cariera ou enfant)</b>';
                        labelSpan.style.color = usingPlugin ? 'green' : '#0073aa';

                        // Met à jour le contenu du <pre> (affichage du code)
                        if (codePre) {
                            const src = usingPlugin ? pluginSource : themeSource;
                            // Afficher le code échappé dans le <pre>
                            codePre.textContent = src || '⚠️ Fichier introuvable ou vide.';
                        }
                    }

                    // Toggle d’affichage du code source
                    if (btn && codePre) {
                        btn.addEventListener('click', () => {
                            const visible = codePre.style.display === 'block';
                            codePre.style.display = visible ? 'none' : 'block';
                            btn.textContent = visible ? 'Afficher le code source du template surchargé' : 'Masquer le code source';
                        });
                    }

                    // Écoute le changement de la case
                    checkbox.addEventListener('change', updateTemplateUI);

                    // Init UI au chargement
                    updateTemplateUI();
                });
            </script>
        </div>
        <?php
        },
        'jobiizy_settings',
        'jobiizy_carousel_section'
    );

    register_setting('jobiizy_settings_group', 'jobiizy_enable_company_carousel_override');
});

