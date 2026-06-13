<?php
/**
 * Enqueue — tous les wp_enqueue_scripts/styles du thème enfant
 */

// ── Prédicats de page ────────────────────────────────────────────────────────
function jobiizy_should_load_cvform_script() {
    return is_singular('job_listing');
}
function jobiizy_should_load_job_global() {
    return is_singular('job_listing');
}
function jobiizy_should_load_job_list_scripts() {
    global $post;
    return (
        (is_page() && isset($post->post_name) && strpos($post->post_name, 'jobs-s-') === 0) ||
        is_page(['jobs-split-view', 'jobs-split-view-2', 'jobs-s-jobiizy', 'jobiizy-offres']) ||
        is_post_type_archive('job_listing') ||
        is_tax(['job_listing_category', 'job_listing_tag'])
    );
}

// ── Style parent + enfant ────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', 'jobiizy_force_child_style', 999);
function jobiizy_force_child_style() {
    wp_enqueue_style(
        'cariera-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );
    wp_dequeue_style('cariera-child-style');
    wp_enqueue_style('cariera-child-style', get_stylesheet_uri(), [], time());
}

// ── Assets CSS + JS principaux (priority 20) ─────────────────────────────────
add_action('wp_enqueue_scripts', 'cariera_child_enqueue_assets', 20);
function cariera_child_enqueue_assets() {
    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    $css_files = [
        'cv-form-style'    => '/assets/css/cv_form_styles_final.css',
        'jobiizy-overview' => '/assets/css/jobiizy-overview.css',
    ];
    foreach ($css_files as $handle => $file) {
        $file_path = $theme_dir . $file;
        if (file_exists($file_path)) {
            wp_enqueue_style($handle, $theme_uri . $file, [], filemtime($file_path));
        }
    }

    $override_css = $theme_dir . '/assets/css/jobiizy-design-override.css';
    if (file_exists($override_css)) {
        wp_enqueue_style(
            'jobiizy-design-override',
            $theme_uri . '/assets/css/jobiizy-design-override.css',
            ['cariera-parent-style', 'cariera-child-style'],
            filemtime($override_css)
        );
    }

    $js_files = [
        'jobiizy-global'            => '/assets/js/jobiizy-global.js',
        'resume-filters'            => '/assets/js/resume-filters.js',
        'job-filters'               => '/assets/js/job-filters.js',
        'jobiizy-custom'            => '/assets/js/custom_with_scroll.js',
        'jobiizy-listingSearchForm' => '/cariera_core/elements/listing-search/jobiizy-listingSearchForm.js',
        'jobiizy-title-capitalize'  => '/assets/js/title-capitalize.js',
        'jobiizy-random-company-bg' => '/assets/js/random-company-bg.js',
        'jobiizy-company-link'      => '/assets/js/splitview-company-link.js',
    ];
    foreach ($js_files as $handle => $file) {
        $file_path = $theme_dir . $file;
        if (file_exists($file_path)) {
            wp_enqueue_script($handle, $theme_uri . $file, ['jquery'], filemtime($file_path), true);
        }
    }

    if (!wp_style_is('cariera-search-forms', 'enqueued') && !wp_style_is('cariera-search-forms', 'registered')) {
        $search_css = get_template_directory() . '/assets/dist/css/wpjm-search-forms.css';
        if (file_exists($search_css)) {
            wp_enqueue_style('cariera-search-forms', get_template_directory_uri() . '/assets/dist/css/wpjm-search-forms.css', [], filemtime($search_css));
        }
    }
}

// ── Flag debug JS ────────────────────────────────────────────────────────────
add_action('wp_head', function() {
    $is_admin = current_user_can('administrator');
    $debug_on = defined('JOBIIZY_DEBUG') && JOBIIZY_DEBUG === true;
    ?>
    <script>
        window.jobiizyDebug = <?php echo ($is_admin && $debug_on) ? 'true' : 'false'; ?>;
        console.log('🔧 jobiizyDebug =', window.jobiizyDebug);
    </script>
    <?php
});

// ── Mobile header CSS — après cariera-frontend (priority 99) ─────────────────
add_action('wp_enqueue_scripts', function() {
    wp_dequeue_style('jobiizy-mobile-header');
    wp_deregister_style('jobiizy-mobile-header');
    wp_enqueue_style(
        'jobiizy-mobile-header-fix',
        get_stylesheet_directory_uri() . '/assets/css/jobiizy-mobile-header-fix.css',
        ['cariera-frontend'],
        filemtime(get_stylesheet_directory() . '/assets/css/jobiizy-mobile-header-fix.css')
    );
}, 99);

// ── replace-cvform.js — sur les single job listing (priority 20) ─────────────
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_cvform_script', 20);
function jobiizy_enqueue_cvform_script() {
    if (current_user_can('administrator')) {
        error_log('[Jobiizy] → jobiizy_enqueue_cvform_script() appelée sur ' . $_SERVER['REQUEST_URI']);
    }
    if (!jobiizy_should_load_cvform_script()) {
        if (current_user_can('administrator')) {
            error_log('[Jobiizy] → Pas une page single job (' . $_SERVER['REQUEST_URI'] . ')');
        }
        return;
    }

    $rel     = '/assets/js/replace-cvform.js';
    $js_path = get_stylesheet_directory() . $rel;
    $js_uri  = get_stylesheet_directory_uri() . $rel;

    if (!file_exists($js_path)) return;

    wp_enqueue_script('jobiizy-replace-cvform', $js_uri, ['jquery'], filemtime($js_path), true);

    if (current_user_can('administrator')) {
        add_action('wp_footer', function() {
            jobiizy_success('jobiizy-replace-cvform.js enqueued ✅');
        }, 99);
    }
}

// ── splitview-redirect.js + nettoyage scripts polluants (priority 100) ───────
add_action('wp_enqueue_scripts', function() {
    if (!is_page(JOBIIZY_JOBS_PAGE_ID)
        && !is_post_type_archive('job_listing')
        && !is_page_template('templates/page-emplois-refonte.php')) return;

    $rel  = '/assets/js/splitview-redirect.js';
    $path = get_stylesheet_directory() . $rel;
    $uri  = get_stylesheet_directory_uri() . $rel;

    if (file_exists($path)) {
        wp_enqueue_script('jobiizy-splitview-redirect', $uri, [], filemtime($path), true);
    }

    wp_dequeue_script('jobiizy-splitview-company-link');
    wp_deregister_script('jobiizy-splitview-company-link');
    wp_dequeue_script('jobiizy-global');
    wp_deregister_script('jobiizy-global');
}, 100);

// ── Google Fonts — Inter ─────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('inter-google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null);
});

