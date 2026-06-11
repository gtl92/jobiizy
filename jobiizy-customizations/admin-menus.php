<?php
/**
 * ============================================================================
 * JOBIIZY CUSTOMIZATIONS – ADMIN MENUS & API PAGE
 * ============================================================================
 *
 * Ce fichier gère :
 *   - Le menu principal "Jobiizy"
 *   - Les sous-menus Diagnostics, Correction, Filtres, Sync Logos
 *   - La page complète d'Offres Externes (API OptionCarriere)
 *   - L’enregistrement des réglages API
 *
 * Structure fonctionnelle :
 *
 * jobiizy_register_all_admin_menus()
 * ├── Menu principal : JobiiZy
 * ├── Offres externes (API)
 * ├── Diagnostic Jobs / Entreprises
 * ├── Correction en masse
 * ├── Filtre Entreprise/Jobs
 * └── Synchronisation Logos
 *
 * Les callbacks des sous-menus sont définies dans leurs fichiers respectifs.
 * La page API complète est définie directement dans ce fichier.
 *
 * ============================================================================
 */

if (!defined('ABSPATH')) exit;

/**
 * ============================================================================
 * Enregistrer tous les menus et sous-menus du plugin
 * ============================================================================
 */
add_action('admin_menu', 'jobiizy_register_all_admin_menus', 10);

function jobiizy_register_all_admin_menus() {

    /**
     * ------------------------------------------------------------------------
     * 1. MENU PRINCIPAL : JobiiZy dans  jobiizy_render_settings_page.php
     * ------------------------------------------------------------------------
     * Slug   : jobiizy_settings
     * Page   : rendue par jobiizy_render_settings_page()
     */
 
        /**
     * ------------------------------------------------------------------------
     * 2. SOUS-MENU :// RESTAURATION ACTIVE JOBS //
     * ------------------------------------------------------------------------
     * Callback : jobiizy_render_restore_active_jobs_page()
     */
    add_submenu_page(
        'jobiizy_settings',
        'Restaurer Active Jobs',
        '🔄 Restaurer Active Jobs',
        'manage_options',
        'jobiizy_restore_active_jobs',
        'jobiizy_render_restore_active_jobs_page'
    );
    /**
     * ------------------------------------------------------------------------
     * 3. SOUS-MENU : Diagnostic Jobs / Entreprises
     * ------------------------------------------------------------------------
     */
        add_submenu_page(
        'jobiizy_settings',
        'Diagnostic Jobs Entreprise',
        '🔍 Diagnostic Jobs',
        'manage_options',
        'jobiizy_diagnostic_jobs',
        'jobiizy_render_diagnostic_jobs_page'
    );

    /**
     * ------------------------------------------------------------------------
     * 4. SOUS-MENU : Correction en masse (jobs)
     * ------------------------------------------------------------------------
     */
        add_submenu_page(
        'jobiizy_settings',
        'Correction Masse Jobs',
        '🔧 Correction Masse',
        'manage_options',
        'jobiizy_fix_all_companies',
        'jobiizy_render_fix_all_companies_page'
    );

    /**
     * ------------------------------------------------------------------------
     * 5. SOUS-MENU : Filtre Entreprise/Jobs
     * ------------------------------------------------------------------------
     */
/*     add_submenu_page(
        'jobiizy_settings',
        'Filtre Entreprise/Jobs',
        '> Filtre Entreprise',
        'manage_options',
        'jobiizy_company_filter',
        'jobiizy_render_company_filter_page'
    ); */

    /**
     * ------------------------------------------------------------------------
     * 6. SOUS-MENU : Fix Logo /  Entreprises
     * ------------------------------------------------------------------------
     * ============================================================
     * JOBIIZY — OUTIL ADMIN : Réparation massive des logos entreprises
     * Ajouté dans le plugin Jobiizy Customizations
     * Menu : Outils → Fix logos entreprises
     * ============================================================
     */
     /* callback : jobiizy_render_fix_company_logos_page()  dans Jobiizy-function.php */
    add_submenu_page(
		'jobiizy_settings',   // 🔥 identifiant du menu parent (ton plugin)
		'Fix logos entreprises',
		'🔥 Fix logos entreprises',
		'manage_options',
		'jobiizy-fix-company-logos',
		'jobiizy_render_fix_company_logos_page'
	);
     
    /**
     * ------------------------------------------------------------------------
     * 2. SOUS-MENU : Offres externes (API OptionCarriere) PAGE SCRAPPING / API JOBS
     * ------------------------------------------------------------------------
     * Callback : jobiizy_render_external_jobs_page()
     */
     add_submenu_page(
            'jobiizy_settings',
            'Offres externes JobiiZy',
            '✅ Offres externes',
            'manage_options',
            'jobiizy_external_jobs',
            'jobiizy_render_external_jobs_page'
      );

    // ----------------------------------------------------
	//  1️⃣ Enregistrement du menu admin
	// ----------------------------------------------------
	add_submenu_page(
			'jobiizy_settings',                // Slug du menu parent (Jobiizy)
			'Anti-Spam',                       // Titre page
			'1️⃣  Anti-Spam',                  // Label menu
			'manage_options',                  // Capacité
			'jobiizy-antispam',                // Slug
			'jobiizy_antispam_admin_page'      // Callback
		);


    add_submenu_page(
        'jobiizy_settings',               // Slug du menu JobiiZy (à ajuster si besoin)
        'Comptes à valider',          
        'Comptes à valider',
        'manage_options',
        'jobiizy-pending-users',
        'jobiizy_render_pending_users_page'
    );
    
add_submenu_page(
    'jobiizy_settings',
    'Historique e-mails',
    'Historique e-mails',
    'manage_options',
    'jobiizy_email_log',
    'jobiizy_render_email_log_page'
);

}

