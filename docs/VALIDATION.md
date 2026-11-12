# Validação da Etapa 1

Execução local em 11/11/2026, branch `feat/etapa-1-triagem`.
Ambiente Debian 13 amd64, PHP CLI 8.4.24. O PHP foi extraído localmente de
pacotes oficiais Debian, obtidos com APT e verificação padrão de assinaturas
e checksums preservada; nenhuma instalação privilegiada foi necessária.
A VM ARM64 não foi acessada. Ferramentas locais ficaram fora do repositório.

| Verificação executada | Resultado |
| --- | --- |
| `php tests/run.php` | 56 testes executados, 0 falhas, nenhum ignorado |
| `php -l` em src/bin/plugins/tests | 13 arquivos PHP sem erros de sintaxe |
| CLI com fixture sintética completa | JSON com 19 resultados: 13 conformes, 6 indeterminados |
| JSON Schema 2020-12 | Ambos os schemas e suas instâncias sintéticas validados com jsonschema do Python |
| `git diff --check` | Sem erros de whitespace |

Os testes cobrem presença por campo/idioma, tags e espaços vazios, ausência versus
extração indisponível, todos os autores, ORCID com checksum e `X`, URLs enganosas,
identidade não verificada, tipos/campos inválidos, distinção objeto/lista JSON,
políticas pendentes, conteúdo reduzido do relatório e CLI com falhas/limite de
tamanho/rejeição de wrappers. O carregamento do plugin usa dublê da API OJS e é
um teste unitário limitado, não um teste de integração.

A CLI funcional foi executada por subprocessos no runner; códigos de saída e
stdout/stderr foram verificados. A fixture usa textos e identificadores
sintéticos, sem consulta de existência de ORCID. Não há teste que use
manuscritos reais ou contate serviços editoriais.

## Limites da validação

- PHP 7.4/8.3: matriz CI configurada, execução remota ainda não verificada.
- OJS 3.3.0.20: APIs e commits examinados; instalação real, registro no aplicativo,
  habilitação e adaptação de submissões não executados.
- DOCX, APA, rótulos editoriais e MCP: não implementados, retornos pendentes
  indeterminados não demonstram análise funcional desses componentes.
- Persistência protegida/auditoria de decisões humanas: não implementada.
- Ubuntu 24.04 ARM64 / estrabao-lab: apenas procedimentos futuros documentados.

Os números acima descrevem a implementação desta etapa, sem afirmar que a
integração completa esteja pronta para homologação ou produção.

## Ajuste após o PR #1

A CI inicial falhou no job PHP 7.4 e passou no job PHP 8.3 de push. A página
pública do job confirma falha no passo `php tests/run.php`; o acesso ao log
completo pela API está bloqueado neste ambiente.

A investigação identificou que o runner iniciava a CLI com `php -n`, ignorando
php.ini e extensões compartilhadas. JSON pode ser compartilhada no PHP 7.4,
enquanto faz parte obrigatória do PHP 8. O runner agora preserva a configuração
do PHP, e a CI declara JSON explicitamente. `fail-fast: false` permite concluir
ambas as versões mesmo se uma falhar. Nenhum teste ou assertiva foi desabilitado.

Validação adicional após a correção: 57 testes aprovados no PHP 8.4.24 e no PHP 7.4.33 da imagem oficial
`php:7.4-cli`, executada sem rede e com o checkout montado somente para leitura.
Digest da imagem: `sha256:620a6b9f4d4feef2210026172570465e9d0c1de79766418d3affd09190a7fda5`.
Essa imagem tem JSON embutido e os testes já passavam nela antes da correção;
isso não reproduz o empacotamento de extensões do runner Ubuntu. A confirmação
da correção na CI depende da nova execução do GitHub Actions.

