<?php
/*
 * Plugin Name: Gerar Ficha Técnica
 * Description: Automatic music technical sheet generator with artist detection, auto tags, and SEO optimization for music blog
 * Version: 1.2.0
 * Author: Ermelindo João Rosário
 * Author URI: https://www.mozbeats.co.mz/contacte-nos/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gerar-ficha-tecnica
 */
 
 if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/* ===================================================== */
/* CONVERTER SHORTCODE EM HTML (VERSÃO ESTÁVEL) */
/* ===================================================== */

function mozbeats_converter_shortcode_html($post_id){

    if ( get_post_type($post_id) !== 'post' ) return;

    $post = get_post($post_id);
    if(!$post) return;

    $content = $post->post_content;

    // Verifica se tem shortcode
    if(
        strpos($content,'[mozbeats_ficha_tecnica]') === false &&
        strpos($content,'[gerar_ficha_tecnica]') === false
    ){
        return;
    }

    // BACKUP (com tempo)
if(!get_post_meta($post_id, '_mozbeats_backup_content', true)){
    update_post_meta($post_id, '_mozbeats_backup_content', $content);
    update_post_meta($post_id, '_mozbeats_backup_time', time());
}

    // 🔥 FORÇAR CONTEXTO CORRETO
    global $post;
    $old_post = $post;
    $post = get_post($post_id);

    setup_postdata($post);

    // 🔥 CAPTURAR OUTPUT REAL DO SHORTCODE
    ob_start();
    echo do_shortcode('[mozbeats_ficha_tecnica]');
    $html = ob_get_clean();

    wp_reset_postdata();
    $post = $old_post;

    // 🚨 segurança
    if(empty(trim($html))){
        return; // NÃO apaga o conteúdo
    }

    // substituir shortcode
    $new_content = str_replace(
        ['[mozbeats_ficha_tecnica]','[gerar_ficha_tecnica]'],
        $html,
        $content
    );

    wp_update_post([
        'ID' => $post_id,
        'post_content' => $new_content
    ]);
}

/* ===================================================== */
/* DESFAZER CONVERSÃO */
/* ===================================================== */

function mozbeats_restaurar_post($post_id){

    $backup = get_post_meta($post_id, '_mozbeats_backup_content', true);

    if(!$backup) return;

    wp_update_post([
        'ID' => $post_id,
        'post_content' => $backup
    ]);

    // limpar backup depois de restaurar
    delete_post_meta($post_id, '_mozbeats_backup_content');
    delete_post_meta($post_id, '_mozbeats_backup_time');
}

function mozbeats_tem_backup() {
     $all_posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    foreach ($all_posts as $post_id) {
        if ( get_post_meta($post_id, '_mozbeats_backup_content', true) ) {
            return true;
        }
    }

    return false;
}

/* ===================================================== */
/* BOTÕES NO POST */
/* ===================================================== */

add_action('post_submitbox_misc_actions','mozbeats_botoes_post');

function mozbeats_botoes_post($post){

    if ($post->post_type !== 'post') return;

    // Cria nonces
    $nonce_converter = wp_create_nonce('mozbeats_converter_post');
    $nonce_restaurar = wp_create_nonce('mozbeats_restaurar_post');

    ?>

    <div class="misc-pub-section">

        <a href="<?php echo esc_url(admin_url(
            'admin-post.php?action=mozbeats_converter_post&post_id='.$post->ID.'&_wpnonce='.$nonce_converter
        )); ?>" class="button button-primary">
        Converter HTML
        </a>

        <br><br>

        <a href="<?php echo esc_url(admin_url(
            'admin-post.php?action=mozbeats_restaurar_post&post_id='.$post->ID.'&_wpnonce='.$nonce_restaurar
        )); ?>" class="button">
        Desfazer
        </a>

    </div>

    <?php
}

/* ===================================================== */
/* AÇÕES DOS BOTÕES */
/* ===================================================== */

