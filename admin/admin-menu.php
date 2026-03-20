<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mozbeats_add_admin_menu() {

    add_menu_page(
        'Gerar Ficha Técnica',
        'Gerar Ficha Técnica',
        'manage_options',
        'mozbeats-config',
        'mozbeats_settings_page',
        'dashicons-album',
        plugin_dir_url(__FILE__) . 'assets/logogera.png',
        25
    );

    add_submenu_page(
        'mozbeats-config',
        'Artistas',
        'Artistas',
        'manage_options',
        'mozbeats-artistas',
        'mozbeats_exibir_artistas'
    );

    add_submenu_page(
    'mozbeats-config',
    'Converter Shortcode',
    'Converter Shortcode',
    'manage_options',
    'mozbeats-converter',
    'mozbeats_converter_admin_page'
);
}

add_action( 'admin_menu', 'mozbeats_add_admin_menu' );