<?php
/**
 * ============================================================
 * JobiiZy – Gestion avancée des utilisateurs (Cariera compatible)
 * Version FUSION + Styles Metro UI
 * ============================================================
 */

if (!defined('ABSPATH')) exit;


/* ------------------------------------------------------------
 * 1) Lire le statut Cariera
 * ------------------------------------------------------------ */
function jobiizy_get_account_status($user_id) {

    if (user_can($user_id, 'administrator')) {
        return 'approved';
    }

    $status = get_user_meta($user_id, 'user_account_status', true);

    return empty($status) ? 'approved' : $status;
}


/* ------------------------------------------------------------
 * 2) Mise à jour du statut Cariera
 * ------------------------------------------------------------ */
function jobiizy_set_account_status($user_id, $status) {

    if (user_can($user_id, 'administrator')) return;

    update_user_meta($user_id, 'user_account_status', $status);

    if ($status === 'approved') {
        update_user_meta($user_id, 'account_approve_key', '');
    }
}


/* ------------------------------------------------------------
 * 3) Nom du rôle (affichage)
 * ------------------------------------------------------------ */
function jobiizy_get_user_role_label($user_id) {

    $user = get_userdata($user_id);
    if (!$user || empty($user->roles)) return 'Inconnu';

    $map = array(
        'administrator' => 'Administrateur',
        'employer'      => 'Employeur',
        'candidate'     => 'Candidat',
    );

    foreach ($map as $slug => $label) {
        if (in_array($slug, $user->roles, true)) {
            return $label;
        }
    }

    return ucfirst($user->roles[0]);
}


/* ------------------------------------------------------------
 * 4) Colonnes supplémentaires
 * ------------------------------------------------------------ */
add_filter('manage_users_columns', function($columns) {

    $columns['jobiizy_role']    = 'Rôle';
    $columns['jobiizy_status']  = 'Statut';
    $columns['jobiizy_actions'] = 'Actions';

    return $columns;
});


/* ------------------------------------------------------------
 * 5) Remplissage colonnes
 * ------------------------------------------------------------ */
add_filter('manage_users_custom_column', function($output, $column, $user_id) {

    if ($column === 'jobiizy_role') {

        $role  = jobiizy_get_user_role_label($user_id);
        $icons = array(
            'Administrateur' => '⭐',
            'Employeur'      => '🏢',
            'Candidat'       => '👤',
        );
        $icon  = isset($icons[$role]) ? $icons[$role] : '👥';

        return "<span style='font-size:14px;'>{$icon} {$role}</span>";
    }

    if ($column === 'jobiizy_status') {

        $status = jobiizy_get_account_status($user_id);

        $colors = array(
            'approved' => '#0a8a0a',
            'pending'  => '#cc8800',
            'denied'   => '#b80000',
        );

        $text = array(
            'approved' => '✓ Approuvé',
            'pending'  => '⏳ En attente',
            'denied'   => '✖ Refusé',
        );

        return "<span style='font-weight:bold;color:{$colors[$status]};'>{$text[$status]}</span>";
    }

    if ($column === 'jobiizy_actions') {

        $status = jobiizy_get_account_status($user_id);

        if (user_can($user_id, 'administrator')) {
            return "<span style='color:green;font-weight:bold;'>✓ Admin</span>";
        }

        return jobiizy_render_user_actions($user_id, $status);
    }

    return $output;
}, 10, 3);


/* ------------------------------------------------------------
 * 6) Boutons actions (Metro style)
 * ------------------------------------------------------------ */
function jobiizy_render_user_actions($user_id, $status) {

    $html = "";

    // Approuver
    if ($status === 'pending' || $status === 'denied') {

        $url = wp_nonce_url(
            add_query_arg(array('jobiizy_action' => 'approve', 'user_id' => $user_id), admin_url('users.php')),
            'jobiizy_approve_'.$user_id
        );

        $html .= "<a class='button button-primary' href='$url' 
            style='margin-right:6px;background:#0078D7;border-color:#005a9e;'>Approuver</a>";
    }

    // Refuser
    if ($status === 'pending' || $status === 'approved') {

        $url = wp_nonce_url(
            add_query_arg(array('jobiizy_action' => 'deny', 'user_id' => $user_id), admin_url('users.php')),
            'jobiizy_deny_'.$user_id
        );

        $html .= "<a class='button' href='$url' 
            style='background:#b80000;color:white;border-color:#7a0000;'>Refuser</a>";
    }

    return $html;
}


