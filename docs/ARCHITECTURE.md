# Arquitetura inicial — MCP Estrabão

## Objetivo
Construir um plugin nativo para OJS 3.3.0.20 e um serviço MCP opcional para apoiar a triagem editorial preliminar da Revista Estrabão.

## Limites operacionais
- Ambiente de homologação separado da revista em produção.
- Nenhuma rejeição, comunicação a autores ou alteração de estado editorial sem aprovação explícita de editor autorizado.
- Resultados automáticos são recomendações com evidências, não decisões finais.
- Dados de submissões, tokens, credenciais e chaves privadas não entram no GitHub.
- Falhas de extração e verificações inconclusivas devem ser registradas como indeterminadas, não como não conformidades comprovadas.

## Componentes previstos
1. **Plugin OJS 3.3**: ponto de integração no fluxo editorial, autenticação/autorização e apresentação de relatórios.
2. **Motor de verificações**: regras versionadas, extração de metadados e documentos, evidências por regra e níveis de confiança.
3. **Servidor MCP opcional**: ferramentas estritamente delimitadas para consulta e geração de pré-relatórios, com controle de acesso e auditoria.
4. **Testes**: fixtures sintéticas em português, inglês e espanhol; testes unitários, de integração e de segurança.

## Verificações iniciais
- Título, resumo e palavras-chave em português, inglês e espanhol.
- Seções rotuladas exigidas pela política editorial vigente em cada resumo.
- ORCID de todos os autores, com distinção entre presença, formato e validação externa.
- Estrutura do manuscrito Word conforme as diretrizes por tipo de texto. A revista
  não possui template e aceita .doc e .docx (esclarecimento posterior à Etapa 1).
- Citações e referências APA 7: indícios e inconsistências com revisão humana para casos ambíguos.

## Próximos marcos
1. Congelar e documentar os requisitos editoriais e as regras exatas.
2. Definir o contrato de relatório JSON e os estados conforme / não conforme / indeterminado.
3. Implementar o esqueleto do plugin OJS e testes sem conexão com produção.
4. Provisionar laboratório OJS 3.3.0.20 na VM estrabao-lab e validar SSH, rede e custos.
5. Implementar integração MCP de leitura e relatórios; habilitar operações editoriais apenas após revisão e autorização.

## Implementação da Etapa 1

O motor local (`src/`) trabalha sobre snapshot normalizado independente do OJS,
com regras de presença trilíngue e ORCID. Regras que dependem de políticas ou de
extração ainda inexistente retornam indeterminado. Os contratos e sua semântica
estão em [CONTRACTS.md](CONTRACTS.md).

O plugin genérico em `plugins/generic/estrabao/` é somente um esqueleto baseado
na API efetiva de 3.3.0.20. Ainda não chama o motor nem consulta submissões.
O módulo `mcp/` documenta o serviço futuro; não expõe ferramentas.
Relatórios JSON registram evidências reduzidas, versões e hash do snapshot;
persistência protegida e auditoria de decisões humanas permanecem pendentes.

A [lista de requisitos pendentes](PENDING_REQUIREMENTS.md) distingue políticas
a confirmar de integrações ainda não implementadas. Não houve acesso à revista
em produção ou à VM `estrabao-lab`.

## Diretrizes recebidas após a Etapa 1

As [diretrizes editoriais fornecidas](EDITORIAL_POLICY.md) registram estrutura,
extensão, resumos e formatação sem exigir template. Esse registro distingue
obrigações, recomendações e exigências condicionais; não modifica as regras já
implementadas. As interpretações ainda pendentes permanecem explícitas.
