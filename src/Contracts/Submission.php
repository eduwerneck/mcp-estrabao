<?php
declare(strict_types=1);
namespace Estrabao\Contracts;

/** Snapshot local normalizado, não um objeto/DAO do OJS. */
final class Submission
{
    private $data;

    public static function fromJson(string $json): self
    {
        $object = json_decode($json, false, 64, JSON_THROW_ON_ERROR);
        if (!$object instanceof \stdClass) {
            throw new \InvalidArgumentException('Snapshot deve ser objeto JSON.');
        }
        foreach (['title', 'subtitle', 'abstract', 'keywords', 'supporting_agencies'] as $field) {
            if (isset($object->$field) && !$object->$field instanceof \stdClass) {
                throw new \InvalidArgumentException('Metadados devem ser objetos JSON.');
            }
        }
        foreach (['keywords', 'supporting_agencies'] as $listField) {
            if (isset($object->$listField)) {
                foreach ($object->$listField as $values) {
                    if (!is_array($values)) {
                        throw new \InvalidArgumentException('Valores devem ser listas JSON.');
                    }
                }
            }
        }
        if (isset($object->authors)) {
            if (!is_array($object->authors)) {
                throw new \InvalidArgumentException('Autores devem ser lista JSON.');
            }
            foreach ($object->authors as $author) {
                if (!$author instanceof \stdClass) {
                    throw new \InvalidArgumentException('Autor deve ser objeto JSON.');
                }
            }
        }
        return new self(json_decode($json, true, 64, JSON_THROW_ON_ERROR));
    }

    public function __construct(array $data)
    {
        if (!in_array($data['schema_version'] ?? null, ['1.0', '1.1', '1.2'], true)) {
            throw new \InvalidArgumentException('schema_version deve ser 1.0, 1.1 ou 1.2.');
        }
        if ($data['schema_version'] === '1.0' && array_key_exists('subtitle', $data)) {
            throw new \InvalidArgumentException('subtitle requer snapshot 1.1.');
        }
        if ($data['schema_version'] !== '1.2' && array_key_exists('supporting_agencies', $data)) { throw new \InvalidArgumentException('supporting_agencies requer snapshot 1.2.'); }
        foreach (['title', 'subtitle', 'abstract', 'keywords', 'supporting_agencies'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                continue; // Desconhecido: extração não disponível.
            }
            if (!is_array($data[$field])) {
                throw new \InvalidArgumentException('Mapa de metadados inválido: ' . $field);
            }
            foreach ($data[$field] as $locale => $value) {
                if (!is_string($locale) || !preg_match('/^[a-z]{2}_[A-Z]{2}$/D', $locale)) {
                    throw new \InvalidArgumentException('Locale inválido.');
                }
                if (in_array($field, ['keywords','supporting_agencies'], true)) {
                    if (!is_array($value) || array_values($value) !== $value) {
                        throw new \InvalidArgumentException('Palavras-chave devem ser listas.');
                    }
                    foreach ($value as $keyword) {
                        if (!is_string($keyword)) {
                            throw new \InvalidArgumentException('Palavra-chave inválida.');
                        }
                    }
                } elseif (!is_string($value)) {
                    throw new \InvalidArgumentException('Texto de metadado inválido.');
                }
            }
        }
        if (isset($data['authors'])) {
            if (!is_array($data['authors']) || array_values($data['authors']) !== $data['authors']) {
                throw new \InvalidArgumentException('Autores devem ser uma lista.');
            }
            foreach ($data['authors'] as $author) {
                if (!is_array($author) || !array_key_exists('orcid', $author)
                    || ($author['orcid'] !== null && !is_string($author['orcid']))) {
                    throw new \InvalidArgumentException('Cada autor deve ter orcid textual ou null.');
                }
            }
        }
        // Aceitar apenas o contrato; nomes, manuscritos e campos adicionais são rejeitados.
        if (array_diff(array_keys($data), ['schema_version', 'title', 'subtitle', 'abstract', 'keywords', 'authors', 'supporting_agencies'])) {
            throw new \InvalidArgumentException('Campo desconhecido no snapshot.');
        }
        foreach ($data['authors'] ?? [] as $author) {
            if (array_keys($author) !== ['orcid']) {
                throw new \InvalidArgumentException('Campo desconhecido no autor.');
            }
        }
        $this->data = $data;
    }

    public function field(string $name): ?array
    {
        return $this->data[$name] ?? null;
    }

    public function digest(): string
    {
        return hash('sha256', json_encode($this->data, JSON_THROW_ON_ERROR));
    }
}
