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
