# Homologação integrada — laboratório local

A [Etapa 54](TRIAGE_COVERAGE.md) acrescenta somente o balanço documental das
diretrizes e prioridades. Os resultados abaixo são da execução da Etapa 53;
não houve nova regressão integrada, mudança de produto ou homologação de produção.

Resultado em 19/11/2026: **regressão aprovada no laboratório**, base aa855f8
(merge PR #53). A [Etapa 53](STAGE53.md) preserva motor 0.41.0 e plugin 0.34.0.0.

| Camada atual | Resultado |
| --- | --- |
| PHP 8.4.27 e 8.1.27 | 1716 testes por versão, zero falhas |
| Estático e contratos | Lint de 119 arquivos PHP, Python e dois schemas CLI aprovados |
| Dez suítes de painel/artigo/metadados | 78 verificações aprovadas |
| 21 modalidades APA | 805 verificações aprovadas |
| Total das 31 suítes reversíveis | 883 verificações aprovadas |
| Ciclo novo com correção combinada | 27 verificações aprovadas |
| Total de navegador | **910 verificações aprovadas** |
| Produto/contratos | 70 arquivos idênticos à base; versões preservadas |

[Resumo atual](evidence/stage53/summary.json),
[pré-checagem](evidence/stage53/preflight.json),
[manifesto](evidence/stage53/release-source-manifest.json) e
[ciclo de correção](evidence/stage53/submission-cycle-results.json).
Resultados por suíte estão no mesmo diretório.

A correção combina anos/sufixos, et al., data ausente, narrativa com página,
autor único/coautorias, artigos iniciais, subtítulos/pontuação e duas datas completas.
Sete citações ligam a entradas da lista de nove referências; as duas entradas com
datas completas permanecem fora da gramática do matcher e são conferidas pela
auditoria específica. Sufixos desses conjuntos exigem revisão humana.

A conclusão exige revisão expressa; bytes preservados após revisão/conclusão.
Revogação, CSRF, troca de arquivo e acesso protegido verificados. Registros antigos
preservados; novos registros existem apenas na base sintética. A troca usa API
local/DAO, sem homologar upload pela interface ou concorrência.

Resultado limitado ao OJS 3.3.0.20/Tema Padrão local. Rede interna, fontes read-only,
e-mail neutralizado e containers desligados ao fim. Sem produção, VM Oracle,
documentos reais, mensagens externas ou deploy. Não certifica infraestrutura real
nem APA integral; a matriz APA7.md permanece aberta.

Regressões anteriores: [695 verificações da Etapa 48](STAGE48.md) e
[287 verificações da Etapa 38](STAGE38.md).

## Histórico da homologação anterior

Os resultados abaixo pertencem à Etapa 27/28 e são mantidos para rastreabilidade;
a versão atual e sua contagem estão acima.

Resultado em 17/11/2026: **aprovado no escopo do laboratório**, sobre o código
112485b (merge do PR #27). Motor 0.20.0, plugin instalado/arquivos 0.12.0.0 e OJS
3.3.0.20. Sem alterações funcionais no motor ou plugin nesta entrega.

Isso não é uma homologação da instalação real da revista. Arquivos/versão reais do tema,
infraestrutura, permissões do usuário de serviço e operação continuam pendentes.
Não houve produção, VM Oracle, envio externo ou publicação do ambiente.

## Complemento — perfil informado (Etapa 28)

Sobre 038aa84 (merge do PR #28), foram aprovadas 25 verificações adicionais em
cinco tamanhos de tela, reproduzindo o perfil informado: Tema Padrão, Noto Sans,
#1E6292 e sem CSS adicional. Datas permanecem no mesmo box sem sobreposição ou
rolagem horizontal, inclusive em 320 px. Produto e opções do tema inalterados;
datas/estado sintéticos restaurados. O tema 1.0.0.0 é o da imagem local, não uma
versão confirmada da revista. [Escopo e evidências](STAGE28.md).

Os números abaixo pertencem à execução da Etapa 27 e não foram reexecutados
no complemento; os 59 hashes do produto foram reconferidos.

## Resultado histórico da Etapa 27

| Camada | Resultado observado |
| --- | --- |
| Motor/contratos PHP 8.4.27 | 922 testes sintéticos, zero falhas |
| Motor/contratos PHP 8.1.27 | 922 testes sintéticos, zero falhas |
| PHP estático | Lint de 85 arquivos aprovado |
| CLI/schemas | Seis saídas de triagem/minuta válidas; entradas 1.0/1.1/1.2 válidas |
| OJS real local + Chromium | 72 verificações aprovadas |
| Identidade do código | 59 arquivos de motor/plugin/contratos com SHA-256 em manifesto |
| Isolamento | Rede interna, sem portas públicas, fontes montadas read-only, e-mail neutralizado |

[Resumo](evidence/stage27/summary.json), [pré-checagem](evidence/stage27/preflight.json),
[imagens/runtime](evidence/stage27/images.json) e
[manifesto dos arquivos](evidence/stage27/release-source-manifest.json).
O resumo registra execução principal de 66 casos e complemento de seis casos de
títulos, sobre o mesmo código de produto sem alterações. O runner final inclui
ambos: nove suítes reversíveis/de leitura (59 casos) e ciclo novo (13).

## Matriz histórica da Etapa 27

| Ensaio | Casos | Evidência |
| --- | ---: | --- |
| Minuta/painel/cópia/acesso/CSRF/rota por query | 13 | [JSON](evidence/stage27/desk-panel-results.json) |
| APA misto: correções, bloqueio e privacidade | 5 | [JSON](evidence/stage27/apa-mixed-results.json) |
| Notas, excesso, duas linhas para revisão e notas de fim | 9 | [JSON](evidence/stage27/footnote-panel-results.json) |
| Submetido em no HTML, sem JS, ausência/incoerência/prévia | 10 | [JSON](evidence/stage27/article-dates-results.json) |
| CRediT: revisão interna, sem acusação indevida | 4 | [JSON](evidence/stage27/credit-panel-results.json) |
| Fomento: fonte indisponível sem presumir omissão | 4 | [JSON](evidence/stage27/funding-panel-results.json) |
| Ética: aplicabilidade/documentação para revisão humana | 4 | [JSON](evidence/stage27/ethics-panel-results.json) |
| IA: uso/não uso no manuscrito, sem duplicação OJS | 4 | [JSON](evidence/stage27/ai-panel-results.json) |
| Títulos: wizard e publicação, siglas, acessibilidade, sem autosave | 6 | [JSON](evidence/stage27/title-guidance-results.json) |
| Wizard com novos rascunhos: bloqueio, liberação, revogação e versão | 13 | [JSON](evidence/stage27/admission-cycle-results.json) |

O ciclo novo confirmou que ABNT não recebe liberação e permanece na etapa 4,
com mensagem visível ao autor. Uma revisão humana APA vinculada à versão permite
concluir o wizard; não significa aceite do artigo ou conferência automática de
ética/CRediT/IA. Revogação, CSRF inválido e arquivo alterado impedem conclusão.
Download editorial funciona para o gerente e não para o autor.

A validação usa estado real do banco e interface. No OJS, `status` do JSON de um
componente pode ser true apenas por ter renderizado o formulário com erros; não é
prova de conclusão. O ensaio confere submission_progress/date_submitted e feedback
nativo, sem confundir o envelope do componente com aceite da submissão.

Capturas inspecionadas:

- [Bloqueio apresentado ao autor](evidence/stage27/author-abnt-feedback.png)
- [Liberação da versão após revisão humana](evidence/stage27/editorial-release.png)
- [Data de submissão no artigo](evidence/stage27/article-dates.png)

## Preparação e ajustes do laboratório

A retomada encontrou arquivos 0.12.0.0 e registro instalado 0.3.0.0. Foi utilizado
**somente no laboratório** o utilitário nativo, após conferir URL/banco sintéticos:

```sh
docker exec estrabao-lab-15-web php lib/pkp/tools/installPluginVersion.php plugins/generic/estrabao/version.xml
```

O registro instalado passou a 0.12.0.0, sem mudança de código ou migração de dados
editoriais reais. Esse comando não é uma orientação para executar diretamente na
revista: segue necessária homologação/estratégia de atualização naquela instalação.
O runner não instala ou atualiza automaticamente; recusa divergência de versões.

O servidor web já neutralizava e-mail por `sendmail_path=/bin/true` no comando de
inicialização. A CLI ainda usava o padrão do PHP; foi alinhada com um override
local em /etc/php81/conf.d/zz-estrabao-lab.ini, contendo somente essa configuração.
SMTP permanece desabilitado. Não foram configuradas contas ou credenciais reais.
A pré-checagem confirma tanto a neutralização CLI quanto o override do servidor.

O Chromium compartilha o namespace de rede do container web; o runner verifica
explicitamente esse vínculo e a rede interna efetiva. O daemon é fixado no socket
local gerenciado. Nenhum conector de produção ou acesso remoto é utilizado.

## Reprodução

Usar exclusivamente o laboratório da Etapa 15: containers já instalados com nomes
estrabao-lab-15-web, -db e -browser; rede interna; base_url
http://127.0.0.1:18080; banco estrabao_synthetic; credenciais apenas sintéticas no
diretório privado. O código montado deve corresponder ao checkout atual. O runner
recusa fontes mutáveis, mounts de outro checkout, versão instalada divergente,
SMTP habilitado, transporte não neutralizado ou armazenamento privado inadequado.
Não provisiona laboratório, não habilita campos nem modifica configuração publicada.

```sh
# Containers precisam estar iniciados e preparados conforme acima.
PYTHONPATH=tests/lab python tests/lab/integrated.py
# Opcional: cria CINCO novos rascunhos sintéticos e exerce conclusão/revogação.
PYTHONPATH=tests/lab python tests/lab/integrated.py --with-submission-cycle
```

A primeira modalidade executa nove suítes, algumas com fixtures temporárias de
bytes/datas restauradas por finally. A segunda também cria um conjunto novo de
rascunhos e usa o fluxo real de revisão/conclusão. Esses registros sintéticos
permanecem no banco para auditoria; não são submissões reais nem publicação externa.
O mapa anterior /tmp/lab-state.json é restaurado. Registros anteriores ao ciclo
ficam preservados, inclusive a versão original usada pelos ensaios reversíveis.

Resultados privados ficam por padrão em /workspace/scratch/estrabao-lab-27. O
resumo só recebe overall_passed=true após todos os casos solicitados. Uma falha
retorna código não zero; resumo parcial/running não deve ser tratado como aprovação.
Não copiar credenciais, cookies, sessões, logs privados ou dumps para o Git.
Somente os JSON/capturas sintéticos selecionados integram evidence/stage27.

Ao terminar, desligar os três containers. Para repetir, iniciá-los e conferir
novamente os pré-requisitos. Não repetir seed/ciclo sobre submissões reais.
O antigo smoke.py é histórico; o ciclo novo usa IDs do seed e evita depender de
IDs fixos para conclusão, revogação e substituição de versão.

## Limites e próxima homologação

O escopo local está aprovado. Permanecem fora dele:

- Arquivos/versão do tema e configuração de campos reais; instalação, atualização e rollback
  em uma homologação representativa da revista.
- Usuário de serviço/permissões, ARM64, TLS/proxy, concorrência com uploads,
  outros canais (API/importação/quickSubmit), backup e recuperação operacional.
- Cobertura semântica integral: APA completa, autenticidade/identidade de fontes,
  linhas renderizadas de notas, papéis CRediT, ética, finalidade/uso real de IA
  e equivalência de agências/projetos permanecem humanas ou fora do subconjunto.
- Os cenários positivos específicos de fomento/declarações são exercitados pelo
  leitor/serviço em testes sintéticos; as fichas no navegador não equivalem a
  validação semântica de conteúdo real.

Foi observada a chave **nativa** navigation.backTo sem tradução no wizard local,
já existente na imagem. É um achado visual não bloqueante; textos do plugin foram
conferidos traduzidos. Não foi alterado o núcleo/locale do OJS para ocultá-lo.
Esse ponto deve ser conferido junto ao tema e idiomas da homologação representativa.

Não há sinal verde para implantação na revista a partir deste ensaio. O perfil informado foi reproduzido na Etapa 28. Restam arquivos/versão do tema
e configuração sanitizada para uma homologação equivalente,
sem acessar produção ou a VM Oracle nesta conversa.

## Atualização da Etapa 55

Plugin 0.35.0.0, motor 0.41.0: 131 verificações OJS nesta entrega
(104 nos 11 painéis, incluindo 26 do checklist; 27 no novo ciclo completo).
Um ensaio adicional de 26 verificações confirmou o runner final do checklist.
Os 21 modos específicos APA da Etapa 53 não foram reexecutados nesta etapa.
1.759 testes por PHP (8.4.27 e 8.1.27), 16 JavaScript, lint de 123 PHP,
compilação Python e schemas CLI aprovados. O registro humano é independente
da liberação APA. Evidências em evidence/stage55; limites operacionais e
ausência de homologação na instalação real permanecem.

## Atualização da Etapa 56

Plugin 0.36.0.0, motor 0.41.0: via humana vinculada para um subconjunto Word
`.doc`, sem leitor de texto/APA automático nem conversão exigida. 156 verificações
OJS aprovadas: 104 nos 11 painéis, 27 no ciclo DOCX e 25 no novo ciclo DOC.
O ensaio final DOC repetiu 25 verificações após ajuste da rechecagem limitada.
1.802 testes por PHP (8.4.27 e 8.1.27), 16 JavaScript, lint de 126 PHP, compilação
Python e schemas CLI aprovados. Persistem 462 avisos por execução PHP de fixtures
de estrutura, idênticos aos da Etapa 55; nenhum aviso novo nessa comparação.
Os 21 modos APA específicos não foram reexecutados. Conferência humana declarada
não prova abertura/semântica; variantes DOC e operação real continuam limitadas.
Evidências selecionadas em evidence/stage56; sem implantação na revista.
