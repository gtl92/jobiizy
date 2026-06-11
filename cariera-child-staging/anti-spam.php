<?php
/**
 * Module Anti-Spam pour les inscriptions Cariera / WP Job Manager
 * Chargé depuis functions.php (cariera-child)
 */

if ( ! function_exists( 'jobiizy_register_anti_spam' ) ) {

    function jobiizy_register_anti_spam() {

        // Ajouter le honeypot
        add_action('register_form', 'jobiizy_antispam_add_honeypot');

        // Vérification post-inscription
        add_action('user_register', 'jobiizy_antispam_check_new_user', 10, 1);
    }

    add_action('init', 'jobiizy_register_anti_spam');
}


/**
 * Champ honeypot invisible
 */
if ( ! function_exists( 'jobiizy_antispam_add_honeypot' ) ) {
    function jobiizy_antispam_add_honeypot() {
        echo '<input type="text" name="hpt_company" value="" style="display:none!important">';
    }
}


/**
 * Vérifications anti-spam silencieuses
 */
if ( ! function_exists( 'jobiizy_antispam_check_new_user' ) ) {
    function jobiizy_antispam_check_new_user( $user_id ) {

        // --- Honeypot
        if (!empty($_POST['hpt_company'] ?? '')) {
            error_log('[JOBIIZY ANTI-SPAM] Honeypot détecté → Suppression user_id=' . $user_id);
            wp_delete_user($user_id);
            exit;
        }

        // --- User-Agent suspect
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ua_blacklist = ['curl','python','httpclient','bot','crawler','scraper','wget','spider','node','java'];

        foreach ($ua_blacklist as $bad) {
            if ($ua && strpos($ua, $bad) !== false) {
                error_log('[JOBIIZY ANTI-SPAM] UA bloqué (' . $bad . ') : '.$ua.' → user_id='.$user_id);
                wp_delete_user($user_id);
                exit;
            }
        }

        // --- Adresse email jetable
        $user  = get_userdata($user_id);
        $email = $user->user_email ?? '';

        $domains = [
            'mailinator','tempmail','10minutemail','guerrillamail','yopmail',
            'trashmail','fakeinbox','dispostable','moakt','dropmail'
        ];

        foreach ($domains as $domain) {
            if (stripos($email, $domain) !== false) {
                error_log('[JOBIIZY ANTI-SPAM] Email jetable : '.$email.' → Suppression user_id='.$user_id);
                wp_delete_user($user_id);
                exit;
            }
        }

        // --- Log OK
        error_log('[JOBIIZY ANTI-SPAM] Inscription OK : '.$email.' (user_id='.$user_id.')');
    }
}