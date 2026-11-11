<?php
declare(strict_types=1);
namespace Estrabao\Contracts;

final class CheckResult implements \JsonSerializable
{
    const PASS = 'conforme';
    const FAIL = 'não conforme';
    const UNKNOWN = 'indeterminado';
    private $data;

    public function __construct(string $ruleId, string $status, array $evidence, string $location, string $recommendation, string $ruleVersion = '1.0.0')
    {
        if (!in_array($status, [self::PASS, self::FAIL, self::UNKNOWN], true) || !$evidence
            || !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D', $ruleVersion)) {
            throw new \InvalidArgumentException('Resultado inválido.');
        }
        $this->data = [
            'rule_id' => $ruleId, 'rule_version' => $ruleVersion, 'status' => $status,
            'evidence' => $evidence, 'location' => $location,
            'recommendation' => $recommendation,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
