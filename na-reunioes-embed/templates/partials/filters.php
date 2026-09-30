<?php
/**
 * Filtros de dia da semana e período do horário.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $filter_day ) ) {
	$filter_day = null;
}
if ( ! isset( $filter_period ) ) {
	$filter_period = null;
}

$weekdays = NA_Reunioes_Time_Utils::get_weekdays();
$periods  = NA_Reunioes_Time_Utils::get_periods();
$today    = NA_Reunioes_Time_Utils::get_sp_components()['dow'];
?>
<div class="na-reunioes-filters" role="group" aria-label="<?php esc_attr_e( 'Filtros de reuniões', 'na-reunioes-embed' ); ?>">
	<div class="na-reunioes-filters__field">
		<label for="na-reunioes-filter-day"><?php esc_html_e( 'Dia da semana', 'na-reunioes-embed' ); ?></label>
		<select id="na-reunioes-filter-day" class="na-reunioes-filter-day">
			<option value=""<?php echo null === $filter_day ? ' selected' : ''; ?>><?php esc_html_e( 'Próximas 24 horas', 'na-reunioes-embed' ); ?></option>
			<?php foreach ( $weekdays as $js_day => $day ) : ?>
				<?php $day_is_selected = null !== $filter_day && (int) $filter_day === (int) $js_day; ?>
				<option value="<?php echo esc_attr( (string) $js_day ); ?>"<?php echo $day_is_selected ? ' selected' : ''; ?>>
					<?php
					$day_label = $day['label'];
					if ( (int) $js_day === (int) $today ) {
						$day_label .= ' (' . __( 'hoje', 'na-reunioes-embed' ) . ')';
					}
					echo esc_html( $day_label );
					?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="na-reunioes-filters__field">
		<label for="na-reunioes-filter-period"><?php esc_html_e( 'Horário', 'na-reunioes-embed' ); ?></label>
		<select id="na-reunioes-filter-period" class="na-reunioes-filter-period">
			<option value=""<?php echo ( null === $filter_period || '' === $filter_period ) ? ' selected' : ''; ?>><?php esc_html_e( 'Todos os horários', 'na-reunioes-embed' ); ?></option>
			<?php foreach ( $periods as $key => $period ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"<?php echo $filter_period === $key ? ' selected' : ''; ?>>
					<?php
					echo esc_html(
						sprintf(
							'%s (%dh–%dh)',
							$period['label'],
							$period['from'],
							$period['to']
						)
					);
					?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
