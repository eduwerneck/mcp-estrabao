# Etapa 7 — inventário visual e limite das comunicações

Motor 0.7.0 após o merge do PR #6. Nova regra `manuscript.visuals.count` 1.0.0.
A diretriz limita Comunicações de Pesquisa a **6 figuras e/ou tabelas no total**.
Não há limite numérico publicado para artigos. Tipo ausente ou artigo retorna
indeterminado, com limite null, sem inventar um máximo ou inferir o tipo.
A contagem estrutural não depende do idioma dos títulos das seções.

## Inventário independente, com validação preservada

`VisualInventory` só recebe as partes de um pacote já aprovado nos limites de
arquivo/ZIP, CRC, XML sem DTD/entidades e tipo DOCX da Etapa 3. Não descompacta
membros, decodifica imagens, executa objetos ou segue relacionamentos. Mídias
externas nunca são buscadas. Falha dessas validações não deixa inventário
aproveitável; `.doc` continua sem parser.

O inventário ocorre antes da verificação de cobertura textual. É possível ter
`available=false` para texto e `visuals.available=true` para inventário. Uma tabela
simples pode ser identificada sem liberar extração parcial para aprovar seções,
formatação ou extensão. Essas regras continuam indeterminadas quando a cobertura
textual não suporta tabelas/desenhos. Esta etapa não implementa texto de tabelas
para contagem de palavras.

## Unidades suportadas e casos ambíguos

O subconjunto conclusivo conta uma unidade por tabela Word textual simples,
retangular e diretamente no corpo principal. Requer grid único, pelo menos duas
linhas e duas colunas, células alinhadas ao grid, parágrafos e algum texto.
Tabelas aninhadas, mescladas, flutuantes, vazias, de célula única, com objetos,
revisões de estrutura ou formatos não suportados ficam ambíguas. O tamanho mínimo
do grid define cobertura técnica, **não uma exigência editorial sobre tabelas**;
ficar fora do subconjunto não reprova a tabela.

A contagem é estrutural e exige revisão da função editorial das tabelas, inclusive
se uma tabela aparentemente textual foi usada para layout. Não afirma qualidade
dos dados, editabilidade de gráficos, conteúdo científico ou adequação de legenda.
Tabelas que continuam em outra página permanecem uma estrutura Word; não há
contagem de páginas ou inferência por renderização.

Desenhos DrawingML (`w:drawing`), pictures (`pic:pic`) e objetos legados
(`w:pict`/`w:object`) são inventariados como ocorrências, mas **não são convertidos
automaticamente em unidades editoriais de figura**. Um desenho pode conter grupo,
painel, imagem decorativa, gráfico ou figura composta. Os totais são observações
sobrepostos de estruturas, não parcelas para somar: uma picture pode estar dentro
de um drawing. Repetir o mesmo recurso em sete inserções não comprova sete figuras.
A regra de figuras compostas e a associação editorial ainda precisam de confirmação.

Assim, toda imagem ou desenho mantém o limite superior desconhecido nesta versão.
Não há aprovação automática de comunicação com figuras nem reprovação baseada
apenas em sete imagens. Arquivos de mídia não inseridos não contam; uma legenda
textual sem objeto também não é uma figura comprovada. Formato, integridade visual,
resolução/DPI e autoria dentro de imagens não são verificados.

## Cobertura e decisão

Controles de conteúdo, conteúdo alternativo, revisões, campos, texto oculto,
subdocumentos, notas ou cabeçalhos/rodapés referenciados sem inspeção completa e
conteúdo do corpo não reconhecido tornam a cobertura incompleta. Visuais em
outras partes XML ou fundo do documento também impedem aprovação pelo inventário
do corpo. Não presume que essas partes são editoriais ou decorativas. Isso não
proíbe cabeçalhos, rodapés ou notas na revista: requer revisão neste subconjunto.

Admite até 1.024 tabelas no inventário; acima disso, registra o limite técnico e
retorna indeterminado sem iniciar inspeção de todas as tabelas. Os limites de
ZIP/XML continuam aplicados antes dessa etapa.

Para comunicação, com cobertura completa:

- Mais de 6 tabelas simples identificadas: não conforme pelo critério estrutural,
  mesmo que existam imagens adicionais não resolvidas.
- Todas as unidades resolvidas no subconjunto e total até 6: conforme. Inclui
  documento sem objetos visuais, sem afirmar conformidade de outras regras.
- Imagens ou tabelas ambíguas, sem excesso já comprovado: indeterminado.

Com cobertura incompleta, a regra é indeterminada mesmo quando há mais de seis
candidatos observados. Não usa uma leitura parcial para comprovar excesso.

## Evidência e execução

O relatório mantém schema 1.0 e argumentos da CLI. Registra hash do DOCX, tipo,
limite aplicável, método `simple_word_tables_with_unresolved_figures_v1`, cobertura,
tabelas simples/candidatas/ambíguas, ocorrências visuais, limite inferior estrutural,
superior conhecido ou null e motivos fixos de incerteza. `counting_units_resolved`
descreve apenas o subconjunto técnico acima, não valida função editorial.

Até 32 amostras de tabelas e 32 amostras visuais registram índices sem copiar texto,
IDs de relacionamento, nomes de mídia, propriedades descritivas ou caminhos.
`body_child_index` é o índice base zero entre elementos diretos do corpo, incluindo
tabelas e seção final; não é índice de parágrafos do extrator textual. Índices de
tabelas incluem candidatos aninhados para localização, sem contá-los como várias
tabelas editoriais. Totais omitidos das amostras são explícitos.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json manuscrito-sintetico.docx research_communication pt
```

Sem documento, a fixture padrão tem 34 resultados (22 conformes, 12 indeterminados).
Com DOCX de comunicação: 35; artigo: 36. Quantidade não implica aprovação editorial.
Imagens e objetos ambíguos continuam exigindo revisão humana; esta etapa não é um
analisador editorial completo de figuras.

Sem novas dependências, alteração/publicação do ambiente, produção, VM Oracle,
OJS, serviços externos ou manuscritos reais.