Foi acrescentado um teste de regressão que injeta configuração sintética de
inicialização em um arquivo ini temporário. Com o comando antigo (`-n`), ele
falhou como esperado (57 testes, 1 falha); com a correção, passou (57 testes,
0 falhas). Isso demonstra a preservação de configuração sem depender da forma
como JSON foi compilada na imagem. Arquivos temporários são removidos ao final.

## Etapa 2 — motor 0.2.0

Validação local da branch `feat/etapa-2-resumos` em 11/11/2026:

| Verificação | Resultado |
| --- | --- |
| Runner no PHP 8.4.24 | 110 testes executados, 0 falhas, nenhum ignorado |
| Runner no PHP 7.4.33 em imagem oficial, sem rede e checkout somente leitura | 110 testes executados, 0 falhas, nenhum ignorado |
| Lint PHP 8.4 | 19 arquivos sem erros |
| Schemas/instâncias JSON Schema 2020-12 | Snapshot e relatório válidos |
| CLI com fixture completa | 27 resultados: 22 conformes e 5 indeterminados |

Os testes novos cobrem todas as combinações dos rótulos aprovados nos três
idiomas, plurais e rótulos não aprovados, menções ambíguas em prosa, delimitadores,
HTML, limites de 249/250/251 palavras, divergência de contagem por hífen/rótulos,
quantidade de palavras-chave, itens vazios, fonte desconhecida e normalização
indisponível. O Unicode decomposto ainda sem normalização suportada produz
indeterminado nas regras novas, sem falsa prova de ausência.

As novas regras foram integradas à CLI e seu conjunto centralizado foi exercitado
pelos testes. A definição técnica e os limites estão em STAGE2.md. A versão
2.0.0 da regra abstract.labels substitui o resultado-pendência anterior sem
confundir históricos. A CI remota desta branch deve ser conferida no PR.
Nenhum acesso à revista em produção ou à VM foi realizado. O plugin continua
somente um esqueleto; esta validação não demonstra integração OJS funcional.

## Etapa 3 — motor 0.3.0

Validação local em 12/11/2026, branch `feat/etapa-3-docx-secoes`, após o merge
`4afdb1c` do PR #2. Debian 13 amd64, PHP CLI 8.4.27 com DOM e ZIP, extraído
sem privilégios de pacotes oficiais Debian. APT preservou assinaturas de índices,
checksums e TLS. Runtime fora do checkout em `/workspace/php-runtime`.

- `php tests/run.php`: 161 testes executados, zero falhas, nenhum ignorado.
- Lint: 22 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12 e instâncias: snapshot, relatório sem DOCX (27 resultados) e
  relatório com DOCX sintético de artigo em português (29 resultados) válidos.
- CLI: seções sintéticas completas reconhecidas; sem copiar corpo ou caminho no
  relatório, com hash independente do DOCX e revisão humana obrigatória.
- Script de instalação/atualização executado e repetido com sucesso; runner,
  lint e CLI exercitados. `install_script` e `start_skill` salvos no rascunho
  do ambiente; publicação e restauração em nova tarefa não verificadas.
- `git diff --check`: sem erros de whitespace.

Os 51 testes novos cobrem seções obrigatórias por tipo, ordem livre, fomento
condicional, menção em prosa, Resultados/Discussão separados, idiomas pendentes,
contexto inválido, runs fragmentados, hash, numeração literal e OOXML (inclusive
cancelamento), negrito direto versus herança não resolvida, DOC sem parser,
URLs/wrappers, ZIP corrompido, CRC alterado, symlink, criptografia, caminhos
inseguros, duplicatas por caixa, tamanho/razão/quantidade de membros, pacote
incompleto, macros, objetos, XML malformado/UTF-16, DTD e entidades externas,
texto oculto, campos/revisões/tabelas/caixas de texto e Unicode decomposto.
Hyperlinks externos permanecem locais, sem seguir o destino.

