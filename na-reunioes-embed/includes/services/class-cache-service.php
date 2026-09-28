<?php
/**
 * Cache via wp_transient para dados brutos do BMLT.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Serviço de cache para reuniões BMLT.
 */
class NA_Reunioes_Cache_Service {

	/**
	 * Chave do transient.
	 */
	private const CACHE_KEY = 'na_reunioes_bmlt_all';

	/**
	 * TTL padrão em segundos.
	 */
	private const DEFAULT_TTL = 600;

	/**
	 * Obtém dados do cache.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get(): ?array {
		$cached = get_transient( self::CACHE_KEY );

		if ( false === $cached || ! is_array( $cached ) ) {
			return null;
		}

		return $cached;
	}

	/**
	 * Armazena dados no cache.
	 *
	 * @param array<int, array<string, mixed>> $data Dados BMLT.
	 * @param int|null                           $ttl  TTL em segundos.
	 * @return void
	 */
	public function set( array $data, ?int $ttl = null ): void {
		$ttl = $ttl ?? self::DEFAULT_TTL;
		set_transient( self::CACHE_KEY, $data, $ttl );
	}

	/**
	 * Limpa o cache.
	 *
	 * @return void
	 */
	public function clear(): void {
		delete_transient( self::CACHE_KEY );
	}
}
