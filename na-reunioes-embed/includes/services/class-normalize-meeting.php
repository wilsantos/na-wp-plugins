<?php
/**
 * Normalização de reuniões BMLT — somente online.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normaliza reuniões BMLT para formato padronizado (online only).
 */
class NA_Reunioes_Normalize_Meeting {

	private const ZOOM_PASSWORD_PADRAO = '000000';

	/**
	 * Normaliza lista de reuniões BMLT (apenas online).
	 *
	 * @param array<int, array<string, mixed>> $meetings Reuniões brutas.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_meetings( array $meetings, bool $only_within_24h = true ): array {
		$normalized = array();
		$seen_ids   = array();

		foreach ( $meetings as $meeting ) {
			$id = (string) ( $meeting['id_bigint'] ?? '' );
			if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
				continue;
			}

			$result = self::normalize_meeting( $meeting, $only_within_24h );
			if ( null !== $result ) {
				$normalized[]    = $result;
				$seen_ids[ $id ] = true;
			}
		}

		return $normalized;
	}

	/**
	 * Normaliza uma reunião BMLT.
	 *
	 * @param array<string, mixed> $meeting         Dados brutos.
	 * @param bool                 $only_within_24h Quando true, descarta reuniões fora das próximas 24h.
	 * @return array<string, mixed>|null
	 */
	public static function normalize_meeting( array $meeting, bool $only_within_24h = true ): ?array {
		$venue_type = (string) ( $meeting['venue_type'] ?? '' );
		$is_online  = '2' === $venue_type;
		$is_hybrid  = '3' === $venue_type;

		if ( ! $is_online && ! $is_hybrid ) {
			return null;
		}

		$link = $meeting['virtual_meeting_link'] ?? '';
		if ( ! self::is_valid_meeting_link( $link ) ) {
			return null;
		}

		$weekday = (int) ( $meeting['weekday_tinyint'] ?? 0 );
		if ( $weekday < 1 || $weekday > 7 ) {
			return null;
		}

		$start_raw  = (string) ( $meeting['start_time'] ?? '00:00:00' );
		$start_hour = (int) explode( ':', $start_raw )[0];

		$occurrence = NA_Reunioes_Time_Utils::get_next_occurrence(
			$weekday,
			$start_raw,
			(string) ( $meeting['duration_time'] ?? '' )
		);

		if ( $only_within_24h && ! NA_Reunioes_Time_Utils::is_within_24_hours( $occurrence['starts_at'] ) ) {
			return null;
		}

		$status = NA_Reunioes_Time_Utils::get_meeting_status(
			$occurrence['starts_at'],
			$occurrence['ends_at']
		);

		$formats = self::parse_formats( $meeting['formats'] ?? null );
		$kinds   = self::infer_meeting_kinds( $meeting );

		$normalized = array(
			'id'                  => (string) $meeting['id_bigint'],
			'name'                => ! empty( $meeting['meeting_name'] ) ? (string) $meeting['meeting_name'] : 'Reunião sem nome',
			'starts_at'           => gmdate( 'c', $occurrence['starts_at'] ),
			'ends_at'             => gmdate( 'c', $occurrence['ends_at'] ),
			'start_time'          => NA_Reunioes_Time_Utils::format_time( $occurrence['starts_at'] ),
			'end_time'            => NA_Reunioes_Time_Utils::format_time( $occurrence['ends_at'] ),
			'day_name'            => $occurrence['day_name'],
			'weekday'             => NA_Reunioes_Time_Utils::bmlt_weekday_to_js_day( $weekday ),
			'start_hour'          => $start_hour,
			'minutes_until_start' => NA_Reunioes_Time_Utils::get_minutes_until_start( $occurrence['starts_at'] ),
			'status'              => $status,
			'type'                => 'online',
			'formats'             => $formats,
			'language'            => $meeting['lang_enum'] ?? 'pt',
			'link'                => $link,
			'platform'            => self::detect_platform( $link ),
			'meeting_info'        => self::extract_meeting_info( $meeting ),
		);

		if ( ! empty( $kinds['meeting_kinds'] ) ) {
			$normalized['meeting_kinds'] = $kinds['meeting_kinds'];
		}
		if ( isset( $kinds['open'] ) ) {
			$normalized['open'] = $kinds['open'];
		}

		if ( 'zoom' === $normalized['platform'] ) {
			$credentials = self::extract_zoom_credentials( $meeting );
			if ( ! empty( $credentials['zoom_id'] ) ) {
				$normalized['zoom_id'] = $credentials['zoom_id'];
			}
			if ( ! empty( $credentials['zoom_pass'] ) ) {
				$normalized['zoom_pass'] = $credentials['zoom_pass'];
			}
		}

		return $normalized;
	}

	/**
	 * @param string|null $link Link da reunião.
	 * @return bool
	 */
	private static function is_valid_meeting_link( ?string $link ): bool {
		if ( empty( $link ) ) {
			return false;
		}
		return str_starts_with( $link, 'http://' ) || str_starts_with( $link, 'https://' );
	}

	/**
	 * @param string $link Link.
	 * @return string|null
	 */
	private static function detect_platform( string $link ): ?string {
		$lower = strtolower( $link );
		if ( str_contains( $lower, 'zoom.us' ) || str_contains( $lower, 'zoom.com' ) ) {
			return 'zoom';
		}
		if ( str_contains( $lower, 'meet.google.com' ) ) {
			return 'meet';
		}
		if ( str_contains( $lower, 'wa.me' ) || str_contains( $lower, 'whatsapp' ) ) {
			return 'whatsapp';
		}
		if ( str_starts_with( $link, 'http' ) ) {
			return 'other';
		}
		return null;
	}

