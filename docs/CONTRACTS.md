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

Estrutura Word, APA 7 e correspondência de citações geram
resultados indeterminados com `implemented: false`; não são analisadores funcionais.
Não há recomendação automática de rejeição nesta etapa.

## Extensão na Etapa 2

O formato de snapshot e a versão de schema 1.0 foram preservados. O motor 0.2.0
acrescenta resultados por idioma para marcadores e extensão dos resumos e
quantidade de palavras-chave, descritos em STAGE2.md. `rule_version` e
`engine_version` aceitam versões numéricas em três componentes no schema.
O resultado `abstract.labels` passa de pendência 1.0.0 para regra 2.0.0.
Resultados anteriores continuam identificáveis pelas versões registradas.

## Etapa 3

Motor 0.3.0: a CLI recebe opcionalmente DOCX, tipo e idioma fora do snapshot.
`input_sha256` continua cobrindo apenas o snapshot; o hash independente do DOCX
faz parte da evidência de `manuscript.docx.structure` 2.0.0. A estrutura deixa
de ser pendência quando há extração suportada e contexto editorial conhecido.
Ver STAGE3.md para resultados adicionais de formatação e fomento e seus limites.

## Etapa 4

Motor 0.4.0: `manuscript.headings.format` 2.0.0 avalia negrito e numeração
efetivos no subconjunto de estilos de STAGE4.md. As evidências incluem posições,
valores resolvidos e lacunas, sem textos ou identificadores de estilos. Schemas
1.0 e argumentos da CLI preservados; demais limitações de DOCX continuam.

## Etapa 5

Motor 0.5.0: cinco regras 1.0.0 de página e tipografia documentadas em STAGE5.md.
Resultados incluem hash do DOCX, escopo, cobertura, contagens e amostras limitadas
por posição, sem texto bruto nem nomes de fontes/estilos. Sem manuscrito, as cinco
regras retornam indeterminado. Snapshot, schemas 1.0 e argumentos da CLI preservados.

## Etapa 6

Motor 0.6.0: `manuscript.word_limit` 1.0.0 registra faixa por tipo, intervalo
técnico, cobertura, categorias e regiões limitadas por posição, sem texto bruto.
Snapshot, schemas 1.0 e argumentos CLI preservados. `input_sha256` cobre o
snapshot; a regra inclui o hash separado do DOCX. Ver STAGE6.md para exclusões,
marcadores, normalização e limitações de idioma e contagem.

## Etapa 7

Motor 0.7.0: `manuscript.visuals.count` 1.0.0 registra inventário estrutural
independente da cobertura textual, com hash do DOCX e amostras limitadas. Não
converte desenhos/pictures automaticamente em figuras editoriais. O máximo 6
aplica-se apenas a comunicações. Schemas 1.0 e CLI preservados. Ver STAGE7.md.

## Etapa 8

Motor 0.8.0: entrada aceita 1.0 sem subtítulo e 1.1 com `subtitle` opcional.
O schema de entrada é revisado para 1.1, com validação das duas variantes; relatório
permanece schema 1.0. As regras `metadata.title.case`, `metadata.subtitle.case` e
`manuscript.headings.case` 1.0.0 sinalizam caixa alta sem reescrita. O hash do
snapshot cobre o subtítulo quando fornecido; o hash DOCX permanece separado nas
evidências. Exemplo: tests/fixtures/with-subtitles.json. Ver STAGE8.md.

## Etapa 9

Motor 0.9.0: `manuscript.footnotes.count` e `manuscript.footnotes.lines` 1.0.0,
com inventário de vínculos independente da extração textual. Contagem conclusiva
exige cobertura completa; linhas permanecem indeterminadas com notas presentes,
sem renderizador. Hash DOCX, contagens e amostras limitadas, sem texto ou IDs.
Entradas 1.0/1.1, relatório 1.0 e CLI preservados. Ver STAGE9.md.

## Etapa 10

Motor 0.10.0: `references.apa7` e `references.citation_matching` passam à versão
2.0.0, substituindo pendências por pré-checagem lexical. APA 7 completa permanece
indeterminada; correspondência só é conclusiva no subconjunto fechado de STAGE10.md.
Mesmos dois IDs e quantidade de resultados, sem novos campos de entrada ou mudança
de schema 1.0 do relatório. Evidências limitadas sem nomes, títulos ou identificadores
bibliográficos. Entradas 1.0/1.1 e CLI preservadas.

## Etapa 11

Motor 0.11.0: `references.apa7` e `references.citation_matching` versão 3.0.0.
APA agregada reprova incompatibilidades concretas e permanece indeterminada
sem elas; nunca certifica conformidade completa. Novas regras 1.0.0:
`references.apa7.recognized_formats` e `references.apa7.citation_format`.
São 45 resultados sem DOCX (47 para artigo, 46 para comunicação). Entradas
1.0/1.1, schema de relatório 1.0 e CLI preservados. Perfis parciais de fonte
são explícitos nas evidências; nenhum libera regras antigas sobre texto incompleto.
Amostras limitadas contêm posições e orientações fixas, sem conteúdo bibliográfico.
Ver STAGE11.md.

## Etapa 12

Motor 0.12.0; `references.apa7` 4.0.0 adiciona política explícita de não aceitação
por incompatibilidade comprovada e revisão obrigatória nos demais casos.
`admission_policy` nas evidências segue STAGE12.md. Sem aprovação automática,
sem execução no OJS. Schemas e quantidades de resultados preservados.

## Etapa 13

Plugin 0.2.0.0 consome a política da regra APA do motor 0.12.0, sem alterar
schemas, versões de regras ou quantidade de resultados. O hook local de
confirmação nunca libera automaticamente resultados indeterminados. Escopo de
ativação, API conferida e limitações operacionais em STAGE13.md.

## Etapa 14

Plugin 0.3.0.0 adiciona revisão humana privada, sem mudar snapshots, schemas,
quantidade de resultados ou a política de não aceitação APA. Motor 0.12.0 usa
constante compartilhada para sua versão. A liberação se vincula a fingerprint
de arquivo/idioma/IDs/versões; nunca altera a regra para “APA conforme”.
Formato privado de histórico, autorização e revogação em STAGE14.md.

## Etapa 15

Plugin 0.3.1.0: callback de bloqueio acrescenta erros e devolve false ao hook
para preservar a notificação e o resultado isValid nativos. A análise interna
passa orientações comprovadas e limitadas ao formulário. Nenhuma alteração
nos schemas, motor 0.12.0 ou quantidade de checks. Ensaios locais em STAGE15.md.