add_action('admin_post_mozbeats_converter_post', function(){

    if ( ! current_user_can('edit_posts') ) {
        wp_die('Sem permissão');
    }

    if (
        ! isset($_GET['_wpnonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_GET['_wpnonce']) ),
            'mozbeats_converter_post'
        )
    ){
        wp_die('Nonce inválido');
    }

    if ( ! isset($_GET['post_id']) ) {
        wp_die('Post inválido');
    }

    $post_id = intval($_GET['post_id']);

    mozbeats_converter_shortcode_html($post_id);

    wp_safe_redirect( admin_url('post.php?post='.$post_id.'&action=edit') );
    exit;

});

add_action('admin_post_mozbeats_restaurar_post', function(){

    if ( ! current_user_can('edit_posts') ) {
        wp_die('Sem permissão');
    }

    // Verifica nonce
    if (
        ! isset($_GET['_wpnonce']) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash($_GET['_wpnonce']) ),
            'mozbeats_restaurar_post'
        )
    ){
        wp_die('Nonce inválido');
    }

    if ( ! isset($_GET['post_id']) ) {
        wp_die('Post inválido');
    }

    $post_id = intval($_GET['post_id']);

    mozbeats_restaurar_post($post_id);

    wp_safe_redirect( admin_url('post.php?post='.$post_id.'&action=edit') );
    exit;

});
/* ===================================================== */
/* DETECTAR FORMATO DO FICHEIRO */
/* ===================================================== */
function mozbeats_detectar_formato_ficheiro( $post_id ) {

    $categorias = get_the_category( $post_id );

    if ( ! $categorias ) {
        return 'MP3';
    }

    foreach ( $categorias as $categoria ) {

        $slug = strtolower( $categoria->slug );

        // Música
        if ( $slug === 'musica' ) {
            return 'MP3';
        }

        // Álbum / EP / Mixtape
        if (
            $slug === 'album' ||
            $slug === 'ep' ||
            $slug === 'mixtape'
        ) {
            return 'ZIP';
        }
    }

    return 'MP3';
}


/* ===================================================== */
/* FUNÇÃO PRINCIPAL DE PROCESSAMENTO DO TÍTULO */
/* ===================================================== */
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-converter.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-settings.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-artistas.php';
}
function mozbeats_processar_titulo($title) {
    $artist = '';
    $song_title = '';
    $participacao = '';

    // Normalizar travessões
    $title_normalizado = preg_replace('/[\p{Pd}]/u', '-', $title);

    if (strpos($title_normalizado, '-') !== false) {
        list($artist, $song_title) = explode('-', $title_normalizado, 2);
    } else {
        $song_title = $title;
    }

    $artist = trim($artist);
    $song_title = trim($song_title);

    // Detectar feat.
    if (preg_match('/\((feat\.?|ft\.?)\s*(.+?)\)/i', $song_title, $m)) {
        $participacao = trim($m[2]);
        $song_title = trim(str_replace($m[0], '', $song_title));
    }

    if(empty($song_title)) $song_title = trim($title);
    if(empty($artist)) $artist = 'Artista';

    return [
        'artist' => $artist,
        'song_title' => $song_title,
        'participacao' => $participacao
    ];
}

/* ===================================================== */
/* NORMALIZAR ARTISTAS E PARTICIPAÇÕES */
/* ===================================================== */

function mozbeats_extrair_lista_artistas($string){

    if(empty($string)) return array();

    // 1️⃣ Decodificar entidades HTML (&#038; &amp;)
    $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');

    // 2️⃣ Substituir & por vírgula
    $string = str_replace('&', ',', $string);

    // 3️⃣ Separar por vírgula
    $array = explode(',', $string);

    // 4️⃣ Limpar espaços e remover vazios
    $array = array_filter(array_map('trim', $array));

    return $array;
}

/* ===================================================== */
/* DETECTAR GÊNEROS PELAS TAGS */
/* ===================================================== */
function mozbeats_detectar_generos($post_id) {

    $default_generos = array(
        'Kizomba','Marrabenta','Afro Beat','Amapiano','Soul',
        'Deep House','Afro House','House','Pop','Afro Pop','Rap','Acústico'
    );

    $options = get_option('mozbeats_settings');
    $custom_generos = array();

    if (!empty($options['custom_generos'])) {
        $custom_generos = array_map('trim', explode("\n", $options['custom_generos']));
    }

    $generos_permitidos = array_merge($default_generos, $custom_generos);

    // 🔥 Normalizar permitido
    $generos_permitidos = array_map(function($g){
        return strtolower(html_entity_decode(trim($g)));
    }, $generos_permitidos);

    $generos_encontrados = array();
    $tags = get_the_tags($post_id);

    if($tags) {
        foreach($tags as $tag) {

            $tag_normalizada = strtolower(
                html_entity_decode(trim($tag->name))
            );

            if(in_array($tag_normalizada, $generos_permitidos)) {

                $link = get_tag_link($tag->term_id);

                $generos_encontrados[] =
                    '<a href="'.esc_url($link).'">'.
                    esc_html($tag->name).
                    '</a>';
            }
        }
    }

    return $generos_encontrados;
}

