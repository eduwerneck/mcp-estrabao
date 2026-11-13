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

## Etapa 10 — motor 0.10.0

Validação local em 12/11/2026, branch `feat/etapa-10-referencias-citacoes`, após o
merge `d5e0431` do PR #9. Mesmo PHP 8.4.27 com DOM/ZIP. Sem novas dependências,
consultas bibliográficas externas, produção, VM Oracle ou alteração do ambiente.

- Runner: 543 testes executados, zero falhas, nenhum ignorado (66 novos).
- Lint: 44 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12, snapshots 1.0/1.1 e relatórios da CLI válidos. Mesmos 43
  resultados sem DOCX, 45 com artigo. Exercitados correspondência conforme,
  chave literal sem referência não conforme e referência institucional fora do
  subconjunto indeterminada. `references.apa7` permaneceu indeterminado nos três.
- `git diff --check`: sem erros de whitespace.

Os testes novos cobrem um/dois autores, et al., narrativa simples, múltiplas
citações parentéticas, página, ano/sufixo, Unicode, hífen/apóstrofo, chaves não
encontradas, autoria divergente e ambígua, referências duplicadas, institucional,
composta, sem data, data completa, iniciais fora do padrão, entrada repartida ou
múltiplas no mesmo parágrafo. Cobrem comunicação pessoal, citação secundária,
múltiplos anos, narrativa não suportada, parênteses, candidato numérico,
símbolos de uma letra, DOI ausente/antigo/moderno sem consulta externa, referência
não observada sem reprovação, fronteiras ausentes/duplicadas/ambíguas, ordem livre,
idiomas pendentes, fonte/normalização indisponível, limites de entradas/citações/
parágrafos e amostras limitadas com ocorrência tardia sem dados privados.
A CLI confirmou hash DOCX, versões 2.0.0 e ausência de texto/caminho no relatório.
Extração parcial com notas/tabelas permaneceu indeterminada.

PHP 7.4/8.3 permanece na matriz CI; execução remota da branch não foi verificada
nesta validação local. STAGE10.md delimita os sinais e formatos reconhecidos;
esta etapa não certifica APA 7, existência de obras ou identidade de autores.
Nenhuma bibliografia real, OJS ou base de dados foi acessada.

## Etapa 11 — motor 0.11.0

Validação local em 12/11/2026, branch `feat/etapa-11-apa7-abnt`, após o merge
`974cc9c` do PR #10. PHP 8.4.27 com DOM/ZIP, sem novas dependências ou mudanças
no ambiente. Apenas documentos e referências sintéticos.

- Runner: 613 testes, zero falhas, nenhum ignorado (70 novos).
- Lint: 47 arquivos PHP sem erros de sintaxe.
- Schemas 2020-12, entradas 1.0/1.1 e relatórios CLI válidos. Sem DOCX: 45
  resultados (25 conformes/20 indeterminados no fixture legado;
  28 conformes/17 indeterminados com subtítulos). DOCX sintético com padrão ABNT:
  47 resultados para artigo e 46 para comunicação; APA não conforme, sem nomes,
  títulos ou editora da referência no relatório.
- `git diff --check`: sem erros de whitespace.

Os testes cobrem sinais compostos ABNT, cautela com caixa alta e fontes jurídicas,
artigo/capítulo/obra/página web, itálicos efetivos, editores, datas, DOI legado e
pontuação ambígua, DOI ausente, numeração e ordem alfabética limitada, conjunções
e espaços em citações, três idiomas, limites e amostra tardia. Incluem Word real
sintético com estilos de caractere e itálico direto, corpo com notas verificadas,
cauda segura após tabela, corrupção na bibliografia, DTD e sigilo de conteúdo.
A certificação completa permanece explicitamente falsa/indeterminada.

