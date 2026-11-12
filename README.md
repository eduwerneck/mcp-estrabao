# MCP Estrabão

Apoio à triagem editorial preliminar da Revista Estrabão (OJS 3.3.0.20).
As Etapas 1 a 4 implementam um motor PHP local, contratos JSON, testes sintéticos e
um esqueleto de plugin. Não há integração funcional com submissões do OJS,
análise APA ou servidor MCP. Há leitura local limitada de DOCX e
verificação lexical de seções (docs/STAGE3.md), com resolução limitada de estilos
e herança de negrito/numeração (docs/STAGE4.md).

## Executar localmente

Requer PHP CLI 7.4 ou superior, com JSON, hash e PCRE; DOCX requer DOM/libxml e ZIP. Sem Composer.
PHP 7.4 é alvo de compatibilidade do código e está fora de suporte: não expor
um ambiente com essa versão à internet. O runtime de homologação OJS precisa
ser escolhido conforme a distribuição exata e suas dependências.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json
```

A CLI aceita um snapshot JSON local de até 1 MiB e produz um relatório em stdout.
Código de saída 0 significa relatório gerado, não conformidade nem aprovação da
submissão; entradas inválidas retornam 1 sem relatório. Não usa rede nem escreve
relatórios em disco. Use exclusivamente fixtures sintéticas nesta etapa.

## Componentes

- `plugins/generic/estrabao/`: esqueleto baseado na API legada do OJS 3.3.0.20.
- `src/Contracts/`: contratos PHP de snapshot, regra e resultado.
- `src/Rules/`: presença trilíngue, ORCID, marcadores e extensão dos resumos,
  quantidade de palavras-chave e seções DOCX; referências ainda pendentes.
- `src/Document/`: leitura DOCX local com limites ZIP/XML, sem extração em disco.
- `src/Reporting/`: relatório versionado com evidências e hash do snapshot.
- `contracts/`: JSON Schemas 2020-12 de entrada e saída.
- `mcp/`: limites e requisitos do serviço opcional, ainda não implementado.
- `tests/`: testes e dados sintéticos, sem dependências do OJS.

Consulte [arquitetura](docs/ARCHITECTURE.md), [contratos](docs/CONTRACTS.md), [regras da Etapa 2](docs/STAGE2.md), [DOCX e seções da Etapa 3](docs/STAGE3.md),
[estilos e herança da Etapa 4](docs/STAGE4.md),
[desenvolvimento e instalação](docs/DEVELOPMENT.md),
[requisitos pendentes](docs/PENDING_REQUIREMENTS.md),
[diretrizes editoriais recebidas](docs/EDITORIAL_POLICY.md),
[procedimentos futuros do laboratório](docs/LAB.md) e
[resultados de validação](docs/VALIDATION.md).

O sistema não rejeita submissões, não envia mensagens, não altera estados
editoriais e não acessa a revista em produção. Todo relatório exige revisão humana.
