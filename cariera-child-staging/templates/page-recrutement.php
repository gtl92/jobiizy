<?php
/**
 * Template Name: Page Recrutement
 * Description: Page packages employeur Jobiizy
 */
get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        // Charger le HTML packages
        include get_stylesheet_directory() . '/pages/jobiizy-packages.html';
        ?>
    </main>
</div>

<?php get_footer(); ?>