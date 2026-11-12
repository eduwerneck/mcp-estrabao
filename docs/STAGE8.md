# Etapa 8 — sinalização de caixa alta em títulos

Motor 0.8.0 após o merge do PR #7. Política adicional
`evitar-caixa-alta-confirmado-2026-11-12`, confirmada pelo responsável: evitar
títulos/subtítulos integralmente em caixa alta, tanto nos metadados do artigo
quanto no manuscrito. Não estabelece sentence case, Title Case ou conversão para
minúsculas. Não modifica textos, metadados reais do OJS ou arquivos Word.

## Snapshot 1.1 e compatibilidade

O contrato de entrada passa a aceitar snapshots **1.0 e 1.1**. O 1.0 continua
aceito com seus mesmos campos; não aceita `subtitle`. O 1.1 acrescenta apenas
`subtitle`, mapa opcional por locale com strings ou null para fonte indisponível.
Subtítulo não se torna obrigatório. Objetos e listas JSON permanecem distintos;
campos desconhecidos e valores inválidos continuam rejeitados.

`contracts/submission.schema.json` identifica a revisão 1.1 e valida as duas
versões, com restrição condicional para 1.0. A saída mantém schema de relatório
1.0; versão do motor identifica as novas regras. `input_sha256` inclui os campos
do snapshot fornecido, inclusive subtítulo quando presente. A fixture antiga
`complete.json` continua 1.0; `with-subtitles.json` exemplifica 1.1.

## Metadados — regras 1.0.0

`metadata.title.case` e `metadata.subtitle.case`, um resultado por pt_BR/en_US/es_ES,
analisam o texto armazenado no snapshot, após normalização local de HTML. Não
interpretam CSS do site ou obrigam o preenchimento de subtítulo. Texto ausente,
vazio, sem letras com caixa, UTF-8 inválido ou Unicode decomposto ainda não
suportado fica indeterminado; não é reprovação de ausência de subtítulo.

O classificador sinaliza apenas caixa alta **integral**. Caixa mista ou minúsculas
é conforme a essa regra específica, sem afirmar que a capitalização é editorialmente
ideal. Não rebaixa siglas, nomes próprios ou abreviações. Em caixa alta:

- Títulos canônicos reconhecidos de seção, como INTRODUÇÃO, são não conformes.
- Frases com conectores internos reconhecidos são sinalizadas como não conformes,
  por exemplo PAISAGEM E FORMAÇÃO DOCENTE ou THE STUDY OF LANDSCAPES.
- Termos isolados, sequências de possíveis siglas ou frases sem esses sinais ficam
  indeterminados, com `all_caps=true`, para revisão de siglas. DNA, UNESCO, DNA RNA
  SI e A ONU não são reprovados automaticamente. Em A ONU, o artigo inicial sozinho
  não determina infração; em ONU E UNESCO, o conector interno dá indício de frase
  inteira em caixa alta. A grafia DNA e RNA é aceita, preservando as siglas.

Conectores são um indício lexical técnico, não reconhecimento semântico infalível
nem uma lista editorial de siglas permitidas. O classificador usa conjuntos pequenos
por idioma (artigos/preposições/conjunções comuns), ignora caixa e não consulta
serviços externos. Possíveis falsos indícios, siglas que coincidem com palavras
comuns e casos sem conector exigem revisão humana. Não tenta adivinhar uma grafia
corrigida. A presença de `all_caps=true` em resultado indeterminado mantém a
sinalização mesmo quando não há prova suficiente para reprovar.

## Manuscrito — `manuscript.headings.case` 1.0.0

Seleciona títulos canônicos reconhecidos, títulos/subtítulos correspondentes ao
snapshot no prefixo do documento e parágrafos com outline efetivo 0–8. Negrito
sozinho ou outline/estilo desconhecido só identifica candidato ambíguo, nunca
prova de título. Não usa nomes de estilo. Não confunde prosa normal em maiúsculas
com título automaticamente. O escopo é dos títulos reconhecidos e candidatos,
sem prometer identificação semântica de todos os subtítulos de um documento.

Resolve `caps` e `smallCaps` de defaults, cadeias `basedOn`, estilos de parágrafo e
caractere e formatação direta, com os limites da Etapa 4. Nos estilos, esses
atributos alternam o valor herdado; na formatação direta são absolutos. Assim,
texto armazenado como Introdução com `caps` efetivo pode ser detectado como título
visualmente em caixa alta. Caps aplicado só a parte dos runs não basta para
concluir que o título inteiro esteja em caixa alta. A resolução segue propriedades
OOXML, sem renderizar ou garantir comportamento de um editor específico.

Capitaletes (`smallCaps`) ficam indeterminados: o responsável não definiu sua
proibição como equivalente a caixa alta. Propriedades inválidas, duplicadas,
conflitantes, scripts não suportados e herança não resolvida ficam indeterminadas.
Um título já comprovadamente em caixa alta no próprio texto continua sinalizado
independentemente de uma lacuna de estilo.

Títulos canônicos incluem os marcadores existentes de seções e resumo/keywords
para sinalização lexical; isso não aprova traduções das seções do corpo nem cria
novas exigências de presença. A antiga regra de presença continua insensível à
caixa: INTRODUÇÃO pode comprovar presença e simultaneamente falhar na regra nova.
Falha de extração ou tabela/desenho sem cobertura textual não permite aprovar
caixa de títulos por leitura parcial.

Uma violação comprovada prevalece no agregado; sem violação, candidato ou sigla
ambígua produz indeterminado. Conforme se limita aos títulos/candidatos efetivamente
identificados e resolvidos, sem inventar validação de títulos não identificáveis.

## Evidência e execução

Registra versão da política, escopo, `all_caps`, motivo fixo, origem disponível,
hash do DOCX quando aplicável e até 32 amostras por posição. Prioriza violações
em amostras tardias e informa quantas foram omitidas. Não copia títulos, subtítulos,
nomes de estilo ou caminhos. `automatic_rewrite=false` em todas as novas regras.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/with-subtitles.json
php bin/triage.php tests/fixtures/with-subtitles.json manuscrito-sintetico.docx article pt
```

As sete verificações novas deixam o relatório padrão sem DOCX com 41 resultados.
A fixture 1.0 tem 25 conformes e 16 indeterminados; a 1.1 com subtítulos tem 28
conformes e 13 indeterminados. Com artigo DOCX: 43; comunicação: 42 resultados.

O motor continua sem adaptador OJS e não altera o preenchimento em formulários ou
CSS do site. A regra de notas de rodapé (até 3, cada uma até 2 linhas) é a próxima
etapa; linhas dependem de renderização. O indicador Submetido em acima de Publicado
fica adiado para a integração HTML, conforme o planejamento do responsável.
Sem novas dependências, produção, VM Oracle ou publicação/reconfiguração do ambiente.
