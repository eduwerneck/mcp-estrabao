<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
use Estrabao\Contracts\{Submission, CheckResult};
use Estrabao\Rules\{MetadataPresence, Orcid, References};
use Estrabao\Reporting\Report;

$tests = 0;
$failed = 0;
function test(string $name, callable $body): void
{
    global $tests, $failed;
    $tests++;
    try {
        $body();
        echo "PASS $name\n";
    } catch (Throwable $e) {
        $failed++;
        fwrite(STDERR, "FAIL $name: " . $e->getMessage() . "\n");
    }
}
function same($expected, $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException('Esperado ' . json_encode($expected) . ', recebido ' . json_encode($actual));
    }
}
function rejects(callable $body): void
{
    try {
        $body();
    } catch (InvalidArgumentException $e) {
        return;
    }
    throw new RuntimeException('Entrada inválida foi aceita.');
}
function fixture(): array
{
    return json_decode(file_get_contents(__DIR__ . '/fixtures/complete.json'), true, 64, JSON_THROW_ON_ERROR);
}
function metadata(array $data): array
{
    return array_map(function ($r) { return $r->jsonSerialize(); }, (new MetadataPresence())->evaluate(new Submission($data)));
}

test('metadados trilíngues completos', function () {
    $checks = metadata(fixture());
    same(9, count($checks));
    same(['conforme'], array_values(array_unique(array_column($checks, 'status'))));
});
foreach (['title', 'abstract', 'keywords'] as $field) {
    foreach (['pt_BR', 'en_US', 'es_ES'] as $locale) {
        test("ausência objetiva $field/$locale", function () use ($field, $locale) {
            $data = fixture();
            unset($data[$field][$locale]);
            $failures = array_values(array_filter(metadata($data), function ($r) { return $r['status'] === 'não conforme'; }));
            same(1, count($failures));
            same('/' . $field . '/' . $locale, $failures[0]['location']);
        });
    }
}
foreach (['', " \t\n", '<p><br></p>', '<p>&nbsp;</p>', "\u{00A0}"] as $blank) {
    test('texto sem conteúdo: ' . json_encode($blank), function () use ($blank) {
        $data = fixture(); $data['title']['pt_BR'] = $blank;
        same('não conforme', metadata($data)[0]['status']);
    });
}
test('palavras-chave vazias não conformes', function () {
    $data = fixture(); $data['keywords']['pt_BR'] = ['', ' '];
    same('não conforme', metadata($data)[6]['status']);
});
test('extração indisponível não é ausência comprovada', function () {
    $checks = metadata(['schema_version' => '1.0']);
    same(['indeterminado'], array_values(array_unique(array_column($checks, 'status'))));
});
test('mapa completo vazio é ausência comprovada', function () {
    $data = fixture(); $data['title'] = [];
    same('não conforme', metadata($data)[0]['status']);
});
foreach ([
    '0000-0000-0000-001X' => true,
    'https://orcid.org/0000-0000-0000-0028' => true,
    '0000-0000-0000-0010' => false,
    '000000000000001X' => false,
    '0000-0000-0000-001x' => false,
    'http://orcid.org/0000-0000-0000-001X' => false,
    'https://orcid.org.evil.test/0000-0000-0000-001X' => false,
    'https://orcid.org/0000-0000-0000-001X?token=x' => false,
    "0000-0000-0000-001X\n" => false,
] as $value => $valid) {
    test('ORCID formato/checksum ' . json_encode($value), function () use ($value, $valid) { same($valid, Orcid::valid($value)); });
}
test('todos os autores avaliados e identidade indeterminada', function () {
    $data = fixture(); $data['authors'][] = ['orcid' => null];
    $checks = array_map(function ($r) { return $r->jsonSerialize(); }, (new Orcid())->evaluate(new Submission($data)));
    same(9, count($checks));
    same('conforme', $checks[1]['status']);
    same('indeterminado', $checks[2]['status']);
    same('não conforme', $checks[6]['status']);
    same('/authors/2/orcid', $checks[6]['location']);
    same('indeterminado', $checks[7]['status']);
});
test('ORCID informado com checksum inválido', function () {
    $data = fixture(); $data['authors'] = [['orcid' => '0000-0000-0000-0010']];
    $checks = (new Orcid())->evaluate(new Submission($data));
    same('conforme', $checks[0]->jsonSerialize()['status']);
    same('não conforme', $checks[1]->jsonSerialize()['status']);
});
foreach ([null, []] as $authors) {
    test('autores indisponíveis ou lista vazia', function () use ($authors) {
        $data = fixture(); $data['authors'] = $authors;
        same('indeterminado', (new Orcid())->evaluate(new Submission($data))[0]->jsonSerialize()['status']);
    });
}
foreach ([
    ['schema_version' => '2'],
    ['schema_version' => '1.0', 'title' => 'texto'],
    ['schema_version' => '1.0', 'title' => ['pt' => 'texto']],
    ['schema_version' => '1.0', 'title' => ['pt_BR' => false]],
    ['schema_version' => '1.0', 'keywords' => ['pt_BR' => ['abc' => 'x']]],
    ['schema_version' => '1.0', 'keywords' => ['pt_BR' => [42]]],
    ['schema_version' => '1.0', 'authors' => [['orcid' => 42]]],
    ['schema_version' => '1.0', 'authors' => [[]]],
    ['schema_version' => '1.0', 'token' => 'synthetic'],
    ['schema_version' => '1.0', 'authors' => [['orcid' => null, 'token' => 'synthetic']]],
] as $index => $data) {
    test('contrato rejeita entrada inválida ' . $index, function () use ($data) { rejects(function () use ($data) { new Submission($data); }); });
}
foreach (['[]', '{"schema_version":"1.0","title":[]}', '{"schema_version":"1.0","authors":{}}', '{"schema_version":"1.0","keywords":{"pt_BR":{}}}'] as $json) {
    test('preservar distinção objeto/lista JSON ' . $json, function () use ($json) { rejects(function () use ($json) { Submission::fromJson($json); }); });
}
test('JSON malformado rejeitado', function () {
    try { Submission::fromJson('{'); } catch (JsonException $e) { return; }
    throw new RuntimeException('JSON inválido aceito.');
});
test('pré-checagem de referências sem fonte é indeterminada', function () {
    $checks = (new References())->evaluate(new Submission(fixture()));
    same(4, count($checks));
    foreach ($checks as $check) {
        same('indeterminado', $check->jsonSerialize()['status']);
        same(true, $check->jsonSerialize()['evidence']['implemented']);
    }
});
test('relatório auditável, sem ORCID nem textos brutos', function () {
    $data = fixture(); $snapshot = new Submission($data);
    $report = Report::generate($snapshot, [new MetadataPresence(), new Orcid(), new References()]);
    same(19, count($report['checks']));
    same(true, $report['human_review_required']);
    same($snapshot->digest(), $report['input_sha256']);
    $data['title']['pt_BR'] = 'Outro título sintético';
    same(false, (new Submission($data))->digest() === $snapshot->digest());
    $encoded = json_encode($report, JSON_THROW_ON_ERROR);
    same(false, strpos($encoded, '0000-0000') !== false);
    same(false, strpos($encoded, 'Synthetic title') !== false);
    foreach ($report['checks'] as $check) {
        same(['rule_id','rule_version','status','evidence','location','recommendation'], array_keys($check));
    }
});
test('status arbitrário e evidência vazia rejeitados', function () {
    rejects(function () { new CheckResult('x', 'aprovado', ['x'=>true], '/', 'x'); });
    rejects(function () { new CheckResult('x', 'conforme', [], '/', 'x'); });
});
test('plugin carrega com dublê explícito da API legada', function () {
    require __DIR__ . '/support/ojs-double.php';
    $plugin = require __DIR__ . '/../plugins/generic/estrabao/index.php';
    same(true, $plugin instanceof GenericPlugin);
    same('estrabaoplugin', $plugin->getName());
    same('plugins.generic.estrabao.displayName', $plugin->getDisplayName());
    same('plugins.generic.estrabao.description', $plugin->getDescription());
});

