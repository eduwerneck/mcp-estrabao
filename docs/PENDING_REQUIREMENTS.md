# Requisitos a confirmar e próximos trabalhos

| Área | Informação ou implementação pendente |
| --- | --- |
| Política editorial | Diretrizes recebidas e registradas em EDITORIAL_POLICY.md; marcadores e equivalentes aprovados; confirmar vigência, convenção editorial de contagem e Condições para submissão. |
| Locales | Confirmar códigos configurados no OJS e mapeamento para pt_BR/en_US/es_ES, inclusive variantes regionais. |
| Autoria | Extração completa e ordenada de autores da publicação corrente; política para ausência de autoria e múltiplas afiliações. |
| ORCID | Política de normalização e verificação autenticada de identidade; autorização explícita antes de consulta externa. |
| Manuscrito Word | Revista sem template; aceita .doc e .docx. Estrutura e limites por tipo registrados em EDITORIAL_POLICY.md. Extração DOCX e seções implementadas com cobertura limitada (STAGE3.md); `.doc`, títulos equivalentes e variações ainda pendentes. |
| Extração segura | Limites ZIP/XML e falhas indeterminadas implementados em STAGE3.md; estilos/herança dos títulos resolvidos no subconjunto de STAGE4.md; ampliar cobertura, listas vinculadas e scripts complexos em etapa futura. Página e tipografia declaradas implementadas no subconjunto de STAGE5.md; intervalo de extensão com exclusões em STAGE6.md, sem convenção editorial oficial de palavra. |
| Figuras/tabelas | Inventário e limite das comunicações implementados no subconjunto de STAGE7.md; confirmar unidades de figuras compostas, função editorial, associação de legendas e ampliar formatos/cobertura. |
| Caixa de títulos | Escopo confirmado para metadados e manuscrito; sinalização local em STAGE8.md, sem reescrita. Integração de formulários/CSS OJS e tratamento semântico de siglas continuam pendentes. |
| Notas de rodapé | Contagem de até 3 notas Word implementada no subconjunto de STAGE9.md; 2 linhas por nota ainda exige renderização/conferência humana. Ampliar vínculos/cobertura e definir tratamento de notas manuais e de fim. |
| Datas no HTML | Adiado conforme planejamento: acrescentar Submetido em acima do box Publicado, com data do fluxo OJS; definir tema e validar em laboratório antes de integrar. |
| APA 7 | Etapa 11 detecta incompatibilidades concretas, padrões semelhantes a ABNT, itálicos de formatos reconhecidos, DOI informado e formato de citações (STAGE11.md). Certificação completa, autoria institucional/composta, citações secundárias, confirmação bibliográfica e formatos fora do subconjunto continuam pendentes. Não consulta DOI nem reprova sua ausência. |
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
