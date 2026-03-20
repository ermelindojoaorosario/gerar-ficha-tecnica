<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ===================================================== */
/* DETECTAR POSTS COM SHORTCODE */
/* ===================================================== */

function mozbeats_posts_com_shortcode(){

    $mozbeats_posts1 = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    's'              => '[mozbeats_ficha_tecnica]',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$mozbeats_posts2 = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    's'              => '[gerar_ficha_tecnica]',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$posts = array_unique( array_merge( $mozbeats_posts1, $mozbeats_posts2 ) );

    return $posts;
}

/* ===================================================== */
/* PÁGINA DO ADMIN */
/* ===================================================== */

function mozbeats_converter_admin_page(){

    $posts = mozbeats_posts_com_shortcode();
    $total = count($posts);

    ?>

    <div class="wrap">

        <h1>Converter Ficha Técnica em HTML</h1>

        <p>Esta ferramenta converte o shortcode em HTML permanente.</p>

        <h2>Posts encontrados: <?php echo esc_html($total); ?></h2>

        <?php if($total > 0 || mozbeats_tem_backup()): ?>

        <form method="post" style="margin-bottom:20px;">

    <?php wp_nonce_field('mozbeats_converter_all'); ?>
    <input type="hidden" name="mozbeats_converter_all" value="1">

    <?php submit_button('Converter Todos os Posts'); ?>

</form>

<form method="post">

    <?php wp_nonce_field('mozbeats_restore_all'); ?>
    <input type="hidden" name="mozbeats_restore_all" value="1">

    <?php submit_button('Desfazer Todas as Conversões', 'secondary'); ?>

</form>

        <?php else: ?>

        <p><strong>Nenhum post com shortcode encontrado.</strong></p>

        <?php endif; ?>

    </div>

    <?php
}

/* ===================================================== */
/* PROCESSAR CONVERSÃO */
/* ===================================================== */

add_action('admin_init','mozbeats_converter_process');

function mozbeats_converter_process(){

    if(!isset($_POST['mozbeats_converter_all'])) return;

    if(!current_user_can('manage_options')) return;

    check_admin_referer('mozbeats_converter_all');

    $posts = mozbeats_posts_com_shortcode();

    foreach($posts as $post_id){

        mozbeats_converter_shortcode_html($post_id);

    }
}
/* ===================================================== */
/* DESFAZER TODOS */
/* ===================================================== */

function mozbeats_restaurar_todos_posts(){

    global $wpdb;

    $all_posts = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$backup_posts = array_filter( $all_posts, function( $post_id ) {
    return get_post_meta( $post_id, '_mozbeats_backup_content', true );
});

$posts = $backup_posts;

    $restaurados = 0;

    foreach($posts as $post_id){

        $backup = get_post_meta($post_id, '_mozbeats_backup_content', true);

        if(!$backup) continue;

        wp_update_post([
            'ID' => $post_id,
            'post_content' => $backup
        ]);

        $restaurados++;
    }

    return $restaurados;
}
add_action('admin_init','mozbeats_restore_process');

function mozbeats_restore_process(){

    if(!isset($_POST['mozbeats_restore_all'])) return;

    if(!current_user_can('manage_options')) return;

    check_admin_referer('mozbeats_restore_all');

    $total = mozbeats_restaurar_todos_posts();

    add_action('admin_notices', function() use ($total){
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Desfazer concluído:</strong> '.esc_html($total).' posts restaurados.</p>';
        echo '</div>';
    });
}
add_action('admin_notices', 'mozbeats_aviso_tempo_restante');

function mozbeats_aviso_tempo_restante(){

    global $wpdb;

    $all_posts = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$ultimo_backup = false;
$ultimo_tempo = 0;

foreach ( $all_posts as $post_id ) {
    $tempo = get_post_meta( $post_id, '_mozbeats_backup_time', true );
    if ( $tempo && $tempo > $ultimo_tempo ) {
        $ultimo_tempo = $tempo;
        $ultimo_backup = (object)[
            'post_id' => $post_id,
            'tempo'   => $tempo,
        ];
    }
}

$backup = $ultimo_backup;

if ( ! empty( $ultimo_backup_posts ) ) {
    $post_id = $ultimo_backup_posts[0];
    $backup = (object)[
        'post_id' => $post_id,
        'tempo'   => get_post_meta( $post_id, '_mozbeats_backup_time', true ),
    ];
} else {
    $backup = false;
}
    if(!$backup) return;

    $restante = (24 * 60 * 60) - (time() - $backup->tempo);

    if($restante <= 0) return;

    $horas = floor($restante / 3600);
    $min = floor(($restante % 3600) / 60);

    echo '<div class="notice notice-warning is-dismissible">';
    echo '<p><strong>Gerar Ficha Técnica:</strong> Você tem '.esc_html($horas).'h '.esc_html($min).'min para restaurar os posts antes que o backup expire.</p>';
    echo '</div>';
}