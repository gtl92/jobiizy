<?php
/**
 * Menus dynamiques selon le rôle utilisateur
 */

function jobiizy_register_menus() {
    register_nav_menus([
        'menu_admin'     => __('Menu Admin'),
        'menu_employeur' => __('Menu Employeur'),
        'menu_candidat'  => __('Menu Candidat'),
        'menu_default'   => __('Menu Public'),
    ]);
}
add_action('init', 'jobiizy_register_menus');

add_filter('wp_nav_menu_args', function( $args ) {
    if ( isset( $args['theme_location'] ) && $args['theme_location'] === 'primary' ) {
        $user          = wp_get_current_user();
        $menu_location = 'menu_default';

        if ( in_array('administrator', (array) $user->roles) ) {
            $menu_location = 'menu_admin';
        } elseif ( in_array('employer', (array) $user->roles) ) {
            $menu_location = 'menu_employeur';
        } elseif ( in_array('candidate', (array) $user->roles) ) {
            $menu_location = 'menu_candidat';
        }

        $args['theme_location'] = $menu_location;
    }
    return $args;
}, 10);
