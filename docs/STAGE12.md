# Etapa 12 — APA como condição de admissão

O responsável esclareceu em 12/11/2026 que submissões em ABNT não serão aceitas.
O motor 0.12.0 passa a registrar essa decisão na regra `references.apa7` 4.0.0.
A leitura e os critérios de detecção da Etapa 11 permanecem inalterados.

## Contrato

`references.apa7.evidence.admission_policy` e as evidências das verificações
de referências incluem:

- `policy_version`: `apa-required-2026-11-12`.
- `decision`: `not_accepted` se houver incompatibilidade comprovada com APA;
  `manual_review_required` nos demais casos.
- `automatic_progression_allowed`: sempre falso nesta etapa.
- `reason`: `proven_apa_incompatibility` ou `apa_compliance_not_established`.
- `blocking_components`: componentes da análise que comprovaram incompatibilidade.
- `ojs_enforcement_implemented` e `editorial_state_changed`: falsos.

O bloqueio cobre ABNT reconhecida e outras incompatibilidades APA comprovadas.
Ausência de documento, formato não reconhecido ou caixa alta isolada não viram
acusação de ABNT. Também não liberam automaticamente a submissão: revisão manual
é necessária. Aprovações parciais não são certificação completa nem aceite.

Não muda a quantidade de resultados, schemas ou entradas; o objeto de evidências
já admite esses campos. O código de saída zero da CLI significa relatório gerado,
nunca aceitação. Consumidores devem observar a política explícita do relatório.

## Limite da entrega

O plugin ainda é esqueleto e não intercepta o fluxo OJS. Portanto, a decisão
é calculada no motor, mas o impedimento efetivo na revista ainda não existe.
A integração deverá consumir a decisão no servidor, validar a versão/hash do
documento e a autorização editorial, e impedir avanço tanto diante de
`not_accepted` quanto de revisão pendente. Não basta ocultar um botão.
O fluxo de revisão e liberação humana ainda precisa ser implementado e testado
em homologação. Nenhum estado editorial foi modificado; sem produção ou VM Oracle.
