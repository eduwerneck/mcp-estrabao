# Etapa 52 — ordem de datas completas declaradas

Base 8945865 (merge PR #52), em 19/11/2026. Motor 0.41.0, plugin 0.34.0.0,
APA 26.0.0 e formatos reconhecidos 17.0.0. Correspondência 13.0.0, formato de
citações 11.0.0 e contratos JSON preservados. Invalida liberações anteriores.

## Calendário e precisão

ReferenceDateOrder confere pares adjacentes com a mesma lista pessoal declarada
(sobrenomes, iniciais e ordem), quando ambas as referências apresentam ano,
mês e dia explícitos e válidos. Usa os nomes de meses portugueses/ingleses já
reconhecidos por WebReference e a validação de calendário existente. Acrescenta
publicationParts, sem alterar a validação anterior de URLs/datas de consulta.

Compara ano, mês e dia numéricos declarados, sem depender da ordem lexical dos
nomes dos meses. Inversão comprovada reprova os formatos/APA e bloqueia conclusão
e liberação com orientação específica para conferir datas, tipo de obra e edição.
Não confirma a data real da publicação nem se o tipo de obra exige mês/dia.
A conferência separa a ordem do calendário da aplicação bibliográfica da data.

Ano isolado, mês sem dia, mês desconhecido, data impossível, data igual e datas
com sufixos fora da gramática nova não recebem dia/mês presumidos nem ordem
fabricada. Coautorias/iniciais diferentes não constituem o mesmo autor declarado.
Não compara pares não adjacentes. Incerteza posterior não apaga uma inversão
independente comprovada de datas válidas.

## Sufixos, edição e revisão privada

Não atribui nem exige automaticamente letras para conjuntos com datas precisas.
full_date_suffix_review_required indica que esse conjunto exige revisão da
correspondência nas citações, do tipo de obra e da edição; a política explícita
é manual_review_for_precise_dates. A regra anterior de títulos/sufixos para
conjuntos de anos simples não é aplicada por aproximação a datas completas.

O painel editorial privado acrescenta Conferência de datas bibliográficas:
orientações fixas para confirmar a informação na fonte, avaliar a aplicabilidade
de mês/dia e conferir sufixos sem completar datas ausentes. Não copia nomes,
dias, meses, anos, URLs ou títulos para essas orientações. Escape e controles
de acesso existentes são preservados; não há mensagem externa automática.

O fallback de autoria reconhecida com data completa agora preserva o marcador
real de publicação original, em vez de depender do conteúdo da entrada técnica
substituta. Publicação original conhecida ou ambígua impede a nova comparação
naquela autoria: o motor não escolhe a edição consultada para decidir a ordem.
Não modifica, reorganiza ou salva referências, citações ou manuscritos.

## Evidência e limites

Relatório acrescenta full_date_order_status, full_date_pairs_checked, pares de
índices em full_date_order_samples, política de sufixos e orientações privadas.
As partes numéricas do calendário permanecem internas. Até 16 amostras, com
limites anteriores de entradas, autoria, texto/ZIP/XML preservados.

Aplicabilidade da precisão por tipo, datas especiais, dados ausentes, idioma
fora dos meses reconhecidos, edições e sufixos de datas completas permanecem
humanos. Não certifica APA integral, data efetiva, identidade ou existência da
fonte. Não acessa rede durante a triagem.

Em 19/11/2026, duas tentativas de consulta ao APA Style receberam 403 do proxy;
não houve leitura desses conteúdos. Foi relido o trecho técnico date-sort da
[mesma revisão CSL já documentada](https://github.com/citation-style-language/styles/blob/af0aea3fffd44fe798cce9285598d0d5cf62ef23/apa.csl#L630),
que distingue precisão conforme tipo. Não é manual oficial nem autoridade
normativa; seu código não foi incorporado. Mantêm-se STAGE11.md/APA7.md.
Registro das consultas em evidence/stage52/normative-check.json.

## Validação

- 1716 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 119 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 62 verificações OJS 3.3.0.20/Chromium: cinco inversões por dia, mês, ano e ano
  bissexto com bloqueio, minuta/privacidade e POST nativo; seis casos revisáveis
  sem avanço automático, com orientação privada de datas/sufixos sem dados da fonte.
- Regressão web anterior: mais 38 verificações, totalizando 100 nesta entrega.
- Casos de ano/mês incompletos, dias iguais, idioma desconhecido, data impossível,
  autoria distinta, edição original e preservação do texto cobertos.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  bytes/estado restaurados. Capturas inspecionadas em evidence/stage52.
  Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --full-dates
```

Requer laboratório previamente configurado. Modalidade adicionada ao runner
integrado opcional; as 695 verificações da Etapa 48 são da base anterior e não
foram repetidas integralmente. Sem produção, VM Oracle, documentos reais,
mensagens externas, publicação do ambiente ou deploy. CI remoto ainda não
verificado nesta entrega.