function cli(array $args): array
{
    // Preserve php.ini and its shared extensions (notably JSON on PHP 7.4).
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../bin/triage.php');
    foreach ($args as $arg) { $command .= ' ' . escapeshellarg($arg); }
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('CLI não iniciada.'); }
    $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $err = stream_get_contents($pipes[2]); fclose($pipes[2]);
    return [proc_close($process), $out, $err];
}
test('CLI gera relatório JSON local', function () {
    list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json']);
    same(0, $code); same('', $err);
    $report = json_decode($out, true, 64, JSON_THROW_ON_ERROR);
    same(45, count($report['checks']));
    same(true, $report['human_review_required']);
});
test('CLI preserva inicialização configurada em arquivos ini', function () {
    $dir = sys_get_temp_dir() . '/estrabao-ini-' . bin2hex(random_bytes(8));
    if (!mkdir($dir, 0700)) { throw new RuntimeException('Diretório temporário não criado.'); }
    $original = getenv('PHP_INI_SCAN_DIR');
    $directories = [];
    // Keep the directories already supplying shared extensions to this PHP.
    foreach (explode(',', php_ini_scanned_files() ?: '') as $ini) {
        if (trim($ini) !== '') { $directories[] = dirname(trim($ini)); }
    }
    $scan = $original === false ? implode(PATH_SEPARATOR, array_unique($directories)) : $original;
    try {
        file_put_contents($dir . '/prepend.php', '<?php file_put_contents(' . var_export($dir . '/loaded', true) . ', "loaded");');
        file_put_contents($dir . '/99-test.ini', 'auto_prepend_file="' . $dir . '/prepend.php"' . PHP_EOL);
        putenv('PHP_INI_SCAN_DIR=' . ($scan === '' ? '' : $scan . PATH_SEPARATOR) . $dir);
        list($code, $out, $err) = cli([__DIR__ . '/fixtures/complete.json']);
        same(0, $code); same('', $err);
        same(true, is_file($dir . '/loaded'));
        same(45, count(json_decode($out, true, 64, JSON_THROW_ON_ERROR)['checks']));
    } finally {
        putenv($original === false ? 'PHP_INI_SCAN_DIR' : 'PHP_INI_SCAN_DIR=' . $original);
        foreach (glob($dir . '/*') as $path) { unlink($path); }
        rmdir($dir);
    }
});
test('CLI sem argumento falha sem saída de relatório', function () {
    list($code, $out, $err) = cli([]);
    same(1, $code); same('', $out); same(true, strlen($err) > 0);
});
test('CLI rejeita URLs e wrappers antes de abrir arquivos', function () {
    foreach (['https://invalid.example/snapshot.json', 'phar:///tmp/synthetic.phar/test.json', 'file:///tmp/synthetic.json'] as $path) {
        list($code, $out, $err) = cli([$path]);
        same(1, $code); same('', $out);
    }
});
foreach (['invalid-json' => '{"token":"synthetic-secret",', 'invalid-contract' => '{"schema_version":"2"}', 'oversize' => null] as $name => $body) {
    test('CLI rejeita ' . $name . ' sem expor conteúdo', function () use ($body) {
        $path = tempnam(sys_get_temp_dir(), 'estrabao-synthetic-');
        try {
            file_put_contents($path, $body ?? str_repeat('x', 1048577));
            list($code, $out, $err) = cli([$path]);
            same(1, $code); same('', $out);
            same(false, strpos($err, 'synthetic-secret') !== false);
        } finally { unlink($path); }
    });
}

require __DIR__ . '/stage2.php';
require __DIR__ . '/stage3.php';
require __DIR__ . '/stage4.php';
require __DIR__ . '/stage5.php';
require __DIR__ . '/stage6.php';
require __DIR__ . '/stage7.php';
require __DIR__ . '/stage8.php';
require __DIR__ . '/stage9.php';
require __DIR__ . '/stage10.php';
require __DIR__ . '/stage11.php';
require __DIR__ . '/stage12.php';
require __DIR__ . '/stage13.php';
require __DIR__ . '/stage14.php';
require __DIR__ . '/stage15.php';
require __DIR__ . '/stage16.php';
require __DIR__ . '/stage17.php';
require __DIR__ . '/stage18.php';
require __DIR__ . '/stage19.php';
require __DIR__ . '/stage20.php';
require __DIR__ . '/stage21.php';
require __DIR__ . '/stage22.php';

echo "$tests testes, $failed falhas\n";
exit($failed ? 1 : 0);
