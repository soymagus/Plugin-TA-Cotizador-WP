<?php
/**
 * Plugin Name: TA Lista de Cotización para WooCommerce
 * Plugin URI:  https://tecnicosamericanos.com/
 * Description: Lista de cotización privada por sesión, solicitudes comerciales y transferencia masiva al carrito.
 * Version:     0.1.0
 * Author:      Técnicos Americanos
 * Text Domain: ta-quote-list
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 10.1
 */

defined( 'ABSPATH' ) || exit;

define( 'TAQL_VERSION', '0.1.0' );
define( 'TAQL_CODENAME', 'Hornero' );
define( 'TAQL_FILE', __FILE__ );
define( 'TAQL_PATH', plugin_dir_path( __FILE__ ) );
define( 'TAQL_URL', plugin_dir_url( __FILE__ ) );

require_once TAQL_PATH . 'includes/class-taql-plugin.php';

register_activation_hook( __FILE__, array( 'TAQL_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TAQL_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'TAQL_Plugin', 'instance' ) );
