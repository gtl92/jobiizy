<?php
/**
 * JOBIIZY CUSTOM WIDGETS
 * Enregistrement des widgets Elementor personnalisés
 * 
 * Installation :
 * 1. Placez ce fichier dans /wp-content/themes/cariera-child/
 * 2. Ajoutez dans functions.php : require_once get_stylesheet_directory() . '/jobiizy-custom-widgets.php';
 * 3. Placez jobiizy-listing-split-view-widget.php dans /wp-content/themes/cariera-child/elementor-widgets/
 * 4. Placez jobiizy-modern-cards.css dans /wp-content/themes/cariera-child/assets/css/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enregistrer les widgets custom
add_action( 'elementor/widgets/register', 'jobiizy_register_custom_widgets' );
function jobiizy_register_custom_widgets( $widgets_manager ) {
	
	// Vérifier qu'Elementor est actif
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}
	
	// Charger le widget Split View custom
	require_once get_stylesheet_directory() . '/elementor-widgets/jobiizy-listing-split-view-widget.php';
	
	// Enregistrer le widget
	$widgets_manager->register( new \Cariera_Child\Elementor\Jobiizy_Listing_Split_View() );
}

// Enregistrer les styles et scripts avec PRIORITÉ ÉLEVÉE
// add_action( 'wp_enqueue_scripts', 'jobiizy_register_custom_assets', 999 );
function jobiizy_register_custom_assets() {
	
	// Enregistrer le CSS moderne avec priorité
	wp_register_style(
		'jobiizy-modern-cards',
		get_stylesheet_directory_uri() . '/assets/css/jobiizy-modern-cards.css',
		[], // Pas de dépendances pour éviter les conflits
		filemtime( get_stylesheet_directory() . '/assets/css/jobiizy-modern-cards.css' ) // Version dynamique
	);
	// Le charger TOUJOURS sur les pages avec le widget
	wp_enqueue_style( 'jobiizy-modern-cards' );
}


// Ajouter la catégorie JobiiZy dans Elementor
add_action( 'elementor/elements/categories_registered', 'jobiizy_add_elementor_category' );
function jobiizy_add_elementor_category( $elements_manager ) {
	
	$elements_manager->add_category(
		'jobiizy-elements',
		[
			'title' => esc_html__( 'JobiiZy Custom', 'cariera' ),
			'icon'  => 'fa fa-plug',
		]
	);
}

// Message de confirmation après activation
add_action( 'admin_notices', 'jobiizy_custom_widgets_notice' );
function jobiizy_custom_widgets_notice() {
	
	// Afficher seulement une fois
	if ( get_option( 'jobiizy_widgets_notice_shown' ) ) {
		return;
	}
	
	// Vérifier qu'on est sur une page Elementor
	$screen = get_current_screen();
	if ( ! $screen || strpos( $screen->id, 'elementor' ) === false ) {
		return;
	}
	
	// Marquer comme affiché
	update_option( 'jobiizy_widgets_notice_shown', true );
	
	?>
	<div class="notice notice-success is-dismissible">
		<p>
			<strong>✅ JobiiZy Custom Widgets activés !</strong><br>
			Vous pouvez maintenant utiliser le widget <strong>"JobiiZy Split-View"</strong> dans Elementor.
		</p>
	</div>
	<?php
}