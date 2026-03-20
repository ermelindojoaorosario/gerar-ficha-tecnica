<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ===================================================== */
/* CONEXÃO */
/* ===================================================== */
add_action('admin_enqueue_scripts', 'mozbeats_admin_assets');

function mozbeats_admin_assets() {

    $plugin_url = plugin_dir_url(dirname(__FILE__));

    wp_enqueue_style(
        'mozbeats-admin-css',
        $plugin_url . 'assets/css/admin-generos.css',
        array(),
        time()
    );

    wp_enqueue_style(
        'mozbeats-artistas-css',
        $plugin_url . 'assets/css/admin-artistas.css',
        array(),
        time()
    );

    wp_enqueue_script(
        'mozbeats-admin-js',
        $plugin_url . 'assets/js/admin-generos.js',
        array('jquery'),
        time(),
        true
    );

    wp_enqueue_script(
        'mozbeats-artistas-js',
        $plugin_url . 'assets/js/admin-artistas.js',
        array('jquery'),
        time(),
        true
    );
}

/* ===================================================== */
/* REGISTRAR CONFIGURAÇÕES */
/* ===================================================== */

function mozbeats_register_settings() {

    register_setting(
        'mozbeats_settings_group',
        'mozbeats_settings',
        array(
            'sanitize_callback' => 'mozbeats_sanitize_settings'
        )
    );

    add_settings_section(
        'mozbeats_main_section',
        'Configurações Gerais',
        '__return_false',
        'mozbeats-config'
    );

    add_settings_field(
        'custom_generos',
        'Gêneros Musicais Permitidos',
        'mozbeats_generos_callback',
        'mozbeats-config',
        'mozbeats_main_section',
        array('label_for' => 'custom_generos')
    );

    add_settings_field(
        'enable_image',
        'Ativar imagem destacada automática',
        'mozbeats_checkbox_callback',
        'mozbeats-config',
        'mozbeats_main_section',
        array('label_for' => 'enable_image')
    );

    add_settings_field(
        'enable_cache',
        'Ativar cache',
        'mozbeats_checkbox_callback',
        'mozbeats-config',
        'mozbeats_main_section',
        array('label_for' => 'enable_cache')
    );

    add_settings_field(
        'custom_text',
        'Texto personalizado antes da ficha',
        'mozbeats_text_callback',
        'mozbeats-config',
        'mozbeats_main_section',
        array('label_for' => 'custom_text')
    );
}
add_action( 'admin_init', 'mozbeats_register_settings' );

/* ===================================================== */
/* SANITIZAÇÃO */
/* ===================================================== */

function mozbeats_sanitize_settings( $input ) {

    $new_input = array();

    $new_input['enable_image'] = isset($input['enable_image']) ? 1 : 0;
    $new_input['enable_cache'] = isset($input['enable_cache']) ? 1 : 0;

    if ( isset($input['custom_text']) ) {
        $new_input['custom_text'] = sanitize_text_field($input['custom_text']);
    }

    if ( isset($input['custom_generos']) ) {
        $new_input['custom_generos'] = sanitize_textarea_field($input['custom_generos']);
    }

    return $new_input;
}

/* ===================================================== */
/* CALLBACK */
/* ===================================================== */

function mozbeats_checkbox_callback( $args ) {

    $options = get_option('mozbeats_settings');
    $id = $args['label_for'];

    ?>

    <input type="checkbox"
        id="<?php echo esc_attr($id); ?>"
        name="mozbeats_settings[<?php echo esc_attr($id); ?>]"
        value="1"
        <?php checked(isset($options[$id]) ? $options[$id] : 0, 1); ?>
    />

    <?php
}

function mozbeats_text_callback( $args ) {

    $options = get_option('mozbeats_settings');
    $id = $args['label_for'];

    $value = isset($options[$id]) ? $options[$id] : '';

    echo '<input type="text"
        id="' . esc_attr($id) . '"
        name="mozbeats_settings[' . esc_attr($id) . ']"
        value="' . esc_attr($value) . '"
        class="regular-text" />';
}