PHP 7.4/8.3 permanece na matriz CI; execução remota desta branch ainda não
verificada. A implementação comunitária CSL foi consultada como apoio durante
o desenvolvimento (STAGE11.md); não há consulta bibliográfica externa no motor.
Nenhum manuscrito real, produção, OJS ou VM Oracle foi acessado.

## Etapa 12 — motor 0.12.0

Validação local em 12/11/2026, sobre o commit 152c96d da Etapa 11.
A referência main observada no remoto ainda estava em 974cc9c; o merge informado
pelo usuário não pôde ser confirmado nessa referência. A nova branch preserva
152c96d como ancestral, sem reescrever ou reaplicar seu conteúdo.

- 617 testes sintéticos, zero falhas (4 novos).
- Lint de 48 arquivos PHP sem erros.
- Relatórios CLI com entradas 1.0/1.1 válidos no schema existente; ambos com
  revisão obrigatória e avanço automático desabilitado na ausência de DOCX.
- Testes confirmam ABNT comprovada e outras incompatibilidades como não aceitas;
  ausência de fonte e formatos parcialmente conformes não liberam aceite.
- `git diff --check` sem erros. CI remoto não verificado.

A mudança calcula política de admissão local. Não implementa o impedimento
no OJS, não altera estados nem comunica autores. Sem produção ou VM Oracle.

## Etapa 13 — plugin 0.2.0.0, motor 0.12.0

Validação local em 12/11/2026, após merge b51b48c do PR #12. PHP 8.4.27
com DOM/ZIP. Fontes públicas OJS/pkp-lib nas revisões exatas de STAGE13.md
foram consultadas para confirmar hook, referências e ordem validate/execute.

- 627 testes sintéticos, zero falhas (10 novos).
- Lint de 50 arquivos PHP sem erros de sintaxe.
- Relatórios CLI das entradas 1.0/1.1 válidos no schema existente, 45 resultados.
- Testes do adaptador com dublês: ativação explícita, isolamento de contexto,
  autenticação/IDs, consulta de arquivo corrente, ABNT e retorno falso do hook,
  ausência/multiplicidade de arquivos, estágio incorreto, travessia, URLs,
  caminho absoluto, symlink externo, motor ausente, exceção e troca de versão.
- `git diff --check` sem erros. Execução remota CI ainda não verificada.

Os dublês não comprovam instalação, renderização da mensagem, autorização real,
transações, bloqueio de outros canais ou ausência real de e-mails no OJS.
Não foi iniciada instalação/banco OJS. Interface de revisão/liberação pendente;
ativação é exclusivamente futura e em homologação. Nenhuma configuração de
ambiente foi alterada, nem produção ou VM Oracle acessada.

## Etapa 14 — plugin 0.3.0.0, motor 0.12.0

