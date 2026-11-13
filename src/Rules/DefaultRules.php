<?php
declare(strict_types=1);
namespace Estrabao\Rules;

final class DefaultRules
{
    public static function all(?array $document = null, ?string $type = null, ?string $language = null): array
    {
        return [new MetadataPresence(), new Orcid(), new AbstractLabels(),
            new AbstractLength(), new KeywordCount(), new ManuscriptStructure($document, $type, $language), new ManuscriptFormatting($document), new ManuscriptLength($document, $type, $language), new VisualCount($document, $type), new MetadataTitleCase(), new ManuscriptTitleCase($document, $language), new Footnotes($document), new References($document, $language), new CreditContent($document, $language), new FundingConsistency($document, $language)];
    }
}