A matriz CI agora requer DOM e ZIP em PHP 7.4/8.3; a execução remota dessas versões
não foi verificada nesta etapa. Não houve acesso a produção nem à VM Oracle.
DOCX tem cobertura deliberadamente limitada; herança de estilos, `.doc`, OJS,
MCP e as demais verificações indicadas em STAGE3.md continuam pendentes.

## Etapa 4 — motor 0.4.0

Validação local em 12/11/2026, branch `feat/etapa-4-estilos-docx`, após o merge
`0b40792` do PR #3, no runtime PHP 8.4.27 já instalado. Não houve instalação
adicional, alteração do ambiente publicado, acesso à produção ou à VM Oracle.

- Runner: 214 testes executados, zero falhas, nenhum ignorado (53 novos).
- Lint: 24 arquivos PHP sem erros de sintaxe.
- JSON Schema 2020-12: schemas válidos e relatórios da CLI sem documento e com
  DOCX sintéticos produzindo formatação conforme, não conforme e indeterminada.
- CLI: presença de seções mantida independente da resolução de estilos;
  evidência sem texto bruto, identificadores de estilo ou caminho do arquivo.
- `git diff --check`: sem erros de whitespace.

Os testes novos exercitam defaults, `basedOn`, estilos de parágrafo e caractere,
alternância de negrito em estilos, precedência direta, runs mistos, espaços
Unicode, ciclos, referências e tipos de estilo inválidos, duplicatas, limite de
cadeia/quantidade, vínculos internos alternativos e externos recusados. Cobrem
numeração herdada e direta, cancelamento, níveis associados por `pStyle`,
`ilvl` direto, override de nível, formatos numéricos, ausência de numeração,
bullets/customizados indeterminados, definições ausentes/duplicadas e vínculos
de lista não suportados. Testes anteriores foram atualizados onde a resolução
efetiva substitui os resultados antes indeterminados (inclusive texto padrão
sem negrito, agora não conforme).

PHP 7.4/8.3 permanece na matriz CI; a execução remota desta etapa não foi
verificada nesta validação local. Ver STAGE4.md para o subconjunto implementado;
esta etapa não valida fonte, tamanho, página, margens, espaçamento nem OJS.

## Etapa 5 — motor 0.5.0

Validação local em 12/11/2026, branch `feat/etapa-5-formatacao-docx`, após o merge
`a0c7fc7` do PR #4. Mesmo PHP 8.4.27 com DOM/ZIP, sem novas dependências,
configuração do ambiente publicado, acesso à produção ou à VM Oracle.

- Runner: 282 testes executados, zero falhas, nenhum ignorado (68 novos).
- Lint: 28 arquivos PHP sem erros de sintaxe.
- Schemas JSON 2020-12 e snapshot sintético válidos. Relatório sem DOCX: 32
  resultados; relatórios com artigo DOCX sintético: 34 resultados. A CLI e schema
  foram exercitados com fonte/tamanho/entrelinhas conformes, divergentes e
  indeterminados, e página/margens conhecidas em todos esses casos.
- `git diff --check`: sem erros de whitespace.

Os testes novos cobrem A4 retrato/paisagem, arredondamento, Letter, medidas
universais não suportadas, cada margem, gutter, margens especiais, seção
intermediária, herança e ausência da seção final, propriedades duplicadas e
revisadas. Cobrem defaults, `basedOn`, estilos de caractere, overrides diretos,
fontes de tema e seus vínculos externos/ausentes, slots ASCII/hAnsi, tamanho em
meios-pontos, scripts não suportados, entrelinhas auto/exact/atLeast, herança de
lineRule, grade, propriedades inválidas, fontes parcialmente desconhecidas,
extração indisponível e documento vazio. A evidência tem amostras limitadas e
preserva divergência tardia sem copiar texto, nomes arbitrários de fonte ou
caminho. Testes anteriores permanecem exercitados, com contagens/versão atualizadas.

