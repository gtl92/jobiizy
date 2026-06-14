<?php
/**
 * Archive taxonomy : job_listing_category
 * Remplace le template Cariera (fond bleu, formulaire gris) par un
 * design sobre fond blanc cohérent avec jobiizy-design-override.css.
 */

$term      = get_queried_object();
$term_name = $term ? $term->name        : __('Offres d\'emploi', 'cariera');
$term_desc = $term ? $term->description : '';
$term_slug = $term ? $term->slug        : '';
$term_count = $term ? (int) $term->count : 0;

get_header();
?>
<style>
/* ── Taxonomy category — styles spécifiques ─────────────────────────── */
.jzc-page {
  background: #fff;
  min-height: 60vh;
}
.jzc-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

/* Header */
.jzc-cat-header {
  border-bottom: 1px solid rgba(11, 20, 55, 0.07);
  padding: 48px 0 40px;
  background: #fff;
}
.jzc-breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8125rem;
  color: var(--jz-neutral-500, #64748b);
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.jzc-breadcrumb a {
  color: var(--jz-neutral-500, #64748b);
  text-decoration: none;
}
.jzc-breadcrumb a:hover {
  color: var(--jz-blue, #00AEEF);
}
.jzc-breadcrumb span[aria-hidden] {
  color: var(--jz-neutral-400, #94a3b8);
}
.jzc-cat-eyebrow {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: var(--jz-blue, #00AEEF);
  background: rgba(0, 174, 239, 0.08);
  border: 1px solid rgba(0, 174, 239, 0.20);
  padding: 3px 10px;
  border-radius: 100px;
  margin-bottom: 14px;
}
.jzc-cat-title {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  font-size: clamp(1.5rem, 4vw, 2.25rem);
  font-weight: 800;
  color: var(--jz-navy, #0B1437);
  margin: 0 0 10px;
  line-height: 1.2;
  letter-spacing: -0.02em;
}
.jzc-cat-meta {
  font-size: 0.9375rem;
  color: var(--jz-neutral-500, #64748b);
  margin: 0 0 10px;
}
.jzc-cat-desc {
  font-size: 0.9375rem;
  color: var(--jz-neutral-600, #475569);
  max-width: 600px;
  margin: 0;
  line-height: 1.65;
}

/* Body */
.jzc-jobs-wrap {
  padding: 48px 0 80px;
}
</style>

<main id="jzc-page" class="jzc-page">

  <!-- ══ EN-TÊTE CATÉGORIE ════════════════════════════════════════════════ -->
  <div class="jzc-cat-header">
    <div class="jzc-container">

      <nav class="jzc-breadcrumb" aria-label="<?php esc_attr_e('Fil d\'Ariane', 'cariera'); ?>">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('Accueil', 'cariera'); ?></a>
        <span aria-hidden="true">/</span>
        <a href="<?php echo esc_url(home_url('/emplois/')); ?>"><?php _e('Emplois', 'cariera'); ?></a>
        <span aria-hidden="true">/</span>
        <span><?php echo esc_html($term_name); ?></span>
      </nav>

      <p class="jzc-cat-eyebrow"><?php _e('Catégorie', 'cariera'); ?></p>
      <h1 class="jzc-cat-title"><?php echo esc_html($term_name); ?></h1>

      <?php if ($term_count > 0) : ?>
        <p class="jzc-cat-meta">
          <?php printf(
            _n('%d offre disponible', '%d offres disponibles', $term_count, 'cariera'),
            $term_count
          ); ?>
        </p>
      <?php endif; ?>

      <?php if ($term_desc) : ?>
        <p class="jzc-cat-desc"><?php echo esc_html($term_desc); ?></p>
      <?php endif; ?>

    </div>
  </div>

  <!-- ══ LISTE DES OFFRES ═════════════════════════════════════════════════ -->
  <div class="jzc-jobs-wrap">
    <div class="jzc-container">
      <?php echo do_shortcode('[jobs category="' . esc_attr($term_slug) . '" per_page="20" show_filters="false"]'); ?>
    </div>
  </div>

</main>

<?php get_footer(); ?>
