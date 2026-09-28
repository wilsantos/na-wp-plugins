<?php
/**
 * Utilitários de tempo — timezone America/Sao_Paulo.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Utilitários de data/hora para reuniões NA.
 */
class NA_Reunioes_Time_Utils {

	public const TIMEZONE = 'America/Sao_Paulo';

	public const MINUTES_SOON_WINDOW     = 60;
	public const MINUTES_MEETING_DURATION = 60;
	public const MINUTES_NEXT_24H         = 1440;

	/**
	 * Retorna componentes da data/hora atual em São Paulo.
	 *
	 * @param int|null $timestamp Epoch UTC opcional.
	 * @return array{year:int,month:int,day:int,hour:int,minute:int,second:int,dow:int}
	 */
	public static function get_sp_components( ?int $timestamp = null ): array {
		$tz  = new DateTimeZone( self::TIMEZONE );
		$dt  = new DateTime( 'now', $tz );
		if ( null !== $timestamp ) {
			$dt->setTimestamp( $timestamp );
		}

		return array(
			'year'   => (int) $dt->format( 'Y' ),
			'month'  => (int) $dt->format( 'n' ),
			'day'    => (int) $dt->format( 'j' ),
			'hour'   => (int) $dt->format( 'G' ),
			'minute' => (int) $dt->format( 'i' ),
			'second' => (int) $dt->format( 's' ),
			'dow'    => (int) $dt->format( 'w' ),
		);
	}

	/**
	 * Converte componentes SP para epoch UTC.
	 *
	 * @param int $year   Ano.
	 * @param int $month  Mês 1-12.
	 * @param int $day    Dia.
	 * @param int $hour   Hora.
	 * @param int $minute Minuto.
	 * @param int $second Segundo.
	 * @return int
	 */
	public static function epoch_from_sp( int $year, int $month, int $day, int $hour, int $minute, int $second = 0 ): int {
		$iso = sprintf(
			'%04d-%02d-%02dT%02d:%02d:%02d-03:00',
			$year,
			$month,
			$day,
			$hour,
			$minute,
			$second
		);
		return (int) ( new DateTime( $iso ) )->getTimestamp();
	}

	/**
	 * Retorna epoch do instante atual em SP.
	 *
	 * @return int
	 */
	public static function get_now_epoch_sp(): int {
		$sp = self::get_sp_components();
		return self::epoch_from_sp(
			$sp['year'],
			$sp['month'],
			$sp['day'],
			$sp['hour'],
			$sp['minute'],
			$sp['second']
		);
	}

	/**
	 * Converte weekday BMLT (1=dom … 7=sáb) para JS (0=dom … 6=sáb).
	 *
	 * @param int $bmlt_weekday Dia BMLT.
	 * @return int
	 */
	public static function bmlt_weekday_to_js_day( int $bmlt_weekday ): int {
		return ( ( $bmlt_weekday - 1 ) % 7 + 7 ) % 7;
	}

	/**
	 * Nome do dia da semana em português.
	 *
	 * @param int $js_day 0-6.
	 * @return string
	 */
	public static function get_day_name( int $js_day ): string {
		$days = array( 'domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado' );
		return $days[ $js_day ] ?? '';
	}

