<?php
declare(strict_types=1);

function stage2Checks(string $rule, array $data): array
{
    return array_map(function ($r) { return $r->jsonSerialize(); }, (new $rule())->evaluate(new \Estrabao\Contracts\Submission($data)));
}

foreach (\Estrabao\Rules\AbstractLabels::LABELS as $locale => $groups) {
    foreach ($groups[0] as $first) {
        foreach ($groups[1] as $second) {
            foreach ($groups[2] as $third) {
                test("marcadores aprovados $locale: $first/$second/$third", function () use ($locale, $first, $second, $third) {
                    $data = fixture(); $data['abstract'][$locale] = "$first: Texto. $second: Texto. $third: Texto.";
                    $checks = stage2Checks(\Estrabao\Rules\AbstractLabels::class, $data);
                    $index = array_search($locale, array_keys(\Estrabao\Rules\AbstractLabels::LABELS), true);
                    same('conforme', $checks[$index]['status']);
                    same('2.0.0', $checks[$index]['rule_version']);
                    same(3, count($checks[$index]['evidence']['groups']));
                });
            }
        }
    }
}
foreach ([
    'Contexto: Texto. Metodologia: Texto.' => 'não conforme',
    'Contexto: Texto. Metodologias: Texto. Resultados: Texto.' => 'não conforme',
    'Contexto: Texto. Método: Texto. Conclusions: Texto.' => 'não conforme',
    'O contexto foi estudado e o método discutido com resultados relevantes.' => 'indeterminado',
    'CONTEXTO: Texto. MÉTODO: Texto. CONCLUSÕES: Texto.' => 'conforme',
    "Contexto\nTexto.\nMétodo\nTexto.\nResultados\nTexto." => 'conforme',
    'Contexto. Texto. Método — Texto. Conclusões - Texto.' => 'conforme',
    '<p><strong>Contexto:</strong> Texto.</p><p><strong>Método:</strong> Texto.</p><p>Resultados: Texto.</p>' => 'conforme',
    '<script>Contexto: Método: Resultados:</script>' => 'indeterminado',
] as $text => $status) {
    test('reconhecimento de rótulos ' . $text, function () use ($text, $status) {
        $data = fixture(); $data['abstract']['pt_BR'] = $text;
        same($status, stage2Checks(\Estrabao\Rules\AbstractLabels::class, $data)[0]['status']);
    });
}
test('rótulos ausentes versus fonte desconhecida', function () {
    $rule = \Estrabao\Rules\AbstractLabels::class;
    $data = fixture(); unset($data['abstract']['pt_BR']);
    same('não conforme', stage2Checks($rule, $data)[0]['status']);
    $data['abstract'] = null;
    same('indeterminado', stage2Checks($rule, $data)[0]['status']);
});
test('Unicode decomposto e UTF-8 inválido não viram ausência comprovada', function () {
    foreach (["Introduc\u{0327}a\u{0303}o: Texto. Método: Texto. Resultados: Texto.", "\xFF"] as $text) {
        $data = fixture(); $data['abstract']['pt_BR'] = $text;
        same('indeterminado', stage2Checks(\Estrabao\Rules\AbstractLabels::class, $data)[0]['status']);
        same('indeterminado', stage2Checks(\Estrabao\Rules\AbstractLength::class, $data)[0]['status']);
    }
});
foreach ([249 => 'conforme', 250 => 'conforme', 251 => 'não conforme'] as $words => $status) {
    test("limite de resumo $words palavras", function () use ($words, $status) {
        $data = fixture(); $data['abstract']['pt_BR'] = trim(str_repeat('palavra ', $words));
        $check = stage2Checks(\Estrabao\Rules\AbstractLength::class, $data)[0];
        same($status, $check['status']);
        same($words, $check['evidence']['minimum_count']);
        same($words, $check['evidence']['maximum_count']);
    });
}
test('rótulos perto do limite pedem revisão da contagem', function () {
    $data = fixture(); $data['abstract']['pt_BR'] = 'Contexto: ' . str_repeat('palavra ', 248) . 'Método: Resultados:';
    $check = stage2Checks(\Estrabao\Rules\AbstractLength::class, $data)[0];
    same('indeterminado', $check['status']);
    same(248, $check['evidence']['minimum_count']);
    same(251, $check['evidence']['maximum_count']);
});
test('hífens perto do limite pedem revisão', function () {
    $data = fixture(); $data['abstract']['pt_BR'] = str_repeat('palavra ', 249) . 'sócio-espacial';
    $check = stage2Checks(\Estrabao\Rules\AbstractLength::class, $data)[0];
    same('indeterminado', $check['status']);
    same(250, $check['evidence']['minimum_count']);
    same(251, $check['evidence']['maximum_count']);
});
test('HTML não junta palavras; espaços Unicode são separadores', function () {
    same([3, 3], \Estrabao\Rules\AbstractLength::counts(\Estrabao\Text\PlainText::normalize('<p>um</p><p>dois&nbsp;três</p>'), 'pt_BR'));
});
foreach ([null, '', '<p>&nbsp;</p>', '<style>x</style>'] as $value) {
    test('extensão de resumo indisponível/vazio não é aprovação', function () use ($value) {
        $data = fixture();
        if ($value === null) { $data['abstract'] = null; } else { $data['abstract']['pt_BR'] = $value; }
        same('indeterminado', stage2Checks(\Estrabao\Rules\AbstractLength::class, $data)[0]['status']);
    });
}
foreach ([0 => 'não conforme', 2 => 'não conforme', 3 => 'conforme', 4 => 'conforme', 5 => 'não conforme'] as $count => $status) {
    test("quantidade de palavras-chave: $count", function () use ($count, $status) {
        $data = fixture(); $data['keywords']['pt_BR'] = array_fill(0, $count, 'tema sintético');
        $check = stage2Checks(\Estrabao\Rules\KeywordCount::class, $data)[0];
        same($status, $check['status']); same($count, $check['evidence']['count']);
    });
}
test('palavras-chave compostas contam por item; vazias são excluídas', function () {
    $data = fixture(); $data['keywords']['pt_BR'] = ['geografia humana', 'espaço urbano', 'pesquisa local', '', '<p>&nbsp;</p>'];
    $check = stage2Checks(\Estrabao\Rules\KeywordCount::class, $data)[0];
    same('conforme', $check['status']); same(3, $check['evidence']['count']); same(2, $check['evidence']['empty_items']);
});
test('palavras-chave indisponíveis e dados não extraíveis são indeterminados', function () {
    $data = fixture(); $data['keywords'] = null;
    same('indeterminado', stage2Checks(\Estrabao\Rules\KeywordCount::class, $data)[0]['status']);
    $data = fixture(); $data['keywords']['pt_BR'] = ['um', 'dois', '<script>x</script>'];
    $check = stage2Checks(\Estrabao\Rules\KeywordCount::class, $data)[0];
    same('indeterminado', $check['status']); same(1, $check['evidence']['unreadable_items']); same(0, $check['evidence']['empty_items']);
});
test('regras novas cobrem os três idiomas sem copiar textos para evidências', function () {
    $report = \Estrabao\Reporting\Report::generate(new \Estrabao\Contracts\Submission(fixture()), \Estrabao\Rules\DefaultRules::all());
    same(33, count($report['checks'])); same('0.6.0', $report['engine_version']);
    $ids = array_count_values(array_column($report['checks'], 'rule_id'));
    same(3, $ids['abstract.labels']); same(3, $ids['abstract.word_limit']); same(3, $ids['metadata.keywords.count']);
    same(11, count(array_filter($report['checks'], function ($r) { return $r['status'] === 'indeterminado'; })));
    same(false, strpos(json_encode($report), 'Estudo sint') !== false);
});
