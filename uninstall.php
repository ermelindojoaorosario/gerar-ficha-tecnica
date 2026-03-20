<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Delete plugin options
 */
delete_option( 'mozbeats_settings' );

/**
 * Delete post meta keys
 */
$mozbeats_meta_keys = [
    '_mozbeats_cache',
    '_mozbeats_backup_content',
    '_mozbeats_backup_time',
    '_mozbeats_anuncio',
    '_mozbeats_sem_genero',
    '_mozbeats_titulo_processado'
];

foreach ( $mozbeats_meta_keys as $mozbeats_meta_key ) {
    delete_post_meta_by_key( $mozbeats_meta_key );
}

/**
 * Delete term meta for 'mozbeats_genero'
 * Loop through all terms that have this meta
 */
$mozbeats_terms_with_meta = get_terms( [
    'taxonomy'   => 'category', // substitua pela sua taxonomia de artistas se necessário
    'hide_empty' => false,
] );

if ( ! is_wp_error( $mozbeats_terms_with_meta ) ) {
    foreach ( $mozbeats_terms_with_meta as $mozbeats_term ) {
        delete_term_meta( $mozbeats_term->term_id, 'mozbeats_genero' );
    }
}