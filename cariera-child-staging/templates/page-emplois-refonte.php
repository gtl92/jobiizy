<?php
/**
 * Template Name: Emplois — Refonte
 *
 * Page /emplois/ — Design System V1.
 * Assigner via : Pages → Emplois → Attributs de page → Modèle → "Emplois — Refonte"
 */

$hero_keywords = sanitize_text_field($_GET['keywords'] ?? '');
$hero_location = sanitize_text_field($_GET['location'] ?? '');

if (!function_exists('jze_cat_icon_class')) {
    function jze_cat_icon_class(string $slug): string {
        $map = [
            'marketing'      => 'la-bullhorn',
            'communication'  => 'la-bullhorn',
            'informatique'   => 'la-code',
            'developpement'  => 'la-code',
            'it'             => 'la-microchip',
            'tech'           => 'la-microchip',
            'digital'        => 'la-microchip',
            'web'            => 'la-globe',
            'commerce'       => 'la-shopping-bag',
            'vente'          => 'la-shopping-bag',
            'retail'         => 'la-store',
            'sante'          => 'la-heartbeat',
            'medical'        => 'la-stethoscope',
            'finance'        => 'la-chart-line',
            'comptabilite'   => 'la-calculator',
            'banque'         => 'la-university',
            'education'      => 'la-graduation-cap',
            'formation'      => 'la-graduation-cap',
            'tourisme'       => 'la-plane',
            'hotellerie'     => 'la-hotel',
            'restauration'   => 'la-utensils',
            'rh'             => 'la-users',
            'ressources'     => 'la-users',
            'logistique'     => 'la-truck',
            'transport'      => 'la-truck',
            'btp'            => 'la-hard-hat',
            'construction'   => 'la-tools',
            'immobilier'     => 'la-building',
            'juridique'      => 'la-balance-scale',
            'droit'          => 'la-balance-scale',
            'luxe'           => 'la-gem',
            'mode'           => 'la-tshirt',
            'design'         => 'la-palette',
            'art'            => 'la-palette',
            'media'          => 'la-broadcast-tower',
            'presse'         => 'la-newspaper',
            'science'        => 'la-flask',
            'recherche'      => 'la-microscope',
            'industrie'      => 'la-industry',
            'energie'        => 'la-bolt',
            'securite'       => 'la-shield-alt',
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

$job_query_args = [
    'post_type'      => 'job_listing',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'orderby'        => 'date',
    'order'          => 'DESC',
];
if ($hero_keywords) {
    $job_query_args['s'] = $hero_keywords;
}
if ($hero_location) {
    $job_query_args['meta_query'] = [[
        'key'     => '_job_location',
        'value'   => $hero_location,
        'compare' => 'LIKE',
    ]];
}
$jze_jobs_query = new WP_Query($job_query_args);

$jze_categories = get_terms([
    'taxonomy'   => 'job_listing_category',
    'hide_empty' => true,
    'number'     => 8,
    'orderby'    => 'count',
    'order'      => 'DESC',
]);

get_header();
?>
<main id="jze-page" class="jze-page">

  <!-- ══ HERO ══════════════════════════════════════════════════════════════════ -->
  <section class="jze-hero" aria-label="Recherche d'emploi">
    <div class="jze-hero-bg" aria-hidden="true"></div>
    <div class="jze-hero-grain" aria-hidden="true"></div>
    <div class="jze-container jze-hero-inner">

      <span class="jze-hero-eyebrow">Emploi 100&nbsp;% en français · Israël</span>
      <h1 class="jze-hero-title">Votre prochain job<br><em>vous attend ici.</em></h1>
      <p class="jze-hero-sub">La première plateforme dédiée aux francophones en Israël. Offres fraîches, recruteurs vérifiés, candidature en un clic.</p>

      <form class="jze-search" method="GET" action="" role="search">
        <div class="jze-search-field">
          <i class="las la-search" aria-hidden="true"></i>
          <input type="text" name="keywords"
                 placeholder="Métier, entreprise, compétence…"
                 value="<?php echo esc_attr($hero_keywords); ?>"
                 autocomplete="off">
        </div>
        <span class="jze-search-sep" aria-hidden="true"></span>
        <div class="jze-search-field">
          <i class="las la-map-marker" aria-hidden="true"></i>
          <input type="text" name="location"
                 placeholder="Ville, région…"
                 value="<?php echo esc_attr($hero_location); ?>"
                 autocomplete="off">
        </div>
        <button type="submit" class="jze-search-btn">
          <i class="las la-search" aria-hidden="true"></i>
          <span>Trouver</span>
        </button>
      </form>

      <div class="jze-hero-chips" aria-label="Recherches populaires">
        <span class="jze-chip-hint">Populaires :</span>
        <?php foreach (['Marketing Tel Aviv', 'Développeur', 'Commerce', 'Remote', 'Alternance'] as $term) : ?>
          <a href="?keywords=<?php echo urlencode($term); ?>" class="jze-chip-glass"><?php echo esc_html($term); ?></a>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- ══ CATÉGORIES ════════════════════════════════════════════════════════════ -->
  <?php if (!is_wp_error($jze_categories) && !empty($jze_categories)) : ?>
  <section class="jze-cats" aria-label="Explorer par catégorie">
    <div class="jze-container">
      <div class="jze-section-head">
        <span class="jze-eyebrow">Explorer</span>
        <h2>Par catégorie</h2>
      </div>
      <div class="jze-cats-grid">
        <?php foreach ($jze_categories as $cat) :
            $icon_class = jze_cat_icon_class($cat->slug);
            $cat_url    = get_term_link($cat);
        ?>
          <a href="<?php echo esc_url($cat_url); ?>" class="jze-cat-tile">
            <div class="jze-cat-icon"><i class="las <?php echo esc_attr($icon_class); ?>"></i></div>
            <div class="jze-cat-info">
              <div class="jze-cat-name"><?php echo esc_html($cat->name); ?></div>
              <div class="jze-cat-count"><?php printf('%d offre%s', $cat->count, $cat->count > 1 ? 's' : ''); ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ══ OFFRES ════════════════════════════════════════════════════════════════ -->
  <section class="jze-featured" aria-label="Offres sélectionnées">
    <div class="jze-container">
      <div class="jze-section-head jze-head-row">
        <div>
          <span class="jze-eyebrow"><?php echo ($hero_keywords || $hero_location) ? 'Résultats' : 'À la une'; ?></span>
          <h2><?php echo ($hero_keywords || $hero_location) ? 'Offres correspondantes' : 'Offres sélectionnées pour vous'; ?></h2>
        </div>
        <?php $archive_url = get_post_type_archive_link('job_listing') ?: home_url('/'); ?>
        <a href="<?php echo esc_url($archive_url); ?>" class="jze-all-link">
          Voir toutes les offres <i class="las la-arrow-right"></i>
        </a>
      </div>

      <?php if ($jze_jobs_query->have_posts()) : ?>
      <div class="jze-job-grid">
        <?php while ($jze_jobs_query->have_posts()) : $jze_jobs_query->the_post();
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
      <?php else : ?>
        <p class="jze-no-results">Aucune offre ne correspond à votre recherche.</p>
      <?php endif; ?>

    </div>
  </section>

</main>
<?php get_footer();