function mozbeats_generos_callback( $args ) {

    $options = get_option('mozbeats_settings');
    $id = $args['label_for'];

    $default_generos = array(
        'Kizomba','Marrabenta','Afro Beat','Amapiano','Soul',
        'Deep House','Afro House','House','Pop','Afro Pop','Rap','Acústico'
    );

    $saved = !empty($options[$id])
        ? array_map('trim', explode("\n", $options[$id]))
        : array();

    $all_generos = array_unique(array_merge($default_generos, $saved));

    echo '<div id="mozbeats-generos-wrapper">';

    foreach($all_generos as $genero){
        echo '<span class="mozbeats-tag" data-value="'.esc_attr($genero).'">'
        .esc_html($genero).
        ' <button type="button" class="remove-genero">×</button></span>';
    }

    echo '</div>';

    echo '<p id="mozbeats-contador" style="margin-top:10px;color:#666;font-size:13px;"></p>';

    echo '<input type="text" id="novo-genero" placeholder="Adicionar novo gênero..." />';
    echo '<button type="button" class="button" id="add-genero">Adicionar</button>';

    echo '<textarea id="' . esc_attr($id) . '" name="mozbeats_settings[' . esc_attr($id) . ']" style="display:none;">'
    . esc_textarea(implode("\n", $all_generos))
    . '</textarea>';
}

/* ===================================================== */
/* PÁGINA CONFIGURAÇÕES */
/* ===================================================== */

function mozbeats_settings_page() {
?>

<div class="wrap">

<h1>Configurações - Gerar Ficha Técnica</h1>
<p>Faça a gestão dos estilos musicais, configure cache e imagem de destaque se necessário</p>

<form method="post" action="options.php">

<?php
settings_fields('mozbeats_settings_group');
do_settings_sections('mozbeats-config');
submit_button();
?>

</form>

</div>

<?php
}

/* ===================================================== */
/* AJAX - CARREGAR BIOGRAFIA */
/* ===================================================== */

add_action('wp_ajax_mozbeats_get_biografia', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Acesso negado');
    }

    check_ajax_referer('mozbeats_bio_nonce','nonce');

    $term_id = isset($_POST['artista_id'])
        ? intval(wp_unslash($_POST['artista_id']))
        : 0;

    $bio = get_term_meta($term_id,'mozbeats_biografia',true);
?>

<form method="post" class="bio-form">

<?php wp_nonce_field('mozbeats_bio_nonce','nonce'); ?>

<?php

wp_editor(
    $bio,
    'bio_'.$term_id,
    array(
        'textarea_name' => 'bio['.$term_id.']',
        'media_buttons' => false,
        'teeny' => false,
        'tinymce' => true,
        'quicktags' => true,
        'textarea_rows' => 12
    )
);

?>

<input type="hidden" name="artista_id" value="<?php echo esc_attr($term_id); ?>" />

<button type="submit" class="button button-primary" style="margin-top:10px;">
Salvar Biografia
</button>

</form>

<?php

wp_die();

});

/* ===================================================== */
/* AJAX - SALVAR BIOGRAFIA */
/* ===================================================== */

add_action('wp_ajax_mozbeats_save_biografia', function() {

    if (!current_user_can('manage_options')) {
        wp_die('Acesso negado');
    }

    check_ajax_referer('mozbeats_bio_nonce','nonce');

    if (!isset($_POST['artista_id']) || !isset($_POST['bio'])) {
        wp_die('Dados inválidos');
    }

    $term_id = intval(
        wp_unslash($_POST['artista_id'])
    );

    $texto = '';

    if (isset($_POST['bio'][$term_id])) {

        $texto = wp_kses_post(
            wp_unslash($_POST['bio'][$term_id])
        );

    }

    update_term_meta(
        $term_id,
        'mozbeats_biografia',
        $texto
    );

    wp_die('ok');

});