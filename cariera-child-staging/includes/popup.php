<?php
/**
 * Popup login — rendu HTML, enqueue CSS/JS, popup Cariera combinée
 */

add_action('wp_footer', 'jobiizy_render_login_popup');
function jobiizy_render_login_popup() {
    if (is_user_logged_in()) return;
    ?>
    <div id="jobiizy-login-popup-overlay">
        <div class="jobiizy-popup-box">

            <!-- Bouton fermer -->
            <button class="jobiizy-popup-close" aria-label="Fermer">×</button>

            <!-- Contenu -->
            <div style="text-align:center;">
                <span class="jobiizy-popup-emoji">🔓</span>

                <h2>Connectez-vous pour postuler</h2>

                <p>
                    Pour postuler à cette offre, vous devez être inscrit et connecté à votre compte Jobiizy.
                </p>

                <!-- Boutons -->
                <div class="jobiizy-popup-actions">
                    <!-- Bouton principal -->
                    <a href="<?php echo esc_url(home_url('/pages/connexion-inscription/')); ?>"
                       id="jobiizy-login-confirm"
                       class="jobiizy-popup-btn-primary">
                        Se connecter / S'inscrire
                    </a>

                    <!-- Boutons secondaires sur une ligne -->
                    <div class="jobiizy-popup-secondary-row">
                        <a href="<?php echo esc_url(home_url('/pages/devenir-candidat/')); ?>"
                           id="jobiizy-become-candidate"
                           class="jobiizy-popup-btn-secondary">
                            👤 Candidat
                        </a>

                        <a href="<?php echo esc_url(home_url('/pages/devenir-employeur/')); ?>"
                           id="jobiizy-become-employer"
                           class="jobiizy-popup-btn-secondary">
                            💼 Employeur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function jobiizy_enqueue_popup_script() {
    if (is_user_logged_in()) return;

    wp_enqueue_style(
        'jobiizy-popup-login',
        get_stylesheet_directory_uri() . '/assets/css/jobiizy-popup-login.css',
        [],
        filemtime(get_stylesheet_directory() . '/assets/css/jobiizy-popup-login.css')
    );

    wp_enqueue_script(
        'jobiizy-popup-login',
        get_stylesheet_directory_uri() . '/assets/js/popup-login.js',
        [],
        '1.0.3',
        true
    );

    wp_localize_script('jobiizy-popup-login', 'jobiizyPopupRoutes', [
        'login'     => esc_url(home_url('/pages/connexion-inscription/')),
        'candidate' => esc_url(home_url('/pages/devenir-candidat/')),
        'employer'  => esc_url(home_url('/pages/devenir-employeur/')),
    ]);
}
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_popup_script');

/**
 * Popup combinée Jobiizy + Cariera
 * Injecte #login-register-popup sur devenir-employeur, devenir-candidat, connexion-inscription.
 */
add_action('wp_footer', function() {
    if ( ! is_page( ['devenir-employeur', 'devenir-candidat', 'connexion-inscription'] ) ) return;

    if ( is_user_logged_in() ) {
        $login_page_id = get_option('cariera_login_register_page');
        $redirect_url  = $login_page_id ? get_permalink($login_page_id) : home_url('/dashboard/');
        if ( $redirect_url ) {
            wp_safe_redirect( $redirect_url );
            exit;
        }
        return;
    }

    if ( function_exists('cariera_login_register_popup') ) {
        cariera_login_register_popup();
        if ( current_user_can('administrator') ) {
            jobiizy_success('Popup Cariera injectée via fonction ✅');
        }
        return;
    }

    $child_tpl  = trailingslashit( get_stylesheet_directory() ) . 'templates/popups/login-register.php';
    $parent_tpl = trailingslashit( get_template_directory() )   . 'templates/popups/login-register.php';

    if ( file_exists( $child_tpl ) ) {
        include $child_tpl;
        if ( current_user_can('administrator') ) {
            jobiizy_success('Popup injectée via template enfant ✅');
        }
        return;
    }

    if ( file_exists( $parent_tpl ) ) {
        include $parent_tpl;
        if ( current_user_can('administrator') ) {
            jobiizy_success('Popup injectée via template parent ✅');
        }
        return;
    }

    if ( current_user_can('administrator') ) {
        jobiizy_error('Impossible d\'injecter #login-register-popup (template introuvable)');
    }
}, 100);
