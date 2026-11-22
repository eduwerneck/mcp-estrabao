# MCP Estrabão

Apoio à triagem editorial preliminar da Revista Estrabão (OJS 3.3.0.20).
Estado atual: [acesso contextual à triagem — Etapa 64](docs/STAGE64.md),
[balanço da triagem](docs/TRIAGE_COVERAGE.md) e
[próximas entregas](docs/PENDING_REQUIREMENTS.md).
As Etapas 1 a 63 implementam um motor PHP local, contratos JSON, testes sintéticos e
um adaptador experimental de bloqueio no wizard OJS (Etapa 13). O conjunto foi
validado no laboratório OJS isolado; o tema e a infraestrutura reais da revista
ainda não foram homologados. Não há certificação completa de APA ou servidor MCP.
Há leitura local limitada de DOCX e
verificação lexical de seções (docs/STAGE3.md), com resolução limitada de estilos
e herança de negrito/numeração (docs/STAGE4.md) e verificação de página e
tipografia declaradas (docs/STAGE5.md) e intervalo de extensão por tipo
com exclusões documentadas (docs/STAGE6.md). O inventário visual e o limite das
comunicações estão em docs/STAGE7.md; figuras ambíguas exigem revisão humana.
A sinalização de caixa alta em títulos e subtítulos está em docs/STAGE8.md,
com snapshot 1.1 opcional para subtítulos e compatibilidade com entradas 1.0.
A [Etapa 19](docs/STAGE19.md) orienta o preenchimento no OJS e reconhece títulos
e subtítulos combinados no Word, sem conversão automática.
A contagem de notas Word está em docs/STAGE9.md; a [Etapa 20](docs/STAGE20.md)
acrescenta ficha interna para conferir as duas linhas e indicadores de notas de fim.
A Etapa 18 amplia a leitura com tratamento de marcadores Word e perfil explícito
de texto parcial em documentos com imagens/tabelas (docs/STAGE18.md).
A pré-checagem autor–ano de referências e citações está em docs/STAGE10.md,
sem certificar APA 7 nem consultar fontes externas. A Etapa 11 amplia a detecção
de incompatibilidades com APA, padrões semelhantes a ABNT, itálicos e formato
de citações, com orientações de correção: [escopo e limites](docs/STAGE11.md).

## Executar localmente

