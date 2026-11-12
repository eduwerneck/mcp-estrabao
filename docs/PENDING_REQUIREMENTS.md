# Requisitos a confirmar e próximos trabalhos

| Área | Informação ou implementação pendente |
| --- | --- |
| Política editorial | Diretrizes recebidas e registradas em EDITORIAL_POLICY.md; marcadores e equivalentes aprovados; confirmar vigência, convenção editorial de contagem e Condições para submissão. |
| Locales | Confirmar códigos configurados no OJS e mapeamento para pt_BR/en_US/es_ES, inclusive variantes regionais. |
| Autoria | Extração completa e ordenada de autores da publicação corrente; política para ausência de autoria e múltiplas afiliações. |
| ORCID | Política de normalização e verificação autenticada de identidade; autorização explícita antes de consulta externa. |
| Manuscrito Word | Revista sem template; aceita .doc e .docx. Estrutura e limites por tipo registrados em EDITORIAL_POLICY.md. Implementar extração e confirmar títulos equivalentes e variações admitidas. |
| Extração segura | Limites de arquivo e descompactação, proteção contra ZIP bomb, caminhos ZIP, XML externo, arquivos corrompidos; falhas serão indeterminadas. |
| APA 7 | Escopo determinístico aprovado, exemplos sintéticos e tratamento humano de ambiguidades e correspondências de citações. |
| OJS | Adaptador de Publication/Submission/Author e keywords na versão exata; ponto de invocação, publicação corrente, permissões e isolamento por revista. |
| Plugin | Validar instalação, ciclo de registro, locales, habilitação/desabilitação em OJS 3.3.0.20 real; implementar UI e apresentação escapada das evidências. |
| Relatórios | Persistência privada, retenção, identidade do operador, vínculo à revisão da submissão, auditoria de acesso e decisão humana. |
| Rejeição preliminar | Critérios editoriais e autorização de editor; futura recomendação não executará rejeição, mensagens nem alteração de estado. |
| MCP opcional | Versão do protocolo, transporte, autenticação/autorização, ferramentas de consulta/relatório, escopos, limites e auditoria. Nenhum servidor nesta etapa. |
| Homologação | Runtime compatível, extensões, banco, TLS e recursos do laboratório; não houve acesso à VM. |

As regras da Etapa 2 verificam também marcadores e extensão dos resumos e
quantidade de palavras-chave, conforme [STAGE2.md](STAGE2.md). As
[diretrizes fornecidas](EDITORIAL_POLICY.md) registram os demais requisitos.
Nenhuma política ausente foi preenchida por suposição. Os limites da arquitetura
original continuam válidos. Alterações editoriais exigem aprovação explícita
em trabalho futuro e não são parte desta implementação.
