<?php
// Page désactivée volontairement — Option A (suppression totale)
// Gardé uniquement pour référence
return;

error_log("🔥 email-verification.php CLONE CHARGÉ");

/**
 * ============================================================
 * JobiiZy – Tableau de bord vérification e-mails (Admin MetroUI)
 * ============================================================
 */

if (!defined('ABSPATH')) exit;


/* ------------------------------------------------------------
 * 1) Ajout dans le menu JobiiZy  voir dans ~/wp-content/plugins/JobiiZy-customizations/admin-menus.php
 * ------------------------------------------------------------ */
/* 
add_action('admin_menu', function () {
    add_submenu_page(
        'jobiizy_settings',
        'Vérification des comptes',
        'Vérification e-mails',
        'manage_options',
        'jobiizy-email-validation',
        'jobiizy_render_email_admin_page'
    );
});
 */


/* ------------------------------------------------------------
 * 2) Rendu de la page admin
 * ------------------------------------------------------------ */
function jobiizy_render_email_admin_page() {

    $pending_users = get_users([
        'meta_key'   => 'user_account_status',
        'meta_value' => 'email_pending',
        'number'     => 500,
    ]);

    $verified_users = get_users([
        'meta_key'   => 'user_account_status',
        'meta_value' => 'email_verified',
        'number'     => 500,
    ]);

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom:20px;">📧 Vérification des comptes (JobiiZy)</h1>';

    echo '<p style="font-size:14px;color:#555;">Administration des comptes en attente de validation par e-mail.</p>';

    echo '<div style="display:flex;gap:30px;margin-top:25px;">';

    /* -----------------------------
     * Bloc 1 — Comptes en attente
     * ----------------------------- */
    jobiizy_render_email_block(
        title: "⏳ En attente de vérification",
        color: "#d48806",
        users: $pending_users,
        empty_label: "Aucun compte en attente.",
        block_type: "pending"
    );

    /* -----------------------------
     * Bloc 2 — Comptes vérifiés
     * ----------------------------- */
    jobiizy_render_email_block(
        title: "✔ Comptes vérifiés",
        color: "#0a8a0a",
        users: $verified_users,
        empty_label: "Aucun compte vérifié.",
        block_type: "verified"
    );

    echo "</div></div>";
}


/* ------------------------------------------------------------
 * 3) Bloc de rendu (réutilisable)
 * ------------------------------------------------------------ */
function jobiizy_render_email_block($title, $color, $users, $empty_label, $block_type) {

    echo "<div style='flex:1;background:white;border-radius:6px;padding:20px;
                 box-shadow:0 2px 6px rgba(0,0,0,0.08);'>";

    echo "<h2 style='color:$color;margin-top:0;'>$title</h2>";

    if (empty($users)) {
        echo "<p><em>$empty_label</em></p></div>";
        return;
    }

    echo '<table class="widefat striped" style="margin-top:15px;">';
    echo '<thead><tr>
            <th>Utilisateur</th>
            <th>Email</th>
            <th>Rôle</th>
            <th style="width:220px;">Actions</th>
          </tr></thead><tbody>';

    foreach ($users as $user) {

        $role = implode(', ', $user->roles);

        echo "<tr>";
        echo "<td>{$user->display_name}</td>";
        echo "<td>{$user->user_email}</td>";
        echo "<td>$role</td>";
        echo "<td>";

        // Bouton Renvoyer email
        $resend_url = wp_nonce_url(
            add_query_arg([
                'jobiizy_resend_email' => 1,
                'user_id' => $user->ID
            ], admin_url('users.php')),
            'jobiizy_resend_'.$user->ID
        );

        echo "<a href='$resend_url' class='button' 
                style='background:#0078D7;color:white;border-color:#005a9e;margin-right:6px;'>Renvoyer e-mail</a>";

        // Bouton Approuver (Cariera)
        if ($block_type === "verified") {
            $approve_url = wp_nonce_url(
                add_query_arg(['jobiizy_action' => 'approve', 'user_id' => $user->ID], admin_url('users.php')),
                'jobiizy_approve_'.$user->ID
            );

            echo "<a href='$approve_url' class='button button-primary'
                    style='background:#0a8a0a;border-color:#066606;'>Approuver</a>";
        }

        echo "</td>";
        echo "</tr>";
    }

    echo "</tbody></table></div>";
}


/* ------------------------------------------------------------
 * 4) Action : renvoi de l’e-mail
 * ------------------------------------------------------------ */
add_action('load-users.php', function () {

    if (!isset($_GET['jobiizy_resend_email']) || !isset($_GET['user_id'])) {
        return;
    }

    $user_id = intval($_GET['user_id']);
    check_admin_referer('jobiizy_resend_'.$user_id);

    if (function_exists('jobiizy_send_verification_email')) {
        jobiizy_send_verification_email($user_id);
    }

    wp_redirect(admin_url('admin.php?page=jobiizy-email-validation&msg=resend_ok'));
    exit;
});