/* ------------------------------------------------------------
 * 7) Actions Cariera (approve / deny)
 * ------------------------------------------------------------ */
add_action('load-users.php', function() {

    if (!isset($_GET['jobiizy_action']) || !isset($_GET['user_id'])) return;

    $id = intval($_GET['user_id']);

    if ($_GET['jobiizy_action'] === 'approve') {

        check_admin_referer('jobiizy_approve_'.$id);

        jobiizy_set_account_status($id, 'approved');
        do_action('cariera_new_user_approve_approve_user', $id);

        wp_redirect(admin_url('users.php?jobiizy_msg=approved'));
        exit;
    }

    if ($_GET['jobiizy_action'] === 'deny') {

        check_admin_referer('jobiizy_deny_'.$id);

        jobiizy_set_account_status($id, 'denied');
        do_action('cariera_new_user_approve_deny_user', $id);

        wp_redirect(admin_url('users.php?jobiizy_msg=denied'));
        exit;
    }
});


/* ------------------------------------------------------------
 * 8) Filtre Statut + bouton Réinitialiser
 * ------------------------------------------------------------ */
add_action('restrict_manage_users', function($which) {
    
    if ($which !== 'top') return;

    $status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

    echo '<label for="filter_status" class="screen-reader-text">Filtrer par statut</label>';

    echo '<select name="filter_status" id="filter_status" style="margin-right:6px;">';
    echo '<option value="">— Tous les statuts —</option>';
    echo '<option value="approved" ' . selected($status, 'approved', false) . '>✓ Approuvés</option>';
    echo '<option value="pending" '  . selected($status, 'pending', false)  . '>⏳ En attente</option>';
    echo '<option value="denied" '   . selected($status, 'denied', false)   . '>✖ Refusés</option>';
    echo '</select>';

    echo '<input type="submit" class="button button-primary" 
            style="background:#0078D7;border-color:#005a9e;" value="Filtrer" />';

    // Bouton Réinitialiser
    echo '<a href="' . admin_url('users.php') . '" 
            class="button" style="margin-left:6px;background:#f1f1f1;">Réinitialiser</a>';
});


/* ------------------------------------------------------------
 * 9) Filtrage SQL
 * ------------------------------------------------------------ */
add_action('pre_user_query', function($query) {
    global $wpdb;

    if (!is_admin()) return;

    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

    if (!empty($filter_status)) {

        if ($filter_status === 'approved') {

            $query->query_from .= " 
                LEFT JOIN {$wpdb->usermeta} AS jobiizy_status_meta 
                ON ({$wpdb->users}.ID = jobiizy_status_meta.user_id 
                AND jobiizy_status_meta.meta_key = 'user_account_status')
            ";

            $query->query_where .= " 
                AND (jobiizy_status_meta.meta_value = 'approved'
                OR jobiizy_status_meta.meta_value IS NULL
                OR jobiizy_status_meta.meta_value = '')
            ";

        } else {

            $query->query_from .= "
                INNER JOIN {$wpdb->usermeta} AS jobiizy_status_meta 
                ON ({$wpdb->users}.ID = jobiizy_status_meta.user_id 
                AND jobiizy_status_meta.meta_key = 'user_account_status'
                AND jobiizy_status_meta.meta_value = '{$filter_status}')
            ";
        }
    }
});


/* ------------------------------------------------------------
 * Ajouter colonne "Date d'inscription" dans la liste des utilisateurs
 * ------------------------------------------------------------ */

// 1) Ajouter la colonne
add_filter('manage_users_columns', function($columns) {
    $columns['registration_date'] = "Date d'inscription";
    return $columns;
});

