# Contratos de triagem v1

O snapshot é independente do modelo de dados do OJS. Não é uma resposta da API
OJS nem um adaptador implementado. A entrada é definida por
[`submission.schema.json`](../contracts/submission.schema.json); a saída por
[`report.schema.json`](../contracts/report.schema.json). A CLI valida os tipos,
campos e formas objeto/lista com `Submission::fromJson`, sem buscar schemas na rede.

## Semântica da entrada

`schema_version` deve ser `1.0`. `title` e `abstract` são objetos de locale para
texto; `keywords` é objeto de locale para lista de textos; `authors` é lista
completa de objetos contendo apenas `orcid` (texto ou null). Campos desconhecidos
são rejeitados para evitar inclusão acidental de dados confidenciais. A classe
PHP `Submission` aceita a representação em arrays; produtores JSON devem usar
`fromJson` para preservar a distinção entre objeto e lista.

Um campo omitido ou null significa extração desconhecida/indisponível, portanto
indeterminado. Um mapa disponível, inclusive `{}`, declara que a extração daquele
campo foi completa: locale ausente ou conteúdo vazio é não conforme. Não envie
mapas parciais como completos; o futuro adaptador deverá respeitar essa garantia.
Uma lista de autores ausente, null ou vazia não permite validar ORCID de todos os
autores e produz indeterminado. A ausência de autoria em si não é regra desta etapa.

O perfil atual é `pt_BR`, `en_US`, `es_ES`, explicitado no relatório. São códigos
iniciais de normalização, não confirmação das configurações da revista. Conteúdo
em outro código precisa ser normalizado pelo futuro adaptador após confirmação.
A presença não verifica idioma, qualidade, completude do resumo ou quantidade de
palavras-chave. Tags sem texto, espaços Unicode e `&nbsp;` não contam como conteúdo.

ORCID aceita identificador hifenizado ou URL HTTPS exata `https://orcid.org/…`,
com `X` maiúsculo quando aplicável. Espaços externos são removidos na regra.
Formato e checksum ISO 7064 MOD 11-2 são verificados. Isso não prova registro,
existência, vínculo com autor nem autenticação; identidade fica indeterminada.

## Evidências e auditoria

Cada resultado contém `rule_id`, `rule_version`, `status`, `evidence`, `location`
e `recommendation`. Os únicos estados são `conforme`, `não conforme` e
`indeterminado`. `location` usa JSON Pointer para o snapshot; string vazia indica
que não há localização extraída. Não corresponde a página/parágrafo de manuscrito.
Os IDs e versões permitem identificar o comportamento aplicado em cada relatório.

O relatório registra versão do motor, UTC de geração, perfil de locales, escopo
local e SHA-256 do snapshot normalizado via `json_encode` do PHP. O hash depende
da ordem dos campos; não é canonicalização interoperável de JSON nem assinatura.
A evidência registra presença, disponibilidade e checksum, sem copiar textos ou
identificadores dos autores. Verificação independente exige reter o snapshot
original em armazenamento protegido sob política editorial apropriada.

A saída JSON é o registro auditável das verificações realizadas. Não há decisões
editoriais tomadas pelo programa. A CLI não mantém arquivo de auditoria persistente:
o operador pode guardar stdout em diretório privado autorizado, fora do Git.
Controle de acesso, retenção, integridade contra adulteração, cadeia de custódia
e registro de decisões humanas dependem da integração futura. Um hash isolado
não garante autenticidade. Mesmo o relatório reduzido deve ser tratado como dado
editorial, sem publicação ou envio externo automático.

Rótulos de resumo, estrutura DOCX, APA 7 e correspondência de citações geram
resultados indeterminados com `implemented: false`; não são analisadores funcionais.
Não há recomendação automática de rejeição nesta etapa.
