<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;

echo "<!-- JOBIIZY OVERRIDE CHILD (cariera-addons/bookmarks/bookmark-trigger.php) -->\n";
echo "<!-- OVERRIDE JOBIIZY LOADED themes/cariera-child/cariera-addons/bookmarks/bookmark-trigger.php -->";

// Sécurité : si pas de post → on ne fait rien
if ( ! $post instanceof WP_Post ) {
    return;
}

// Si ce n'est PAS une offre d'emploi → on laisse le template d'origine du plugin
if ( 'job_listing' !== $post->post_type ) {
    $plugin_template = WP_PLUGIN_DIR . '/cariera-addons/templates/bookmarks/bookmark-trigger.php';

    if ( file_exists( $plugin_template ) ) {
        include $plugin_template;
    }

    return;
}

// À partir d'ici : on est sur une OFFRE D’EMPLOI
// On NE touche plus à $job_manager_bookmarks pour éviter tout fatal.

// Utilisateur connecté : on garde un bouton simple pour l'instant
if ( is_user_logged_in() ) : ?>

    <a href="#bookmark-popup-<?php echo esc_attr( $post->ID ); ?>"
       class="listing-bookmark btn btn-main btn-effect popup-with-zoom-anim jobiizy-heart-connected jobiizy-heart-btn">

        <i class="lar la-heart"></i>
    </a>

<?php else : ?>

    <!-- Utilisateur NON connecté : bouton cœur HelloWork Jobiizy -->
     <!-- Utilisateur NON connecté : bouton cœur HelloWork Jobiizy -->
	<button id="jobiizy-fav-guest" class="jobiizy-heart-btn" type="button">
		<i class="lar la-heart"></i>
	</button>

<?php endif;