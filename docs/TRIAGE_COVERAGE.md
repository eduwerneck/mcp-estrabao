# Balanço da triagem — cobertura editorial preservada até a Etapa 62

Conferência documental em 20/11/2026, base 6a117b1 (merge PR #60, Etapa 59).
Motor **0.44.0**, plugin **0.39.0.0**, laboratório OJS **3.3.0.20**.
Esta etapa consolida o estado entregue; não muda regras, versões ou política.

As diretrizes são as recebidas do responsável e registradas em
[EDITORIAL_POLICY.md](EDITORIAL_POLICY.md). Sua vigência pública e as Condições
para submissão ainda precisam de confirmação na homologação. Não foi consultada
produção ou VM Oracle. O balanço histórico está na [Etapa 54](STAGE54.md);
as entregas [55](STAGE55.md), [56](STAGE56.md), [57](STAGE57.md),
[58](STAGE58.md) e [59](STAGE59.md) explicam as ampliações posteriores.

## Três decisões independentes

1. **Confirmação da submissão:** o adaptador acrescenta retenção de APA/diretrizes
   de citações ao wizard. Incompatibilidade comprovada exige correção; inconclusivo
   exige revisão humana da versão elegível. Não existe aprovação automática.
2. **Conferência editorial não APA:** o gerente registra estados de 12 grupos,
   responsável e histórico, vinculados à análise. Não concede/revoga a liberação
   APA nem transforma diagnóstico em novo bloqueio automático.
3. **Aceite e publicação:** não são concedidos por nenhum desses registros. A
   minuta é revisável/copíavel e não é enviada automaticamente.

As validações nativas do OJS permanecem exigidas. O comportamento foi exercitado
no wizard, com plugin habilitado e admission_gate ativo; não foi homologado para
API, importações, quickSubmit ou outros canais.

## O que impede a confirmação

| Situação | Resultado do adaptador | Limite |
| --- | --- | --- |
| Incompatibilidade APA reconhecida | Não aceita concluir; apresenta correções aplicáveis | Não identifica toda ABNT ou toda infração APA por aproximação |
| Padrão ABNT composto reconhecido | Exige correção; aprovação humana não supera a falha | Caixa alta isolada não prova ABNT |
| Apud em secundária reconhecida | Bloqueia por decisão editorial explícita | Não confundir menção explicativa/transcrição com infração; não é veto universal atribuído à APA |
| Fonte analisável sem incompatibilidade comprovada | Permanece pendente até revisão humana expressa | Ausência de erro não certifica APA integral |
| Fonte sem vínculo/revisão elegível, ausente ou ambígua | Retém por limitação técnica/contexto | Não acusar infração editorial por falta de leitura |
| Novo arquivo/IDs, idioma, motor/regra diferentes da assinatura | Liberação antiga não autoriza a versão atual | Concorrência real de uploads ainda precisa de homologação operacional |
| Revogação, permissão insuficiente, POST/CSRF inválido ou revisão antiga | Não permite usar/gravar a liberação | Preserva controles nativos e vínculo à revista/submissão |

Fontes: [References](../src/Rules/References.php),
[AdmissionBridge](../plugins/generic/estrabao/AdmissionBridge.inc.php),
[serviço de revisão](../plugins/generic/estrabao/AdmissionReviewService.inc.php) e
[hook do wizard](../plugins/generic/estrabao/EstrabaoPlugin.inc.php).
O escopo de autoria, citações, formatos, ordenação e datas está em [APA7.md](APA7.md).

## Formatos, idiomas e datas

| Frente entregue | Capacidade atual | Pendência humana/técnica |
| --- | --- | --- |
| DOCX | Leitura ZIP/XML limitada, estilos suportados e perfis parciais | Não certifica leitura integral, renderização, todos os objetos/estilos ou fidelidade |
| Word .doc | Via humana vinculada para um subconjunto CFB v3 identificável; declaração de abertura/leitura e APA, download e histórico | Sem texto/APA automático. CFB v4, WordDocument em mini-stream, proteção/variantes não reconhecidas e limites continuam pendentes de encaminhamento; não se exige conversão por mudança da política |
| PDF | Sem triagem automática do plugin | Leitura humana de um PDF fornecido não implementa leitor nem muda a diretriz de Word |
| Citações pt/en/es | Idioma declarado, autoria–ano e formato nos limites lexicais da Etapa 57 | Não infere idioma; não aprova traduções das seções editoriais nem resolve toda prosa/sigla/estrutura |
| Datas completas | Matcher pessoal projeta ano declarado de dia/mês/ano válidos EN/PT, com candidata única; auditoria cronológica anterior permanece | Coortes, sufixos, edição original, instituições e outras precisões permanecem limitados; meses espanhóis não foram adicionados |
| n.d./s.d./s.f. e in press/no prelo/en prensa | Equivalência interna de estados declarados; conserva sufixos suportados e ficha privada | Não confirma ausência de data/publicação/aceite/edição; não gera ano, atribui letras ou reescreve a referência |

Fontes: [DocxReader](../src/Document/DocxReader.php),
[LegacyDocSource](../plugins/generic/estrabao/LegacyDocSource.inc.php),
[ReferenceSections](../src/Document/ReferenceSections.php),
[ReferenceAnalysis](../src/Document/ReferenceAnalysis.php) e
[calendário](../src/Document/WebReference.php).
O vínculo da revisão APA considera o vínculo do arquivo/IDs, idioma, motor/regra e
política; não é uma aprovação de metadados/autoria. O checklist tem vínculo
mais amplo com o snapshot/tipo/autoria disponíveis e continua independente.

Uma fonte pode ser suficiente para uma regra e insuficiente para outra; não
converter perfil parcial em ausência comprovada de conteúdo.

## Diagnósticos e minuta, sem novo impedimento automático por esses critérios

| Exigência recebida | Cobertura entregue | Conferência restante |
| --- | --- | --- |
| Título/resumo/palavras-chave pt/en/es | Presença nos metadados disponíveis; resumo até 250 palavras e 3–4 palavras-chave; rótulos aprovados | Locales reais, tradução/qualidade, convenção de palavra; não exigir todos os sinônimos de um grupo ou unicidade não prevista |
| ORCID de todos os autores | Presença/formato/checksum da coleção disponível | Identidade/autenticação, completude da autoria e afiliações |
| Estrutura e seções | Rótulos portugueses e tipo informado; negrito/numeração no subconjunto de estilos | Conteúdo substantivo, exceções e traduções não aprovadas; sem template exigido |
| Extensão | Artigos 4.000–8.000; comunicações 1.000–3.500; exclusões técnicas com contexto suficiente | Contagem oficial Word/convenção editorial e línguas/documentos fora da segmentação |
| A4, margens 3 cm, TNR 12, espaçamento 1,5 | Propriedades declaradas suportadas | Renderização/exceções; não substituir diretriz da revista por espaçamento genérico APA |
| Títulos/subtítulos sem caixa alta integral | Sinalização e orientação em metadados e manuscrito | Nomes/siglas e CSS real; sem reescrita automática |
| Visuais | Inventário e máximo de 6 nas comunicações quando o mínimo comprovado supera o limite | Não aplicar máximo aos artigos; figuras compostas, 300 dpi, formatos, autoria/conteúdo e editabilidade exigem inspeção |
| Notas | Contagem Word até 3 no inventário suportado; indicadores/ficha de notas de fim | Duas linhas no documento paginado, notas manuais e extração parcial |
| CRediT | Seção/conteúdo reconhecidos e orientação | Papéis, atribuição, contribuição significativa e veracidade; preservar anonimização |
| Fomento | Consistência Word/supportingAgencies se a disponibilidade do campo ao autor estiver confirmada | Agências/projetos e natureza do apoio; não inferir omissão nem exigir declaração universal de ausência |
| Ética | Possíveis menções/localizador e ficha | Aplicabilidade, parecer/autenticidade e declaração; não inferir dispensa |
| IA | Declaração de uso ou não uso no texto, em padrões limitados | Finalidade, ferramenta, interação e veracidade; sem detector ou duplicação obrigatória OJS |
| Referências no metadado OJS | Orientação/aviso local de uma referência por Enter | Sem edição/salvamento automático, exigência de linha em branco extra ou certificação Crossref/APA |

Fontes: [DefaultRules](../src/Rules/DefaultRules.php),
[snapshot OJS](../plugins/generic/estrabao/SubmissionSnapshot.inc.php),
[notas](STAGE20.md), [CRediT](STAGE23.md), [fomento](STAGE24.md),
[ética](STAGE25.md), [IA](STAGE26.md), [títulos](STAGE19.md) e
[metadado das referências](STAGE43.md).

## Registro da conferência editorial

A [Etapa 55](STAGE55.md) registra metadados, resumos/palavras-chave, autoria/ORCID,
estrutura, extensão, apresentação/títulos, visuais, notas, CRediT, fomento, ética e
IA. Estados: pendente, conferido pelo editor e necessita ajustes; não se aplica
somente em visuais, notas, fomento e ética, por decisão humana.

Não há preenchimento pelo motor. Último evento correspondente à análise fornece
os estados; mudança do vínculo volta a pendente e conserva histórico. O vínculo
inclui arquivo/metadados/publicação/tipo, versões e digest da autoria disponível.
Responsável/horário e estados são privados, com limites de 100 eventos/256 KiB;
não há anexos de prova, comentários livres ou certificação da veracidade.

Fonte: [EditorialReviewService](../plugins/generic/estrabao/EditorialReviewService.inc.php)
e [armazenamento separado](../plugins/generic/estrabao/EditorialReviewStore.inc.php).
A revisão APA é independente; registrar necessita ajustes não revoga APA, e
marcar todos conferidos não supera ABNT/apud comprovados nem conclui a submissão.

## Coberturas ausentes e controles posteriores

SI, resolução/conteúdo real das imagens, editabilidade e fidelidade/localização
na fonte não são certificados. Comprovação de revisão profissional e controle de
novos autores após aprovação pertencem à fase editorial correspondente, sem
regra específica implementada para reprovar a submissão inicial. Não há servidor
MCP com transporte/autenticação/ferramentas implementado.

DOI ausente não reprova; DOI informado pode ter formato incompatível. Gestores
bibliográficos são recomendação; Zenodo não é exigência universal. Não há máximo
numérico de autores nem template oficial. Recomendações não viram requisitos novos.

## Evidência e próximos passos

Os **2.120 testes por PHP, 16 JavaScript e 1.138 verificações OJS** são da
[Etapa 59](evidence/stage59/validation.json), não uma execução da Etapa 60.
O código/contratos permanecem idênticos à base e ao manifesto de produto testado.
Esta auditoria não homologa a instalação real nem certifica APA integral.

1. Etapa 61 executada: pacotes e instalação/atualização em duas cópias limpas
   isoladas OJS 3.3.0.20; [resultados e limites](STAGE61.md) e [guia](INSTALLATION.md).
   A cobertura editorial desta auditoria permanece igual; isso não homologa a revista.
2. Etapa 62 executada: permissões, concorrência na revisão e backup/recuperação
   em duas cópias sintéticas; [resultados e limites](STAGE62.md). Não fornece
   transação conjunta entre upload e confirmação final ou certificação real.
3. Etapa 63 proposta: ensaio editorial completo nessa cópia e balanço de resultados.
4. Exceções APA, meses espanhóis e variantes .doc: priorizar separadamente por
   impacto e exemplos sintéticos, sem promessa de certificação completa.

Um PR por entrega. Planejamento não instala, ativa ou publica nada na revista.
