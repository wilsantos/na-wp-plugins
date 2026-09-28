<?php
/**
 * Serviço principal de reuniões online.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Orquestra busca, normalização e classificação de reuniões online.
 */
class NA_Reunioes_Meetings_Service {

	/**
	 * BMLT service.
	 *
	 * @var NA_Reunioes_Bmlt_Service
	 */
	private NA_Reunioes_Bmlt_Service $bmlt;

	/**
	 * Construtor.
	 */
	public function __construct() {
		$this->bmlt = new NA_Reunioes_Bmlt_Service();
	}

	/**
	 * Retorna reuniões online processadas e classificadas.
	 *
	 * @return array<string, mixed>
	 */
	public function get_reunioes_processadas(): array {
		$result     = $this->bmlt->get_meetings();
		$normalized = NA_Reunioes_Normalize_Meeting::normalize_meetings( $result['data'] );
		$classified = $this->classify_meetings( $normalized );

		return array_merge(
			$classified,
			array(
				'meta' => array(
					'timestamp'    => gmdate( 'c', NA_Reunioes_Time_Utils::get_now_epoch_sp() ),
					'timezone'     => NA_Reunioes_Time_Utils::TIMEZONE,
					'current_time' => NA_Reunioes_Time_Utils::get_current_time_formatted(),
					'cache_hit'    => $result['cache_hit'],
					'cache_layer'  => $result['cache_layer'],
					'total_count'  => count( $classified['now'] ) + count( $classified['soon'] ) + count( $classified['next24h'] ),
				),
			)
		);
	}

	/**
	 * Classifica reuniões em now, soon e next24h.
	 *
	 * @param array<int, array<string, mixed>> $meetings Reuniões normalizadas.
	 * @return array{now:array,soon:array,next24h:array}
	 */
	private function classify_meetings( array $meetings ): array {
		$now     = array();
		$soon    = array();
		$next24h = array();

		foreach ( $meetings as $meeting ) {
			$status = $meeting['status'] ?? 'later';
			if ( 'now' === $status ) {
				$now[] = $meeting;
			} elseif ( 'soon' === $status ) {
				$soon[] = $meeting;
			} else {
				$next24h[] = $meeting;
			}
		}

		return array(
			'now'     => $this->sort_meetings( $now, 'now' ),
			'soon'    => $this->sort_meetings( $soon, 'soon' ),
			'next24h' => $this->sort_meetings( $next24h, 'later' ),
		);
	}

	/**
	 * Ordena reuniões por status.
	 *
	 * @param array<int, array<string, mixed>> $meetings Reuniões.
	 * @param string                           $status   Status.
	 * @return array<int, array<string, mixed>>
	 */
	private function sort_meetings( array $meetings, string $status ): array {
		usort(
			$meetings,
			static function ( array $a, array $b ) use ( $status ): int {
				if ( 'now' === $status ) {
					return 0;
				}
				$a_time = strtotime( $a['starts_at'] ?? '' );
				$b_time = strtotime( $b['starts_at'] ?? '' );
				return $a_time <=> $b_time;
			}
		);
		return $meetings;
	}
}
