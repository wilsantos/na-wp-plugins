<?php
/**
 * Seção de reuniões.
 *
 * @package NaReunioesEmbed
 *
 * @var string                           $section_id
 * @var string                           $section_title
 * @var int                              $section_count
 * @var string                           $section_variant now|soon|later|day
 * @var string                           $section_badge
 * @var bool                             $section_open Optional. Abre a seção recolhível.
 * @var bool                             $show_total   Optional. Exibe o total na linha do título.
 * @var array<int, array<string, mixed>> $meetings
 */

defined( 'ABSPATH' ) || exit;

$icon_svg = '';
if ( 'now' === $section_variant ) {
	$icon_svg = '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.1 19.1 19"/></svg>';
} elseif ( 'soon' === $section_variant ) {
	$icon_svg = '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
} else {
	$icon_svg = '<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>';
}
$is_collapsible  = ( 'later' === $section_variant );
$start_collapsed = $is_collapsible && empty( $section_open );
$section_classes = 'py-6 md:py-8';
if ( $is_collapsible ) {
	$section_classes .= ' na-section-collapsible';
	if ( $start_collapsed ) {
		$section_classes .= ' is-collapsed';
	}
}
?>
<section id="<?php echo esc_attr( $section_id ); ?>" class="<?php echo esc_attr( $section_classes ); ?>">
	<div class="na-section-heading flex items-center justify-between mb-4 md:mb-6">
		<?php if ( $is_collapsible ) : ?>
			<button
				type="button"
				class="na-section-toggle flex items-center gap-2 text-left"
				aria-expanded="<?php echo $start_collapsed ? 'false' : 'true'; ?>"
				aria-controls="<?php echo esc_attr( $section_id . '-content' ); ?>"
			>
				<svg class="na-section-toggle-icon h-5 w-5 shrink-0 text-[var(--na-blue)]" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
				<span class="text-[var(--na-blue)]"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG ?></span>
				<h2 class="text-xl md:text-2xl font-bold text-foreground"><?php echo esc_html( $section_title ); ?></h2>
			</button>
		<?php else : ?>
			<div class="flex items-center gap-2">
				<span class="text-[var(--na-blue)]"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG ?></span>
				<h2 class="text-xl md:text-2xl font-bold text-foreground"><?php echo esc_html( $section_title ); ?></h2>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $show_total ) ) : ?>
			<span class="na-reunioes-count">
				<?php echo esc_html( (string) $visible_count . ' ' . $count_label ); ?>
			</span>
		<?php endif; ?>
	</div>
	<div
		id="<?php echo esc_attr( $section_id . '-content' ); ?>"
		class="<?php echo esc_attr( $is_collapsible ? 'na-section-collapsible__content' : '' ); ?> grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch"
		<?php echo $start_collapsed ? 'hidden' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
		<?php foreach ( $meetings as $meeting ) : ?>
			<?php
			if ( 'now' === $section_variant ) {
				include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-card-now.php';
			} elseif ( 'soon' === $section_variant ) {
				include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-card-soon.php';
			} else {
				include NA_REUNIOES_EMBED_PATH . 'templates/partials/meeting-list-item.php';
			}
			?>
		<?php endforeach; ?>
	</div>
</section>
