<?php
declare(strict_types=1);
namespace Estrabao\Contracts;

interface Rule
{
    /** @return array<CheckResult> */
    public function evaluate(Submission $submission): array;
}
