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
