<!-- === HEADER MOBILE JOBIIZY (compatible Cariera) === -->
<header class="cariera-main-header main-header header1 sticky-header sticky-mobile-header">
  <div class="header-container container-fluid">

    <!-- ===== Logo ===== -->
    <div class="logo">
      <a class="navbar-brand logo-wrapper" href="<?php echo esc_url(home_url('/')); ?>" title="<?php bloginfo('name'); ?>" rel="home">
        <img src="https://jobiizy.com/wp-content/uploads/2025/02/LogoaJobIIZYLatest-1.png" class="logo" alt="<?php bloginfo('name'); ?>">
        <img src="https://jobiizy.com/wp-content/uploads/2025/02/LogoaJobIIZYLatest_White-1.png" class="logo-white" alt="<?php bloginfo('name'); ?>">
      </a>
    </div>

    <!-- ===== Extra Menu Icons ===== -->
    <div class="extra-menu">
      <div class="extra-menu-item extra-notifications">
        <a href="#" id="notifications-trigger" aria-label="Notifications">
          <i class="las la-bell"></i>
          <span class="notification-count">3</span>
        </a>
      </div>
      <div class="extra-menu-item extra-shop mini-cart woocommerce">
        <a href="#shopping-cart-modal" class="cart-contents popup-with-zoom-anim" aria-label="Panier">
          <i class="las la-shopping-bag"></i>
          <span class="notification-count cart-count">1</span>
        </a>
      </div>
      <div class="extra-menu-item extra-user">
        <a href="#" id="user-account-extra" aria-label="Compte utilisateur">
          <span class="avatar-img">
            <?php
            $current_user = wp_get_current_user();
            $avatar = get_avatar_url($current_user->ID, ['size' => 40]);
            ?>
            <img src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" width="40" height="40" class="avatar photo">
          </span>
        </a>
      </div>
    </div>

    <!-- ===== Mobile Menu Trigger ===== -->
    <div class="mmenu-trigger">
      <button id="mobile-nav-toggler" class="hamburger hamburger--collapse" type="button" aria-label="Menu mobile">
        <span class="hamburger-box">
          <span class="hamburger-inner"></span>
        </span>
      </button>
    </div>

  </div>
</header>