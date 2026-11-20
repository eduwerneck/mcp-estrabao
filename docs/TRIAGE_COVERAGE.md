# Balanço da triagem e prioridades

Revisão documental em 19/11/2026, base 35f8732 (merge PR #54), após a Etapa 53.
Motor 0.41.0, plugin 0.34.0.0 e OJS de laboratório 3.3.0.20. Este balanço não
altera regras, versões, formulários ou a política de admissão.

A base editorial é o texto recebido do responsável e seus adendos em
[EDITORIAL_POLICY.md](EDITORIAL_POLICY.md). A vigência pública e as Condições
para submissão ainda precisam ser confirmadas; não houve acesso ao site de
produção ou à VM Oracle para este balanço. O histórico de entregas não substitui
a conferência do código atual.

Atualização posterior: a [Etapa 55](STAGE55.md), plugin 0.35.0.0, implementa
o registro privado da conferência dos critérios fora de APA. O balanço abaixo
preserva o diagnóstico da Etapa 54; a liberação APA continua independente.

Atualização posterior da [Etapa 56](STAGE56.md): há via humana para um
subconjunto `.doc` identificável, com declaração de leitura e revisão APA, sem
extração automática. A retenção técnica de outras fontes e variantes permanece.
As tabelas abaixo preservam o balanço histórico da Etapa 54.

Atualização posterior da [Etapa 57](STAGE57.md): citações EN/ES passam a ser
conferidas com idioma declarado e fronteiras lexicais documentadas. Isso não
aprova traduções de seções editoriais nem encerra as lacunas do matcher.

Atualização posterior da [Etapa 58](STAGE58.md): o matcher liga datas completas
pessoais reconhecidas a autor–ano em contexto único; ambiguidades/edições/sufixos
continuam humanos. Os limites do calendário e da auditoria de formato permanecem.

## Conclusão principal

**Diagnóstico no relatório não equivale a bloqueio na entrada.** O bloqueio
adicional do plugin na confirmação do wizard é de APA/diretrizes de citações.
Os outros critérios podem produzir não conformidades na minuta, mas não são
transformados por esse adaptador em condições automáticas de admissão.
As validações nativas do OJS continuam independentes; este quadro não as remove
nem certifica toda a configuração de campos da revista.

A liberação humana existente declara revisão de APA para a versão atual do
arquivo. Não registra aprovação integral de metadados, extensão, ética, fomento,
IA, CRediT ou apresentação visual. Não equivale a aceite editorial/publicação.

Evidência: [AdmissionBridge](../plugins/generic/estrabao/AdmissionBridge.inc.php)
avalia References; [AdmissionReviewService](../plugins/generic/estrabao/AdmissionReviewService.inc.php)
usa DefaultRules para gerar a minuta, mas salva liberação com apaReviewed.
[DefaultRules](../src/Rules/DefaultRules.php) reúne os demais diagnósticos.

## 1. O que impede a conclusão no adaptador atual

Condições: plugin habilitado, admission_gate ativado e confirmação pelo wizard
exercitado no laboratório. Não afirmar o mesmo para API, importações ou outros
canais não homologados.

| Situação | Comportamento atual | Limite |
| --- | --- | --- |
| Incompatibilidade APA comprovada | Não aceita a conclusão e apresenta correções reconhecidas | Abrange somente os modelos/padrões cobertos; não identifica toda ABNT por aproximação |
| Padrão ABNT reconhecido | Bloqueia; liberação humana não supera a falha | Caixa alta isolada não prova ABNT |
| Apud em citação secundária reconhecida | Bloqueia por regra editorial explícita | Menção explicativa fora da gramática não é infração; não é alegação de veto universal na APA |
| APA inconclusiva, com fonte legível | Mantém pendente até revisão humana expressa | Sem erro detectado não significa conformidade integral |
| Arquivo novo ou motor/regra diferentes da liberação | Liberação anterior deixa de valer | Não certifica concorrência de uploads em infraestrutura real |
| Fonte ilegível, arquivo ausente/ambíguo ou contexto sem vínculo verificável | Retém a conclusão; liberação não fica disponível quando falta análise revisável | Limitação técnica não vira acusação editorial nem rejeição por extensão |
| Revisão revogada, usuário sem permissão ou CSRF inválido | Não permite usar a liberação para concluir | Preserva controles nativos do OJS |

O detalhe APA está em [APA7.md](APA7.md): formatos, autoria, itálicos, DOI
informado, citações, localizadores, ordem, títulos/sufixos e datas completas são
**coberturas parciais**. O motor não consulta fontes nem autentica identidades.

## 2. O que é diagnosticado, sem novo bloqueio automático por esse critério

Não conformidade objetiva pode compor a devolutiva revisada pelo editor.
Inconclusivos ficam separados, sem acusação ao autor. A coluna abaixo descreve
o motor/minuta, não uma nova autorização de bloqueio na confirmação do wizard.

| Exigência documentada | Cobertura no relatório/minuta | O que permanece humano ou limitado |
| --- | --- | --- |
| Título, resumo e palavras-chave em pt/en/es | Presença nos dados disponíveis da publicação corrente | Locales reais, qualidade/tradução e disponibilidade da fonte; ausência no Word não prova ausência no OJS |
| Resumos até 250 palavras | Contagem técnica por idioma | Hífens e convenção editorial de palavra; fonte incompleta não prova excesso/ausência |
| Marcadores de resumo | Alternativas expressamente aprovadas nos três idiomas | Não adicionar sinônimos/plurais nem exigir todos os rótulos de um mesmo grupo |
| 3 a 4 palavras-chave por idioma | Quantidade reconhecida | Duplicatas são registradas, sem criar exigência de unicidade |
| ORCID de todos os autores | Presença, formato e checksum dos autores disponíveis | Identidade/autenticação não verificadas; coleção de autores precisa estar íntegra |
| Estrutura do manuscrito | Seções portuguesas reconhecidas, com tipo informado e leitura suficiente | Traduções das seções não aprovadas, títulos ambíguos e presença de conteúdo substancial |
| Seções não numeradas e em negrito | Numeração/negrito efetivos no subconjunto de estilos suportado | Heranças complexas e renderização; não exige template ou nomes de estilos Word |
| Artigos de 4.000 a 8.000 palavras; comunicações de 1.000 a 3.500 | Intervalo técnico com exclusões, tipo informado e segmentação portuguesa verificável | Convenção oficial de contagem, documentos parciais e outras línguas; não é contagem oficial do Word |
| A4, margens 3 cm, Times New Roman 12 e espaçamento 1,5 | Propriedades declaradas no subconjunto de seções/runs/estilos suportado | Layout visual e exceções; não impor espaçamento APA genérico diferente da diretriz editorial |
| Evitar títulos/subtítulos integralmente em caixa alta | Sinalização em metadados e manuscrito; orientação nos formulários | Siglas/nomes próprios, casos semânticos e CSS real; sem reescrita automática |
| Até 6 figuras/tabelas nas comunicações | Inventário estrutural e falha quando o mínimo comprovado supera 6 | Imagens/figuras compostas e unidades incertas; não aplicar esse máximo aos artigos |
| Até 3 notas de rodapé | Contagem de notas Word vinculadas no inventário suportado | Notas manuais/de fim e cobertura incompleta; duas linhas exigem paginação visual |
| Créditos (CRediT) | Seção/conteúdo reconhecidos e ficha interna | Taxonomia, atribuição, contribuição significativa e veracidade; preservar anonimização |
| Fomento Word/OJS | Conflitos comprovados em padrões limitados, se supportingAgencies estiver disponível ao autor | Equivalência agência/projeto e natureza financeira/institucional; não presume fomento nem exige declaração de ausência universal |
| Ética quando aplicável | Localizador de possíveis menções e ficha interna | Aplicabilidade, parecer/número/autenticidade ou declaração formal de não aplicabilidade; nunca inferir dispensa |
| Declarar uso ou não uso de IA no manuscrito | Presença em padrões limitados; ausência só com leitura suficiente; ficha interna | Finalidade permitida, ferramenta, interação e veracidade; não usa detector nem exige repetição no OJS |
| Referências separadas no metadado OJS | Orientação de uma referência por Enter e aviso local de possível colagem | Não valida APA/Crossref, não exige linha em branco extra e não salva/reorganiza o campo |

Fontes: [DefaultRules](../src/Rules/DefaultRules.php),
[SubmissionSnapshot](../plugins/generic/estrabao/SubmissionSnapshot.inc.php),
[estrutura](../src/Rules/ManuscriptStructure.php),
[formatação](../src/Rules/ManuscriptFormatting.php),
[extensão](../src/Rules/ManuscriptLength.php),
[visuais](../src/Rules/VisualCount.php),
[ORCID](../src/Rules/Orcid.php),
[notas](../src/Rules/Footnotes.php),
[fomento](STAGE24.md), [ética](STAGE25.md), [IA](STAGE26.md) e
[separação de referências](STAGE43.md).

A leitura DOCX pode ter perfil parcial. Cobertura suficiente para uma regra não
significa leitura integral para todas as outras: extensão, seções, notas e APA
têm condições próprias. Fonte ausente/incompleta produz indeterminado, sem ser
convertida em descumprimento editorial.

## 3. O que exige conferência ou ainda não tem verificação específica

| Exigência/capacidade | Estado atual | Consequência prática |
| --- | --- | --- |
| Word .doc, aceito pela política | Leitor automático somente .docx | Não anunciar suporte integral a Word. A retenção técnica do wizard não oferece revisão equivalente de fonte ilegível; falta um fluxo alternativo verificável, sem trocar a política por obrigação de converter |
| PDF | Sem triagem automática | Avaliação humana de arquivo fornecido não equivale a suporte do plugin nem altera a política de arquivo Word |
| Citações/referências em inglês e espanhol | Auditoria de formatos atende pt/en/es; correspondência e formato de citações são executados somente em pt | Não anunciar cobertura equivalente nas três línguas; revisão humana para as partes não analisadas |
| Referências com datas completas | Cronologia específica já conferida; matcher autor–ano não incorpora essas entradas | Exemplo integrado tem nove referências, sete reconhecidas pelo matcher e duas conferidas pela auditoria de datas; sufixos/contexto dessas entradas ficam humanos |
| SI | Sem regra específica de validação de unidades | Conferência editorial, sem acusação por ausência de detector |
| 300 dpi e formatos JPEG/TIFF/EPS | Inventário não verifica resolução/formato editorial das figuras | Inspeção das imagens; definição de mínimo/exato e dimensão de exibição ainda pendente |
| Tabelas/gráficos editáveis | Inventário não prova editabilidade | Conferir objetos e arquivos, sem inferir editabilidade por extensão |
| Títulos/autoria dentro de imagens | Sem inspeção específica de conteúdo de imagem | Conferência visual; ausência de metadado não prova ausência de autoria |
| Duas linhas por nota | Não há renderizador para medir linhas | Conferir no documento paginado; ficha interna não equivale a registro persistido de toda revisão visual |
| Revisão profissional após aprovação; sem novos autores depois | Sem controle específico de comprovação/histórico nessa fase | Planejar no estágio editorial correspondente, não reprovar a submissão inicial por requisito posterior |
| Rastreabilidade da revisão não APA | Há fichas/orientações, mas o registro de liberação é de APA | Não interpretar a assinatura da revisão APA como aprovação de todos os itens |
| Infraestrutura/tema/configuração reais | Homologação somente local, no OJS 3.3.0.20/Tema Padrão | Não ativar/publicar por causa do balanço; preparar ensaio isolado representativo |
| Servidor MCP | Não implementado | O nome do repositório não prova transporte, autenticação ou ferramentas MCP disponíveis |

Evidências: [DocxReader](../src/Document/DocxReader.php),
[References](../src/Rules/References.php), [inventário visual](STAGE7.md),
[notas](STAGE20.md), [ciclo integrado](STAGE53.md) e
[registro de revisão](../plugins/generic/estrabao/AdmissionReviewService.inc.php).

## 4. O que não deve virar exigência nova

DOI ausente não reprova: disponibilidade é recomendação, embora DOI informado
possa ter formato incompatível. Zotero/Mendeley são recomendações; Zenodo não é
obrigatório para toda submissão. Linguagem clara/inclusiva exige leitura humana.
Não há limite numérico de autores. Não exigir template, títulos de seção
traduzidos por suposição ou declaração universal de ausência de fomento.
O veto editorial de apud é o aprovado, no padrão reconhecido; não presumir quais
obras o autor efetivamente consultou.

## 5. Prioridades propostas para as próximas entregas

Estas são propostas de planejamento, não requisitos novos já publicados ou
mudanças já autorizadas no fluxo de admissão. Um PR por objetivo verificável.

| Prioridade | Entrega proposta | Critério concreto de conclusão |
| --- | --- | --- |
| 1 — esclarecer e registrar a triagem integral | Painel/checklist editorial consolidado dos critérios não APA, com fonte, escopo, pendência e registro por versão da análise | Ensaio sintético em que APA foi revisada, mas há metadado/estrutura/declaração pendente; interface não apresenta o manuscrito como integralmente aprovado e registra o que foi conferido. Não tornar todo item impeditivo silenciosamente |
| 2 — resolver o caminho dos formatos aceitos | Fluxo de conferência para .doc e demais fontes indisponíveis, compatível com a política Word e com controle de versão | Arquivo .doc aceito na política tem encaminhamento editorial explícito, sem certificação automática de leitura, sem conversão/envio externos silenciosos e sem liberar falha APA comprovada por outra via |
| 3 — ampliar cobertura de maior impacto na entrada | Correspondência/citações EN/ES e integração de datas completas ao matcher, em entregas separadas | Provas positivas/negativas de idioma, ano/sufixo, edição, ambiguidade e liberação; não reduzir data completa ao ano para fabricar identidade nem inventar traduções das seções |
| 4 — preparar homologação operacional isolada | Instalação/configuração do pacote em cópia sintética representativa, campos/locales, tema, permissões, arquivo atual e habilitação/desabilitação | Instalação reproduzível e retorno ao estado anterior no laboratório; distinguir testes de upload pela interface dos ensaios atuais via API local/DAO; produção/VM Oracle fora do escopo |
| 5 — ampliar exceções APA selecionadas | Escolher lacuna por impacto observado, não apenas continuar a lista de sinais/partículas | Exemplo sintético revisável, regra documentada, diagnóstico/devolutiva/bloqueio de falha comprovada, incerteza preservada e regressão pertinente |

A próxima implementação recomendada é a prioridade 1, mantendo explícito o
limite do bloqueio APA e registrando as demais conferências. Prioridades 2 e 3
são lacunas de uso relevantes antes de apresentar o conjunto como triagem geral.
Para qualquer novo impedimento por critério não APA, a regra de admissão precisa
ser definida expressamente; este documento não altera o comportamento atual.

## 6. Base de verificação deste balanço

O levantamento cruzou política recebida, regras atuais, adaptação OJS e evidências
da [Etapa 53](STAGE53.md): 1716 testes por PHP, 16 JavaScript e 910 verificações no
OJS local. São resultados daquela execução, não novos testes nesta etapa.

A inspeção CLI de snapshot sintético sem DOCX/tipo gerou 51 instâncias e 32 IDs
distintos. Isso não mede porcentagem de cobertura editorial: regras se repetem
por idioma/autor e algumas dependem da fonte/contexto. Por exemplo,
manuscript.headings.format é condicional e não aparece nesse inventário.
O manifesto comprova os 70 arquivos de produto/contratos idênticos à base.
Não houve nova consulta normativa externa, acesso a produção, VM Oracle,
comunicação com autores, instalação ou publicação do ambiente.
