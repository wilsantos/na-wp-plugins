<?php
/**
 * Badges de formato de reunião.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gera badges HTML a partir dos formatos BMLT.
 */
class NA_Reunioes_Format_Badges {

	/**
	 * Mapa de formatos BMLT para label e cor.
	 *
	 * @var array<string, array{label:string,color:string}>
	 */
	private const FORMAT_MAP = array(
		'A'        => array( 'label' => 'Aberta para visitantes', 'color' => 'green' ),
		'VM'       => array( 'label' => 'Online', 'color' => 'blue' ),
		'HY'       => array( 'label' => 'Híbrida', 'color' => 'purple' ),
		'TP'       => array( 'label' => 'Reunião de Partilha', 'color' => 'orange' ),
		'ES'       => array( 'label' => 'Estudo', 'color' => 'violet' ),
		'EST'      => array( 'label' => 'Estudo', 'color' => 'violet' ),
		'LIT'      => array( 'label' => 'Estudo', 'color' => 'violet' ),
		'PDS'      => array( 'label' => 'Reunião de Partilha', 'color' => 'orange' ),
		'SP'       => array( 'label' => 'Reunião de Partilha', 'color' => 'orange' ),
		'SH'       => array( 'label' => 'Reunião de Partilha', 'color' => 'orange' ),
		'TEMATICA' => array( 'label' => 'Temática', 'color' => 'orange' ),
	);

	/**
	 * Classes Tailwind por cor.
	 *
	 * @var array<string, string>
	 */
	private const COLOR_CLASSES = array(
		'green'  => 'bg-green-100 text-green-700 border-green-200',
		'slate'  => 'bg-slate-100 text-slate-700 border-slate-200',
		'blue'   => 'bg-blue-100 text-blue-700 border-blue-200',
		'purple' => 'bg-purple-100 text-purple-700 border-purple-200',
		'orange' => 'bg-orange-100 text-orange-700 border-orange-200',
		'violet' => 'bg-violet-100 text-violet-700 border-violet-200',
		'pink'   => 'bg-pink-100 text-pink-700 border-pink-200',
	);

	/**
	 * Parseia string de formatos BMLT.
	 *
	 * @param string|null $format_string Formatos separados por vírgula.
	 * @return array<int, array{key:string,label:string,color:string}>
	 */
	public static function parse_formats( ?string $format_string ): array {
		if ( empty( $format_string ) ) {
			return array();
		}

		$raw     = strtoupper( $format_string );
		$formats = array_map( 'trim', explode( ',', $raw ) );
		$result  = array();

		$has_text = static function ( string $pattern ) use ( $raw ): bool {
			return 1 === preg_match( $pattern, $raw );
		};

		$access_format = in_array( 'F', $formats, true ) ? null : ( in_array( 'A', $formats, true ) ? 'A' : null );
		$modality      = null;
		foreach ( array( 'VM', 'HY' ) as $token ) {
			if ( in_array( $token, $formats, true ) ) {
				$modality = $token;
				break;
			}
		}

		$study_format = null;
		foreach ( array( 'ES', 'EST', 'LIT' ) as $token ) {
			if ( in_array( $token, $formats, true ) ) {
				$study_format = $token;
				break;
			}
		}
		if ( ! $study_format && $has_text( '/ESTUDO|LITERATURA|PASSOS|TRADI[ÇC][ÕO]ES/' ) ) {
			$study_format = 'ES';
		}

		$thematic_format = null;
		foreach ( array( 'TEM', 'TM' ) as $token ) {
			if ( in_array( $token, $formats, true ) ) {
				$thematic_format = 'TEMATICA';
				break;
			}
		}
		if ( ! $thematic_format && $has_text( '/TEM[ÁA]TIC/' ) ) {
			$thematic_format = 'TEMATICA';
		}

		$partilha_format = null;
		foreach ( array( 'TP', 'PDS', 'SP', 'SH' ) as $token ) {
			if ( in_array( $token, $formats, true ) ) {
				$partilha_format = $token;
				break;
			}
		}
		if ( ! $partilha_format && $has_text( '/TEMPO DE PARTILHA|PARTILHA/' ) ) {
			$partilha_format = 'TP';
		}

		$type_format = $study_format ?? $thematic_format ?? $partilha_format;

		if ( $access_format && isset( self::FORMAT_MAP[ $access_format ] ) ) {
			$result[] = array_merge( array( 'key' => $access_format ), self::FORMAT_MAP[ $access_format ] );
		}
		if ( $modality && isset( self::FORMAT_MAP[ $modality ] ) ) {
			$result[] = array_merge( array( 'key' => $modality ), self::FORMAT_MAP[ $modality ] );
		}
		if ( $type_format && isset( self::FORMAT_MAP[ $type_format ] ) ) {
			$result[] = array_merge( array( 'key' => $type_format ), self::FORMAT_MAP[ $type_format ] );
		}

		return $result;
	}