PHP 7.4/8.3 segue na matriz CI; a execução remota da nova branch não foi
verificada nesta validação local. Não houve renderização nem instalação de Word,
fontes ou OJS. Os escopos de página e corpo e as limitações estão em STAGE5.md.

## Etapa 6 — motor 0.6.0

Validação local em 12/11/2026, branch `feat/etapa-6-extensao-manuscrito`, após o
merge `852fabe` do PR #5. Mesmo runtime PHP 8.4.27 com DOM/ZIP, sem novas
dependências, alteração/publicação do ambiente, acesso a produção ou VM Oracle.

- Runner: 331 testes executados, zero falhas, nenhum ignorado (49 novos).
- Lint: 32 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12, snapshot e relatórios da CLI válidos. Sem DOCX: 33 resultados;
  com artigo DOCX: 35; com comunicação: 34. Exercitados artigo conforme,
  comunicação abaixo do mínimo, comunicação com hífen cruzando o máximo e idioma
  inglês com títulos do corpo ainda pendentes (indeterminado).
- `git diff --check`: sem erros de whitespace.

Os testes novos cobrem 3999/4000/8000/8001 palavras em artigos e
999/1000/3500/3501 em comunicações, hífens, apóstrofos, números e espaços Unicode.
Cobrem títulos correspondentes ao snapshot, resumos/keywords trilíngues,
referências, ordem livre, títulos numerados, menções em prosa, prefixo desconhecido,
créditos/fomento e legendas incertos, subtítulos em negrito/outline, herança e
override de outline, parágrafos vazios, limites ausentes/duplicados, ambiguidade
resumo/corpo, idiomas/tipo pendentes, extração/normalização indisponível e amostras
limitadas sem texto bruto. A CLI executou casos conforme, não conforme e
indeterminado com hash real do DOCX. Fixtures de longas repetições usam ZIP store,
sem desativar CRC ou os limites do leitor.

PHP 7.4/8.3 segue na matriz CI; a execução remota da branch não foi verificada
nesta validação local. STAGE6.md documenta o intervalo técnico e suas limitações;
a convenção oficial de palavra e materiais condicionais ainda requer confirmação
editorial. Nenhuma contagem foi feita com manuscrito real.

## Etapa 7 — motor 0.7.0

Validação local em 12/11/2026, branch `feat/etapa-7-figuras-tabelas`, após o merge
`79a4b83` do PR #6. Mesmo PHP 8.4.27 com DOM/ZIP. Sem novas dependências,
configuração/publicação do ambiente, produção, VM Oracle ou manuscritos reais.

- Runner: 375 testes executados, zero falhas, nenhum ignorado (44 novos).
- Lint: 35 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12 e relatórios da CLI válidos: 34 resultados sem DOCX, 35 com
  comunicação e 36 com artigo. Exercitados 6 tabelas conformes, 7 não conformes,
  mistura de 6 tabelas e desenho indeterminada e artigo sem limite publicado.
  A regra de extensão permaneceu indeterminada com tabelas, sem extração parcial.
- `git diff --check`: sem erros de whitespace.

Os testes novos cobrem 0/1/6/7 tabelas simples, limite por tipo, independência de
idioma, preservação da cobertura textual, imagens inline/anchor, recurso repetido,
grupos, objetos legados, tabelas vazias, de layout, mescladas, flutuantes,
aninhadas, com imagens e revisadas. Cobrem conteúdo não suportado, altChunk,
campos, notas, cabeçalhos, fundo, texto oculto, visuais em outra parte, mídias não
inseridas, vínculos externos não seguidos, pacote com DTD recusado, DOC sem parser,
limite técnico de tabelas e evidência limitada sem texto, nomes ou IDs privados.
A CLI exercitou limite e excesso com hash real do DOCX. Imagens não são unidades
editoriais confirmadas; sete inserções não causam reprovação por si sós.

