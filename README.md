# MCP Estrabão

Apoio à triagem editorial preliminar da Revista Estrabão (OJS 3.3.0.20).
As Etapas 1 a 18 implementam um motor PHP local, contratos JSON, testes sintéticos e
um adaptador experimental de bloqueio no wizard OJS (Etapa 13). Não há
integração homologada com submissões do OJS,
certificação completa de APA ou servidor MCP. Há leitura local limitada de DOCX e
verificação lexical de seções (docs/STAGE3.md), com resolução limitada de estilos
e herança de negrito/numeração (docs/STAGE4.md) e verificação de página e
tipografia declaradas (docs/STAGE5.md) e intervalo de extensão por tipo
com exclusões documentadas (docs/STAGE6.md). O inventário visual e o limite das
comunicações estão em docs/STAGE7.md; figuras ambíguas exigem revisão humana.
A sinalização de caixa alta em títulos e subtítulos está em docs/STAGE8.md,
com snapshot 1.1 opcional para subtítulos e compatibilidade com entradas 1.0.
A contagem de notas Word está em docs/STAGE9.md; linhas exigem renderização.
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