/* ===================================================== */
/* DETECTAR GÊNERO DO ARTISTA INTELIGENTE */
/* ===================================================== */
function mozbeats_detectar_genero_artista($artist_name) {

    $artist_name = trim($artist_name);

    $tag = get_term_by('name', $artist_name, 'post_tag');

    if($tag){
        $genero = get_term_meta($tag->term_id, 'mozbeats_genero', true);

        if($genero){
            return $genero;
        }
    }

    // fallback automático
    $artist_name = strtolower($artist_name);
    $last_char = mb_substr($artist_name, -1);

    if($last_char === 'a'){
        return 'feminino';
    }

    return 'masculino';
}

/* ===================================================== */
/* ADICIONAR META BOX PARA CÓDIGO DE ANÚNCIO */
/* ===================================================== */
function mozbeats_adicionar_meta_box_anuncio() {
    add_meta_box(
        'mozbeats_anuncio_box',
        'Código de Anúncio Parceiro',
        'mozbeats_exibir_meta_box_anuncio',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'mozbeats_adicionar_meta_box_anuncio');

function mozbeats_exibir_meta_box_anuncio($post){
    wp_nonce_field('mozbeats_salvar_anuncio', 'mozbeats_anuncio_nonce');
    $value = get_post_meta($post->ID, '_mozbeats_anuncio', true);
    echo '<textarea style="width:100%;height:100px;" name="mozbeats_anuncio">'.esc_textarea($value).'</textarea>';
}

function mozbeats_salvar_meta_box_anuncio( $post_id ) {

    if ( ! isset( $_POST['mozbeats_anuncio_nonce'] ) ) {
        return;
    }

    $nonce = sanitize_text_field( wp_unslash( $_POST['mozbeats_anuncio_nonce'] ) );

    if ( ! wp_verify_nonce( $nonce, 'mozbeats_salvar_anuncio' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['mozbeats_anuncio'] ) ) {

        $anuncio = wp_kses_post( wp_unslash( $_POST['mozbeats_anuncio'] ) );
        update_post_meta( $post_id, '_mozbeats_anuncio', $anuncio );
    }
}
add_action('save_post_post','mozbeats_salvar_meta_box_anuncio');

/* ===================================================== */
/* SHORTCODE */
/* ===================================================== */
function mozbeats_ficha_tecnica_shortcode() {
    global $post;
    if(!$post) return '';
    
    if (is_preview()) {
    delete_post_meta($post->ID, '_mozbeats_cache');
}
    
    $options = get_option('mozbeats_settings');
$ativar_cache  = isset($options['enable_cache']) ? $options['enable_cache'] : 0;

    // CACHE
    if(
    $ativar_cache &&
    get_post_status($post->ID) === 'publish'
){
    $cache = get_post_meta($post->ID, '_mozbeats_cache', true);
    if($cache) return $cache;
}

    $title = get_the_title($post->ID);
    $dados = mozbeats_processar_titulo($title);
    $artist = $dados['artist'];
    // Ignorar valor padrão "Artista"
    if(strtolower(trim($artist)) === 'artista'){
    $artist = '';
}
    $song_title = $dados['song_title'];
    $participacao = esc_html($dados['participacao']);
    $year = wp_date('Y');

    // Contar artistas
    $normalized = str_replace('&', ',', $artist);
    $artists_array = array_filter(array_map('trim', explode(',', $normalized)));
    $generos = [];

// Detectar gênero corretamente (suporta múltiplos artistas)
$generos = [];

foreach($artists_array as $art){
    $generos[] = mozbeats_detectar_genero_artista($art);
}

if(count($artists_array) > 1){

    $artista_plural = 'dos artistas';

} else {

    if(in_array('grupo', $generos)){
        $artista_plural = 'do grupo';
    }
    elseif(in_array('feminino', $generos)){
        $artista_plural = 'da artista';
    }
    else{
        $artista_plural = 'do artista';
    }

}

    // Detectar tipo de lançamento
    $categorias = wp_get_post_categories($post->ID, array('fields'=>'slugs'));
    $tipo_lancamento = 'música';
    foreach($categorias as $cat){
        $cat_lower = strtolower($cat);
        if(strpos($cat_lower,'ep')!==false){ $tipo_lancamento='EP'; break; }
        elseif(strpos($cat_lower,'album')!==false || strpos($cat_lower,'álbum')!==false){ $tipo_lancamento='álbum'; break; }
        elseif(strpos($cat_lower,'mixtape')!==false){ $tipo_lancamento='mixtape'; break; }
    }
    $tipo_lancamento_lower = strtolower($tipo_lancamento);

    // Detectar gênero musical
    $generos = mozbeats_detectar_generos($post->ID);
    $generos_texto = '';
    if ( ! empty( $generos ) ) {
    $generos_texto = wp_strip_all_tags( implode( ' e ', $generos ) );
}

    // Frase bonita
    $descricao = "&ldquo;$song_title&rdquo; é ";
    if($tipo_lancamento_lower==='música' || $tipo_lancamento_lower==='ep' || $tipo_lancamento_lower==='mixtape'){
        $descricao .= "a mais recente $tipo_lancamento ";
    }else{
        $descricao .= "o mais recente $tipo_lancamento ";
    }
    $descricao .= "$artista_plural <strong>$artist</strong>";
    if(!empty($participacao)){

    // Normalizar separadores
    $participacao = html_entity_decode($participacao, ENT_QUOTES, 'UTF-8');
$normalized_feat = str_replace('&', ',', $participacao);
    $feat_array = array_filter(array_map('trim', explode(',', $normalized_feat)));

    $total_feat = count($feat_array);

    if($total_feat > 1){

        // Construção correta com vírgulas e "e" apenas no último
        $ultimo = array_pop($feat_array);

        if(count($feat_array) > 0){
            $nomes_formatados = implode(', ', $feat_array) . ' e ' . $ultimo;
        } else {
            $nomes_formatados = $ultimo;
        }

        $descricao .= ", com participação especial dos artistas <strong>"
            . $nomes_formatados
            . "</strong>";

    } else {

        $descricao .= ", com participação especial do artista <strong>"
            . $participacao
            . "</strong>";
    }
}
    if(!empty($generos_texto)){
        $descricao .= ". Um lançamento no estilo <strong>$generos_texto</strong>";
    }
    $descricao .= ", com qualidade de som recomendada para todos os fãs.";

    // === IMAGEM DE DESTAQUE ===
    $conteudo_post = $post->post_content;
    $imagem_html = '';
    $options = get_option('mozbeats_settings');
    $ativar_imagem = isset($options['enable_image']) ? $options['enable_image'] : 0;
    $ativar_cache  = isset($options['enable_cache']) ? $options['enable_cache'] : 0;

if(
    $ativar_imagem &&
    has_post_thumbnail($post->ID) &&
    stripos($conteudo_post, '<img') === false
){
        $thumb_id = get_post_thumbnail_id($post->ID);
        $thumb_url = wp_get_attachment_image_url($thumb_id,'medium'); // 300x300
        $thumb_caption = trim(get_post($thumb_id)->post_excerpt); // legenda

        $imagem_html .= '<div class="mozbeats-imagem" style="text-align:center;margin-bottom:10px;">';
        $imagem_html .= '<img src="'.esc_url($thumb_url).'" width="300" height="300">';
        if(!empty($thumb_caption)){
            $imagem_html .= '<p class="mozbeats-legenda" style="font-size:12px;color:#555;margin-top:5px;">'.esc_html($thumb_caption).'</p>';
        }
        $imagem_html .= '</div>';
    }

    // === ANÚNCIO ===
    $anuncio = get_post_meta($post->ID,'_mozbeats_anuncio',true);
    $anuncio_html = '';
    if(!empty($anuncio)){
        $anuncio_html .= '<div class="mozbeats-anuncio" style="text-align:center;margin-bottom:10px;">';
        $anuncio_html .= $anuncio;
        $anuncio_html .= '</div>';
    }

    // === OUTPUT COMPLETO ===
    $output  = $anuncio_html; // anúncio primeiro
    $output .= $imagem_html;  // depois imagem
    $output .= "<p><strong>Baixar música \"$title\" $year</strong></p>";
    $output .= "<p>$descricao</p>";

    $output .= "<h3>Ficha Técnica</h3>";
    $output .= "<p><strong>Artista:</strong> $artist</p>";
    $output .= "<p><strong>Título:</strong> $song_title</p>";
    if(!empty($participacao)){
        $output .= "<p><strong>Participação:</strong> $participacao</p>";
    }
    $output .= "<p><strong>Ano:</strong> $year</p>";
    // Mostrar gênero apenas se existir
if(!empty($generos)){
    $output .= "<p><strong>Gênero Musical:</strong> ".implode(', ', $generos)."</p>";
}

// Mostrar formato sempre
$formato = mozbeats_detectar_formato_ficheiro($post->ID);
$output .= "<p><strong>Formato do Ficheiro:</strong> $formato</p>";

    // Schema.org
    $schema = [
        "@context"=>"https://schema.org",
        "@type"=>"MusicRecording",
        "name"=>$song_title,
        "byArtist"=>["@type"=>"MusicGroup","name"=>$artist],
        "datePublished"=>$year
    ];
    if(!empty($participacao)){
        $schema["contributor"] = ["@type"=>"MusicGroup","name"=>$participacao];
    }
    $output .= '<script type="application/ld+json">'.wp_json_encode($schema).'</script>';

    // Guardar cache
    if(
    $ativar_cache &&
    !defined('DOING_AUTOSAVE') &&
    !wp_is_post_revision($post->ID) &&
    get_post_status($post->ID) === 'publish'
){
    update_post_meta($post->ID, '_mozbeats_cache', $output);
}
return $output;
}
add_shortcode('mozbeats_ficha_tecnica','mozbeats_ficha_tecnica_shortcode');
add_shortcode('gerar_ficha_tecnica','mozbeats_ficha_tecnica_shortcode');

/* ======================================
   AVISO DE RECOMENDAÇÃO OPCIONAL
   (some automaticamente se Mozbeats Safe Button estiver ativo)
====================================== */

function mozbeats_ft_recommend_smart_download() {

    if ( ! is_admin() ) {
        return;
    }

    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    // Lista de plugins ativos
    $active_plugins = (array) get_option( 'active_plugins', array() );

    // Verifica também multisite
    if ( is_multisite() ) {
        $network_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
        $active_plugins = array_merge( $active_plugins, $network_plugins );
    }

    foreach ( $active_plugins as $plugin ) {
        if ( strpos( $plugin, 'mozbeats-safe-button' ) !== false ) {
            return; // Encontrou o plugin → NÃO mostra aviso
        }
    }

    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>Gerar Ficha Técnica:</strong>
            Para melhorar a experiência de download, recomendamos utilizar também o
            <a href="https://www.mozbeats.co.mz/mozbeats-safe-button.php" target="_blank">
                Mozbeats Safe Button
            </a>.
            Esta integração é opcional.
        </p>
    </div>
    <?php
}

add_action( 'admin_notices', 'mozbeats_ft_recommend_smart_download' );
/* ===================================================== */
/* LIMPAR CACHE AO ATUALIZAR POST */
/* ===================================================== */

function mozbeats_limpar_cache_geral($post_id, $post, $update){

    if ( $post->post_type !== 'post' ) return;

    if ( wp_is_post_revision($post_id) ) return;

    delete_post_meta($post_id, '_mozbeats_cache');
}
add_action('save_post','mozbeats_limpar_cache_geral',10,3);

/* ===================================================== */
/* AVISO SE POST NÃO TIVER GÊNERO MUSICAL */
/* ===================================================== */

add_action('save_post','mozbeats_verificar_genero_post',30,3);

function mozbeats_verificar_genero_post($post_id, $post, $update){

    if ( $post->post_type !== 'post' ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;

    $generos = mozbeats_detectar_generos($post_id);

    if ( empty($generos) ) {
        update_post_meta($post_id, '_mozbeats_sem_genero', 1);
    } else {
        delete_post_meta($post_id, '_mozbeats_sem_genero');
    }
}

add_action('admin_notices','mozbeats_aviso_sem_genero');

function mozbeats_aviso_sem_genero(){

    global $pagenow, $post;

    if ( $pagenow !== 'post.php' && $pagenow !== 'post-new.php' ) {
        return;
    }

    if ( ! isset( $post->ID ) ) {
        return;
    }

    $post_id = intval( $post->ID );

    if ( get_post_meta( $post_id, '_mozbeats_sem_genero', true ) ) {

        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Gerar Ficha Técnica:</strong> Este post foi salvo sem um gênero musical válido.</p>';
        echo '</div>';

    }
}
/* ===================================================== */
/* AUTO TAGS - ARTISTAS E PARTICIPAÇÕES */
/* ===================================================== */

add_action('save_post','mozbeats_auto_tags_artistas',50,3);

function mozbeats_auto_tags_artistas($post_id, $post, $update){

    if ( $post->post_type !== 'post' ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;

    $titulo_atual = trim($post->post_title);
    if(empty($titulo_atual)) return;

    // Verificar se o título mudou
    $titulo_antigo = get_post_meta($post_id, '_mozbeats_titulo_processado', true);

    if($titulo_antigo === $titulo_atual){
        return; // Nada mudou
    }

    // Atualizar meta do último título
    update_post_meta($post_id, '_mozbeats_titulo_processado', $titulo_atual);

    $dados = mozbeats_processar_titulo($titulo_atual);

    $artist = trim($dados['artist']);
    $participacao = trim($dados['participacao']);

    $novas_tags = array();

    // Artista principal
    if(strtolower($artist) !== 'artista' && !empty($artist)){
        $novas_tags = array_merge(
            $novas_tags,
            mozbeats_extrair_lista_artistas($artist)
        );
    }

    // Participações
    if(!empty($participacao)){
        $novas_tags = array_merge(
            $novas_tags,
            mozbeats_extrair_lista_artistas($participacao)
        );
    }

    $novas_tags = array_unique($novas_tags);

    // Tags atuais
    $tags_atuais = wp_get_post_tags($post_id, array('fields'=>'names'));

    // Remover artistas antigos gerados automaticamente
    $tags_filtradas = array_filter($tags_atuais, function($tag){

        // Nunca manter "Artista"
        if(strtolower(trim($tag)) === 'artista') return false;

        return true;
    });

    // Mesclar tags manuais + novas
    $todas_tags = array_unique(array_merge($tags_filtradas, $novas_tags));

    wp_set_post_tags($post_id, $todas_tags, false);
}
/* ===================================================== */
/* AUTO TAG - ANO DE PUBLICAÇÃO */
/* ===================================================== */

add_action('save_post','mozbeats_auto_tag_ano',60,3);

function mozbeats_auto_tag_ano($post_id, $post, $update){

    if ( $post->post_type !== 'post' ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;

    // Pegar ano da data do post
    $ano_post = get_the_date('Y', $post_id);

    if(empty($ano_post)) return;

    // Buscar tags atuais
    $tags_atuais = wp_get_post_tags($post_id, array('fields'=>'names'));

    // Remover qualquer tag que seja apenas número de 4 dígitos (ano antigo)
    $tags_filtradas = array_filter($tags_atuais, function($tag){
        return !preg_match('/^\d{4}$/', trim($tag));
    });

    // Adicionar ano atual
    $tags_filtradas[] = $ano_post;

    $tags_final = array_unique($tags_filtradas);

    wp_set_post_tags($post_id, $tags_final, false);
}
/* =====================================================
LIMPAR CACHE QUANDO CONFIGURAÇÕES MUDAREM
===================================================== */

add_action('update_option_mozbeats_settings','mozbeats_limpar_cache_config',10,2);

function mozbeats_limpar_cache_config($old_value,$value){

    $posts = get_posts(array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));

    foreach($posts as $post_id){
        delete_post_meta($post_id,'_mozbeats_cache');
    }

}

