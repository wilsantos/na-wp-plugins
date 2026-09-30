<?php
/**
 * Plugin Name:       NA Reuniões Embed
 * Description:       Shortcode [na_reunioes] para exibir reuniões online de Narcóticos Anônimos.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            NA Brasil
 * Text Domain:       na-reunioes-embed
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

define( 'NA_REUNIOES_EMBED_VERSION', '1.0.6' );
define( 'NA_REUNIOES_EMBED_FILE', __FILE__ );
define( 'NA_REUNIOES_EMBED_PATH', plugin_dir_path( __FILE__ ) );
define( 'NA_REUNIOES_EMBED_URL', plugin_dir_url( __FILE__ ) );

require_once NA_REUNIOES_EMBED_PATH . 'includes/class-na-reunioes-plugin.php';

/**
 * Inicializa o plugin.
 *
 * @return NA_Reunioes_Plugin
 */
function na_reunioes_embed() {
	return NA_Reunioes_Plugin::instance();
}

na_reunioes_embed();
