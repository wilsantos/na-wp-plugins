<?php
/**
 * Orquestrador principal do plugin.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-cache-service.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-time-utils.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-format-badges.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-normalize-meeting.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-bmlt-service.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-meetings-service.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/class-na-reunioes-renderer.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/class-na-reunioes-shortcode.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/rest/class-na-reunioes-rest.php';

/**
 * Classe principal do plugin NA Reuniões Embed.
 */
class NA_Reunioes_Plugin {

	/**
	 * Instância singleton.
	 *
	 * @var NA_Reunioes_Plugin|null
	 */
	private static ?NA_Reunioes_Plugin $instance = null;

	/**
	 * Indica se o shortcode foi renderizado na página atual.
	 *
	 * @var bool
	 */
	private static bool $shortcode_used = false;

	/**
	 * Retorna a instância singleton.
	 *
	 * @return NA_Reunioes_Plugin
	 */
	public static function instance(): NA_Reunioes_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construtor privado.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'enqueue_assets_fallback' ), 5 );
	}

	/**
	 * Registra o shortcode.
	 *
	 * @return void
	 */
	public function register_shortcode(): void {
		$shortcode = new NA_Reunioes_Shortcode();
		add_shortcode( 'na_reunioes', array( $shortcode, 'render' ) );
	}

	/**
	 * Registra rotas REST.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$rest = new NA_Reunioes_Rest();
		$rest->register_routes();
	}

	/**
	 * Marca o shortcode como usado (para enqueue condicional).
	 *
	 * @return void
	 */
	public static function mark_shortcode_used(): void {
		self::$shortcode_used = true;
	}

	/**
	 * Verifica se o shortcode foi usado.
	 *
	 * @return bool
	 */
	public static function is_shortcode_used(): bool {
		return self::$shortcode_used;
	}

	/**
	 * Verifica se a página atual contém o shortcode.
	 *
	 * @return bool
	 */
	private function page_has_shortcode(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post();
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'na_reunioes' );
	}

	/**
	 * Enfileira CSS/JS quando o shortcode está no conteúdo da página.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets(): void {
		if ( ! self::$shortcode_used && ! $this->page_has_shortcode() ) {
			return;
		}

		$this->enqueue_assets();
	}

	/**
	 * Fallback: shortcode pode ser renderizado após wp_enqueue_scripts.
	 *
	 * @return void
	 */
	public function enqueue_assets_fallback(): void {
		if ( ! self::$shortcode_used ) {
			return;
		}

		if ( wp_style_is( 'na-reunioes-embed', 'enqueued' ) ) {
			return;
		}

		$this->enqueue_assets();
		wp_print_styles( array( 'na-reunioes-geist', 'na-reunioes-embed-scoped', 'na-reunioes-embed' ) );
	}

	/**
	 * Enfileira CSS e JS do embed.
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		wp_enqueue_style(
			'na-reunioes-geist',
			'https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'na-reunioes-embed-scoped',
			NA_REUNIOES_EMBED_URL . 'assets/css/na-reunioes-scoped.css',
			array( 'na-reunioes-geist' ),
			NA_REUNIOES_EMBED_VERSION
		);

		wp_enqueue_style(
			'na-reunioes-embed',
			NA_REUNIOES_EMBED_URL . 'assets/css/na-reunioes.css',
			array( 'na-reunioes-embed-scoped' ),
			NA_REUNIOES_EMBED_VERSION
		);

		wp_enqueue_script(
			'na-reunioes-embed',
			NA_REUNIOES_EMBED_URL . 'assets/js/embed.js',
			array(),
			NA_REUNIOES_EMBED_VERSION,
			true
		);

		wp_localize_script(
			'na-reunioes-embed',
			'naReunioesEmbed',
			array(
				'restUrl' => esc_url_raw( rest_url( 'na-reunioes/v1/reunioes' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
