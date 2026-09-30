<?php
/**
 * View de reuniões online.
 *
 * @package NaReunioesEmbed
 *
 * @var array<int, array<string, mixed>> $now
 * @var array<int, array<string, mixed>> $soon
 * @var array<int, array<string, mixed>> $next24h
 * @var array<int, array<string, mixed>> $day_meetings
 * @var int|null                         $filter_day
 * @var string|null                      $filter_period
 * @var string|null                      $error
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $day_meetings ) ) {
	$day_meetings = array();
}
if ( ! isset( $filter_day ) ) {
	$filter_day = null;
}
if ( ! isset( $filter_period ) ) {
	$filter_period = null;
}

$renderer      = new NA_Reunioes_Renderer();
$browsing_day  = null !== $filter_day;
$visible_count = $browsing_day
	? count( $day_meetings )
	: ( count( $now ) + count( $soon ) + count( $next24h ) );
$count_label   = 1 === $visible_count
	? __( 'reunião', 'na-reunioes-embed' )
	: __( 'reuniões', 'na-reunioes-embed' );
if ( $browsing_day ) {
	$total_section = 'day';
} elseif ( ! empty( $now ) ) {
	$total_section = 'now';
} elseif ( ! empty( $soon ) ) {
	$total_section = 'soon';
} else {
	$total_section = 'later';
}
?>
<div class="na-reunioes-online-view space-y-0">
	<div class="na-reunioes-top">
		<div class="na-reunioes-toolbar">
			<?php include NA_REUNIOES_EMBED_PATH . 'templates/partials/filters.php'; ?>
		</div>
		<?php echo NA_Reunioes_Format_Badges::render_types_legend(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="mb-4 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive na-reunioes-error">
			<?php echo esc_html( $error ); ?>
		</div>
	<?php endif; ?>

	<div class="na-reunioes-sections">
		<?php if ( $browsing_day && ! empty( $day_meetings ) ) : ?>
			<?php
			$weekdays    = NA_Reunioes_Time_Utils::get_weekdays();
			$periods     = NA_Reunioes_Time_Utils::get_periods();
			$day_meta    = $weekdays[ $filter_day ] ?? array( 'label' => '' );
			$period_meta = ( is_string( $filter_period ) && isset( $periods[ $filter_period ] ) ) ? $periods[ $filter_period ] : null;

			$section_id    = 'reunioes-dia';
			$section_title = sprintf(
				/* translators: %s: weekday name */
				__( 'Reuniões de %s', 'na-reunioes-embed' ),
				(string) ( $day_meta['label'] ?? '' )
			);
			if ( $period_meta ) {
				$section_title .= ' — ' . $period_meta['label'];
			}
			$section_count   = count( $day_meetings );
			$section_variant = 'day';
			$section_badge   = 1 === $section_count
				? __( 'reunião', 'na-reunioes-embed' )
				: __( 'reuniões', 'na-reunioes-embed' );
			$section_open    = false;
			$show_total      = ( 'day' === $total_section );
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'later' );
				},
				$day_meetings
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( ! $browsing_day && ! empty( $now ) ) : ?>
			<?php
			$section_id    = 'reunioes-agora';
			$section_title = __( 'Reuniões em Andamento', 'na-reunioes-embed' );
			$section_count = count( $now );
			$section_variant = 'now';
			$section_badge   = __( 'em andamento', 'na-reunioes-embed' );
			$section_open    = false;
			$show_total      = ( 'now' === $total_section );
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'now' );
				},
				$now
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( ! $browsing_day && ! empty( $soon ) ) : ?>
			<?php
			$section_id    = 'reunioes-breve';
			$section_title = __( 'Reuniões em Breve', 'na-reunioes-embed' );
			$section_count = count( $soon );
			$section_variant = 'soon';
			$section_badge   = __( 'em breve', 'na-reunioes-embed' );
			$section_open    = false;
			$show_total      = ( 'soon' === $total_section );
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'soon' );
				},
				$soon
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( ! $browsing_day && ! empty( $next24h ) ) : ?>
			<?php
			$section_id    = 'reunioes-proximas';
			$section_title = __( 'Próximas Reuniões', 'na-reunioes-embed' );
			$section_count = count( $next24h );
			$section_variant = 'later';
			$section_badge   = __( 'próximas', 'na-reunioes-embed' );
			$section_open    = is_string( $filter_period ) && '' !== $filter_period;
			$show_total      = ( 'later' === $total_section );
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'later' );
				},
				$next24h
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php
		$has_results = $browsing_day
			? ! empty( $day_meetings )
			: ( ! empty( $now ) || ! empty( $soon ) || ! empty( $next24h ) );
		?>
		<?php if ( ! $has_results ) : ?>
			<div class="py-16 text-center na-reunioes-empty">
				<p class="text-lg text-muted-foreground">
					<?php
					if ( $browsing_day ) {
						$weekdays    = NA_Reunioes_Time_Utils::get_weekdays();
						$periods     = NA_Reunioes_Time_Utils::get_periods();
						$day_phrase  = (string) ( $weekdays[ $filter_day ]['phrase'] ?? '' );
						$empty_text  = trim( 'Nenhuma reunião online encontrada ' . $day_phrase );
						if ( is_string( $filter_period ) && isset( $periods[ $filter_period ] ) ) {
							$empty_text .= ' ' . $periods[ $filter_period ]['phrase'];
						}
						$empty_text .= '.';
						echo esc_html( $empty_text );
					} elseif ( is_string( $filter_period ) && '' !== $filter_period ) {
						$periods      = NA_Reunioes_Time_Utils::get_periods();
						$period_phrase = (string) ( $periods[ $filter_period ]['phrase'] ?? '' );
						echo esc_html( trim( 'Nenhuma reunião online encontrada ' . $period_phrase . ' nas próximas 24h.' ) );
					} else {
						esc_html_e( 'Nenhuma reunião online encontrada nas próximas 24h.', 'na-reunioes-embed' );
					}
					?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
