<?php
/**
 * Wrapper do embed.
 *
 * @package NaReunioesEmbed
 *
 * @var array<int, array<string, mixed>> $now
 * @var array<int, array<string, mixed>> $soon
 * @var array<int, array<string, mixed>> $next24h
 * @var array<string, mixed>             $meta
 * @var string                           $rest_url
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	id="na-reunioes-embed"
	class="na-reunioes-embed font-sans antialiased min-h-[200px] bg-background text-foreground"
	data-rest-url="<?php echo esc_url( $rest_url ); ?>"
>
	<main class="mx-auto max-w-7xl px-4 na-reunioes-main">
		<?php
		include NA_REUNIOES_EMBED_PATH . 'templates/partials/online-view.php';
		?>
	</main>
	<div class="na-reunioes-toast-container fixed bottom-4 right-4 z-50 flex flex-col gap-2" aria-live="polite"></div>
</div>
