<?php
/**
 * Template Name: Offre Globale — Jobiizy
 *
 * Page /emplois-offre-globale/
 * Design : cards jze-* de page-emplois-refonte.php
 * Formulaire : style sombre avec inputs arrondis (inspiré /les-jobs/)
 *
 * CSS chargé via enqueue.php : jobiizy-offre-globale.css
 * Body class : jobiizy-offre-globale-page (à ajouter dans body-class.php)
 *
 * Assigner via : Pages → Offre Globale → Attributs de page
 *   → Modèle → "Offre Globale — Jobiizy"
 */

// ── Helpers réutilisés depuis page-emplois-refonte.php ────────────────────────
if (!function_exists('jze_cat_icon_class')) {
    function jze_cat_icon_class(string $slug): string {
        $map = [
            'marketing'     => 'la-bullhorn',
            'communication' => 'la-bullhorn',
            'informatique'  => 'la-code',
            'developpement' => 'la-code',
            'it'            => 'la-microchip',
            'tech'          => 'la-microchip',
            'digital'       => 'la-microchip',
            'web'           => 'la-globe',
            'commerce'      => 'la-shopping-bag',
            'vente'         => 'la-shopping-bag',
            'retail'        => 'la-store',
            'sante'         => 'la-heartbeat',
            'medical'       => 'la-stethoscope',
            'finance'       => 'la-chart-line',
            'comptabilite'  => 'la-calculator',
            'banque'        => 'la-university',
            'education'     => 'la-graduation-cap',
            'formation'     => 'la-graduation-cap',
            'tourisme'      => 'la-plane',
            'hotellerie'    => 'la-hotel',
            'restauration'  => 'la-utensils',
            'rh'            => 'la-users',
            'ressources'    => 'la-users',
            'logistique'    => 'la-truck',
            'transport'     => 'la-truck',
            'btp'           => 'la-hard-hat',
            'construction'  => 'la-tools',
            'immobilier'    => 'la-building',
            'juridique'     => 'la-balance-scale',
            'droit'         => 'la-balance-scale',
            'luxe'          => 'la-gem',
            'mode'          => 'la-tshirt',
            'design'        => 'la-palette',
            'art'           => 'la-palette',
            'media'         => 'la-broadcast-tower',
            'presse'        => 'la-newspaper',
            'science'       => 'la-flask',
            'recherche'     => 'la-microscope',
            'industrie'     => 'la-industry',
            'energie'       => 'la-bolt',
            'securite'      => 'la-shield-alt',
        ];
        foreach ($map as $keyword => $icon) {
            if (strpos($slug, $keyword) !== false) {
                return $icon;
            }
        }
        return 'la-briefcase';
    }
}

if (!function_exists('jze_logo_palette')) {
    function jze_logo_palette(string $seed): array {
        $palettes = [
            ['bg' => '#EAF6FF', 'fg' => '#006FA3'],
            ['bg' => '#F0EDFF', 'fg' => '#3C3CA1'],
            ['bg' => '#FFF3E8', 'fg' => '#C05A00'],
            ['bg' => '#EDFCF5', 'fg' => '#007A43'],
            ['bg' => '#FFF0F0', 'fg' => '#C0392B'],
        ];
        $idx = abs(crc32($seed)) % count($palettes);
        return $palettes[$idx];
    }
}

// ── Paramètres de recherche / filtres URL ─────────────────────────────────────
$search_keywords = sanitize_text_field($_GET['search_keywords'] ?? '');
$search_location = sanitize_text_field($_GET['search_location'] ?? '');
$search_category = isset($_GET['search_categories']) ? absint($_GET['search_categories']) : 0;
$search_type     = sanitize_text_field($_GET['search_job_type'] ?? '');

// Pagination
$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = 12;

// ── Query WP_Query filtrée ────────────────────────────────────────────────────
$query_args = [
    'post_type'      => 'job_listing',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

if ($search_keywords) {
    $query_args['s'] = $search_keywords;
}
if ($search_location) {
    $query_args['meta_query'][] = [
        'key'     => '_job_location',
        'value'   => $search_location,
        'compare' => 'LIKE',
    ];
}
if ($search_category) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'job_listing_category',
        'field'    => 'term_id',
        'terms'    => $search_category,
    ];
}
if ($search_type) {
    $query_args['tax_query'][] = [
        'taxonomy' => 'job_listing_type',
        'field'    => 'slug',
        'terms'    => $search_type,
    ];
}

