<?php
/**
 * Serviço para buscar dados da API BMLT.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Busca e cacheia reuniões do BMLT.
 */
class NA_Reunioes_Bmlt_Service {

	private const BMLT_URL = 'https://bmlt.na.org.br/ativo/main_server/client_interface/json/?switcher=GetSearchResults&venue_types[]=2&venue_types[]=3';

	/**
	 * Cache service.
	 *
	 * @var NA_Reunioes_Cache_Service
	 */
	private NA_Reunioes_Cache_Service $cache;

	/**
	 * Construtor.
	 */
	public function __construct() {
		$this->cache = new NA_Reunioes_Cache_Service();
	}

	/**
	 * Obtém reuniões do cache ou da API.
	 *
	 * @return array{data:array<int,array<string,mixed>>,cache_hit:bool,cache_layer:string}
	 */
	public function get_meetings(): array {
		$cached = $this->cache->get();
		if ( null !== $cached ) {
			return array(
				'data'        => $cached,
				'cache_hit'   => true,
				'cache_layer' => 'transient',
			);
		}

		$data = $this->fetch_from_bmlt();
		$this->cache->set( $data );

		return array(
			'data'        => $data,
			'cache_hit'   => false,
			'cache_layer' => 'api',
		);
	}

	/**
	 * Busca dados brutos do BMLT.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function fetch_from_bmlt(): array {
		$response = wp_remote_get(
			self::BMLT_URL,
			array(
				'timeout' => 60,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'BMLT API error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			throw new RuntimeException( 'BMLT API error: HTTP ' . $code );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}
}
