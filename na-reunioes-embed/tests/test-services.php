<?php
/**
 * Testes unitários leves (sem WordPress) para time utils e format badges.
 *
 * Executar: php tests/test-services.php
 *
 * @package NaReunioesEmbed
 */

// Simula constantes mínimas do plugin.
define( 'ABSPATH', __DIR__ . '/../' );
define( 'NA_REUNIOES_EMBED_PATH', dirname( __DIR__ ) . '/' );

require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-time-utils.php';
require_once NA_REUNIOES_EMBED_PATH . 'includes/services/class-format-badges.php';

$passed = 0;
$failed = 0;

function assert_true( bool $condition, string $message ): void {
	global $passed, $failed;
	if ( $condition ) {
		++$passed;
		echo "[OK] $message\n";
	} else {
		++$failed;
		echo "[FAIL] $message\n";
	}
}

// BMLT weekday: 1=domingo → JS 0.
assert_true(
	0 === NA_Reunioes_Time_Utils::bmlt_weekday_to_js_day( 1 ),
	'bmlt_weekday_to_js_day domingo'
);

assert_true(
	1 === NA_Reunioes_Time_Utils::bmlt_weekday_to_js_day( 2 ),
	'bmlt_weekday_to_js_day segunda'
);

assert_true(
	0 === NA_Reunioes_Time_Utils::sanitize_weekday( '0' ),
	'sanitize_weekday domingo'
);

assert_true(
	null === NA_Reunioes_Time_Utils::sanitize_weekday( '' ),
	'sanitize_weekday vazio'
);

assert_true(
	null === NA_Reunioes_Time_Utils::sanitize_weekday( '8' ),
	'sanitize_weekday inválido'
);

assert_true(
	'manha' === NA_Reunioes_Time_Utils::sanitize_period( 'manha' ),
	'sanitize_period manha'
);

assert_true(
	null === NA_Reunioes_Time_Utils::sanitize_period( 'almoco' ),
	'sanitize_period inválido'
);

$period_cases = array(
	0  => 'madrugada',
	6  => 'madrugada',
	7  => 'manha',
	11 => 'manha',
	12 => 'tarde',
	17 => 'tarde',
	18 => 'noite',
	21 => 'noite',
	22 => 'final-noite',
	23 => 'final-noite',
);

foreach ( $period_cases as $hour => $period ) {
	assert_true(
		NA_Reunioes_Time_Utils::matches_period( (int) $hour, $period ),
		"hora $hour pertence a $period"
	);
}

assert_true(
	! NA_Reunioes_Time_Utils::matches_period( 6, 'manha' ),
	'6h não é manhã'
);

assert_true(
	! NA_Reunioes_Time_Utils::matches_period( 7, 'madrugada' ),
	'7h não é madrugada'
);

assert_true(
	NA_Reunioes_Time_Utils::matches_period( 15, null ),
	'período vazio não filtra'
);

// format_time_remaining
assert_true(
	'45 min' === NA_Reunioes_Time_Utils::format_time_remaining( 45 ),
	'format_time_remaining minutos'
);

assert_true(
	'2h' === NA_Reunioes_Time_Utils::format_time_remaining( 120 ),
	'format_time_remaining horas exatas'
);

// parseFormats — A sem F gera badge aberta.
$badges = NA_Reunioes_Format_Badges::parse_formats( 'A,VM' );
assert_true(
	count( $badges ) >= 2,
	'parse_formats retorna badges A e VM'
);

// F cancela A.
$badges_closed = NA_Reunioes_Format_Badges::parse_formats( 'F,A,VM' );
$has_access    = false;
foreach ( $badges_closed as $badge ) {
	if ( 'A' === $badge['key'] ) {
		$has_access = true;
	}
}
assert_true( ! $has_access, 'F cancela badge de acesso A' );

echo "\nResultado: $passed passou, $failed falhou\n";
exit( $failed > 0 ? 1 : 0 );
