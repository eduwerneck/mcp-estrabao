# Etapa 54 — balanço das diretrizes e prioridades

Base 35f8732 (merge PR #54), em 19/11/2026. Motor 0.41.0, plugin 0.34.0.0,
política, contratos e comportamento preservados. Entrega documental solicitada
após a regressão integrada da Etapa 53.

## Resultado

[TRIAGE_COVERAGE.md](TRIAGE_COVERAGE.md) cruza as diretrizes recebidas com regras,
adaptação OJS e evidências atuais, distinguindo:

1. Bloqueio adicional de APA/diretrizes de citações na confirmação do wizard,
   retenções técnicas e revisão de versão do arquivo.
2. Diagnósticos/minuta dos demais critérios, sem presumir que constituem novos
   impedimentos automáticos na entrada.
3. Conferências humanas, capacidades ausentes e prioridades propostas.

A assinatura de revisão APA não aprova toda a triagem. Há lacunas práticas para
.doc, aceito pela política mas sem leitor/liberação equivalente; citações EN/ES
e entradas com datas completas no matcher. SI, resolução/conteúdo de imagens,
editabilidade e controles posteriores à aprovação não são certificados pelas
rotinas existentes. Recomendações não viram exigências adicionais.

A prioridade proposta é registrar a revisão editorial consolidada dos itens
fora de APA; depois, encaminhamento verificável de .doc, cobertura de idioma/
datas e homologação operacional isolada. Não amplia o gate, não define novos
bloqueios, não instala, ativa ou publica o plugin.

## Verificação e limites

- Leitura da política documentada, DefaultRules, regras específicas, reader,
  snapshot da publicação e serviços de admissão/minuta/revisão.
- Inventário CLI de snapshot sintético sem DOCX/tipo: 51 instâncias e 32 IDs
  distintos. Não é porcentagem de cobertura nem lista exaustiva de todos os
  resultados condicionais; manuscript.headings.format é um exemplo ausente.
- Saída CLI conforme o schema, links relativos/estrutura das tabelas conferidos,
  JSON das evidências válido e diff sem erros.
- Manifesto de 70 arquivos de produto/contratos idênticos à base e à versão de
  produto da Etapa 53. Não há mudança funcional para exigir nova regressão.
- Os 1716 testes por PHP, 16 JavaScript e 910 verificações OJS citados no balanço
  são da Etapa 53; não foram reexecutados ou anunciados como testes desta etapa.

Evidências em evidence/stage54: inventário limitado de regras, manifesto,
constatações vinculadas aos fontes e verificação documental. Não incorporam
manuscritos reais, credenciais ou configurações privadas.

A vigência pública da política e as Condições para submissão não foram
confirmadas. Sem nova consulta normativa externa, acesso a produção, VM Oracle,
comunicação com autores, execução de serviços, publicação do ambiente ou deploy.
Próximas prioridades são propostas, não implementação nem requisito novo vigente.
