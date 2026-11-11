<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
use Estrabao\Contracts\Submission;
use Estrabao\Reporting\Report;
use Estrabao\Rules\DefaultRules;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
try {
    if ($argc !== 2 || preg_match('/^[a-z][a-z0-9+.-]*:/i', $argv[1])
        || !is_file($argv[1]) || !is_readable($argv[1]) || filesize($argv[1]) > 1048576) {
        throw new InvalidArgumentException('Uso: php bin/triage.php snapshot.json (arquivo local até 1 MiB).');
    }
    $snapshot = Submission::fromJson(file_get_contents($argv[1]));
    $report = Report::generate($snapshot, DefaultRules::all());
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $error) {
    // Não revelar conteúdo do manuscrito ou caminhos internos em erros.
    fwrite(STDERR, 'Entrada inválida ou relatório não gerado. Consulte o contrato e o uso da CLI.' . PHP_EOL);
    exit(1);
}
