# Requisitos a confirmar e próximos trabalhos

| Área | Informação ou implementação pendente |
| --- | --- |
| Política editorial | Diretrizes recebidas e registradas em EDITORIAL_POLICY.md; marcadores e equivalentes aprovados; confirmar vigência, convenção editorial de contagem e Condições para submissão. |
| Locales | Confirmar códigos configurados no OJS e mapeamento para pt_BR/en_US/es_ES, inclusive variantes regionais. |
| Autoria | Extração completa e ordenada de autores da publicação corrente; política para ausência de autoria e múltiplas afiliações. |
| ORCID | Política de normalização e verificação autenticada de identidade; autorização explícita antes de consulta externa. |
| Manuscrito Word | Revista sem template; aceita .doc e .docx. Estrutura e limites por tipo registrados em EDITORIAL_POLICY.md. Extração DOCX e seções implementadas com cobertura limitada (STAGE3.md); Etapa 18 acrescenta normalização de marcadores Word e perfil de texto parcial para imagens/tabelas simples. `.doc`, títulos equivalentes e conteúdos complexos continuam pendentes. |
| Extração segura | Limites ZIP/XML e falhas indeterminadas implementados em STAGE3.md; estilos/herança dos títulos resolvidos no subconjunto de STAGE4.md; Etapa 18 amplia cobertura de texto Word com figuras/tabelas simples e registra exclusões, sem certificar leitura integral. Listas vinculadas e scripts complexos continuam pendentes. Página e tipografia declaradas implementadas no subconjunto de STAGE5.md; intervalo de extensão com exclusões em STAGE6.md, sem convenção editorial oficial de palavra. |
| Figuras/tabelas | Inventário e limite das comunicações implementados no subconjunto de STAGE7.md; confirmar unidades de figuras compostas, função editorial, associação de legendas e ampliar formatos/cobertura. |
| Caixa de títulos | Escopo confirmado para metadados e manuscrito; sinalização local em STAGE8.md, sem reescrita. Etapa 19 acrescenta orientação nos formulários OJS e reconhecimento de título/subtítulo combinados no Word. Homologação no tema/CSS da revista e tratamento semântico de siglas continuam pendentes. |
| Notas de rodapé | Contagem de até 3 notas Word implementada no subconjunto de STAGE9.md; Etapa 20 acrescenta indicadores de notas de fim e ficha interna para conferir duas linhas; o total visual continua exigindo documento paginado e revisão humana. Ampliar vínculos/cobertura, definir tratamento editorial de notas manuais/de fim e registrar revisão visual auditável. |
| Ética | Etapa 25 acrescenta ficha interna e localizador limitado de possíveis menções no Word. Aplicabilidade, aprovação, autenticidade/número do parecer e declaração formal continuam para revisão humana; sem bloqueio automático ou inferência de dispensa. |
| Fomento | Etapa 24 compara Word e supportingAgencies quando a disponibilidade do campo na submissão é confirmada. Sem presumir fomento ou omissão de apoio financeiro; equivalência de agências/projetos, variantes e declarações fora dos padrões exigem revisão. Configuração real da revista e homologação permanecem pendentes. |
| CRediT | Etapa 23 distingue seção vazia, presença de texto e revisão humana de taxonomia/atribuição/veracidade. Não certifica contribuições; ampliar cobertura de estruturas, idiomas e registro de conferência sem quebrar anonimização. |
| Datas no HTML | Etapa 22 implementa Submetido em antes de Publicado no mesmo box, com data do fluxo OJS, validada no tema padrão do laboratório. Identificar tema/versão reais e homologar markup, formato e dados históricos antes de implantar. |
| APA 7 | Etapa 11 detecta incompatibilidades concretas, padrões semelhantes a ABNT, itálicos de formatos reconhecidos, DOI informado e formato de citações (STAGE11.md). Etapa 21 acrescenta separadores de autoria pessoal, limite de lista não abreviada e rótulos de periódico em formatos mistos. Certificação completa, autoria institucional/composta, citações secundárias, confirmação bibliográfica e formatos fora do subconjunto continuam pendentes. Não consulta DOI nem reprova sua ausência. |
| OJS | Adaptador de Publication/Submission/Author e keywords na versão exata; ponto de invocação, publicação corrente, permissões e isolamento por revista. |
| Plugin | Validar instalação, ciclo de registro, locales, habilitação/desabilitação em OJS 3.3.0.20 real; implementar UI e apresentação escapada das evidências. |
| Relatórios | Persistência privada, retenção, identidade do operador, vínculo à revisão da submissão, auditoria de acesso e decisão humana. |
| Rejeição preliminar | Política APA autorizada em 12/11/2026: incompatibilidade comprovada implica submissão não aceita; indeterminado exige revisão sem avanço automático (STAGE12.md). Adaptador opt-in do wizard implementado na Etapa 13, validado só com dublês. Etapa 14 acrescenta interface de gerente, liberação por versão e registro privado, validados com dublês. Etapa 15 exercitou o wizard em OJS 3.3.0.20 local real e corrigiu feedback/traduções. Homologação no tema/infraestrutura da revista, auditoria operacional, concorrência com uploads e outros canais permanecem pendentes; não ativar em produção. |
| MCP opcional | Versão do protocolo, transporte, autenticação/autorização, ferramentas de consulta/relatório, escopos, limites e auditoria. Nenhum servidor nesta etapa. |
| Homologação | Runtime compatível, extensões, banco, TLS e recursos do laboratório; não houve acesso à VM. |

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

## Ordem de retomada aprovada

1. Leitura DOCX: ampliação entregue na Etapa 18; limites restantes documentados.
2. Títulos/subtítulos: orientação nos formulários e reconhecimento ampliado entregues na Etapa 19; limites documentados.
3. Notas de rodapé: contagem revisada e ficha de conferência entregues na Etapa 20; contagem visual automática e registro da revisão permanecem pendentes.
4. APA 7: formatos mistos e listas pessoais ampliados na Etapa 21; instituições, nomes compostos e exceções continuam pendentes.
5. Datas no HTML: indicador entregue na Etapa 22 para o tema padrão; homologação com tema/configuração reais permanece pendente.

CRediT recebeu a primeira verificação de conteúdo na Etapa 23. A Etapa 24 acrescenta consistência de fomento Word/OJS. A Etapa 25 acrescenta conferência de ética sem inferir aplicabilidade. Declaração de IA segue depois, com o tratamento de não uso ainda a confirmar
ou ausência a partir de fonte incompleta. Um item/PR por vez; o histórico de
devolutivas não foi priorizado sobre essas pendências.
