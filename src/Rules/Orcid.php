<?php
declare(strict_types=1);
namespace Estrabao\Rules;
use Estrabao\Contracts\{Rule, Submission, CheckResult};

final class Orcid implements Rule
{
    public static function valid(string $value): bool
    {
        if (!preg_match('~^(?:https://orcid\.org/)?([0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{3}[0-9X])$~D', $value, $matches)) {
            return false;
        }
        $digits = str_replace('-', '', $matches[1]);
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $total = ($total + (int) $digits[$i]) * 2;
        }
        $check = (12 - ($total % 11)) % 11;
        return $digits[15] === ($check === 10 ? 'X' : (string) $check);
    }

    public function evaluate(Submission $submission): array
    {
        $authors = $submission->field('authors');
        if (!$authors) {
            return [new CheckResult('author.orcid.presence', CheckResult::UNKNOWN,
                ['authors_available' => $authors !== null, 'author_count' => $authors === null ? null : 0], '/authors',
                'Obter a lista completa de autores; não há autores verificáveis neste snapshot.')];
        }
        $results = [];
        foreach ($authors as $index => $author) {
            $value = trim($author['orcid'] ?? '');
            $present = $value !== '';
            $valid = $present && self::valid($value);
            $location = '/authors/' . $index . '/orcid';
            $results[] = new CheckResult('author.orcid.presence', $present ? CheckResult::PASS : CheckResult::FAIL,
                ['present' => $present], $location, $present ? 'Nenhuma correção de presença necessária.' : 'Informar ORCID deste autor.');
            $results[] = new CheckResult('author.orcid.syntax', !$present ? CheckResult::UNKNOWN : ($valid ? CheckResult::PASS : CheckResult::FAIL),
                ['format_and_checksum_valid' => $valid, 'algorithm' => 'ISO 7064 MOD 11-2'], $location,
                $valid ? 'Validade sintática não comprova identidade.' : 'Conferir formato e dígito verificador do ORCID.');
            $results[] = new CheckResult('author.orcid.identity', CheckResult::UNKNOWN,
                ['external_verification_performed' => false], $location,
                'Verificação de identidade depende de fluxo autenticado autorizado; não realizada nesta etapa.');
        }
        return $results;
    }
}