Requer PHP CLI 7.4 ou superior, com JSON, hash e PCRE; DOCX requer DOM/libxml e ZIP. Sem Composer.
PHP 7.4 é alvo de compatibilidade do código e está fora de suporte: não expor
um ambiente com essa versão à internet. O runtime de homologação OJS precisa
ser escolhido conforme a distribuição exata e suas dependências.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json
php bin/desk.php tests/fixtures/complete.json
```

A CLI aceita um snapshot JSON local de até 1 MiB e produz um relatório em stdout.
Código de saída 0 significa relatório gerado, não conformidade nem aprovação da
submissão; entradas inválidas retornam 1 sem relatório. Não usa rede nem escreve
relatórios em disco. Use exclusivamente fixtures sintéticas nesta etapa.

A [Etapa 21](docs/STAGE21.md) amplia a detecção de referências com formatos
mistos e listas pessoais fora do padrão APA 7, mantendo bloqueio por incompatibilidade comprovada.

A [Etapa 22](docs/STAGE22.md) acrescenta a data de submissão ao box de publicação
no HTML de artigos, validada no tema padrão OJS local.

A [Etapa 23](docs/STAGE23.md) confere se a seção CRediT tem texto e mantém
taxonomia, atribuição e veracidade como revisão humana separada.

A [Etapa 24](docs/STAGE24.md) compara as declarações de fomento no Word e no
campo de agências do OJS, distinguindo vazio, ausência explícita e apoio institucional.

A [Etapa 25](docs/STAGE25.md) acrescenta ficha interna de ética e localiza possíveis
menções no Word, mantendo aplicabilidade e documentação para revisão humana.

A [Etapa 26](docs/STAGE26.md) confere a declaração de uso ou não uso de IA no
manuscrito e mantém ferramenta, finalidade e interação para revisão humana.

A [Etapa 27](docs/STAGE27.md) consolida a [homologação local](docs/HOMOLOGATION.md):
922 testes em cada versão PHP e 72 verificações no OJS real do laboratório.

A [Etapa 28](docs/STAGE28.md) verifica o perfil informado do Tema Padrão
(Noto Sans, #1E6292 e sem CSS adicional): 25 verificações em cinco tamanhos de tela.
Arquivos/versão reais e infraestrutura permanecem fora da homologação local.

A [Etapa 29](docs/STAGE29.md) amplia APA 7 para autoria composta e listas
abreviadas, com bloqueio e correção na entrada. O [mapa APA 7](docs/APA7.md)
registra cobertura e prioridades restantes.

A [Etapa 30](docs/STAGE30.md) confere citações com três ou mais autores e o
ponto de et al., preservando casos de desambiguação para conferência editorial.

A [Etapa 31](docs/STAGE31.md) amplia a correspondência institucional literal e
siglas declaradas, com ambiguidade para revisão e bloqueio de ABNT institucional
no subconjunto reconhecido.

A [Etapa 32](docs/STAGE32.md) confere a fonte efetivamente consultada nas citações
secundárias e bloqueia apud por decisão editorial explícita da Estrabão.

A [Etapa 33](docs/STAGE33.md) amplia modelos de referências: edição, editoria
e tipografia de capítulos e ordem de intervalos numéricos de páginas.

A [Etapa 34](docs/STAGE34.md) amplia o vínculo autor–ano para sobrenomes
compostos literais, preservando formas parciais e prosa para conferência.

A [Etapa 35](docs/STAGE35.md) confere anos explícitos agrupados, sufixos autor–ano
e ordem de obras em citações parentéticas no subconjunto seguro, sem escolher
obras ou atribuir identidades/letras automaticamente.

A [Etapa 36](docs/STAGE36.md) confere et al. ambíguo e a quantidade de autores
omitidos, aceitando as expansões adequadas no subconjunto seguro e preservando
identidade, duplicatas e contextos incompletos para revisão.

A [Etapa 37](docs/STAGE37.md) liga datas sem ano e no prelo nas citações e
referências reconhecidas, preservando sufixos e encaminhando estados divergentes
para conferência, sem atribuir datas ou certificar o estado de publicação.

A [Etapa 38](docs/STAGE38.md) reúne a regressão integrada e um ciclo de
correção com as ampliações APA combinadas, preservando motor e plugin.

A [Etapa 39](docs/STAGE39.md) confere itálico nos modelos declarados de teses,
dissertações e relatórios numerados, preservando a conferência da fonte e
encaminhando descrições incompletas para revisão.

A [Etapa 40](docs/STAGE40.md) confere calendários e estrutura de URLs HTTP
informadas nas referências, reconhece datas de consulta no subconjunto e mantém
a identificação da página/site e os dados reais para revisão, sem acessar links.

A [Etapa 41](docs/STAGE41.md) confere modelos declarados de dados e software:
título em itálico, versão/descrição/fonte sem itálico e metadados incertos para
revisão, preservando a grafia oficial de siglas e produtos.

A [Etapa 42](docs/STAGE42.md) confere metadados de tradução e publicação
original, preservando a revisão de edição/tipo e protegendo anos originais e
citações com barra contra ausência ou ordenação presumidas.

A [Etapa 43](docs/STAGE43.md) orienta uma referência por Enter no metadado do
OJS e avisa sobre possíveis referências concatenadas, no formulário do autor e
na publicação. A orientação é local, sem edição/salvamento ou bloqueio automático.

A [Etapa 44](docs/STAGE44.md) amplia a ordem bibliográfica do DOCX para
cronologia de autorias declaradas idênticas e comparação segura de prefixos,
preservando acentos/partículas e deixando empates e grafias incertas para revisão.

## Componentes

- `plugins/generic/estrabao/`: esqueleto baseado na API legada do OJS 3.3.0.20.
- `src/Contracts/`: contratos PHP de snapshot, regra e resultado.
- `src/Rules/`: presença trilíngue, ORCID, marcadores e extensão dos resumos,
  quantidade de palavras-chave, seções DOCX e pré-checagem autor–ano; APA completa pendente.
- `src/Document/`: leitura DOCX local com limites ZIP/XML, sem extração em disco.
- `src/Reporting/`: relatório versionado com evidências e hash do snapshot.
- `contracts/`: JSON Schemas 2020-12 de entrada e saída.
- `mcp/`: limites e requisitos do serviço opcional, ainda não implementado.
- `tests/`: testes e dados sintéticos, sem dependências do OJS.

Consulte [arquitetura](docs/ARCHITECTURE.md), [contratos](docs/CONTRACTS.md), [regras da Etapa 2](docs/STAGE2.md), [DOCX e seções da Etapa 3](docs/STAGE3.md),
[estilos e herança da Etapa 4](docs/STAGE4.md),
[página e tipografia da Etapa 5](docs/STAGE5.md),
[extensão do manuscrito da Etapa 6](docs/STAGE6.md),
[inventário visual da Etapa 7](docs/STAGE7.md),
[caixa de títulos da Etapa 8](docs/STAGE8.md),
[notas de rodapé da Etapa 9](docs/STAGE9.md),
[referências e citações da Etapa 10](docs/STAGE10.md),
[APA e padrões ABNT da Etapa 11](docs/STAGE11.md),
[desenvolvimento e instalação](docs/DEVELOPMENT.md),
[requisitos pendentes](docs/PENDING_REQUIREMENTS.md),
[diretrizes editoriais recebidas](docs/EDITORIAL_POLICY.md),
[modelo definitivo de devolutiva editorial](docs/DESK_RESPONSE.md),
[geração local de minutas da Etapa 16](docs/STAGE16.md),
[devolutiva no painel da Etapa 17](docs/STAGE17.md),
[cobertura DOCX da Etapa 18](docs/STAGE18.md),
[procedimentos futuros do laboratório](docs/LAB.md) e
[resultados de validação](docs/VALIDATION.md).

A Etapa 12 registra submissão não aceita diante de incompatibilidade comprovada
com APA 7, incluindo padrões ABNT reconhecidos. Casos indeterminados exigem
revisão, sem liberação automática. O adaptador da [Etapa 13](docs/STAGE13.md) intercepta a confirmação quando
explicitamente configurado; a [Etapa 14](docs/STAGE14.md) acrescenta revisão/liberação humana por versão.
A [Etapa 15](docs/STAGE15.md) exercitou o wizard em OJS 3.3.0.20 local isolado
e corrigiu traduções e a exibição do motivo do bloqueio. Implantação e
homologação no tema/infraestrutura da revista continuam pendentes.
O motor local não altera estados nem envia mensagens.
Ver [política de admissão](docs/STAGE12.md). Sem acesso à produção.

A [Etapa 45](docs/STAGE45.md) confere erros inequívocos de p./pp. e intervalos
de páginas, com orientação e bloqueio. Candidatos a citações diretas recebem
conferência privada de fonte/localizador e limite de 40 palavras; transcrições
internas ficam protegidas de correções automáticas.

A [Etapa 46](docs/STAGE46.md) amplia localizadores para citações narrativas
junto ao ano ou depois de uma transcrição claramente delimitada, com bloqueio
por erro comprovado e revisão privada de candidatos a citações diretas.

A [Etapa 47](docs/STAGE47.md) amplia a ordenação entre autor único e coautorias
com prefixos de autoria declarados iguais, orientando a correção e bloqueando
inversões comprovadas, sem modificar a ordem interna dos autores de cada obra.

A [Etapa 48](docs/STAGE48.md) consolida a regressão integrada: 695 verificações
no OJS local, com correção combinada, nova análise e revisão humana antes da
conclusão. Produto e versões preservados; não certifica APA integral.

A [Etapa 49](docs/STAGE49.md) confere títulos e sufixos de conjuntos completos
da mesma autoria e ano nos modelos reconhecidos, com bloqueio por erro comprovado
e revisão para títulos duplicados, artigos iniciais e comparações incertas.

A [Etapa 50](docs/STAGE50.md) amplia títulos com artigos iniciais quando todas
as leituras com/sem prefixo concordam, sem inferir idioma da referência nem
modificar títulos. Leituras divergentes permanecem para revisão humana.

A [Etapa 51](docs/STAGE51.md) amplia títulos/subtítulos com pontuação delimitada
quando a comparação literal permite uma decisão segura, preservando siglas,
nomes próprios e texto. Diferenças de pontuação não são normalizadas por aproximação.

A [Etapa 52](docs/STAGE52.md) confere a ordem de datas completas declaradas
para a mesma autoria e acrescenta orientação editorial privada. Dados ausentes,
precisão mista, edição e sufixos dessas datas permanecem para revisão humana.

A [Etapa 53](docs/STAGE53.md) consolida 910 verificações no OJS local com
títulos, subtítulos, artigos iniciais e datas completas no ciclo de correção,
sem mudar o produto ou dispensar revisão humana.

O [balanço da Etapa 54](docs/STAGE54.md) distingue bloqueio APA na
confirmação, diagnósticos para devolutiva e verificações humanas/ausentes. Registra
as prioridades propostas para triagem consolidada, Word .doc, idiomas e homologação
isolada; não altera o bloqueio nem publica mudanças na revista.

A [Etapa 55](docs/STAGE55.md) registra a conferência editorial de 12 grupos
fora de APA, com estados explícitos, responsável e histórico privado vinculado
à análise. Não substitui a liberação APA nem cria novos bloqueios automáticos.

A [Etapa 56](docs/STAGE56.md) acrescenta conferência humana explícita para
um subconjunto de Word `.doc`, com download vinculado e histórico da revisão.
Não exige conversão nem extrai texto/valida APA automaticamente nesses arquivos.
Variantes não identificadas continuam pendentes, sem rejeição por extensão.

A [Etapa 57](docs/STAGE57.md) aplica a conferência de citações a inglês e espanhol
com idioma declarado, delimitação lexical e ambiguidades para revisão humana.
Não aprova traduções de seções obrigatórias nem certifica APA integral.

A [Etapa 58](docs/STAGE58.md) liga autor–ano a uma data completa reconhecida
quando há candidata pessoal única, sem escolher obras duplicadas, completar
datas ou atribuir sufixos. Ligação técnica não substitui a conferência da fonte.

A [Etapa 59](docs/STAGE59.md) reconhece `s.f.` e `en prensa`, preservando sufixos
e dúvidas de estado/edição. A ficha privada orienta a conferência na fonte,
sem gerar ano, traduzir o arquivo ou confirmar publicação automaticamente.

A [Etapa 60](docs/STAGE60.md) consolida cobertura, revisão humana e lacunas,
com plano de pacote/instalação limpa isolada. É documental: mantém motor 0.44.0
e plugin 0.39.0.0, sem novos bloqueios, deploy ou publicação do ambiente.

A [Etapa 61](docs/STAGE61.md) entrega pacotes reproduzíveis e testa instalação
nova e atualização em cópias limpas OJS 3.3.0.20, sem montar o checkout. O
[guia](docs/INSTALLATION.md) descreve os dois componentes, configuração privada
e preservação/invalidação das revisões. Produção e VM Oracle seguem fora do escopo.

A [Etapa 62](docs/STAGE62.md) testa serviço não root, gravações simultâneas,
locks e restauração completa em outra cópia isolada. O [guia operacional](docs/OPERATIONS.md)
registra a preservação dos dados, os testes nativos e os limites que permanecem,
incluindo a ausência de transação conjunta entre upload e confirmação final.

A [Etapa 63](docs/STAGE63.md) percorre o wizard nativo desde o início, com
upload/revisão de DOCX pelo autor, ABNT retida, minuta privada, correção e
liberação APA explícita. A conclusão registra recebimento, sem aceite/publicação
automáticos; [procedimento e limites](docs/EDITORIAL_REHEARSAL.md).

A [Etapa 64](docs/STAGE64.md) acrescenta **Visualizar triagem** às ações da lista
de submissões para a gerência editorial, com destino por manuscrito e explicação
para submissões fora da etapa de confirmação. Plugin 0.40.0.0; motor 0.44.0.
