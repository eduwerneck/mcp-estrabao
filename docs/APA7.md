# Cobertura atual de APA 7 — Etapa 60

Balanço em 20/11/2026, base 6a117b1 (merge PR #60, Etapa 59). Motor 0.44.0,
plugin 0.39.0.0. Regras: APA 29.0.0, correspondência 16.0.0, formatos 18.0.0 e
citações 14.0.0. Esta etapa é documental e preserva comportamento/versões.

A política recebida exige APA na submissão e não aceita ABNT. A instrução de
18/11/2026 também veta apud em secundárias reconhecidas; esse veto é editorial,
não uma afirmação de proibição universal na APA. Erro comprovado exige correção;
inconclusivo exige revisão humana, sem aprovação automática. O bloqueio atua na
confirmação; a decisão editorial de rejeição e o envio de desk são separados.
O autor pode salvar rascunho.

O adaptador foi exercitado na confirmação do wizard OJS, com opt-in ativo.
API/importações e operação real não foram homologadas. Liberação APA da versão
não é aceite editorial, publicação ou aprovação da triagem inteira. Ver
[balanço da triagem](TRIAGE_COVERAGE.md) e [homologação](HOMOLOGATION.md).

## Mapa entregue e lacunas

| Frente | Cobertura atual | Limite/pendência |
| --- | --- | --- |
| ABNT/formatos mistos | Padrões compostos de posição de autor/data, separadores, cidade/editora e rótulos reconhecidos | Sem identificação universal por aproximação; maiúsculas isoladas não provam ABNT |
| Autoria pessoal | Nomes simples/compostos e partículas delimitadas, iniciais, separadores, lista até 20; estrutura abreviada 19 + reticências + último | Identidade, autoria/ordem/total reais, sufixos e nomes fora da gramática |
| Citações pt/en/es | Idioma declarado; chaves literais, conjunções, espaços, et al., anos e grupos reconhecidos | Delimitação lexical, outras prosa/siglas/estruturas; não infere idioma nem aprova traduções de seções editoriais |
| Instituições/siglas | Prefixos e aliases explicitamente declarados no subconjunto | Primeira apresentação, identidade, instituições fora da gramática; não deriva siglas por iniciais |
| Secundárias/apud | Liga só a fonte consultada; como citado em/as cited in/como se citó en nos perfis; apud reconhecido bloqueia | Não exige original não consultado; transcrições e variantes fora da gramática exigem revisão |
| Datas completas | Auditoria de calendário/ordem e matcher pessoal com data EN/PT completa válida e candidata única | Sem inferir dia, identidade, edição ou letras; coortes e precisões mistas ficam humanas; instituições/meses espanhóis ainda limitados |
| Datas especiais | n.d./s.d./s.f. e in press/no prelo/en prensa; equivalência interna, sufixos de ausência de data suportados e ficha privada | Não confirma ausência de data, aceite/publicação ou edição; variantes como en prensa-a ficam fora da gramática |
| Correspondência | Citações reconhecidas para referências; ausência só em subconjunto fechado | Identidade/fonte lida ficam humanas; o sentido referências para citações não é verificado exaustivamente |
| Tipos e itálicos | Subconjuntos de artigos, capítulos/editoria/edição, teses/dissertações, relatórios numerados e dados/software declarados | Outros modelos, graus/fontes, relatórios sem número e descrições fora da gramática |
| Tradução/republicação | Metadados declarados e cronologia original/edição; protege ano original na correspondência | Identificar obra/edição efetivas, citações com barra e formatos fora do subconjunto |
| Ordenação/títulos/sufixos | Prefixos decisivos, autor único/coautorias, títulos/sufixos em coortes seguras; artigos iniciais/pontuação com leitura concordante | Empates, duplicatas, colação, iniciais distintas, obras/estados/edições ambíguos; não atribui letras ou reescreve títulos |
| DOI/URL | Formato do DOI informado e estrutura de URL/calendários reconhecidos | Ausência de DOI não reprova; não verifica existência, metadados ou disponibilidade dos links |
| Transcrições/localizadores | p./pp. e intervalos no vínculo seguro; candidatos delimitados parentéticos/narrativos e orientação privada | Fidelidade, fonte real, blocos/layout, fontes sem paginação e formas fora da gramática |
| DOCX | Leitura limitada e perfis parciais com regras condicionadas à cobertura | Não equivale à renderização/extração integral de qualquer documento |
| .doc | Via humana vinculada em subconjunto CFB v3, com declaração de leitura e APA | Não aplica detecção automática de APA/ABNT ao texto; variantes não reconhecidas ficam pendentes, sem exigir conversão ou rejeitar a extensão |

Fontes principais: [References](../src/Rules/References.php),
[ReferenceAnalysis](../src/Document/ReferenceAnalysis.php),
[ReferenceStyleAudit](../src/Document/ReferenceStyleAudit.php),
[ordenação](../src/Document/ReferenceOrder.php),
[transcrições](../src/Document/CitationLocator.php),
[bridge](../plugins/generic/estrabao/AdmissionBridge.inc.php).
Ampliações recentes: [Etapa 56](STAGE56.md), [57](STAGE57.md), [58](STAGE58.md) e
[59](STAGE59.md). Limites específicos desses documentos continuam válidos.

## O que falta e como acompanhar

Não há certificação integral de APA. A vigência pública das diretrizes ainda
precisa ser confirmada; não foi feita nova consulta normativa externa para este
balanço. Identidade, veracidade, obra consultada e fidelidade permanecem humanas.

As prioridades de instalação/atualização em cópia limpa, operação isolada e ensaio
editorial estão em [PENDING_REQUIREMENTS.md](PENDING_REQUIREMENTS.md). Exceções
normativas e variantes de leitura serão priorizadas por impacto/exemplo sintético,
sem prazo de encerramento de toda APA. Pacote e ensaio não são deploy autorizado.

As contagens da [Etapa 59](evidence/stage59/validation.json) são evidência anterior:
2.120 testes por PHP, 16 JavaScript, 1.138 verificações OJS. Não foram reexecutados
nesta etapa documental. Manifesto e verificações atuais em [STAGE60.md](STAGE60.md).

O [histórico até a Etapa 59](APA7_HISTORY.md) preserva as ampliações, tentativas
de consulta normativa registradas e referências anteriores. Não é a fila vigente.