	/**
	 * Calcula a próxima ocorrência de uma reunião.
	 *
	 * @param int    $weekday_tinyint Dia BMLT 1-7.
	 * @param string $start_time      Horário início HH:MM:SS.
	 * @param string $duration_time   Duração HH:MM:SS.
	 * @return array{starts_at:int,ends_at:int,day_name:string}
	 */
	public static function get_next_occurrence( int $weekday_tinyint, string $start_time, string $duration_time ): array {
		$sp         = self::get_sp_components();
		$target_dow = self::bmlt_weekday_to_js_day( $weekday_tinyint );

		$start_parts = array_map( 'intval', explode( ':', $start_time ) );
		$start_hours = $start_parts[0] ?? 0;
		$start_mins  = $start_parts[1] ?? 0;

		$duration_minutes = self::MINUTES_MEETING_DURATION;
		if ( $duration_time ) {
			$dur_parts = array_map( 'intval', explode( ':', $duration_time ) );
			$parsed    = ( $dur_parts[0] ?? 0 ) * 60 + ( $dur_parts[1] ?? 0 );
			if ( $parsed > 0 ) {
				$duration_minutes = $parsed;
			}
		}

		$start_epoch_today = self::epoch_from_sp(
			$sp['year'],
			$sp['month'],
			$sp['day'],
			$start_hours,
			$start_mins,
			0
		);
		$end_epoch_today = $start_epoch_today + ( $duration_minutes * 60 );
		$now_epoch       = self::get_now_epoch_sp();

		$days_until = $target_dow - $sp['dow'];

		if ( 0 === $days_until ) {
			if ( $end_epoch_today <= $now_epoch ) {
				$days_until = 7;
			}
		} elseif ( $days_until < 0 ) {
			$days_until += 7;
		}

		$start_epoch = $start_epoch_today + ( $days_until * 86400 );
		$end_epoch   = $start_epoch + ( $duration_minutes * 60 );

		$start_dow = self::get_sp_components( $start_epoch )['dow'];

		return array(
			'starts_at' => $start_epoch,
			'ends_at'   => $end_epoch,
			'day_name'  => self::get_day_name( $start_dow ),
		);
	}

	/**
	 * Minutos até o início da reunião.
	 *
	 * @param int $starts_at Epoch UTC.
	 * @return int
	 */
	public static function get_minutes_until_start( int $starts_at ): int {
		return (int) floor( ( $starts_at - self::get_now_epoch_sp() ) / 60 );
	}

	/**
	 * Minutos até o fim da reunião.
	 *
	 * @param int $ends_at Epoch UTC.
	 * @return int
	 */
	public static function get_minutes_until_end( int $ends_at ): int {
		$diff = (int) floor( ( $ends_at - self::get_now_epoch_sp() ) / 60 );
		return max( 0, $diff );
	}

	/**
	 * Status da reunião: now, soon ou later.
	 *
	 * @param int $starts_at Epoch início.
	 * @param int $ends_at   Epoch fim.
	 * @return string
	 */
	public static function get_meeting_status( int $starts_at, int $ends_at ): string {
		$now              = self::get_now_epoch_sp();
		$soon_threshold   = $now + ( self::MINUTES_SOON_WINDOW * 60 );

		if ( $starts_at <= $now && $now < $ends_at ) {
			return 'now';
		}
		if ( $starts_at > $now && $starts_at <= $soon_threshold ) {
			return 'soon';
		}
		return 'later';
	}

	/**
	 * Verifica se a reunião está nas próximas 24h.
	 *
	 * @param int $starts_at Epoch início.
	 * @return bool
	 */
	public static function is_within_24_hours( int $starts_at ): bool {
		$now      = self::get_now_epoch_sp();
		$next_24h = $now + ( self::MINUTES_NEXT_24H * 60 );
		return $starts_at <= $next_24h;
	}

	/**
	 * Formata horário HH:mm em SP.
	 *
	 * @param int $timestamp Epoch UTC.
	 * @return string
	 */
	public static function format_time( int $timestamp ): string {
		$dt = new DateTime( '@' . $timestamp );
		$dt->setTimezone( new DateTimeZone( self::TIMEZONE ) );
		return $dt->format( 'H:i' );
	}

	/**
	 * Retorna hora atual formatada em SP.
	 *
	 * @return string
	 */
	public static function get_current_time_formatted(): string {
		return self::format_time( self::get_now_epoch_sp() );
	}

	/**
	 * Formata minutos restantes para exibição.
	 *
	 * @param int $minutes Minutos.
	 * @return string
	 */
	public static function format_time_remaining( int $minutes ): string {
		if ( $minutes < 60 ) {
			return $minutes . ' min';
		}
		$hours = (int) floor( $minutes / 60 );
		$mins  = $minutes % 60;
		if ( 0 === $mins ) {
			return $hours . 'h';
		}
		return $hours . 'h ' . $mins . 'min';
	}
}
