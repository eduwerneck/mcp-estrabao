# Etapa 9 — notas de rodapé

Motor 0.9.0 após o merge do PR #8. A diretriz recebida já estabelece **até 3 notas
de rodapé, com no máximo 2 linhas por nota**. Implementa contagem estrutural de
notas Word; registra a verificação de linhas como indeterminada sem renderização.
Não altera as notas, os textos, o limite publicado ou o fluxo editorial.

## Inventário de vínculos

`FootnoteInventory` opera somente depois da validação de tamanho, ZIP/CRC, tipo
DOCX e XML sem DTD/entidades. Localiza a parte de notas por relacionamento interno,
com o mesmo resolvedor seguro de partes usado pelas etapas anteriores. Não segue
URLs, não converte DOC, não executa conteúdo e não extrai membros em disco.

Exige raiz `w:footnotes` e um Override único de content type de notas para a parte
selecionada. Um caminho local alternativo é aceito pelo relacionamento; ausência,
duplicata, parte órfã, tipo incorreto ou destino externo fica indeterminado, nunca
é interpretado como zero notas. Variantes de pacote sem Override explícito não
são resolvidas por suposição nesta versão.

Conta definições normais de `w:footnote` vinculadas a chamadas `w:footnoteReference`
no corpo principal. Confere os IDs numéricos normalizados, sem presumir numeração
consecutiva nem IDs fixos para separadores. Tipos `separator`,
`continuationSeparator` e `continuationNotice` são internos do Word e não contam
como notas normais; um vínculo que aponte para esses tipos é inconclusivo.

Para uma contagem conclusiva, cada nota normal deve ter uma chamada resolvida,
sem referências repetidas, definições duplicadas ou notas normais sem chamada.
Definições possivelmente residuais não provocam reprovação por excesso: tornam o
inventário indeterminado até revisão. Texto de chamada, número visível ou múltiplas
menções ao mesmo ID não são usados para inventar notas adicionais.

Chamadas precisam estar em runs textuais, dentro de parágrafos do corpo, podendo
estar em tabelas. Chamadas fora dessa posição, em outras partes, conteúdo oculto,
revisões, campos, controles, importações e containers não suportados impedem uma
conclusão. Objetos gráficos não são decodificados para procurar notas escritas à
mão; notas manuais como simples texto não são identificadas como notas Word.

Notas de fim (`endnotes`) são outra estrutura e não são convertidas em notas de
rodapé. Um resultado de zero notas Word não autoriza nem valida notas de fim ou
anotações manuais; esse material fica fora do escopo desta regra.

O inventário admite até 2.048 definições, 4.096 chamadas e 4.096 relacionamentos
na parte do documento. IDs de relacionamento precisam ser únicos e não vazios. Acima disso, informa
limite técnico e retorna indeterminado, sem usar leitura parcial para provar
excesso. Os limites ZIP/XML da Etapa 3 continuam anteriores a essa inspeção.

## Resultados — versão 1.0.0

`manuscript.footnotes.count`:

- Com inventário completo, 0 a 3 notas normais vinculadas: conforme.
- Com inventário completo, 4 ou mais: não conforme.
- Fonte ausente, pacote recusado ou vínculos/cobertura inconclusivos: indeterminado.

`manuscript.footnotes.lines`:

- Zero notas confirmado: conforme, pois não há notas para renderizar.
- Qualquer nota presente ou fonte inconclusiva: indeterminado. A regra registra
  `renderer_available=false` e não afirma implementar contagem visual.

Não conta `w:br`, parágrafos XML ou caracteres como linhas de página. A quantidade
visual depende de fonte efetiva, largura, margens, paginação e do editor. Mesmo
quebras explícitas e notas longas não geram conclusão automática de duas linhas
nesta implementação. A conferência deve ser feita no documento renderizado.

A contagem é aplicável independentemente de artigo/comunicação e idioma. Não
exige notas quando não há nenhuma. Contar notas não valida conteúdo, necessidade
editorial ou o limite de linhas.

## Cobertura independente e auditoria

O leitor pode retornar `available=false` para texto e `footnotes.available=true`
para o inventário. Chamadas de rodapé continuam fora da cobertura textual anterior:
esta etapa não libera leitura parcial para aprovar extensão, formatação ou caixa
dos títulos. A regra de figuras/tabelas também mantém suas limitações. A inspeção
de vínculos de notas é independente da extração do conteúdo textual das notas.

A evidência contém hash do DOCX, máximo 3, cobertura, contagens observadas, motivos
fixos e até 32 amostras com índices de chamadas/definições. Os índices são base
zero nas coleções XML, incluindo separadores na coleção de definições; não são o
número visível da nota nem índice dos parágrafos do extrator de texto. Não copia
conteúdo de notas, IDs, nomes de partes, caminhos ou dados de autores. O total de
amostras omitidas é explícito.

O inventário não entra pelo JSON: é produzido pelo leitor a partir do DOCX. Os
snapshots 1.0/1.1, relatório schema 1.0 e argumentos da CLI permanecem iguais.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/with-subtitles.json manuscrito-sintetico.docx article pt
```

Sem DOCX, há 43 resultados: fixture 1.0 com 25 conformes e 18 indeterminados;
fixture 1.1 com subtítulos com 28 conformes e 15 indeterminados. Com artigo DOCX,
há 45; com comunicação, 44. Sem fonte, as novas regras ficam indeterminadas,
em vez de aprovar falsamente zero notas.

Sem novas dependências ou renderizador instalado, produção, VM Oracle, OJS,
manuscrito real ou alteração/publicação do ambiente. O indicador Submetido em no
box Publicado permanece adiado conforme o planejamento.
