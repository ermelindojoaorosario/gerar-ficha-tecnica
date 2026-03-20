<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action('wp_ajax_mozbeats_set_genero', function(){

    if(!current_user_can('manage_options')){
        wp_send_json_error('Sem permissão');
    }

    if ( ! isset($_POST['nonce']) ) {
    wp_send_json_error('Nonce ausente');
}

$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

if ( ! wp_verify_nonce( $nonce, 'mozbeats_nonce' ) ) {
    wp_send_json_error('Nonce inválido');
}

    if(
        !isset($_POST['term_id']) ||
        !isset($_POST['genero'])
    ){
        wp_send_json_error('Dados incompletos');
    }

    $term_id = intval($_POST['term_id']);
    $genero  = sanitize_text_field(wp_unslash($_POST['genero']));

    update_term_meta($term_id, 'mozbeats_genero', $genero);

    wp_send_json_success([
        'genero' => esc_html($genero)
    ]);
});


add_action('admin_enqueue_scripts', function(){

    wp_enqueue_script(
        'mozbeats-js',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('mozbeats-js', 'mozbeats_ajax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mozbeats_nonce')
    ]);

});


function mozbeats_exibir_artistas(){

    echo '<div class="wrap">';
    echo '<h1>Artistas Detectados</h1>';

    echo '<input type="text" id="mozbeats-busca-artista" placeholder="Buscar artista..." style="width:300px;padding:8px;margin:15px 0;">';

    echo '<div id="mozbeats-artistas-grid">';

    $options = get_option('mozbeats_settings');

    $generos = !empty($options['custom_generos'])
        ? array_map('trim', explode("\n", $options['custom_generos']))
        : array();

    $tags = get_tags(array(
        'hide_empty' => true
    ));

    foreach($tags as $tag){

        $nome = $tag->name;

        if(in_array($nome,$generos)) continue;
        if(preg_match('/^\d{4}$/',$nome)) continue;

        $link = get_tag_link($tag->term_id);

        echo '<div class="mozbeats-artista-card" data-name="'.esc_attr(strtolower($nome)).'">';

        echo '<h3>'.esc_html($nome).'</h3>';

        echo '<p><strong>Músicas:</strong> '.intval($tag->count).'</p>';

        echo '<a class="button button-primary" target="_blank" href="'.esc_url($link).'">Ver Página</a>';
        // Exibe gênero atual
        $genero = get_term_meta($tag->term_id, 'mozbeats_genero', true);
echo '<p><strong>Gênero:</strong> '.(esc_html($genero) ?: 'Não definido').'</p>';

// Botões para definir gênero
echo '<button class="button mozbeats-set-genero" data-id="'.esc_html($tag->term_id).'" data-genero="masculino">👨 Masculino</button> ';
echo '<button class="button mozbeats-set-genero" data-id="'.esc_html($tag->term_id).'" data-genero="feminino">👩 Feminino</button>';
echo '<button class="button mozbeats-set-genero" data-id="'.esc_html($tag->term_id).'" data-genero="grupo">👥 Grupo</button>';

        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}
