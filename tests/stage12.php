<?php
declare(strict_types=1);

test('ABNT comprovada implica submissão não aceita, sem avanço automático', function () {
    $results = apaResults(referenceDocument(['Texto sem citação.'], [
        'SILVA, João. Livro. São Paulo: Editora, 2020.',
    ]));
    $apa = $results['references.apa7']; $policy = $apa['evidence']['admission_policy'];
    same('5.0.0', $apa['rule_version']); same('não conforme', $apa['status']);
    same('not_accepted', $policy['decision']); same(false, $policy['automatic_progression_allowed']);
    same('proven_apa_incompatibility', $policy['reason']);
    same(true, in_array('recognized_reference_formats', $policy['blocking_components'], true));
    same(false, $policy['ojs_enforcement_implemented']); same(false, $policy['editorial_state_changed']);
});
test('falta de documento exige revisão sem acusar ABNT e sem liberar avanço', function () {
    $checks = array_column(referenceChecks(null), null, 'rule_id');
    $policy = $checks['references.apa7']['evidence']['admission_policy'];
    same('indeterminado', $checks['references.apa7']['status']);
    same('manual_review_required', $policy['decision']); same(false, $policy['automatic_progression_allowed']);
    same([], $policy['blocking_components']);
});
test('formato APA reconhecido não certifica aceitação da submissão', function () {
    $doc = italicDocument(['Silva, A. (2020). Estudo da paisagem. Editora Exemplo.'], [['Estudo da paisagem']]);
    $checks = apaResults($doc); same('conforme', $checks['references.apa7.recognized_formats']['status']);
    $policy = $checks['references.apa7']['evidence']['admission_policy'];
    same('manual_review_required', $policy['decision']); same(false, $policy['automatic_progression_allowed']);
});
test('bloqueio APA cobre incompatibilidade comprovada além de ABNT', function () {
    $doc = italicDocument(['Silva, A. (2020). Estudo da paisagem. Editora Exemplo. doi:10.1234/synthetic'], [['Estudo da paisagem']]);
    $apa = apaResults($doc)['references.apa7'];
    same('não conforme', $apa['status']); same('not_accepted', $apa['evidence']['admission_policy']['decision']);
});
