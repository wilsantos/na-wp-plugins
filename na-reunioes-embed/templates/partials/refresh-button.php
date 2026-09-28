<?php
/**
 * Botão de atualizar reuniões.
 *
 * @package NaReunioesEmbed
 */

defined( 'ABSPATH' ) || exit;
?>
<button
	type="button"
	class="na-reunioes-refresh inline-flex items-center gap-2 rounded-md border border-[var(--na-blue)] bg-background px-3 py-1.5 text-sm font-medium text-[var(--na-blue)] hover:bg-muted disabled:opacity-50"
>
	<svg class="na-reunioes-refresh-icon h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/></svg>
	<?php esc_html_e( 'Atualizar', 'na-reunioes-embed' ); ?>
</button>