	/**
	 * @param string|null $formats Formatos BMLT.
	 * @return array<int, string>|null
	 */
	private static function parse_formats( ?string $formats ): ?array {
		if ( empty( $formats ) ) {
			return null;
		}
		$parts = array_filter( array_map( 'trim', explode( ',', $formats ) ) );
		return ! empty( $parts ) ? array_values( $parts ) : null;
	}

	/**
	 * @param array<string, mixed> $meeting Reunião BMLT.
	 * @return string|null
	 */
	private static function extract_meeting_info( array $meeting ): ?string {
		$raw      = trim( (string) ( $meeting['virtual_meeting_additional_info'] ?? '' ) );
		$comments = trim( (string) ( $meeting['comments'] ?? '' ) );

		$cleaned = preg_replace( '/\bID[:\s]+[\d\s]+/i', '', $raw );
		$cleaned = preg_replace( '/Senha[:\s]+[\d]+(\s*\([^)]*\))?/i', '', (string) $cleaned );
		$cleaned = str_replace( '|', '', (string) $cleaned );
		$cleaned = trim( $cleaned );

		if ( $cleaned && $comments ) {
			return $cleaned . ' | ' . $comments;
		}
		return $cleaned ?: ( $comments ?: null );
	}

	/**
	 * @param string $raw ID bruto.
	 * @return string|null
	 */
	private static function normalize_zoom_id( string $raw ): ?string {
		$digits = preg_replace( '/\D/', '', $raw );
		$len    = strlen( $digits );
		if ( $len < 9 || $len > 12 ) {
			return null;
		}
		return $digits;
	}

	/**
	 * @param array<string, mixed> $meeting Reunião.
	 * @return array{zoom_id?:string,zoom_pass?:string}
	 */
	private static function extract_zoom_credentials( array $meeting ): array {
		$link      = (string) ( $meeting['virtual_meeting_link'] ?? '' );
		$info_text = ( $meeting['virtual_meeting_additional_info'] ?? '' ) . ' ' . ( $meeting['comments'] ?? '' );
		$zoom_id   = null;

		if ( preg_match( '/\/(?:wc\/)?j\/(\d{9,12})/i', $link, $m ) ) {
			$zoom_id = self::normalize_zoom_id( $m[1] );
		}

		if ( ! $zoom_id && preg_match( '/[?&](?:confno|meetingid)=([\d-]{9,20})/i', $link, $m ) ) {
			$zoom_id = self::normalize_zoom_id( $m[1] );
		}

		if ( ! $zoom_id && preg_match( '/\b(?:id(?:\s+da)?\s+reuni[aã]o|meeting\s*id|id)\b\s*[:#-]?\s*([\d\s-]{9,20})/i', $info_text, $m ) ) {
			$zoom_id = self::normalize_zoom_id( $m[1] );
		}

		if ( ! $zoom_id && preg_match( '/(?:\D|^)(\d{9,12})(?:\D|$)/', $info_text, $m ) ) {
			$zoom_id = self::normalize_zoom_id( $m[1] );
		}

		return array(
			'zoom_id'   => $zoom_id,
			'zoom_pass' => self::ZOOM_PASSWORD_PADRAO,
		);
	}

	/**
	 * @param array<string, mixed> $meeting Reunião.
	 * @return array{meeting_kinds:array<int,string>,open?:bool}
	 */
	private static function infer_meeting_kinds( array $meeting ): array {
		$source = strtolower(
			( $meeting['formats'] ?? '' ) . ' ' .
			( $meeting['virtual_meeting_additional_info'] ?? '' ) . ' ' .
			( $meeting['comments'] ?? '' )
		);

		$kinds         = array();
		$format_tokens = array_filter(
			preg_split( '/[^a-zA-Z0-9]+/', (string) ( $meeting['formats'] ?? '' ) ) ?: array()
		);
		$format_tokens = array_map( 'strtoupper', $format_tokens );

		if ( in_array( 'A', $format_tokens, true ) || in_array( 'O', $format_tokens, true ) ) {
			$kinds[] = 'Aberta';
		}
		if ( in_array( 'F', $format_tokens, true ) || in_array( 'C', $format_tokens, true ) ) {
			$kinds[] = 'Fechada';
		}
		if ( in_array( 'ST', $format_tokens, true ) ) {
			$kinds[] = 'Estudo';
		}

		if ( preg_match( '/\b(aberta|open|p[úu]blica)\b/', $source ) ) {
			$kinds[] = 'Aberta';
		}
		if ( preg_match( '/\b(fechada|closed|somente\s+adictos?)\b/', $source ) ) {
			$kinds[] = 'Fechada';
		}
		if ( preg_match( '/\b(estudo|study|literatura|passos?|tradi[cç][õo]es?)\b/', $source ) ) {
			$kinds[] = 'Estudo';
		}
		if ( preg_match( '/\b(partilha|partilhas|sharing|compartilhamento)\b/', $source ) ) {
			$kinds[] = 'Partilhas';
		}

		$kinds = array_values( array_unique( $kinds ) );

		$open = null;
		if ( in_array( 'Fechada', $kinds, true ) ) {
			$open = false;
		} elseif ( in_array( 'Aberta', $kinds, true ) ) {
			$open = true;
		}

		return array(
			'meeting_kinds' => $kinds,
			'open'          => $open,
		);
	}
}
