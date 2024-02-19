<?php

/**
 * Plugin Name: Parzibyte tools
 * Description: Plugin para el blog de Parzibyte. Agrega captcha lazy loading a comentarios y login
 * Version: 1.0
 * Author: Parzibyte
 */
//https://www.google.com/recaptcha/admin/
define("PARZIBYTE_PLUGIN_CLAVE_SECRETA_CAPTCHA", "");
define("PARZIBYTE_PLUGIN_CLAVE_SITIO_CAPTCHA", "");
// Agregar el script de JavaScript al encabezado
function parzibyte_plugin_agregar_script_adsense()
{
    wp_enqueue_script('parzibyte_plugin_ads', "https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4859317680104877", array(), null, true);
}
function validar_captcha_antes_de_guardar_comentario($comment_data)
{
    if (is_comment_by_admin()) {
        return $comment_data;
    }
    if (mb_strlen($comment_data["comment_content"]) > 255) {
        wp_die("Comment too long");
    }
    if (!isset($_POST["g-recaptcha-response"])) {
        wp_die("Incorrect captcha");
    }
    // Obtén el valor del campo oculto
    $recaptcha = sanitize_text_field($_POST['g-recaptcha-response']);
    if (!verificarToken($recaptcha, PARZIBYTE_PLUGIN_CLAVE_SECRETA_CAPTCHA)) {
        wp_die("Incorrect captcha");
    }
    $comment_data["comment_author_url"] = "";
    return $comment_data;
}


function remover_url_de_comentario($fields)
{
    if (isset($fields['url'])) {
        unset($fields['url']);
    }
    return $fields;
}
function agregar_script_captcha_comentarios()
{
    wp_register_script('wp_captcha_comment', plugins_url('js/captcha.js', __FILE__), array(), '1.0', true);
    wp_enqueue_script('wp_captcha_comment');
}

function agregar_script_captcha_login()
{
    wp_register_script('wp_captcha_login', plugins_url('js/captcha_login.js', __FILE__), array(), '1.0', true);
    wp_enqueue_script('wp_captcha_login');
}
function agregar_contenedor_captcha($submit_button)
{
    $html_personalizado = '<div class="g-recaptcha" data-sitekey="' . PARZIBYTE_PLUGIN_CLAVE_SITIO_CAPTCHA . '"></div>';
    return $html_personalizado . $submit_button;
}
function verificarToken($token, $claveSecreta)
{
    # La API en donde verificamos el token
    $url = "https://www.google.com/recaptcha/api/siteverify";
    # Los datos que enviamos a Google
    $datos = [
        "secret" => $claveSecreta,
        "response" => $token,
    ];
    // Crear opciones de la petición HTTP
    $opciones = array(
        "http" => array(
            "header" => "Content-type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($datos), # Agregar el contenido definido antes
        ),
    );
    # Preparar petición
    $contexto = stream_context_create($opciones);
    # Hacerla
    $resultado = file_get_contents($url, false, $contexto);
    # Si hay problemas con la petición (por ejemplo, que no hay internet o algo así)
    # entonces se regresa false. Este NO es un problema con el captcha, sino con la conexión
    # al servidor de Google
    if ($resultado === false) {
        # Error haciendo petición
        return false;
    }

    # En caso de que no haya regresado false, decodificamos con JSON
    # https://parzibyte.me/blog/2018/12/26/codificar-decodificar-json-php/

    $resultado = json_decode($resultado);
    # La variable que nos interesa para saber si el usuario pasó o no la prueba
    # está en success
    $pruebaPasada = $resultado->success;
    # Regresamos ese valor, y listo (sí, ya sé que se podría regresar $resultado->success)
    return $pruebaPasada;
}
function is_comment_by_admin()
{
    $current_user = wp_get_current_user();

    if (in_array('administrator', (array) $current_user->roles)) {
        return true;
    }
    return false;
}
function agregar_contenedor_captcha_login()
{
    $html_personalizado = '<div class="g-recaptcha" data-sitekey="' . PARZIBYTE_PLUGIN_CLAVE_SITIO_CAPTCHA . '"></div>';
    echo $html_personalizado;
}
function revisar_captcha_en_login($user, $username, $password)
{
    if (!isset($_POST['wp-submit'])) {
        return $user;
    }
    if (!isset($_POST["g-recaptcha-response"])) {
        return new WP_Error('authentication_failed', __('captcha_required'));
    }
    $recaptcha = sanitize_text_field($_POST['g-recaptcha-response']);
    if (!verificarToken($recaptcha, PARZIBYTE_PLUGIN_CLAVE_SECRETA_CAPTCHA)) {
        return new WP_Error('authentication_failed', __('wrong_captcha'));
    }
    return $user;
}
add_action('login_form', 'agregar_contenedor_captcha_login');
add_filter('authenticate', 'revisar_captcha_en_login', 30, 3);
add_filter('preprocess_comment', 'validar_captcha_antes_de_guardar_comentario');
add_action('login_enqueue_scripts', 'agregar_script_captcha_login');
add_action('wp_enqueue_scripts', 'agregar_script_captcha_comentarios');
add_action('wp_enqueue_scripts', 'parzibyte_plugin_agregar_script_adsense');
add_filter('comment_form_default_fields', 'remover_url_de_comentario');
add_filter('comment_form_submit_button', 'agregar_contenedor_captcha');