$jobs_query = new WP_Query($query_args);

// ── Catégories pour le filtre ─────────────────────────────────────────────────
$categories = get_terms([
    'taxonomy'   => 'job_listing_category',
    'hide_empty' => true,
    'orderby'    => 'name',
]);

// ── Types de contrats pour le filtre ─────────────────────────────────────────
$job_types = get_terms([
    'taxonomy'   => 'job_listing_type',
    'hide_empty' => true,
]);

get_header();
?>

<main id="jzog-page" class="jzog-page">

  <!-- ══ BARRE DE RECHERCHE SOMBRE ══════════════════════════════════════════ -->
  <section class="jzog-search-bar" aria-label="Filtrer les offres">
    <div class="jze-hero-bg" aria-hidden="true"></div>
    <div class="jze-hero-grain" aria-hidden="true"></div>

    <div class="jze-container jze-hero-inner">
      <span class="jze-hero-eyebrow">Emploi 100&nbsp;% en français · Israël</span>
      <h1 class="jze-hero-title">Votre prochain job<br><em>vous attend ici.</em></h1>

      <div class="jzog-search-header">
        <h1 class="jzog-search-title">
          <i class="las la-search" aria-hidden="true"></i>
          Toutes les offres
        </h1>
        <?php if ($jobs_query->found_posts) : ?>
          <span class="jzog-count">
            <?php printf(
                '%d offre%s',
                $jobs_query->found_posts,
                $jobs_query->found_posts > 1 ? 's' : ''
            ); ?>
          </span>
        <?php endif; ?>
      </div>

      <form id="jzog-main-form" class="jzog-form" method="GET" action="" role="search">

        <!-- Mot-clé -->
        <div class="jzog-field">
          <i class="las la-search jzog-field-icon" aria-hidden="true"></i>
          <input
            type="text"
            name="search_keywords"
            id="jzog-keywords"
            placeholder="Métier, entreprise, compétence…"
            value="<?php echo esc_attr($search_keywords); ?>"
            autocomplete="off"
            class="jzog-input"
          >
          <!-- Dropdown autocomplétion (même structure que /emplois/) -->
          <div class="jobiizy-autocomplete-dropdown jobiizy-keywords-dropdown">
            <div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
            <div class="jobiizy-autocomplete-results"></div>
          </div>
        </div>

        <!-- Localisation -->
        <div class="jzog-field jzog-field--location">
          <i class="las la-map-marker jzog-field-icon" aria-hidden="true"></i>
          <input
            type="text"
            name="search_location"
            id="jzog-location"
            placeholder="Ville, région…"
            value="<?php echo esc_attr($search_location); ?>"
            autocomplete="off"
            class="jzog-input"
          >
          <div class="jobiizy-autocomplete-dropdown jobiizy-location-dropdown">
            <div class="jobiizy-autocomplete-loader"><i class="las la-spinner la-spin"></i></div>
            <div class="jobiizy-autocomplete-results"></div>
          </div>
        </div>

        <!-- Catégorie -->
        <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
        <div class="jzog-field jzog-field--select">
          <i class="las la-tag jzog-field-icon" aria-hidden="true"></i>
          <select name="search_categories" class="jzog-select">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $cat) : ?>
              <option value="<?php echo esc_attr($cat->term_id); ?>"
                <?php selected($search_category, $cat->term_id); ?>>
                <?php echo esc_html($cat->name); ?>
                (<?php echo absint($cat->count); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Type de contrat -->
        <?php if (!is_wp_error($job_types) && !empty($job_types)) : ?>
        <div class="jzog-field jzog-field--select">
          <i class="las la-briefcase jzog-field-icon" aria-hidden="true"></i>
          <select name="search_job_type" class="jzog-select">
            <option value="">Tous les contrats</option>
            <?php foreach ($job_types as $type) : ?>
              <option value="<?php echo esc_attr($type->slug); ?>"
                <?php selected($search_type, $type->slug); ?>>
                <?php echo esc_html($type->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
<!-- Ajouter le bouton Filtres juste avant le bouton submit -->
<button type="button" class="jzog-btn-filters" id="jzog-btn-filters">
  <i class="las la-sliders-h" aria-hidden="true"></i>
  Filtres
</button>

        <button type="submit" class="jzog-btn-search">
          <i class="las la-search" aria-hidden="true"></i>
          <span>Rechercher</span>
        </button>

        <?php if ($search_keywords || $search_location || $search_category || $search_type) : ?>
          <a href="<?php echo esc_url(get_permalink()); ?>" class="jzog-btn-reset" title="Effacer les filtres">
            <i class="las la-times"></i>
          </a>
        <?php endif; ?>

      </form>
      <!-- Overlay -->
<div class="jzog-drawer-overlay" id="jzog-drawer-overlay"></div>

<!-- Drawer filtres mobile -->
<div class="jzog-filters-drawer" id="jzog-filters-drawer" aria-hidden="true">
  <div class="jzog-drawer-handle"></div>
  <p class="jzog-drawer-title"><i class="las la-sliders-h"></i> Filtres</p>

  <!-- Localisation -->
  <div class="jzog-field jzog-field--select">
    <i class="las la-map-marker jzog-field-icon"></i>
    <input type="text" form="jzog-main-form" name="search_location"
           id="jzog-location-drawer" placeholder="Ville, région…"
           class="jzog-input" autocomplete="off">
  </div>

  <!-- Catégorie -->
  <?php if (!is_wp_error($categories) && !empty($categories)) : ?>
  <div class="jzog-field jzog-field--select">
    <i class="las la-tag jzog-field-icon"></i>
    <select name="search_categories" form="jzog-main-form" class="jzog-select">
      <option value="">Toutes catégories</option>
      <?php foreach ($categories as $cat) : ?>
        <option value="<?php echo esc_attr($cat->term_id); ?>"
          <?php selected($search_category, $cat->term_id); ?>>
          <?php echo esc_html($cat->name); ?> (<?php echo absint($cat->count); ?>)
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <!-- Contrat -->
  <?php if (!is_wp_error($job_types) && !empty($job_types)) : ?>
  <div class="jzog-field jzog-field--select">
    <i class="las la-briefcase jzog-field-icon"></i>
    <select name="search_job_type" form="jzog-main-form" class="jzog-select">
      <option value="">Tous les contrats</option>
      <?php foreach ($job_types as $type) : ?>
        <option value="<?php echo esc_attr($type->slug); ?>"
          <?php selected($search_type, $type->slug); ?>>
          <?php echo esc_html($type->name); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endif; ?>

  <button type="submit" form="jzog-main-form" class="jzog-btn-search jzog-drawer-apply">
    <i class="las la-search"></i> Appliquer les filtres
  </button>
</div>
    </div>
  </section>

  <!-- ══ GRILLE DES OFFRES ═══════════════════════════════════════════════════ -->
  <section class="jzog-results" aria-label="Liste des offres">
    <div class="jze-container">

      <?php if ($jobs_query->have_posts()) : ?>

        <div class="jze-job-grid">
          <?php while ($jobs_query->have_posts()) : $jobs_query->the_post();
              $job_id      = get_the_ID();
              $company     = (string) get_post_meta($job_id, '_company_name', true);
              $location_v  = (string) get_post_meta($job_id, '_job_location', true);
              $logo_url    = (string) get_post_meta($job_id, '_company_logo', true);
              $salary      = (string) get_post_meta($job_id, '_job_salary', true);
              $is_featured = (bool)   get_post_meta($job_id, '_featured', true);
              $is_new      = (time() - get_the_time('U')) < 3 * DAY_IN_SECONDS;
              $palette     = jze_logo_palette($company ?: get_the_title());
              $letter      = mb_strtoupper(mb_substr($company ?: get_the_title(), 0, 1));
              $permalink   = get_the_permalink();
              $is_logged   = is_user_logged_in();

              $type_terms  = get_the_terms($job_id, 'job_listing_type');
              $job_type    = (!is_wp_error($type_terms) && !empty($type_terms)) ? $type_terms[0]->name : '';

              $cat_terms   = get_the_terms($job_id, 'job_listing_category');
              $cat_label   = (!is_wp_error($cat_terms) && !empty($cat_terms)) ? $cat_terms[0]->name : '';
          ?>

            <article class="jze-card<?php echo $is_featured ? ' jze-card--featured' : ''; ?>">

              <?php if ($is_featured) : ?>
                <div class="jze-ribbon"><i class="las la-star"></i> À la une</div>
              <?php endif; ?>

              <div class="jze-job-top">
                <div class="jze-job-logo"
                     style="background:<?php echo esc_attr($palette['bg']); ?>;color:<?php echo esc_attr($palette['fg']); ?>;">
                  <?php if ($logo_url) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company); ?>">
                  <?php else : ?>
                    <?php echo esc_html($letter); ?>
                  <?php endif; ?>
                </div>
                <div class="jze-job-ttl">
                  <h3><?php the_title(); ?></h3>
                  <span class="jze-job-company">
                    <?php echo esc_html($company); ?>
                    <?php if ($company && $location_v) echo ' · '; ?>
                    <?php echo esc_html($location_v); ?>
                  </span>
                </div>
              </div>

              <p class="jze-job-desc"><?php echo wp_trim_words(get_the_excerpt(), 18, '…'); ?></p>

              <div class="jze-job-meta">
                <?php if ($job_type) : ?>
                  <span><i class="las la-briefcase"></i><?php echo esc_html($job_type); ?></span>
                <?php endif; ?>
                <?php if ($salary) : ?>
                  <span><i class="las la-money-bill-wave"></i><?php echo esc_html($salary); ?></span>
                <?php endif; ?>
                <span>
                  <i class="las la-clock"></i>
                  <?php echo esc_html(human_time_diff(get_the_time('U'), current_time('timestamp'))); ?>
                </span>
              </div>

              <div class="jze-job-foot">
                <div class="jze-job-badges">
                  <?php if ($is_new) : ?>
                    <span class="jze-badge jze-badge--new">Nouveau</span>
                  <?php endif; ?>
                  <?php if ($cat_label) : ?>
                    <span class="jze-badge"><?php echo esc_html($cat_label); ?></span>
                  <?php endif; ?>
                </div>
                <a href="<?php echo esc_url($permalink); ?>"
                   class="jze-cta-btn"
                   <?php if (!$is_logged) echo 'data-popup-login="1"'; ?>>
                  Voir l'offre <i class="las la-arrow-right"></i>
                </a>
              </div>

            </article>

          <?php endwhile; wp_reset_postdata(); ?>
        </div>

        <!-- ── Pagination ───────────────────────────────────────────────── -->
        <?php if ($jobs_query->max_num_pages > 1) :
            $base_url = get_permalink();
            $params   = array_filter([
                'search_keywords'   => $search_keywords,
                'search_location'   => $search_location,
                'search_categories' => $search_category ?: '',
                'search_job_type'   => $search_type,
            ]);
        ?>
        <nav class="jzog-pagination" aria-label="Pages de résultats">
          <?php for ($p = 1; $p <= $jobs_query->max_num_pages; $p++) :
              $url = add_query_arg(array_merge($params, ['paged' => $p]), $base_url);
          ?>
            <a href="<?php echo esc_url($url); ?>"
               class="jzog-page-btn<?php echo $p === $paged ? ' is-current' : ''; ?>"
               <?php echo $p === $paged ? 'aria-current="page"' : ''; ?>>
              <?php echo $p; ?>
            </a>
          <?php endfor; ?>
        </nav>
        <?php endif; ?>

      <?php else : ?>

        <div class="jzog-empty">
          <i class="las la-search" aria-hidden="true"></i>
          <p>Aucune offre ne correspond à votre recherche.</p>
          <a href="<?php echo esc_url(get_permalink()); ?>" class="jze-cta-btn">
            Réinitialiser les filtres
          </a>
        </div>

      <?php endif; ?>

    </div>
  </section>

</main>

<?php get_footer();