Validação local em 12/11/2026, base 197ad29 (merge do PR #13). PHP 8.4.27
com DOM/ZIP, sem dependências novas. Fontes públicas exatas de autorização,
roteamento, CSRF, download e ações do plugin foram conferidas (STAGE14.md).
Assinaturas de LinkAction e RedirectAction também conferidas no mesmo pkp-lib.

- 651 testes sintéticos, zero falhas (24 novos).
- Lint de 54 arquivos PHP sem erros de sintaxe.
- Entradas CLI 1.0/1.1 e relatórios válidos no schema existente: 45 resultados,
  versão de motor preservada. XML do plugin válido, versão 0.3.0.0.
- Testes de revisão: gerente do contexto, negação a autor/outro contexto,
  POST/CSRF/declaração, recusa de liberação ABNT, liberação inconclusiva,
  preservação de erros nativos, revogação e perda de papel do responsável.
- Testes de versão: arquivo/IDs/idioma alterados e aba antiga invalidam a
  liberação; arquivo ilegível continua retido. Revogação funciona mesmo após
  corrupção do arquivo e não revive ao restaurar seus bytes anteriores.
- Testes de registro: permissões 0700/0600, privacidade, corrupção, separação
  por contexto/submissão, lock ocupado e limite de histórico com reserva para
  revogação. Testes de handler/modelo, rota explícita, ação no plugin e download.
- `git diff --check` sem erros. CI remoto ainda não verificado.

O dublê do TemplateManager verifica o modelo e o caminho do template, sem
renderizar Smarty. Não houve instalação OJS, teste visual em navegador, banco,
email real ou homologação end-to-end. Rotas com/sem PATH_INFO e concorrência com
upload permanecem para homologação. Registro privado não foi configurado fora
dos diretórios temporários dos testes. Sem produção, VM Oracle ou publicação
nova do ambiente.

## Etapa 15 — plugin 0.3.1.0, motor 0.12.0

Validação em 12/11/2026, base 0bd0a1e (PR #14). Laboratório Docker internal local,
OJS 3.3.0.20 real instalado, PHP 8.1.27, MariaDB 10.6, Chromium 156.0.8078.79,
Selenium 4.52.0. Fontes e digests em STAGE15.md. Só fixtures sintéticas no OJS.

- Runner: 655 testes aprovados, zero falhas (4 novos), tanto em PHP 8.4.27 do
  workspace quanto em PHP 8.1.27 da imagem OJS.
- Lint: 59 arquivos PHP, incluindo scripts opcionais do laboratório, sem erros.
- 17 verificações reais HTTP/UI aprovadas, com sessões de autor/gerente e
  conferência de progresso/data no banco. Cobrem ABNT, liberação/conclusão,
  revogação, CSRF, autorização/download, troca de fileId/bytes e rotas com/sem
  PATH_INFO. Capturas inspecionadas e JSON em docs/evidence/stage15.
- Relatórios CLI 1.0/1.1 válidos no schema existente, 45 checks. XML plugin
  válido na versão 0.3.1.0; scripts Python analisados sem erro de sintaxe.
- `git diff --check` sem erros. CI remoto ainda não verificado.

A homologação encontrou e corrigiu a ausência do catálogo mestre en_US e o
retorno precoce do hook que impedia a notificação nativa. O motivo e orientações
APA comprovadas agora aparecem ao autor. Aviso nativo de “pronta para finalizar”
e chave de navegação do núcleo estão registrados como limites visuais existentes.

A substituição da versão usou os serviços/DAO no seed local, sem ensaio visual
do componente de upload nem concorrência. Não comprova tema/infraestrutura real,
ARM64, outros canais de admissão, backup ou ausência de corridas com uploads.
Não houve email externo, acesso à produção/Oracle ou republicação do ambiente.
O documento real enviado pelo usuário foi examinado separadamente, a pedido,
sem cópia para o laboratório, Git ou evidências sintéticas.

## Etapa 16 — gerador de devolutiva 1.0.0

Validação local em 12/11/2026, após 73b13c9 (merge do modelo definitivo).
Motor 0.12.0 e plugin 0.3.1.0 preservados. Sem novas dependências.

- Runner: 687 testes, zero falhas (32 novos), em PHP 8.4.27 do workspace e
  PHP 8.1.27 da imagem OJS, executada sem rede e com repositório read-only.
- Lint: 62 arquivos PHP sem erros de sintaxe.
- Schema novo 2020-12 válido; snapshots 1.0/1.1 e fixture de correções válidos.
  CLI baseline: nenhuma correção inventada, revisão editorial pendente. Fixture
  de correções: exatamente três pedidos. DOCX ABNT sintético: correções APA e
  hash documental correspondente, sem nomes/título/DOI ou caminho na minuta.
- Fixture e exemplos de saída da etapa são integralmente sintéticos.
- `git diff --check` sem erros. CI atualizado com smoke da nova CLI, mas execução
  remota ainda não verificada.

Os testes novos cobrem ausência versus indeterminação, idiomas, título privado,
rótulos, extensão, palavras-chave, ORCID, seções/CRediT, margem/fonte, limites por
tipo, notas, APA/ABNT versus DOI, deduplicação, não aprovação automática,
fontes/versões desconhecidas, conteúdo privado/instruções arbitrárias, proveniência,
mistura de documentos, limites técnicos, erros da CLI e pendências separadas.

Não houve alteração no fluxo OJS nem novo ensaio de navegador: a entrega é o
formatador/CLI local. Nenhuma comunicação, alteração de estado, acesso à produção
ou VM Oracle; o documento real da conversa não foi usado nesta validação.

## Etapa 17 — plugin 0.4.0.0

Validação local em 12/11/2026, gerador 1.0.0 e motor 0.12.0 preservados.
Base final 7ebf393 (merge do PR #17), confirmada e incorporada antes do envio.
Sem reaplicar conteúdo ou publicar ambiente.

- 706 testes sintéticos, zero falhas (19 novos), em PHP 8.4.27 e PHP 8.1.27.
- Lint: 64 arquivos PHP sem erros de sintaxe.
- 13 verificações reais no OJS 3.3.0.20 local, PHP 8.1.27/MariaDB/Chromium do
  laboratório internal da Etapa 15. Cobrem geração com DAO real, traduções,
  edição/cópia real ao clipboard, exclusão da ficha interna da cópia, fallback
  manual, permanência do bloqueio ABNT, GET/CSRF/acesso de autor e rota por query.
  Estado das submissões comparado antes/depois: nenhuma alteração editorial.
- XML do plugin 0.4.0.0 e contrato da minuta válidos; nova CLI preservada.
  Script tests/lab/desk-panel.py analisado e executado no laboratório. Captura
  inspecionada e JSON em docs/evidence/stage17.
- `git diff --check` sem erros. CI remoto ainda não verificado.

Testes novos incluem publicação atual e IDs, snapshot 1.1, metadados do DAO em
vez de campos postados, variantes de locale conservadoras, autoria indisponível,
limites técnicos, geração sem aprovação, fonte ABNT bloqueada, proveniência,
alteração de documento/metadados durante geração, privacidade e handler.

Catálogo/cache de idioma antigo foi atualizado somente no laboratório; na
atualização do plugin, usar limpeza administrativa de caches em homologação.
Testes usam apenas dados sintéticos, sem artigo real, produção, VM Oracle,
envio aos autores, gravação de cartas no ledger ou configuração publicada nova.
Permanecem os limites de concorrência após exibição, tema/infraestrutura real,
outros canais e normalização adicional de locales descritos em STAGE17.md.

## Etapa 18 — cobertura segura de DOCX

Validação local em 12/11/2026, base e1aa1ec (merge do PR #18), motor 0.13.0.
Plugin 0.4.0.0 e gerador de minuta 1.0.0 preservados.

- 750 testes sintéticos, zero falhas, em PHP 8.4.27 e PHP 8.1.27; 44 testes novos.
- Lint: 68 arquivos PHP sem erros de sintaxe; `git diff --check` sem erros.
- CLI de triagem/minuta e schemas JSON validados com snapshots sintéticos e
  DOCX composto sintético: 47 checks, cobertura parcial explícita, divergência
  de fonte e padrão ABNT reconhecido presentes na minuta. Evidências em
  docs/evidence/stage18; nenhum conteúdo de manuscrito real nesses exemplos.
- Integração do serviço do painel exercitada com motor real e dublês do OJS.
  Liberação vinculada à versão anterior do motor é invalidada pelo fingerprint.
  Não houve novo ensaio de navegador nem alteração de interface nesta etapa.
- Testes existentes ajustados para versões das regras e presença comprovável
  de títulos em texto Word parcial; contagem integral continua indeterminada.

Cobertura inclui marcadores Word conhecidos, DOM original preservado, imagens
locais e camadas de efeito, tabelas, caminhos/relacionamentos inválidos, campos,
revisões, texto em propriedades, limites, cauda bibliográfica e privacidade.
Mantidos os controles de ZIP/XML; dados gráficos não são renderizados ou lidos
por OCR. CI remoto ainda não verificado.

O manuscrito autorizado na conversa foi retestado separadamente, fora do Git,
sem alteração do original ou da carta anteriormente aprovada. Não foi inserido
no laboratório OJS. Sem produção, VM Oracle, comunicação com autores, publicação
ou atualização de ambiente.

## Etapa 19 — títulos e subtítulos

Base e149d16 (merge do PR #19). Motor 0.14.0, plugin 0.5.0.0.
Validação concluída em 13/11/2026 após retomada do ambiente.

- 765 testes sintéticos aprovados, zero falhas, em PHP 8.4.27 e PHP 8.1.27;
  15 novos testes de reconhecimento, idioma, caixa efetiva, minuta e hooks.
- Lint de 69 arquivos PHP sem erros. `git diff --check` sem erros.
- Conferência manual anterior à suspensão, em OJS 3.3.0.20/Chromium local:
  dois campos do wizard AJAX com orientação traduzida; dois campos de título e
  subtítulo no formulário de publicação também com orientação. Não houve save.
  Não constitui uma nova suíte automatizada de navegador.
- Schemas e formato da minuta não foram alterados; versões das regras e do motor
  propagadas ao catálogo da devolutiva e às expectativas dos testes existentes.

Homologação no tema real permanece pendente. Sem acesso à produção, VM Oracle,
comunicação com autores, mudança de estado editorial ou publicação do ambiente.
CI remoto ainda não verificado.

## Etapa 20 — conferência de notas

Validação em 13/11/2026, base f706ea9 (merge do PR #20), motor 0.15.0,
plugin 0.6.0.0.

- 779 testes sintéticos aprovados em PHP 8.4.27 e 8.1.27, zero falhas; 14 novos.
- Lint de 71 arquivos PHP aprovado; `git diff --check` sem erros.
- 9 verificações reais no OJS 3.3.0.20 local com Chromium: três chamadas e
  instruções traduzidas, vínculo de download, linhas fora da carta, clipboard
  sem ficha interna, excesso de quatro notas, indicador de notas de fim, zero
  sem ficha e estado editorial preservado. Captura inspecionada.
- Fixture temporária exclusivamente sintética restaurada após cada suíte; não
  houve alteração de DAO, metadados, decisões ou avanço de submissão.

As duas linhas continuam indeterminadas sem renderização e conferência humana.
Não se usou manuscrito real, produção, VM Oracle ou envio a autores. Sem publicação
do ambiente. CI remoto ainda não verificado.

## Etapa 21 — APA 7 com formatos mistos

Validação local em 13/11/2026, base 52f5417 (merge do PR #21), motor 0.16.0
e plugin 0.7.0.0.

- 806 testes sintéticos aprovados, zero falhas, em PHP 8.4.27 e PHP 8.1.27;
  27 novos. Incluem listas de 20/21 autores, separadores, formatos válidos,
  instituições e títulos como controles negativos, cauda parcial, privacidade,
  minuta e bloqueio de liberação no painel.
- Lint de 73 arquivos PHP e `git diff --check` sem erros.
- 5 verificações em OJS 3.3.0.20 local real/Chromium: ausência de liberação,
  correções específicas, sem acusação genérica de ABNT, carta sem conteúdo da
  referência/DOI e POST do wizard bloqueado com formulário nativo válido.
  Estado editorial inalterado e fixture restaurada. Captura inspecionada.

Testes exclusivamente sintéticos; sem PDF/manuscritos reais, produção, VM Oracle,
envio a autores ou publicação do ambiente. Não certifica APA completa; CI remoto
ainda não verificado.

## Etapa 22 — data de submissão pública

Validação em 13/11/2026, base b11c063 (merge do PR #22), plugin 0.8.0.0,
motor 0.16.0 preservado.

- 837 testes sintéticos, zero falhas, em PHP 8.4.27 e PHP 8.1.27; 31 novos.
- Lint de 76 arquivos PHP sem erros e `git diff --check` aprovado.
- 10 verificações em OJS 3.3.0.20/tema padrão/Chromium local: visitante anônimo,
  data de calendário, posição antes de Publicado no mesmo box, data publicada
  preservada, sem duplicação, sem mudança de estado/datas pelo indicador,
  renderização sem JavaScript, ausência/incoerência e prévia sem indicador,
  restauração dos registros sintéticos. Captura inspecionada.

Fixture simulou artigo publicado somente no laboratório internal e restaurou os
registros originais. Não chamou rotinas de publicação/e-mail. Sem dados reais,
produção, VM Oracle, comunicação com autores ou publicação do ambiente.
Tema real não identificado nem homologado. CI remoto ainda não verificado.

## Etapa 23 — conteúdo CRediT

Validação em 13/11/2026, base ae27b68 (merge do PR #23), motor 0.17.0 e
plugin 0.9.0.0.

- 852 testes sintéticos, zero falhas, em PHP 8.4.27 e PHP 8.1.27; 15 novos.
- Lint de 78 arquivos PHP sem erros; `git diff --check` aprovado.
- 4 verificações no OJS 3.3.0.20 local real com Chromium: grupo CRediT traduzido,
  ausência de título sem acusação de seção vazia, pendências fora da carta e
  estado editorial preservado. Teste apenas leu registros sintéticos existentes.
- Novas regras exercitadas com leitor DOCX real e serviço do painel em testes
  sintéticos. Ajustadas contagens/versionamento anteriores para duas regras novas.

Presença de texto não certifica taxonomia/atribuição/veracidade. Não houve dados
reais, produção, VM Oracle, envio a autores ou publicação do ambiente.
CI remoto ainda não verificado.

## Etapa 24 — consistência de fomento

Validação em 13/11/2026, base 9b75673 (merge do PR #24), motor 0.18.0 e
plugin 0.10.0.0.

- 884 testes sintéticos, zero falhas, em PHP 8.4.27 e 8.1.27; 32 novos.
- Lint de 80 arquivos PHP aprovado e `git diff --check` sem erros.
- 4 verificações de leitura no OJS 3.3.0.20/Chromium local: orientação interna
  traduzida, indisponibilidade sem acusação de omissão, separação da carta e
  estado editorial preservado. Não houve edição de configurações ou metadados.
- Contrato 1.2, direções da comparação e disponibilidade request/require versus
  enable/0/ausente validados sinteticamente com adaptador, leitor e serviço reais
  sob dublês OJS. Campos postados não substituem o DAO. Captura inspecionada.

Não certifica financiamento, equivalência de agências ou números de projetos.
Sem manuscritos reais, produção, VM Oracle, envio a autores ou publicação do
ambiente. CI remoto ainda não verificado.

## Etapa 25 — conferência de ética

Validação em 13/11/2026, base 2c05671 (merge do PR #25), motor 0.19.0 e
plugin 0.11.0.0.

- 903 testes sintéticos, zero falhas, em PHP 8.4.27 e PHP 8.1.27; 19 novos.
- Lint de 82 arquivos PHP aprovado e `git diff --check` sem erros.
- 4 verificações de leitura no OJS 3.3.0.20/Chromium local: ficha traduzida,
  ausência de marcador sem presumir ausência/dispensa, ficha fora da carta e
  estado editorial preservado. Captura inspecionada. Nenhum dado foi editado.
- Cenários com indícios, negações e texto clínico sintético exercitados pelo
  leitor e serviço reais sob dublês, sem copiar nomes ou protocolos na saída.

A regra permanece indeterminada: não certifica ética nem cria bloqueio ou
exigência automática aos autores. Sem dados reais, produção, VM Oracle, mensagens
a autores ou publicação do ambiente. CI remoto ainda não verificado.
