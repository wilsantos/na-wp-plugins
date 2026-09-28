<?php
/**
 * REST API do plugin.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Endpoints REST para reuniões online.
 */
class NA_Reunioes_Rest {

	/**
	 * Namespace da API.
	 */
	private const NAMESPACE = 'na-reunioes/v1';

	/**
	 * Registra rotas REST.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/reunioes',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_reunioes' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'   => array(
						'default'           => 'online',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => static function ( $value ) {
							return 'online' === $value;
						},
					),
					'format' => array(
						'default'           => 'json',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * GET /reunioes — retorna reuniões online processadas.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_reunioes( WP_REST_Request $request ) {
		unset( $request );

		try {
			$service  = new NA_Reunioes_Meetings_Service();
			$response = $service->get_reunioes_processadas();
			$format   = $request->get_param( 'format' );

			if ( 'html' === $format ) {
				$renderer = new NA_Reunioes_Renderer();
				$html     = $renderer->render_online_view( $response );

				$rest_response = new WP_REST_Response( $html, 200 );
				$rest_response->header( 'Content-Type', 'text/html; charset=UTF-8' );
			} else {
				$rest_response = new WP_REST_Response( $response, 200 );
			}

			$rest_response->header( 'Cache-Control', 'public, max-age=60' );
			$rest_response->header( 'X-Timezone', NA_Reunioes_Time_Utils::TIMEZONE );

			return $rest_response;
		} catch ( Exception $e ) {
			return new WP_Error(
				'na_reunioes_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}
}
