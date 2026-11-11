# Etapa 2 — regras locais dos resumos

Implementado no motor 0.2.0, sem conexão com OJS ou serviços externos.
Esta etapa inclui o registro da política em EDITORIAL_POLICY.md e as três regras
abaixo. A aprovação da tabela de marcadores veio do responsável pelo projeto.
O registro não afirma que a página pública foi consultada ou atualizada.

## Marcadores — `abstract.labels` versão 2.0.0

Um resultado por idioma, com os três grupos de alternativas aprovadas.
É suficiente uma alternativa de cada grupo. Não exige ordem entre os grupos,
ambas as alternativas, conteúdo científico válido ou títulos do corpo do texto.
A versão 2.0.0 distingue a implementação da versão 1.0.0, que apenas registrava
uma pendência indeterminada.

O reconhecimento técnico é insensível a maiúsculas/minúsculas, exige limite de
palavra e aceita dois-pontos, ponto seguido de espaço/fim, travessão, hífen
seguido de espaço ou marcador isolado em linha. Não impõe uma dessas pontuações
como regra editorial. Uma menção ao termo sem delimitação reconhecível resulta
em indeterminado quando não houver outro grupo comprovadamente ausente.
A falta literal de todas as alternativas de um grupo no texto extraído é não
conforme. Não aceita plural ou sinônimo não aprovado, nem rótulo de outro idioma.
A detecção é lexical; não valida se o conteúdo da seção corresponde ao rótulo.

A evidência contém alternativas, estado e posição em bytes no texto normalizado.
Essa posição não é uma coordenada do DOCX nem da string HTML original. Não copia
frases do resumo ou identificadores de autores para o relatório.

## Extensão — `abstract.word_limit` versão 1.0.0

Máximo de 250 palavras por versão linguística do resumo. Não há mínimo publicado.
O texto principal e as exclusões de sua contagem não são analisados nesta etapa.

A convenção técnica `conservative_interval_v1` calcula:

- Limite inferior: termos separados por espaços Unicode contendo letra ou número,
  após remover nomes de marcadores aprovados. A remoção abrange também menções
  em prosa, para não produzir falso excesso por contagem de rótulos.
- Limite superior: sequências de letras/números, separando pontuação, hífens e
  apóstrofos e incluindo os rótulos.

Se o limite superior for até 250, retorna conforme. Se o inferior exceder 250,
retorna não conforme. Se o intervalo cruzar 250, retorna indeterminado. Texto
vazio/indisponível não aprova a regra de extensão; sua ausência é avaliada pela
regra de presença já existente. As contagens e o método ficam na evidência.
Essas duas convenções não constituem uma definição oficial exaustiva de palavra;
casos editoriais ambíguos continuam sujeitos à revisão humana. O limite publicado
é aplicado ao resumo como um todo, sem presumir uma exclusão oficial dos rótulos.

## Quantidade — `metadata.keywords.count` versão 1.0.0

De 3 a 4 itens não vazios por idioma. Cada item do array normalizado é uma
palavra-chave, mesmo quando composto por várias palavras. O futuro adaptador não
deve fornecer uma string com várias palavras-chave empacotadas como um item.
Itens vazios não contam. Duplicatas exatas são registradas, sem reprovação por
unicidade, pois a política não definiu essa exigência. Não limita nem avalia
quantas palavras há dentro de uma expressão-chave.

## Normalização e limites

Normalização sem DOM, XML, rede ou execução: remove tags, converte entidades e
preserva separações de blocos HTML. Conteúdo com script/style/iframe/object,
UTF-8 inválido ou marcas Unicode combinantes sem normalização canônica suportada
não gera prova de ausência; as novas regras retornam indeterminado. Unicode
precomposto é aceito. Essa limitação conservadora evita rejeitar rótulos cuja
representação Unicode ainda não é tratada; não impõe uma nova regra editorial.

Um campo null/omitido continua significando fonte indisponível. Mapa presente e
locale ausente significa ausência no snapshot completo, conforme CONTRACTS.md.
Nenhuma dessas regras modifica estado editorial ou comunica resultados a autores.

## Executar e validar

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json
```

A fixture completa agora possui resumos rotulados e três palavras-chave por idioma.
Produz 27 resultados: 22 conformes e 5 indeterminados (duas verificações de
identidade ORCID e três análises ainda não implementadas: Word, APA e citações).
O conjunto oficial da CLI está centralizado em `DefaultRules::all()`.

Continuam pendentes extração Word (.doc e .docx), extensão do corpo por tipo,
formatação, figuras/tabelas/notas, CRediT, ética, IA, adaptação OJS, persistência
protegida e MCP. Não houve acesso à produção ou à VM. Não instalar na revista.
