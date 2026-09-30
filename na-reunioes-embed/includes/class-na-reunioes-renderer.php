<?php
/**
 * Renderer de templates HTML.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Monta HTML a partir dos dados de reuniões.
 */
class NA_Reunioes_Renderer {

	/**
	 * Caminho dos templates.
	 *
	 * @var string
	 */
	private string $template_path;

	/**
	 * Construtor.
	 */
	public function __construct() {
		$this->template_path = NA_REUNIOES_EMBED_PATH . 'templates/';
	}

	/**
	 * Renderiza o embed completo.
	 *
	 * @param array<string, mixed> $data Dados processados.
	 * @return string
	 */
	public function render_embed( array $data ): string {
		ob_start();
		$filters = $data['filters'] ?? array();
		$this->load_template(
			'embed-wrapper.php',
			array(
				'now'           => $data['now'] ?? array(),
				'soon'          => $data['soon'] ?? array(),
				'next24h'       => $data['next24h'] ?? array(),
				'day_meetings'  => $data['day'] ?? array(),
				'filter_day'    => array_key_exists( 'day', $filters ) ? $filters['day'] : null,
				'filter_period' => array_key_exists( 'period', $filters ) ? $filters['period'] : null,
				'meta'          => $data['meta'] ?? array(),
				'rest_url'      => esc_url( rest_url( 'na-reunioes/v1/reunioes' ) ),
			)
		);
		return (string) ob_get_clean();
	}

	/**
	 * Renderiza apenas a view online (para re-render via JS).
	 *
	 * @param array<string, mixed> $data Dados.
	 * @return string
	 */
	public function render_online_view( array $data ): string {
		ob_start();
		$filters = $data['filters'] ?? array();
		$this->load_template(
			'partials/online-view.php',
			array(
				'now'           => $data['now'] ?? array(),
				'soon'          => $data['soon'] ?? array(),
				'next24h'       => $data['next24h'] ?? array(),
				'day_meetings'  => $data['day'] ?? array(),
				'filter_day'    => array_key_exists( 'day', $filters ) ? $filters['day'] : null,
				'filter_period' => array_key_exists( 'period', $filters ) ? $filters['period'] : null,
				'error'         => $data['error'] ?? null,
			)
		);
		return (string) ob_get_clean();
	}

	/**
	 * Enriquece reunião com minutos até início/fim.
	 *
	 * @param array<string, mixed> $meeting Reunião.
	 * @param string               $status Status.
	 * @return array<string, mixed>
	 */
	public static function enrich_meeting( array $meeting, string $status ): array {
		$starts_at = strtotime( $meeting['starts_at'] ?? '' );
		$ends_at   = strtotime( $meeting['ends_at'] ?? '' );

		$meeting['status'] = $status;
		$meeting['formats_string'] = NA_Reunioes_Format_Badges::formats_to_string( $meeting['formats'] ?? null );

		if ( 'soon' === $status || 'later' === $status ) {
			$meeting['minutes_until_start'] = NA_Reunioes_Time_Utils::get_minutes_until_start( $starts_at );
		}
		if ( 'now' === $status ) {
			$meeting['minutes_until_end'] = NA_Reunioes_Time_Utils::get_minutes_until_end( $ends_at );
		}

		return $meeting;
	}

	/**
	 * Carrega um template PHP.
	 *
	 * @param string               $template Nome do arquivo.
	 * @param array<string, mixed> $vars     Variáveis.
	 * @return void
	 */
	private function load_template( string $template, array $vars = array() ): void {
		$path = $this->template_path . $template;
		if ( ! file_exists( $path ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- template vars.
		extract( $vars, EXTR_SKIP );
		include $path;
	}
}
