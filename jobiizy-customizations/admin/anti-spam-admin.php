<?php
/**
 * Page Admin Anti-Spam Jobiizy
 */
// ----------------------------------------------------
// 1️⃣ Enregistrement du menu admin
// ----------------------------------------------------
/* 
add_action('admin_menu', function () {
    add_submenu_page(
        'jobiizy-settings',                // Slug du menu parent (Jobiizy)
        'Anti-Spam',                       // Titre page
        'Anti-Spam',                       // Label menu
        'manage_options',                  // Capacité
        'jobiizy-antispam',                // Slug
        'jobiizy_antispam_admin_page'      // Callback
    );
});
 */


// ----------------------------------------------------
// 2️⃣ Page Admin HTML
// ----------------------------------------------------
function jobiizy_antispam_admin_page()
{
    // Charger options
    $domains = get_option('jobiizy_antispam_domains', []);
    $uas     = get_option('jobiizy_antispam_useragents', []);

    // Récupérer logs PHP
	// =======================================================
	// LECTURE DU error_log DU THÈME ENFANT (Cariera-child)
	// =======================================================
	
	$log_file = get_stylesheet_directory() . '/error_log';
	
	if (!file_exists($log_file)) {
		echo '<div class="notice notice-error"><p>🚫 Aucun fichier error_log trouvé dans le thème enfant.</p></div>';
		return;
	}

    $log_lines = [];

    if (file_exists($log_file)) {
        $lines = array_reverse(array_slice(file($log_file), -500)); // 500 dernières lignes
 		echo '<div class="notice notice-success"><p> Fichier log trouvé dans le thème enfant.</p></div>';
       foreach ($lines as $line) {
            if (strpos($line, 'JOBIIZY ANTI-SPAM') !== false) {
                $log_lines[] = $line;
            }
        }
    }

    ?>
    <div class="wrap">
        <h1>🛡 Jobiizy – Anti-Spam</h1>

        <p style="font-size:15px;color:#555">
            Tableau de bord des protections anti-spam (Cariera / WP Job Manager).
        </p>

        <hr><br>

        <!-- 🧮 Compteur -->
        <h2>📊 Statistiques</h2>
        <p>
            <strong>Bots bloqués : </strong>
            <?php echo count($log_lines); ?> (dans les 500 dernières lignes du debug.log)
        </p>

        <br><br>

        <!-- 📜 Logs -->
<h2>📄 Logs récents</h2>
<?php if (!empty($log_lines)) : ?>
<table class="widefat fixed striped">
    <thead>
        <tr>
            <th style="width:180px;">Date</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
	<?php foreach ($log_lines as $line) : ?>
		<?php
		// Format attendu :
		// [DATE] [JOBIIZY ANTI-SPAM] Message du log
	
		preg_match('/^\[.*?\]\s+\[JOBIIZY ANTI-SPAM\]\s+(.*)$/', $line, $m);
	
		$date = '—';
	
		// on extrait proprement la date
		if (preg_match('/\[(.*?)\]/', $line, $d)) {
			$date = $d[1];
		}
	
		$message = trim($m[1] ?? $line);
		?>
		<tr>
			<td><?php echo esc_html($date); ?></td>
			<td><?php echo esc_html($message); ?></td>
		</tr>
	<?php endforeach; ?>
    </tbody>
</table>
<?php else : ?>
    <p>Aucune entrée anti-spam récente.</p>
<?php endif; ?>

        <br><br>

        <!-- 🚫 Domaines jetables -->
        <h2>🚫 Domaines e-mails bloqués</h2>
        <form method="post">
            <?php wp_nonce_field('jobiizy_antispam_save'); ?>

            <textarea name="jobiizy_domains" rows="5" style="width: 100%;"><?php
                echo esc_textarea(implode("\n", $domains));
            ?></textarea>

            <p><em>Un domaine par ligne (ex: mailinator.com)</em></p>

            <input type="submit" name="jobiizy_save_domains" class="button button-primary" value="Enregistrer">
        </form>

        <br><br>

        <!-- 🤖 User-Agents -->
        <h2>🤖 User-Agents bloqués</h2>
        <form method="post">
            <?php wp_nonce_field('jobiizy_antispam_save'); ?>

            <textarea name="jobiizy_uas" rows="5" style="width: 100%;"><?php
                echo esc_textarea(implode("\n", $uas));
            ?></textarea>

            <p><em>Un User-Agent par ligne (ex: python, curl, wget)</em></p>

            <input type="submit" name="jobiizy_save_uas" class="button button-primary" value="Enregistrer">
        </form>
    </div>
    <?php
}


// ----------------------------------------------------
// 3️⃣ Sauvegarde des options
// ----------------------------------------------------
add_action('admin_init', function () {

    if (!isset($_POST['jobiizy_save_domains']) && !isset($_POST['jobiizy_save_uas'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['_wpnonce'], 'jobiizy_antispam_save')) {
        return;
    }

    // Sauver domaines
    if (isset($_POST['jobiizy_save_domains'])) {
        $domains = array_filter(array_map('trim', explode("\n", $_POST['jobiizy_domains'])));
        update_option('jobiizy_antispam_domains', $domains);
    }

    // Sauver user-agents
    if (isset($_POST['jobiizy_save_uas'])) {
        $uas = array_filter(array_map('trim', explode("\n", $_POST['jobiizy_uas'])));
        update_option('jobiizy_antispam_useragents', $uas);
    }
});