<?php
/**
 * Card de reunião em andamento.
 *
 * @package NaReunioesEmbed
 *
 * @var array<string, mixed> $meeting
 */

defined( 'ABSPATH' ) || exit;

$platform_label = ( 'zoom' === ( $meeting['platform'] ?? '' ) ) ? 'Zoom' : __( 'Plataforma', 'na-reunioes-embed' );
?>
<article class="na-meeting-card na-card-now overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm" data-meeting-id="<?php echo esc_attr( (string) $meeting['id'] ); ?>">
	<div class="na-meeting-card__body p-4">
		<div class="na-meeting-card__content space-y-3">
			<div class="flex items-center justify-between gap-2">
				<span class="na-status-pill inline-flex items-center rounded-full bg-[var(--na-blue)] px-2.5 py-0.5 text-xs font-semibold text-white">
					<?php esc_html_e( 'Em andamento', 'na-reunioes-embed' ); ?>
				</span>
				<?php if ( ! empty( $meeting['minutes_until_end'] ) ) : ?>
					<span class="text-sm font-medium text-[var(--na-status-soon-ink)]">
						<?php
						printf(
							/* translators: %s: time remaining */
							esc_html__( 'termina em %s', 'na-reunioes-embed' ),
							esc_html( NA_Reunioes_Time_Utils::format_time_remaining( (int) $meeting['minutes_until_end'] ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<h3 class="text-lg font-bold leading-tight text-[var(--na-blue)]"><?php echo esc_html( $meeting['name'] ); ?></h3>

			<div class="na-meeting-card__kind">
				<?php echo NA_Reunioes_Format_Badges::render_primary_kind( $meeting ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<div class="na-meeting-card__footer">
			<div class="text-2xl font-bold text-[var(--na-blue)]">
				<?php echo esc_html( $meeting['start_time'] . ' - ' . $meeting['end_time'] ); ?>
			</div>

			<?php if ( ! empty( $meeting['zoom_id'] ) ) : ?>
				<p class="text-sm text-muted-foreground">
					<?php
					echo esc_html( $platform_label . ' - ID: ' . $meeting['zoom_id'] );
					if ( ! empty( $meeting['zoom_pass'] ) ) {
						echo esc_html( ' - Senha: ' . $meeting['zoom_pass'] );
					}
					?>
				</p>
			<?php endif; ?>

			<div class="na-meeting-card__actions flex items-center gap-2">
				<?php if ( ! empty( $meeting['link'] ) ) : ?>
					<a
						href="<?php echo esc_url( $meeting['link'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						class="flex-1 inline-flex items-center justify-center gap-2 rounded-md bg-[var(--na-blue)] px-4 py-2 text-sm font-medium text-white hover:bg-[var(--na-blue-light)]"
					>
						<svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
						<?php esc_html_e( 'Entrar', 'na-reunioes-embed' ); ?>
					</a>
				<?php endif; ?>
				<button
					type="button"
					class="na-reunioes-share inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-border bg-background hover:bg-muted"
					aria-label="<?php esc_attr_e( 'Compartilhar reunião', 'na-reunioes-embed' ); ?>"
					data-share-name="<?php echo esc_attr( $meeting['name'] ); ?>"
					data-share-start="<?php echo esc_attr( $meeting['start_time'] ); ?>"
					data-share-end="<?php echo esc_attr( $meeting['end_time'] ); ?>"
				>
					<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
				</button>
			</div>
		</div>
	</div>
</article>