// 2) Afficher les valeurs
add_filter('manage_users_custom_column', function($output, $column, $user_id) {

    if ($column === 'registration_date') {
        $user = get_userdata($user_id);
        if (!$user) return '-';

        // Format FR
        $date = mysql2date('d/m/Y H:i', $user->user_registered);
		return '<span style="background:#eef3ff;color:#0049ff;padding:4px 8px;
         border-radius:4px;font-size:12px;white-space:nowrap;">'
         . esc_html($date) .
       '</span>';
    }

    return $output;

}, 10, 3);

/* ------------------------------------------------------------
 * 10) Colonnes triables
 * ------------------------------------------------------------ */
add_filter('manage_users_sortable_columns', function($columns) {
    $columns['jobiizy_status'] = 'jobiizy_status';
    $columns['jobiizy_role']   = 'jobiizy_role';
    $columns['registration_date']   = 'registered';
    return $columns;
});


/* ------------------------------------------------------------
 * 11) Notice avec compteur + Metro style
 * ------------------------------------------------------------ */
add_action('admin_notices', function() {

    if (!isset($_GET['filter_status']) || empty($_GET['filter_status'])) return;

    $status = sanitize_text_field($_GET['filter_status']);

    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->usermeta}
        WHERE meta_key = 'user_account_status'
        AND meta_value = %s
    ", $status));

    $labels = array(
        'approved' => array('label' => '✓ Approuvés', 'color' => '#0a8a0a'),
        'pending'  => array('label' => '⏳ En attente', 'color' => '#cc8800'),
        'denied'   => array('label' => '✖ Refusés', 'color' => '#b80000'),
    );

    echo '<div class="notice notice-info"><p>';
    echo '<span style="font-weight:bold;color:' . esc_attr($labels[$status]['color']) . ';">'
        . esc_html($labels[$status]['label']) . '</span>';
    echo ' — ' . intval($count) . ' utilisateur(s)';
    echo '</p></div>';
});


/* ------------------------------------------------------------
 * 12) PAGE ADMIN : COMPTES À VALIDER
 * ------------------------------------------------------------ */
/* 
add_action('admin_menu', function() {
    add_submenu_page(
        'jobiizy-settings',               // Slug du menu JobiiZy (à ajuster si besoin)
        'Comptes à valider',          
        'Comptes à valider',
        'manage_options',
        'jobiizy-pending-users',
        'jobiizy_render_pending_users_page'
    );
});
 */


function jobiizy_render_pending_users_page() {

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom:20px;">🧩 Comptes en attente d’approbation</h1>';

    $users = get_users(array(
        'meta_key'   => 'user_account_status',
        'meta_value' => 'pending',
        'number'     => 500,
    ));

    if (empty($users)) {
        echo '<p><strong>Aucun compte en attente.</strong></p></div>';
        return;
    }

    echo '<table class="widefat fixed striped" style="margin-top:15px;">';
    echo '<thead><tr>
            <th>Utilisateur</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Actions</th>
          </tr></thead><tbody>';

    foreach ($users as $user) {

        $role = jobiizy_get_user_role_label($user->ID);

        $approve = wp_nonce_url(
            add_query_arg(array('jobiizy_action' => 'approve', 'user_id' => $user->ID), admin_url('users.php')),
            'jobiizy_approve_'.$user->ID
        );

        $deny = wp_nonce_url(
            add_query_arg(array('jobiizy_action' => 'deny', 'user_id' => $user->ID), admin_url('users.php')),
            'jobiizy_deny_'.$user->ID
        );

        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . esc_html($role) . '</td>';
        echo '<td>
                <a class="button button-primary" 
                    style="background:#0078D7;border-color:#005a9e;margin-right:6px;" 
                    href="' . esc_url($approve) . '">Approuver</a>

                <a class="button" 
                    style="background:#b80000;color:white;border-color:#7a0000;" 
                    href="' . esc_url($deny) . '">Refuser</a>
              </td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

?>