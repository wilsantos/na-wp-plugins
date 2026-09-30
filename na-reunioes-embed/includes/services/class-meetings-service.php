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
	 * Sem dia, mantém a janela das próximas 24h. Com dia (0–6), lista a grade
	 * daquele dia da semana. O período, quando informado, restringe a hora de início.
	 *
	 * @param int|null    $day    Dia JS 0–6, ou null para as próximas 24h.
	 * @param string|null $period Chave do período, ou null para todos os horários.
	 * @return array<string, mixed>
	 */
	public function get_reunioes_processadas( ?int $day = null, ?string $period = null ): array {
		$result       = $this->bmlt->get_meetings();
		$browsing_day = null !== $day;
		$normalized   = NA_Reunioes_Normalize_Meeting::normalize_meetings( $result['data'], ! $browsing_day );

		if ( $browsing_day ) {
			$day_meetings = array();
			foreach ( $normalized as $meeting ) {
				if ( (int) ( $meeting['weekday'] ?? -1 ) === $day ) {
					$day_meetings[] = $meeting;
				}
			}
			$day_meetings = $this->filter_by_period( $day_meetings, $period );
			$classified   = array(
				'now'     => array(),
				'soon'    => array(),
				'next24h' => array(),
				'day'     => $this->sort_by_start_time( $day_meetings ),
			);
		} else {
			$classified         = $this->classify_meetings( $normalized );
			$classified['now']  = $this->filter_by_period( $classified['now'], $period );
			$classified['soon'] = $this->filter_by_period( $classified['soon'], $period );
			$classified['next24h'] = $this->filter_by_period( $classified['next24h'], $period );
			$classified['day']  = array();
		}

		return array_merge(
			$classified,
			array(
				'filters' => array(
					'day'    => $day,
					'period' => $period,
				),
				'meta'    => array(
					'timestamp'    => gmdate( 'c', NA_Reunioes_Time_Utils::get_now_epoch_sp() ),
					'timezone'     => NA_Reunioes_Time_Utils::TIMEZONE,
					'current_time' => NA_Reunioes_Time_Utils::get_current_time_formatted(),
					'cache_hit'    => $result['cache_hit'],
					'cache_layer'  => $result['cache_layer'],
					'total_count'  => count( $classified['now'] ) + count( $classified['soon'] ) + count( $classified['next24h'] ) + count( $classified['day'] ),
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

	/**
	 * Restringe reuniões ao período do dia, pela hora de início.
	 *
	 * @param array<int, array<string, mixed>> $meetings Reuniões.
	 * @param string|null                      $period   Chave do período.
	 * @return array<int, array<string, mixed>>
	 */
	private function filter_by_period( array $meetings, ?string $period ): array {
		if ( null === $period || '' === $period ) {
			return $meetings;
		}

		$filtered = array();
		foreach ( $meetings as $meeting ) {
			$hour = (int) ( $meeting['start_hour'] ?? -1 );
			if ( NA_Reunioes_Time_Utils::matches_period( $hour, $period ) ) {
				$filtered[] = $meeting;
			}
		}

		return $filtered;
	}

	/**
	 * Ordena pela hora de início no relógio (HH:mm).
	 *
	 * @param array<int, array<string, mixed>> $meetings Reuniões.
	 * @return array<int, array<string, mixed>>
	 */
	private function sort_by_start_time( array $meetings ): array {
		usort(
			$meetings,
			static function ( array $a, array $b ): int {
				return strcmp( (string) ( $a['start_time'] ?? '' ), (string) ( $b['start_time'] ?? '' ) );
			}
		);
		return $meetings;
	}
}
