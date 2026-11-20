# Requisitos a confirmar e próximos trabalhos

O balanço atual está em [TRIAGE_COVERAGE.md](TRIAGE_COVERAGE.md) (Etapa 54).
Ele separa diagnóstico e bloqueio, confirma o estado no código e propõe prioridades.
A tabela abaixo preserva o histórico de requisitos; a ordem histórica de etapas
não é uma autorização para ampliar automaticamente o bloqueio de admissão.

| Área | Informação ou implementação pendente |
| --- | --- |
| Política editorial | Diretrizes recebidas e registradas em EDITORIAL_POLICY.md; marcadores e equivalentes aprovados; confirmar vigência, convenção editorial de contagem e Condições para submissão. |
| Locales | Confirmar códigos configurados no OJS e mapeamento para pt_BR/en_US/es_ES, inclusive variantes regionais. |
| Autoria | Snapshot lê a coleção de autores disponível na publicação corrente para conferir ORCID. Completude/ordem real, nomes, múltiplas afiliações e contribuição significativa continuam para conferência; não há limite numérico editorial de autores. |
| ORCID | Política de normalização e verificação autenticada de identidade; autorização explícita antes de consulta externa. |
| Manuscrito Word | Revista sem template; aceita .doc e .docx. Estrutura e limites por tipo registrados em EDITORIAL_POLICY.md. Extração DOCX e seções implementadas com cobertura limitada (STAGE3.md); Etapa 18 acrescenta normalização de marcadores Word e perfil de texto parcial para imagens/tabelas simples. Etapa 56 oferece via humana vinculada para um subconjunto Word `.doc`; leitura automática, variantes não identificadas, títulos equivalentes e conteúdos complexos continuam pendentes. |
| Extração segura | Limites ZIP/XML e falhas indeterminadas implementados em STAGE3.md; estilos/herança dos títulos resolvidos no subconjunto de STAGE4.md; Etapa 18 amplia cobertura de texto Word com figuras/tabelas simples e registra exclusões, sem certificar leitura integral. Listas vinculadas e scripts complexos continuam pendentes. Página e tipografia declaradas implementadas no subconjunto de STAGE5.md; intervalo de extensão com exclusões em STAGE6.md, sem convenção editorial oficial de palavra. |
| Figuras/tabelas | Inventário e limite das comunicações implementados no subconjunto de STAGE7.md; confirmar unidades de figuras compostas, função editorial, associação de legendas e ampliar formatos/cobertura. |
| Caixa de títulos | Escopo confirmado para metadados e manuscrito; sinalização local em STAGE8.md, sem reescrita. Etapa 19 acrescenta orientação nos formulários OJS e reconhecimento de título/subtítulo combinados no Word. Homologação no tema/CSS da revista e tratamento semântico de siglas continuam pendentes. |
| Notas de rodapé | Contagem de até 3 notas Word implementada no subconjunto de STAGE9.md; Etapa 20 acrescenta indicadores de notas de fim e ficha interna para conferir duas linhas; o total visual continua exigindo documento paginado e revisão humana. Ampliar vínculos/cobertura, definir tratamento editorial de notas manuais/de fim e registrar revisão visual auditável. |
| IA | Etapa 26 registra uso ou não uso obrigatório no manuscrito, sem duplicação OJS. Confere presença em padrões limitados e acrescenta ficha interna; veracidade, finalidade e interação continuam humanas. Não usa detector ou presume ausência por falta de marcador. |
| Ética | Etapa 25 acrescenta ficha interna e localizador limitado de possíveis menções no Word. Aplicabilidade, aprovação, autenticidade/número do parecer e declaração formal continuam para revisão humana; sem bloqueio automático ou inferência de dispensa. |
| Fomento | Etapa 24 compara Word e supportingAgencies quando a disponibilidade do campo na submissão é confirmada. Sem presumir fomento ou omissão de apoio financeiro; equivalência de agências/projetos, variantes e declarações fora dos padrões exigem revisão. Configuração real da revista e homologação permanecem pendentes. |
| CRediT | Etapa 23 distingue seção vazia, presença de texto e revisão humana de taxonomia/atribuição/veracidade. Não certifica contribuições; ampliar cobertura de estruturas, idiomas e registro de conferência sem quebrar anonimização. |
| Datas no HTML | Etapa 22 implementa Submetido em antes de Publicado no mesmo box, com data do fluxo OJS, validada no tema padrão do laboratório. Etapa 28 verifica o perfil informado (Tema Padrão, Noto Sans, #1E6292, sem CSS adicional) em cinco telas locais. Arquivos/versão reais, formato e dados históricos permanecem pendentes antes de implantar. |
| APA 7 | Etapa 11 detecta incompatibilidades concretas, padrões semelhantes a ABNT, itálicos de formatos reconhecidos, DOI informado e formato de citações (STAGE11.md). Etapa 21 acrescenta separadores de autoria pessoal, limite de lista não abreviada e rótulos de periódico em formatos mistos. Etapa 29 amplia autoria composta, partículas, estrutura de listas abreviadas e os padrões ABNT com esses nomes, com orientação e bloqueio. Etapa 30 confere três ou mais autores e ponto de et al. em citações ligadas, preservando desambiguação. Etapa 31 acrescenta instituições por prefixos reconhecidos, siglas declaradas e ABNT institucional composta; primeira apresentação, identidade e ambiguidades seguem humanas. Etapa 33 amplia capítulos/editoria, edições explícitas e intervalos de páginas nos modelos reconhecidos. Etapa 34 amplia a correspondência literal de sobrenomes compostos, sem presumir identidade ou equivalência de formas parciais. Etapa 35 confere anos explícitos, sufixos declarados e ordem de citações no subconjunto seguro; atribuição de letras e ambiguidades continuam humanas. Etapa 36 amplia et al. colidente, omissão de pelo menos dois autores e nomes suficientes em contexto seguro; iniciais, duplicatas e contexto incompleto seguem humanos. Etapa 37 liga datas sem ano e no prelo e preserva sufixos declarados; divergências e estado real de publicação exigem revisão. Etapa 39 acrescenta modelos declarados de teses/dissertações e relatórios numerados, com itálico de título/descrição/fonte e dados semânticos para conferência. Etapa 40 confere calendários reconhecidos e sintaxe HTTP, sem acessar links ou impor campos a toda referência com URL. Tipo de página/site e necessidade de consulta seguem humanos. Etapa 41 acrescenta descrições explícitas de dados/software e itálico de título/versão/descrição/fonte, sem exigir campos indisponíveis; grafia oficial e dados reais ficam para revisão. Etapa 42 confere metadados de tradução/republicação e cronologia declarada e protege anos originais na correspondência; citações com barra, tipo e edição real continuam humanos. Etapa 44 amplia ordenação adjacente por prefixos seguros e anos numéricos da mesma autoria declarada, com empates e colação incerta para revisão. Etapa 45 confere p./pp. e intervalos inequívocos e sinaliza transcrições para revisão privada, sem presumir consulta às obras internas. Etapa 46 amplia narrativas com localizador junto ao ano ou após transcrição delimitada; variantes fora do modelo continuam humanas. Etapa 47 amplia ordenação entre autor único e coautorias e pelos coautores seguintes após prefixos pessoais idênticos, sem ordenar internamente autores de uma obra. Etapa 49 confere títulos/sufixos de conjuntos completos da mesma autoria/ano em subconjunto seguro; duplicatas, artigos iniciais e datas originais ficam protegidos. Etapa 50 amplia prefixos de artigos iniciais somente quando leituras alternativas concordam, sem presumir idioma da referência. Etapa 51 amplia subtítulos e pontuação delimitada por comparação literal segura, sem normalizar sinais, nomes ou siglas. Etapa 52 confere ordem de datas completas explícitas para a mesma autoria e orienta revisão privada de precisão, sufixos e edição, sem completar dados. Certificação completa, outras autorias institucionais, sufixos, formas secundárias fora da cobertura e confirmação bibliográfica e formatos fora do subconjunto continuam pendentes. Não consulta DOI nem reprova sua ausência. |
| OJS | Adaptador da publicação corrente e campos/keywords/ORCID opera no laboratório, com vínculo à revista/submissão e fonte conferida contra alterações. Configuração real dos campos/locales, completude dos dados e canais fora do wizard permanecem pendentes. |
| Plugin | Registro/versões locais e UI privada de revisão/minuta existem em OJS 3.3.0.20. Empacotamento, instalação em cópia limpa e ciclo de habilitação/desabilitação/configuração operacional ainda precisam de homologação específica; isso não autoriza instalação em produção. |
| Relatórios | Triagem/minuta locais e ledger privado de revisão APA por versão estão implementados no laboratório, com controles de acesso e sem envio automático. Etapa 55 acrescenta estados explícitos para 12 grupos não APA, responsável e histórico vinculado à análise. Provas anexas, retenção operacional e auditoria da instalação real permanecem pendentes. |
| Separação das referências no OJS | Etapa 43 acrescenta orientação e aviso local no campo citationsRaw, no wizard e na publicação, sem edição/salvamento automático. Reconhece indícios no subconjunto autor–ano; padrões incertos exigem conferência. Uma referência por Enter; linha em branco extra não é exigida nem garante identificação Crossref. Não acrescenta bloqueio/relatório CLI. Etapa 44 confere ordenação no DOCX em subconjuntos seguros; ordenação do metadado não é adicionada a este aviso local. |
| Rejeição preliminar | Política APA autorizada em 12/11/2026: incompatibilidade comprovada implica submissão não aceita; indeterminado exige revisão sem avanço automático (STAGE12.md). Adaptador opt-in do wizard implementado na Etapa 13, validado só com dublês. Etapa 14 acrescenta interface de gerente, liberação por versão e registro privado, validados com dublês. Etapa 15 exercitou o wizard em OJS 3.3.0.20 local real e corrigiu feedback/traduções. Homologação no tema/infraestrutura da revista, auditoria operacional, concorrência com uploads e outros canais permanecem pendentes; não ativar em produção. |
| MCP opcional | Versão do protocolo, transporte, autenticação/autorização, ferramentas de consulta/relatório, escopos, limites e auditoria. Nenhum servidor nesta etapa. |
| Homologação | Etapa 53 reúne 910 verificações locais em 31 suítes e ciclo de correção; Etapa 28 reproduz o perfil informado do Tema Padrão. Isso não homologa a instalação da revista, pacote/ciclo operacional, campos/locales reais, ARM64, TLS/proxy, concorrência de uploads ou canais alternativos. Produção e VM Oracle fora do escopo. |

As regras da Etapa 2 verificam também marcadores e extensão dos resumos e
quantidade de palavras-chave, conforme [STAGE2.md](STAGE2.md). As
[diretrizes fornecidas](EDITORIAL_POLICY.md) registram os demais requisitos.
Nenhuma política ausente foi preenchida por suposição. Os limites da arquitetura
original continuam válidos. Alterações editoriais exigem aprovação explícita
em trabalho futuro e não são parte desta implementação.

## Devolutiva consolidada

Modelo aprovado em DESK_RESPONSE.md e gerador local implementado na Etapa 16.
Etapa 17 integra a geração/cópia ao painel e lê os metadados da publicação
autorizada; validação local somente com registros sintéticos. Homologação no
ambiente/tema da revista e um fluxo específico de envio revisado continuam pendentes. Aprovar modelo/gerar minuta
não autoriza comunicação automática aos autores.

## Ordem de retomada aprovada — histórico

1. Leitura DOCX: ampliação entregue na Etapa 18; limites restantes documentados.
2. Títulos/subtítulos: orientação nos formulários e reconhecimento ampliado entregues na Etapa 19; limites documentados.
3. Notas de rodapé: contagem revisada e ficha de conferência entregues na Etapa 20; contagem visual automática e registro da revisão permanecem pendentes.
4. APA 7: formatos mistos e listas pessoais ampliados na Etapa 21; autoria composta/listas abreviadas ampliadas na Etapa 29; instituições/siglas ampliadas na Etapa 31; citações secundárias e veto a apud ampliados na Etapa 32; modelos de artigos/obras autônomas/capítulos ampliados na Etapa 33; sobrenomes compostos literais ampliados na Etapa 34; anos/sufixos e citações múltiplas ampliados na Etapa 35; et al. e nomes adicionais ampliados na Etapa 36; marcadores sem data/no prelo ligados na Etapa 37; demais tipos, traduções, ordenação bibliográfica ampliada, datas completas/originais, formas não reconhecidas e exceções continuam pendentes. Etapa 38 reúne a regressão integrada e o ciclo de correção com APA combinada, sem ampliar a cobertura normativa. Etapa 39 amplia os modelos declarados de teses/dissertações e relatórios numerados. Etapa 40 acrescenta calendários e sintaxe HTTP com identificação de página/site para revisão. Etapa 41 acrescenta dados/software declarados. Etapa 42 amplia metadados de tradução/republicação e protege anos originais, sem normalizar citações com barra. Etapa 44 amplia ordenação em subconjuntos seguros. Etapa 45 amplia localizadores e candidatos a citações diretas em subconjunto seguro. Etapa 46 amplia citações narrativas com localizadores em subconjunto delimitado. Etapa 47 amplia ordenação entre listas pessoais em subconjunto seguro. Etapa 49 amplia títulos e sufixos em conjuntos completos reconhecidos. Etapa 52 amplia datas completas declaradas; sufixos dessas datas e precisão mista seguem humanos. Seguem artigos iniciais com leituras divergentes, pontuação decisiva/incerta e estruturas fora do modelo, duplicatas, datas especiais, iniciais diferentes, prefixos comuns de coautorias de comprimentos distintos, colação incerta, variantes fora do subconjunto, fidelidade e layout das transcrições. Etapa 48 consolida 695 verificações no OJS local com ciclo de correção combinado; cobertura normativa e homologação de produção continuam pendentes. Etapa 53 consolida a regressão atual (910 verificações locais), sem encerrar as lacunas normativas ou a homologação de produção. Prioridade atual conforme [APA7.md](APA7.md).
5. Datas no HTML: indicador entregue na Etapa 22 para o tema padrão; perfil informado validado localmente na Etapa 28; arquivos/versão e configuração reais permanecem pendentes.

CRediT recebeu a primeira verificação de conteúdo na Etapa 23. A Etapa 24 acrescenta consistência de fomento Word/OJS. A Etapa 25 acrescenta conferência de ética sem inferir aplicabilidade. A Etapa 26 acrescenta declaração de IA; confirmado uso ou não uso no manuscrito, sem duplicação OJS
ou ausência a partir de fonte incompleta. Um item/PR por vez; o histórico de
devolutivas não foi priorizado sobre essas pendências.

## Prioridades propostas após o balanço da Etapa 54

1. Registrar a conferência editorial consolidada dos critérios fora de APA,
   sem interpretar a liberação APA como aprovação de toda a triagem. Primeira
   entrega implementada na [Etapa 55](STAGE55.md), sem ampliar o bloqueio.
2. Definir e implementar um encaminhamento verificável para .doc, aceito pela
   política e ainda sem leitor/fluxo equivalente de liberação; não exigir conversão
   por mudança silenciosa da política.
3. Ampliar citações/correspondência EN/ES e datas completas no matcher, em
   entregas separadas, mantendo ambiguidades para revisão.
4. Preparar homologação operacional em cópia isolada representativa, sem produção
   ou VM Oracle. A validação atual é do laboratório, não da instalação real.
5. Priorizar outras exceções APA por impacto e exemplos sintéticos verificáveis.

Escopo, fontes e critérios de conclusão em [TRIAGE_COVERAGE.md](TRIAGE_COVERAGE.md).
Propostas documentais; nenhuma dessas mudanças foi implementada ou ativada pela
Etapa 54; a Etapa 55 implementa somente o registro privado do primeiro item. A política de novos impedimentos não APA precisa estar explícita antes
de qualquer ampliação funcional do adaptador.

## Atualização após a Etapa 55

A prioridade 1 recebeu sua primeira implementação: [STAGE55.md](STAGE55.md).
O histórico privado registra estados humanos por análise, incluindo notas,
CRediT, fomento, ética e IA. Não armazena provas anexas nem certifica o conteúdo
conferido. A liberação APA e a política de bloqueio continuam independentes.
As prioridades seguintes (.doc, idiomas/datas e homologação operacional)
continuam pendentes e devem ser tratadas em entregas separadas.

## Atualização após a Etapa 56

[STAGE56.md](STAGE56.md) entrega a primeira via humana para `.doc` identificável,
sem conversão exigida ou verificação automática de APA. CFB v4, mini-stream de
WordDocument, arquivos protegidos/não reconhecidos e outros limites continuam
pendentes de encaminhamento; a aceitação editorial de `.doc` foi preservada.
Citações/correspondência EN/ES, datas e homologação operacional seguem na fila.

## Atualização após a Etapa 57

[STAGE57.md](STAGE57.md) entrega a primeira cobertura de citações EN/ES por idioma
declarado, com referências lexicamente delimitadas e preservação de ambiguidades.
Seções editoriais traduzidas, títulos/estruturas fora do subconjunto, datas
completas no matcher e marcadores `s.f.`/`en prensa` continuam pendentes.
A próxima entrega prevista é correspondência com datas completas; a auditoria
cronológica já existente não implementa essa relação.

## Atualização após a Etapa 58

[STAGE58.md](STAGE58.md) entrega a primeira correspondência com datas completas
pessoais reconhecidas, preservando coortes ambíguas e sem atribuir letras.
Instituições com datas completas, meses espanhóis, precisão/edições fora da
gramática e sufixos não são resolvidos por aproximação. A próxima prioridade
proposta é datas especiais em espanhol (`s.f.`/`en prensa`), seguida do balanço
de cobertura e homologação isolada. As propostas posteriores não são entregas
já implementadas nem autorização para produção.

## Atualização após a Etapa 59

[STAGE59.md](STAGE59.md) entrega s.f./en prensa com equivalências internas e
conferência privada do estado/edição. A próxima etapa proposta é atualizar o
balanço APA/triagem, separando bloqueio, conferência humana e cobertura ausente.
Meses espanhóis, formatos/autorais/edições fora do subconjunto e variantes `.doc`
continuam pendentes. Instalação limpa e homologação operacional isolada continuam
planejadas; nenhuma entrega autoriza produção ou uso da VM Oracle.
