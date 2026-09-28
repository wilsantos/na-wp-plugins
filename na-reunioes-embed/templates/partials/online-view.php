<?php
/**
 * View de reuniões online.
 *
 * @package NaReunioesEmbed
 *
 * @var array<int, array<string, mixed>> $now
 * @var array<int, array<string, mixed>> $soon
 * @var array<int, array<string, mixed>> $next24h
 * @var string|null                      $error
 */

defined( 'ABSPATH' ) || exit;

$renderer = new NA_Reunioes_Renderer();
?>
<div class="na-reunioes-online-view space-y-0">
	<?php if ( empty( $now ) ) : ?>
		<div class="flex justify-end py-4">
			<?php include NA_REUNIOES_EMBED_PATH . 'templates/partials/refresh-button.php'; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="mb-4 rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive na-reunioes-error">
			<?php echo esc_html( $error ); ?>
		</div>
	<?php endif; ?>

	<div class="na-reunioes-sections">
		<?php if ( ! empty( $now ) ) : ?>
			<?php
			$section_id    = 'reunioes-agora';
			$section_title = __( 'Reuniões em Andamento', 'na-reunioes-embed' );
			$section_count = count( $now );
			$section_variant = 'now';
			$section_badge   = __( 'em andamento', 'na-reunioes-embed' );
			$show_refresh    = true;
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'now' );
				},
				$now
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( ! empty( $soon ) ) : ?>
			<?php
			$section_id    = 'reunioes-breve';
			$section_title = __( 'Reuniões em Breve', 'na-reunioes-embed' );
			$section_count = count( $soon );
			$section_variant = 'soon';
			$section_badge   = __( 'em breve', 'na-reunioes-embed' );
			$show_refresh    = false;
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'soon' );
				},
				$soon
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( ! empty( $next24h ) ) : ?>
			<?php
			$section_id    = 'reunioes-proximas';
			$section_title = __( 'Próximas Reuniões', 'na-reunioes-embed' );
			$section_count = count( $next24h );
			$section_variant = 'later';
			$section_badge   = __( 'próximas', 'na-reunioes-embed' );
			$show_refresh    = false;
			$meetings        = array_map(
				static function ( $m ) use ( $renderer ) {
					return $renderer::enrich_meeting( $m, 'later' );
				},
				$next24h
			);
			include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-section.php';
			?>
		<?php endif; ?>

		<?php if ( empty( $now ) && empty( $soon ) && empty( $next24h ) ) : ?>
			<div class="py-16 text-center na-reunioes-empty">
				<p class="text-lg text-muted-foreground">
					<?php esc_html_e( 'Nenhuma reunião online encontrada nas próximas 24h.', 'na-reunioes-embed' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