PHP 7.4/8.3 permanece na matriz CI; execução remota da branch não foi verificada
nesta validação local. STAGE7.md descreve o subconjunto estrutural e as pendências
sobre figuras compostas, função editorial e ampliação da cobertura. Não houve
renderização, decodificação de imagens, análise de DPI ou integração OJS.

## Etapa 8 — motor 0.8.0

Validação local em 12/11/2026, branch `feat/etapa-8-caixa-titulos`, após o merge
`77ecc21` do PR #7. Mesmo PHP 8.4.27 com DOM/ZIP. Sem novas dependências,
instalação de plugins, produção, VM Oracle ou alteração/publicação do ambiente.

- Runner: 430 testes executados, zero falhas, nenhum ignorado (55 novos).
- Lint: 39 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12: entrada 1.0 e 1.1 válidas, casos inválidos rejeitados em
  concordância com o contrato PHP, relatório 1.0 válido. CLI com as duas fixtures:
  41 resultados; com artigo DOCX: 43 resultados. Exercitados caixa mista conforme,
  caixa alta literal e por estilo não conformes, sigla UNESCO indeterminada.
- `git diff --check`: sem erros de whitespace.

Testes novos cobrem texto nos três idiomas, caixa mista/minúsculas, siglas,
conectores, Unicode/HTML, metadados sem reescrita, subtítulos opcionais, snapshots
legados e novos, objetos/listas e valores inválidos. No Word, cobrem presença de
seção independente da caixa, títulos do snapshot, outline, candidato só em negrito,
prosa não classificada como título, defaults, `basedOn`, estilo de caractere,
alternância de caps, override direto, capitaletes indeterminados, propriedades
inválidas/duplicadas/conflitantes, herança cíclica, runs parcialmente em caps,
extração incompleta e evidência limitada com violação tardia sem texto bruto.

PHP 7.4/8.3 permanece na matriz CI; execução remota desta branch não verificada
nesta validação local. Não houve alteração dos formulários ou CSS do OJS nem
instalação de plugins. Notas de rodapé e indicador de submissão seguem nas etapas
planejadas.

## Etapa 9 — motor 0.9.0

Validação local em 12/11/2026, branch `feat/etapa-9-notas-rodape`, após o merge
`a525ef0` do PR #8. Mesmo runtime PHP 8.4.27 com DOM/ZIP. Sem novas dependências,
renderizador, plugins, produção, VM Oracle ou alteração/publicação do ambiente.

- Runner: 477 testes executados, zero falhas, nenhum ignorado (47 novos).
- Lint: 42 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12 e as entradas 1.0/1.1 válidos. CLI sem DOCX: 43 resultados;
  com artigo DOCX: 45. Exercitados zero e três notas conformes, quatro notas não
  conformes e quatro chamadas sem definição indeterminadas, com relatórios válidos.
  Linhas ficam conformes apenas no caso de zero notas confirmado; nos demais,
  indeterminadas, sem contagem visual simulada.
- `git diff --check`: sem erros de whitespace.

Testes novos cobrem limites 0/1/3/4, tipos normal e separadores com IDs não fixos,
vínculos ausentes, externos, duplicados, órfãos e com target/content type inválido,
IDs de nota ausentes/desconhecidos/repetidos, notas sem chamada, grafo de
relacionamentos inválido, caminho local alternativo, conteúdo não suportado,
revisões, campos, chamadas em cabeçalho, texto oculto, tabelas, posição inválida
de chamada, endnotes fora do escopo, notas longas/quebras explícitas sem inferir
linhas visuais, DTD recusado, fonte indisponível, limites técnicos e amostras
limitadas. A CLI confirmou sigilo de conteúdo/caminho e hash independente do DOCX.
As limitações de extração textual anteriores foram preservadas.

PHP 7.4/8.3 segue na matriz CI; execução remota da branch não verificada nesta
validação local. STAGE9.md define a cobertura estrutural; limite de linhas exige
conferência renderizada. Nenhum manuscrito real, fonte privada ou OJS foi acessado.