// ── halfajax-button.js — pages split-view ────────────────────────────────────
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_halfajax_button_script');
function jobiizy_enqueue_halfajax_button_script() {
    global $post;

    $is_split_view = (
        (is_page() && isset($post->post_name) && strpos($post->post_name, 'jobs-s-') === 0)
        || is_page(['jobs-split-view', 'jobs-split-view-2', 'jobiizy-offres'])
    );
    if (!$is_split_view) return;

    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/halfajax-button.js';
    $js_path = get_stylesheet_directory()     . '/assets/js/halfajax-button.js';

    if (file_exists($js_path)) {
        wp_enqueue_script('jobiizy-halfajax-button', $js_uri, ['jquery'], filemtime($js_path), true);
    }
}

// ── Bookmark CSS + JS (priority 20) ─────────────────────────────────────────
add_action('wp_enqueue_scripts', function() {
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    $css = $dir . '/assets/css/jobiizy-bookmark.css';
    if (file_exists($css)) {
        wp_enqueue_style('jobiizy-bookmark-css', $uri . '/assets/css/jobiizy-bookmark.css', [], filemtime($css));
    }

    $js = $dir . '/assets/js/jobiizy-bookmark.js';
    if (file_exists($js)) {
        wp_enqueue_script('jobiizy-bookmark-js', $uri . '/assets/js/jobiizy-bookmark.js', ['jquery'], filemtime($js), true);
    }
}, 20);

