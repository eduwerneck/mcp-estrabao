# Etapa 51 — subtítulos e pontuação na comparação bibliográfica

Base ba6e302 (merge PR #51), em 19/11/2026. Motor 0.40.0, plugin 0.33.0.0,
APA 25.0.0 e formatos reconhecidos 16.0.0. Correspondência 13.0.0, formato de
citações 11.0.0 e contratos JSON preservados. Invalida liberações anteriores.

## Comparação literal em modelos delimitados

Amplia ReferenceTitleOrder para títulos/subtítulos já extraídos dos modelos
reconhecidos de obra autônoma candidata e artigo. Não altera a identificação de
campos nem toma periódico/editora como parte do título. Admite palavras com
apóstrofos internos e separadores delimitados: espaço, dois-pontos, ponto e
vírgula ou vírgula seguidos de espaço; hífen/travessão entre palavras, sem espaços
ou com espaços dos dois lados; & entre espaços. Admite ? ou ! terminal no campo
reconhecido. Não certifica correção tipográfica ou completude da referência.

Compara a grafia literal, normalizando apenas a caixa técnica anterior. Pode
decidir por letras ASCII inequivocamente diferentes antes da pontuação ou depois
de um prefixo idêntico, incluindo pontuação compartilhada. Exemplo: Estudo: Beta
antes de Estudo: Alfa é uma inversão comprovada. Não remove sinais, converte
hífens em espaços, expande siglas ou altera nomes próprios.

Se a primeira diferença for entre sinais, entre sinal e espaço/letra ou entre
acentos incertos, conserva revisão humana. Estudo: Alfa versus Estudo Alfa não
recebe ordem adivinhada. Quando um título for prefixo literal do outro, exige
letras adicionais na cauda: Alfa versus Alfa! permanece indeterminado, pois a
pontuação terminal isolada não prova títulos bibliograficamente distintos.

Artigos iniciais continuam sujeitos à concordância de todas as leituras da Etapa
50. Prefixo candidato seguido diretamente de pontuação (como A: pesquisa) não
recebe papel linguístico presumido. Números, parênteses, aspas externas, barras,
pontos internos e sequências não reconhecidas continuam fora desta ampliação.

## Diagnóstico e proteção

Mantém conjuntos completos de mesma autoria declarada e ano numérico elegível,
limites de 26 entradas/512 bytes por título e as proteções de datas originais,
duplicatas, coautorias e identidade. Todos os pares precisam ter ordem segura
antes de conferir a sequência de letras. Um terceiro título incerto impede
atribuir sufixos aos demais por amostragem.

Inversões ou sufixos incoerentes comprovados bloqueiam a conclusão/liberação com
as orientações específicas existentes. Incerteza exige revisão humana, sem
avanço automático. Acrescenta title_punctuation_strategy e a contagem
title_punctuation_pairs_checked; não exporta títulos ou chaves técnicas.
Não reescreve, reorganiza ou salva títulos, citações ou referências.

Preserva demais limites de extração/autoria/entradas e 16 amostras por categoria.
Não confirma fonte, existência bibliográfica, idioma, fidelidade, layout ou APA
integral. Mantém ressalvas de APA7.md/STAGE11.md, sem nova consulta normativa externa.

## Validação

- 1679 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 117 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 50 verificações direcionadas OJS 3.3.0.20/Chromium: cinco incompatibilidades
  com bloqueio, minuta/privacidade e POST nativo; seis casos corretos/incertos
  sem conclusão automática. Inclui subtítulo, sigla, apóstrofo e artigo inicial.
- Regressão de títulos/sufixos: mais 50 verificações nativas, totalizando 100.
  A fixture antiga com pontuação agora decidível foi substituída por diferença
  realmente incerta; a nova cobertura prova o caso anteriormente adiado.
- Casos de pontuação divergente, duplicata, números, prefixo sem letras adicionais,
  acentos, terceiro título incerto e preservação de nomes/siglas/texto são cobertos.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  bytes/estado restaurados pelas duas suítes. Capturas inspecionadas em
  evidence/stage51. Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --punctuation
```

Requer laboratório previamente configurado. Modalidade adicionada ao runner
integrado opcional; as 695 verificações da Etapa 48 são da base anterior e não
foram repetidas integralmente. Sem produção, VM Oracle, documentos reais,
mensagens externas, publicação do ambiente ou deploy. CI remoto ainda não
verificado nesta entrega.
