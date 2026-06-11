<?php

/**
 * ============================================================
 * JobiiZy – Historique des e-mails envoyés
 * ============================================================
 */

function jobiizy_render_email_log_page() {
    global $wpdb;
    $table = $wpdb->prefix . "jobiizy_email_log";

    // Gestion du message de confirmation suppression
    if (isset($_GET['jobiizy_email_log_cleared'])) {
        echo '<div class="notice notice-success"><p>🧹 Historique des e-mails effacé.</p></div>';
    }

    // Récupération des logs
    $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 200");

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom:25px;">📧 Historique des e-mails envoyés</h1>';

    // ------------------------------------------------------------
    // Bouton "Vider l’historique"
    // ------------------------------------------------------------
    echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="action" value="jobiizy_clear_email_log">';
    wp_nonce_field('jobiizy_clear_email_log');
    echo '<button class="button button-secondary" 
            style="background:white;color:#d00000;border:1px solid #d00000;padding:8px 14px;border-radius:4px;">
            🗑️ Vider l’historique
          </button>';
    echo '</form>';

    // ------------------------------------------------------------
    // Aucun email ?
    // ------------------------------------------------------------
    if (!$logs) {
        echo '<p>Aucun e-mail enregistré pour le moment.</p>';
        echo '</div>';
        return;
    }

    // ------------------------------------------------------------
    // Tableau des logs
    // ------------------------------------------------------------
    echo '<table class="widefat fixed striped" style="margin-top:10px;">';
    echo '<thead>
            <tr>
                <th width="150">Date</th>
                <th width="220">Destinataire</th>
                <th width="150">Type</th>
                <th>Sujet</th>
                <th width="90">Statut</th>
                <th width="60">Détails</th>
            </tr>
          </thead><tbody>';

    foreach ($logs as $log) {

        $status_color = $log->status === 'sent' ? 'green' : 'red';

        echo "<tr>
                <td>{$log->sent_at}</td>
                <td>{$log->email_to}</td>
                <td>{$log->type}</td>
                <td>{$log->subject}</td>
                <td style='font-weight:bold;color:$status_color;'>{$log->status}</td>
                <td><a href='?page=jobiizy_email_log&view={$log->id}'>Voir</a></td>
              </tr>";
    }

    echo '</tbody></table>';

    // ------------------------------------------------------------
    // Mode aperçu d’un email
    // ------------------------------------------------------------
    if (isset($_GET['view'])) {

        $id = intval($_GET['view']);
        $entry = $wpdb->get_row("SELECT * FROM $table WHERE id = $id");

        if ($entry) {

            echo "<h2 style='margin-top:40px;'>📬 Aperçu de l’e-mail #$id</h2>";

            echo "<div style='background:white;padding:20px;border:1px solid #ccc;
                          max-width:800px;border-radius:6px;'>";
            
            echo "<p><strong>Destinataire :</strong> {$entry->email_to}</p>";
            echo "<p><strong>Type :</strong> {$entry->type}</p>";
            echo "<p><strong>Sujet :</strong> {$entry->subject}</p>";
            echo "<p><strong>Statut :</strong> {$entry->status}</p>";

            echo "<hr style='margin:20px 0;'>";

            echo "<div style='max-height:600px;overflow:auto;border:1px solid #ddd;
                          padding:15px;background:#fafafa;'>";

            echo $entry->message;

            echo "</div></div>";
        }
    }

    echo '</div>'; // wrap
}



/**
 * ============================================================
 * ACTION – Vider l’historique des e-mails
 * ============================================================
 */

function jobiizy_clear_email_log() {
    if (!current_user_can('manage_options')) {
        wp_die("Accès refusé.");
    }

    check_admin_referer('jobiizy_clear_email_log');

    global $wpdb;
    $table = $wpdb->prefix . "jobiizy_email_log";

    $wpdb->query("TRUNCATE TABLE $table");

    wp_redirect(
        add_query_arg(
            'jobiizy_email_log_cleared',
            '1',
            admin_url('admin.php?page=jobiizy_email_log')
        )
    );
    exit;
}

add_action('admin_post_jobiizy_clear_email_log', 'jobiizy_clear_email_log');
?>