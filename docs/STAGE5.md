# Etapa 5 — página e tipografia declaradas

Motor 0.5.0, após o merge do PR #4. Mantém os argumentos da CLI, schema 1.0,
limites de ZIP/XML e cobertura restrita de STAGE3.md/STAGE4.md. Não instala fontes,
conversores ou serviços e não renderiza o Word. As verificações descrevem as
propriedades declaradas no arquivo; substituição de fontes, glyphs, paginação e
exceções editoriais exigem revisão humana.

## Novos resultados — versão 1.0.0

| Regra | Escopo e critério |
| --- | --- |
| `manuscript.page.a4` | Todas as seções: dimensões A4, 11906 × 16838 twips, em qualquer orientação. |
| `manuscript.page.margins` | Todas as seções: top/right/bottom/left de 1701 twips, correspondentes a 3 cm. |
| `manuscript.body.font` | Runs textuais do corpo principal: família declarada Times New Roman, sem distinguir caixa. |
| `manuscript.body.font_size` | Runs textuais do corpo principal: `sz=24`, equivalente a 12 pontos. |
| `manuscript.body.line_spacing` | Parágrafos textuais do corpo principal: `spacing line=360`, em modo `auto`, equivalente a 1,5 linhas. |

Os tamanhos de página e margem admitem ±2 twips de arredondamento (cerca de
0,04 mm), uma convenção técnica documentada de conversão para unidades inteiras,
não uma tolerância editorial geral. Fonte/tamanho/entrelinhas não têm tolerância.
Não há exigência de orientação retrato, template ou nomes específicos de estilos.

## Página e seções

Lê seções intermediárias em `pPr/sectPr` e a seção final em `body/sectPr`, na
ordem do documento. Uma propriedade `pgSz` ou `pgMar` omitida pode herdar o elemento
da seção anterior. A primeira seção sem propriedades permanece desconhecida;
não se presume o padrão do Word ou da impressora. Atributos ausentes dentro de
um elemento declarado não são preenchidos com suposições. Medidas universais
como `210mm`, números negativos ou inválidos ficam indeterminados nesta versão.

Exige seção final única no fim do corpo para afirmar cobertura completa. Seções
revisadas, propriedades duplicadas ou estrutura ambígua não geram prova de
conformidade. Uma divergência em seção válida continua sendo evidência mesmo
quando outra seção não pôde ser avaliada.

Margens com gutter positivo/desconhecido, margens espelhadas, gutter no topo,
modo livro e configurações de settings indisponíveis por vínculo inseguro ficam
indeterminadas. `settings` é localizado por relacionamento interno; nenhum
relacionamento externo é seguido. Dimensões de A4 continuam independentes dessas
limitações de margem. Cabeçalho/rodapé e suas distâncias não são confundidos com
as quatro margens do texto.

## Tipografia e entrelinhas

Reutiliza cadeias `basedOn`, defaults e estilos de parágrafo e caractere da Etapa
4. Aplica `docDefaults`, estilo de parágrafo, estilo de caractere e formatação
direta. Fonte e tamanho são propriedades absolutas, sem a alternância de `b`.
`rFonts` combina slots por atributo; tema prevalece sobre literal na mesma camada,
e uma fonte literal em camada superior substitui o tema herdado daquele slot.

Seleciona `ascii` para caracteres ASCII visíveis e `hAnsi` para os demais
caracteres suportados. Resolve `majorAscii`, `majorHAnsi`, `minorAscii` e
`minorHAnsi` no `a:latin` do tema local indicado pelo relacionamento do documento.
Tema ausente, externo, ambíguo, vazio ou não suportado não é resolvido por rede
ou pelas fontes instaladas. Não usa um nome literal como fallback para tema
explicitamente selecionado e não resolvido.

Scripts complexos, texto não latino e hints especiais de script permanecem
indeterminados para fonte/tamanho. Nomes alternativos de fonte e aliases não
são inventados. Ausência de fonte ou tamanho na hierarquia completa não é
preenchida com Times/Calibri nem com 12 pontos. Runs sem conteúdo além de espaços
Unicode são ignorados. Havendo slots parcialmente desconhecidos, uma divergência
comprovada em outro slot continua registrada.

`spacing` herda atributos separadamente. `lineRule` omitido equivale a `auto`
somente quando não existe regra herdada. `exact` e `atLeast` são medidas em pontos,
não múltiplos de linha: ficam indeterminados, mesmo com valor numérico 360. Grades
de documento não padrão impedem concluir entrelinhas efetiva. Espaços antes/depois
do parágrafo não são tratados como entrelinhas. Não se afirma conformidade para
parágrafos sem medida de entrelinhas conhecida.

O escopo tipográfico inclui títulos e demais parágrafos extraídos do corpo,
sem excluir resumos ou referências por inferência. Não examina fonte/tamanho de
cabeçalhos, rodapés ou notas. Tabelas, campos, revisões e demais lacunas de extração
da Etapa 3 continuam impedindo conclusões nesta versão. As regras do corpo não
representam uma aprovação de toda a tipografia do arquivo.

## Evidência, execução e limites

Cada regra registra hash independente do DOCX, escopo, cobertura, totais por
estado e até 32 amostras com posição de seção/parágrafo/run. Amostras priorizam
divergências e indeterminações; o número de itens omitidos é explícito. A evidência
não copia texto, nomes arbitrários de fonte/estilo ou caminhos. Medidas numéricas
são registradas nas unidades indicadas. Divergências comprovadas prevalecem;
sem divergência, lacunas ou ausência de conteúdo produzem indeterminado.

`php tests/run.php` exercita fixtures DOCX construídas em tempo de execução.
`php bin/triage.php tests/fixtures/complete.json` agora produz 32 resultados
(22 conformes e 10 indeterminados, pois não há manuscrito). Com manuscrito de
artigo, há 34 resultados; quantidade não implica conformidade editorial.

Sem acesso à produção, VM Oracle ou OJS. Sem novas dependências. `.doc`, figuras,
notas, APA, ética/IA, integração OJS e MCP continuam pendentes. O ambiente publicado
não foi reconfigurado nem publicado novamente por esta implementação.
