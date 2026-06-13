<?php
/**
 * CVthèque — protection d'accès et lien menu conditionnel
 */

add_filter('nav_menu_link_attributes', 'jobiizy_filter_cvtheque_menu_link', 10, 3);
function jobiizy_filter_cvtheque_menu_link($atts, $item, $args) {
    if ($args->theme_location === 'primary' && $item->ID === 13414) {
        $user  = wp_get_current_user();
        $roles = (array) $user->roles;

        $has_access = in_array('administrator', $roles) || in_array('employer-plus', $roles);

        if (!$has_access && in_array('employer', $roles)) {
            if (function_exists('job_package_is_active') && job_package_is_active($user->ID)) {
                $has_access = true;
            }
        }

        if ($has_access) {
            $atts['href'] = home_url('/jobiizy-profils/');
        }
    }
    return $atts;
}

add_action('template_redirect', 'jobiizy_protected_cvtheque_page');
function jobiizy_protected_cvtheque_page() {
    if (!is_page('jobiizy-profils')) return;

    if (!is_user_logged_in()) {
        wp_redirect(home_url('/jobiizy-profils-future/'));
        exit;
    }

    $user        = wp_get_current_user();
    $user_roles  = (array) $user->roles;
    $allowed_roles = get_option('jobiizy_private_access_roles', []);
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    $has_access = (bool) array_intersect($user_roles, $allowed_roles);

    if (!$has_access && in_array('employer', $user_roles)) {
        if (function_exists('job_package_is_active') && job_package_is_active($user->ID)) {
            $has_access = true;
        }
    }

    if (!$has_access) {
        wp_redirect(home_url('/jobiizy-profils-future/'));
        exit;
    }
}

function has_active_job_package($user_id) {
    if (!class_exists('WooCommerce')) return false;

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed', 'processing'],
        'limit'       => -1,
    ]);

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product || $product->get_type() !== 'job_package') continue;

            $packages = get_posts([
                'post_type'      => 'job_package',
                'post_status'    => 'publish',
                'author'         => $user_id,
                'posts_per_page' => -1,
                'meta_query'     => [
                    ['key' => '_order_id',   'value' => $order->get_id(),   'compare' => '='],
                    ['key' => '_product_id', 'value' => $product->get_id(), 'compare' => '='],
                ],
            ]);

            foreach ($packages as $package) {
                $expiry = get_post_meta($package->ID, '_package_expiry', true);
                if (!$expiry || strtotime($expiry) >= current_time('timestamp')) {
                    return true;
                }
            }

            if (empty($packages)) return true;
        }
    }
    return false;
}
