<?php
declare(strict_types=1);
namespace Estrabao\Rules;

final class DefaultRules
{
    public static function all(): array
    {
        return [new MetadataPresence(), new Orcid(), new AbstractLabels(),
            new AbstractLength(), new KeywordCount(), new PendingPolicy()];
    }
}
