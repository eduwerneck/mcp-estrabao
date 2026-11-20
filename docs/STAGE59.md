# Etapa 59 — datas especiais em espanhol

Base ac26b55 (merge PR #59), em 20/11/2026. Motor 0.44.0, plugin 0.39.0.0.
Regras references.apa7 29.0.0, citation_matching 16.0.0, recognized_formats 18.0.0
e citation_format 14.0.0; perfil de padrões v13. Contratos JSON e política
editorial preservados.

## Resultado

O parser reconhece `s.f.` e `en prensa` nos locais de data suportados de citações
e referências. São **marcadores declarados**, não constatações sobre a obra:

| Forma lida | Chave interna | Conferência que permanece humana |
| --- | --- | --- |
| s.f.; s. f.; variantes de caixa | undated, como n.d./s.d. | Se realmente falta data na fonte e se a edição é a consultada |
| s.f.-a, s.f.-b | undated-a, undated-b | Identidade das obras e correção dos sufixos declarados |
| en prensa | inpress, como in press/no prelo | Situação real da publicação e edição consultada |

A chave é usada internamente para comparação literal. Texto, metadados e arquivo
não são traduzidos ou reescritos; não se calcula um ano a partir da data atual,
da submissão ou da publicação OJS. Não se atribuem letras nem se confirma que uma
obra foi efetivamente publicada, aceita ou consultada.

O reconhecimento funciona nos perfis pt/en/es já suportados: uma referência
pode conter o marcador espanhol sem que o idioma do manuscrito seja inferido
dele. A delimitação e as demais limitações linguísticas da Etapa 57 permanecem.
A expansão também alcança cabeçalhos institucionais nos padrões existentes,
sem verificar identidade/autenticação ou introdução correta de siglas.

## Decisões e limites

- Chave única pode ficar ligada, mas formato da citação/data e admissão APA
  continuam para conferência humana; não existe liberação automática.
- n.d./s.d./s.f. equivalentes preservam o sufixo informado. Não se traduz a
  referência nem se conclui que o marcador é verdadeiro por essa equivalência.
- Ano numérico versus ausência de data/no prelo, estados divergentes e sufixos
  diferentes da mesma autoria ficam ambíguos, sem provar referência ausente.
- Rótulos ambíguos como Data/Date/Fecha não são presumidos como autores de
  uma obra ausente. Nomes reais coincidentes com esses rótulos podem exigir revisão.
- Duplicatas, nomes parciais, contexto incompleto, transcrições e limitações de
  leitura preservam as ressalvas existentes. Datas especiais da mesma autoria
  não fundamentam prova numérica forte de desambiguação.
- Chave realmente ausente só falha no subconjunto fechado já exigido. Erros
  locais comprovados, como `&` narrativo numa citação ligada, continuam bloqueando.
- Secundárias continuam ligando somente a fonte consultada. Apud permanece
  proibido pela instrução editorial existente, inclusive com esses marcadores.
- `sf`, `s.f`, `enprensa`, `en prensa-a`, `en revisión` e outras variantes fora
  da gramática não são completadas ou convertidas por aproximação.

A auditoria de formato reconhece os novos cabeçalhos, mas não certifica o estado
real da publicação. A correspondência com datas completas da Etapa 58 e seu
calendário EN/PT permanecem iguais. Meses espanhóis, outras precisões de data,
autorias/edições e formas fora do subconjunto continuam pendentes.

## Interface e compatibilidade

A área editorial ganha uma ficha privada de datas especiais, traduzida nos três
locales. Orienta a verificar estado/edição na fonte, conservar sufixos e não gerar
um ano. Ela aparece quando citações **ou referências reconhecidas** têm estado
especial, inclusive se a citação traz um ano numérico divergente. Os contadores
não dependem das 32 amostras exibidas e não copiam o texto do manuscrito.

A minuta continua revisável em português. Pendências não são convertidas em
infrações ao autor e a ficha não é inserida automaticamente na carta. A nova
versão do motor invalida liberações anteriores por assinatura, preservando seu
histórico; é necessária nova revisão. O checklist não APA permanece separado.
A via `.doc` continua manual, sem extração/verificação automática do conteúdo.

## Validação

Testes PHP incluem variantes reconhecidas, equivalências/sufixos, divergências,
duplicatas, instituições, secundárias/apud, transcrições, erros bloqueantes,
contadores além das amostras e recusa de assinatura do motor/regra anteriores.
O caso antigo de s.f. fora da gramática foi atualizado; o limite de variantes
não reconhecidas continua coberto por outros marcadores.

O ciclo OJS cria novos rascunhos para os dois marcadores nos três idiomas e
exercita a ficha privada, bloqueio, minuta, divergência, equivalência, correção,
revisão humana, novo arquivo com bytes iguais, CSRF e conclusão. Preserva os
registros e o ponteiro anteriores, sem apagar os novos dados sintéticos.

```sh
php tests/run.php
# Somente no laboratório interno existente e após preflight:
PYTHONPATH=tests/lab python tests/lab/integrated.py --with-apa-regression --with-submission-cycle --with-doc-cycle --with-language-cycle --with-date-match-cycle --with-spanish-date-cycle
```

Contagens e resultados selecionados em
[evidence/stage59/validation.json](evidence/stage59/validation.json). Inclui a
regressão dos 21 modos APA e dos ciclos anteriores. Nenhuma consulta externa
bibliográfica, envio de mensagens, produção/VM Oracle, manuscrito real,
publicação do ambiente ou deploy. Não certifica APA integral nem a instalação
real da revista. Próxima entrega proposta: balanço da cobertura e pendências.
