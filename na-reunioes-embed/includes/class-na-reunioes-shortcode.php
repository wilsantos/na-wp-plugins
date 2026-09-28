<?php
/**
 * Shortcode [na_reunioes].
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderiza o shortcode de reuniões online.
 */
class NA_Reunioes_Shortcode {

	/**
	 * Renderiza o shortcode.
	 *
	 * @param array<string, string>|string $atts Atributos.
	 * @return string
	 */
	public function render( $atts = array() ): string {
		unset( $atts );

		NA_Reunioes_Plugin::mark_shortcode_used();

		try {
			$service = new NA_Reunioes_Meetings_Service();
			$data    = $service->get_reunioes_processadas();
		} catch ( Exception $e ) {
			return '<div class="na-reunioes-error rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">' .
				esc_html__( 'Erro ao carregar reuniões. Tente novamente em instantes.', 'na-reunioes-embed' ) .
				'</div>';
		}

		$renderer = new NA_Reunioes_Renderer();
		return $renderer->render_embed( $data );
	}
}