	/**
	 * Monta badges finais com fallbacks de open/type.
	 *
	 * @param string|null $formats String de formatos.
	 * @param bool|null   $open    Se reunião é aberta.
	 * @param string      $type    Tipo da reunião (online).
	 * @return array<int, array{key:string,label:string,color:string}>
	 */
	public static function build_badges( ?string $formats, ?bool $open, string $type = 'online' ): array {
		$badges = self::parse_formats( $formats );

		$flagged_closed = false;
		if ( $formats ) {
			$tokens = array_map( 'trim', explode( ',', strtoupper( $formats ) ) );
			$flagged_closed = in_array( 'F', $tokens, true );
		}

		$has_access = false;
		foreach ( $badges as $badge ) {
			if ( 'A' === $badge['key'] ) {
				$has_access = true;
				break;
			}
		}

		if ( ! $has_access && true === $open && ! $flagged_closed ) {
			array_unshift(
				$badges,
				array(
					'key'   => 'A',
					'label' => 'Aberta para visitantes',
					'color' => 'green',
				)
			);
		}

		$has_modality = false;
		foreach ( $badges as $badge ) {
			if ( in_array( $badge['key'], array( 'VM', 'HY' ), true ) ) {
				$has_modality = true;
				break;
			}
		}

		if ( ! $has_modality && $type ) {
			$modality_badge = array(
				'key'   => 'online' === $type ? 'VM' : 'PRES',
				'label' => 'online' === $type ? 'Online' : 'Presencial',
				'color' => 'online' === $type ? 'blue' : 'slate',
			);

			$idx = -1;
			foreach ( $badges as $i => $badge ) {
				if ( 'A' !== $badge['key'] ) {
					$idx = $i;
					break;
				}
			}

			if ( -1 === $idx ) {
				$badges[] = $modality_badge;
			} else {
				array_splice( $badges, $idx, 0, array( $modality_badge ) );
			}
		}

		return $badges;
	}

	/**
	 * Renderiza HTML dos badges.
	 *
	 * @param string|null $formats Formatos.
	 * @param bool|null   $open    Aberta.
	 * @param string      $type    Tipo.
	 * @return string
	 */
	public static function render( ?string $formats, ?bool $open, string $type = 'online' ): string {
		$badges = self::build_badges( $formats, $open, $type );
		if ( empty( $badges ) ) {
			return '';
		}

		$html = '<div class="flex flex-wrap gap-1.5">';
		foreach ( $badges as $badge ) {
			$color_class = self::COLOR_CLASSES[ $badge['color'] ] ?? self::COLOR_CLASSES['slate'];
			$html       .= sprintf(
				'<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border %s">%s</span>',
				esc_attr( $color_class ),
				esc_html( $badge['label'] )
			);
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Badge principal do tipo de reunião (Aberta, Fechada, Estudo, Partilhas).
	 *
	 * @param array<string, mixed> $meeting Reunião normalizada.
	 * @return string
	 */
	public static function render_primary_kind( array $meeting ): string {
		$kind = self::pick_primary_kind( $meeting );
		if ( ! $kind ) {
			return '';
		}

		return sprintf(
			'<span class="na-kind-badge inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">%s%s</span>',
			self::get_kind_icon( $kind ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
			esc_html( $kind )
		);
	}

	/**
	 * Legenda de tipos exibida no cabeçalho da seção.
	 *
	 * @return string
	 */
	public static function render_types_legend(): string {
		$items = array(
			array(
				'kind'        => 'Aberta',
				'description' => __( 'público em geral', 'na-reunioes-embed' ),
				'class'       => 'bg-green-100 text-green-700 border-green-200',
			),
			array(
				'kind'        => 'Fechada',
				'description' => __( 'que tem ou acha que tem problema com drogas', 'na-reunioes-embed' ),
				'class'       => 'bg-blue-100 text-blue-700 border-blue-200',
			),
			array(
				'kind'        => 'Estudo',
				'description' => __( 'estudo de literatura', 'na-reunioes-embed' ),
				'class'       => 'bg-violet-100 text-violet-700 border-violet-200',
			),
		);

		$html = '<div class="na-types-legend flex flex-wrap items-center gap-2 mb-4 md:mb-6">';
		$html .= '<span class="text-sm text-muted-foreground">' . esc_html__( 'Tipos:', 'na-reunioes-embed' ) . '</span>';

		foreach ( $items as $item ) {
			$html .= sprintf(
				'<span class="na-types-legend__item inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs %1$s">%2$s<span><strong>%3$s</strong> - %4$s</span></span>',
				esc_attr( $item['class'] ),
				self::get_legend_icon( $item['kind'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG.
				esc_html( $item['kind'] ),
				esc_html( $item['description'] )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Escolhe o tipo principal da reunião.
	 *
	 * @param array<string, mixed> $meeting Reunião.
	 * @return string|null
	 */
	private static function pick_primary_kind( array $meeting ): ?string {
		$kinds    = $meeting['meeting_kinds'] ?? array();
		$priority = array( 'Fechada', 'Aberta', 'Estudo', 'Partilhas' );

		foreach ( $priority as $kind ) {
			if ( in_array( $kind, $kinds, true ) ) {
				return $kind;
			}
		}

		if ( isset( $meeting['open'] ) ) {
			return $meeting['open'] ? 'Aberta' : 'Fechada';
		}

		return null;
	}

	/**
	 * Ícone SVG da legenda de tipos.
	 *
	 * @param string $kind Tipo.
	 * @return string
	 */
	private static function get_legend_icon( string $kind ): string {
		switch ( $kind ) {
			case 'Aberta':
				return '<svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
			case 'Estudo':
				return '<svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/></svg>';
			default:
				return '<svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
		}
	}

	/**
	 * Ícone SVG por tipo de reunião.
	 *
	 * @param string $kind Tipo.
	 * @return string
	 */
	private static function get_kind_icon( string $kind ): string {
		switch ( $kind ) {
			case 'Aberta':
				return '<svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>';
			case 'Estudo':
				return '<svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/></svg>';
			case 'Partilhas':
				return '<svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
			default:
				return '<svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
		}
	}

	/**
	 * Converte array de formatos para string.
	 *
	 * @param array<int, string>|string|null $formats Formatos.
	 * @return string|null
	 */
	public static function formats_to_string( $formats ): ?string {
		if ( is_array( $formats ) ) {
			return implode( ',', $formats );
		}
		return is_string( $formats ) ? $formats : null;
	}
}