// ── inject-company-name.js (priority 35) ─────────────────────────────────────
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_inject_company_script', 35);
function jobiizy_enqueue_inject_company_script() {
    $js_path = get_stylesheet_directory() . '/assets/js/inject-company-name.js';
    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/inject-company-name.js';

    if (file_exists($js_path)) {
        wp_enqueue_script('jobiizy-inject-company-name', $js_uri, ['jquery'], filemtime($js_path), true);
    }
}

// ── Split view CSS + JS — pages job listing ──────────────────────────────────
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_split_view_assets');
function jobiizy_enqueue_split_view_assets() {
    if (!is_singular('job_listing') && !is_post_type_archive('job_listing')
        && !is_tax('job_listing_category') && !is_tax('job_listing_type')) {
        return;
    }

    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    $css_file = $theme_dir . '/assets/css/jobiizy-split-view.css';
    if (file_exists($css_file)) {
        wp_enqueue_style('jobiizy-split-view', $theme_uri . '/assets/css/jobiizy-split-view.css', [], filemtime($css_file));
    }

    $js_file = $theme_dir . '/assets/js/jobiizy-split-view.js';
    if (file_exists($js_file)) {
        wp_enqueue_script('jobiizy-split-view', $theme_uri . '/assets/js/jobiizy-split-view.js', ['jquery'], filemtime($js_file), true);
    }
}

// ── HC Offcanvas nav + mobile-upgrade (priority 30) ──────────────────────────
add_action('wp_enqueue_scripts', 'jobiizy_enqueue_hc_offcanvas', 30);
function jobiizy_enqueue_hc_offcanvas() {
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    wp_enqueue_style('hc-offcanvas-nav', $uri . '/assets/libs/hc-offcanvas-nav/hc-offcanvas-nav.carbon.css', [], '3.3.2');
    wp_enqueue_script('hc-offcanvas-nav', $uri . '/assets/libs/hc-offcanvas-nav/hc-offcanvas-nav.js', [], '3.3.2', true);

    $css_m = $dir . '/assets/css/jobiizy-mobile-upgrade.css';
    if (file_exists($css_m)) {
        wp_enqueue_style('jobiizy-mobile-upgrade', $uri . '/assets/css/jobiizy-mobile-upgrade.css', ['cariera-frontend', 'hc-offcanvas-nav'], filemtime($css_m));
    }

    $js_m = $dir . '/assets/js/jobiizy-mobile-upgrade.js';
    if (file_exists($js_m)) {
        wp_enqueue_script('jobiizy-mobile-upgrade', $uri . '/assets/js/jobiizy-mobile-upgrade.js', ['jquery', 'hc-offcanvas-nav'], filemtime($js_m), true);
    }
}

// ── jobiizy-emplois-refonte.css + jobiizy-split-view.css — template Emplois Refonte ──
add_action('wp_enqueue_scripts', function() {
    if (!is_page_template('templates/page-emplois-refonte.php')) return;
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    $split_css = $dir . '/assets/css/jobiizy-split-view.css';
    if (file_exists($split_css)) {
        wp_enqueue_style('jobiizy-split-view', $uri . '/assets/css/jobiizy-split-view.css', [], filemtime($split_css));
    }

    $path = $dir . '/assets/css/jobiizy-emplois-refonte.css';
    if (!file_exists($path)) return;
    wp_enqueue_style(
        'jobiizy-emplois-refonte',
        $uri . '/assets/css/jobiizy-emplois-refonte.css',
        ['jobiizy-design-override', 'jobiizy-split-view'],
        filemtime($path)
    );
});
