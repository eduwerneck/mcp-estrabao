# Etapa 50 — comparação conservadora de artigos iniciais

Base 71ec3f6 (merge PR #50), em 19/11/2026. Motor 0.39.0, plugin 0.32.0.0,
APA 24.0.0 e formatos reconhecidos 15.0.0. Correspondência 13.0.0, formato de
citações 11.0.0 e contratos JSON preservados. Invalida liberações anteriores.

## Leituras alternativas, sem presumir idioma

O idioma do manuscrito não comprova o idioma de cada referência. Não usa esse
idioma para remover palavras dos títulos. Para os prefixos lexicais reconhecidos
(a, an, the, o, os, as, um, uma, uns, umas, el, la, los, las, un, una, unos, unas),
a comparação considera tanto o título literal quanto uma chave interna sem
um único prefixo inicial. A chave não substitui o título nem é exportada.

Todos os cruzamentos entre as alternativas de cada par precisam concordar em
uma ordem estrita. Exemplo: The Alfa antes de Zebra estudo tem a mesma ordem
com ou sem The; O Alfa versus Beta estudo muda de ordem e permanece indeterminado.
A palavra não é certificada como artigo nem recebe idioma atribuído pelo motor.
Esta é uma ampliação por concordância das leituras, não seleção automática de
uma regra linguística ou cobertura completa de ordenação por idioma.

Conflito entre leituras, empate de qualquer chave, prefixos consecutivos, título
composto somente pelo prefixo e comparação acentuada incerta tornam todo o
conjunto indeterminado. Mesmo prefixo em dois títulos não força uma interpretação
comum: todos os cruzamentos continuam sendo conferidos. Não elimina palavras,
reescreve títulos, reorganiza referências ou atribui sufixos automaticamente.

## Integração e resultado

Mantém as condições da Etapa 49: conjunto completo de mesma autoria declarada
e ano numérico elegível, títulos dos modelos reconhecidos de obra autônoma
candidata/artigo, sem pontuação ou números no título, identidade/edição não
presumidas e proteção de datas originais. A conferência de sufixos só prossegue
se todos os pares do conjunto tiverem ordem inequívoca.

Inversão de títulos ou sufixos incoerentes comprovados bloqueiam a conclusão e
liberação com a orientação específica anterior. Casos incertos permanecem para
revisão humana, sem conclusão automática. Acrescenta apenas o identificador fixo
title_article_strategy e a contagem title_article_pairs_checked; não exporta
títulos, prefixos retirados, chaves de comparação ou idioma inferido.

Até 26 entradas por conjunto, 512 bytes por título e 16 amostras por categoria;
preserva demais limites de autores, entradas e extração. Prefixos não cobertos,
idioma/papel real da palavra, casos em que leituras divergem, colação geral,
pontuação, fontes e demais lacunas de APA7.md continuam pendentes. Mantém
STAGE11.md, sem nova consulta normativa externa ou certificação integral de APA.

## Validação

- 1639 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 116 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 50 verificações direcionadas OJS 3.3.0.20/Chromium: quatro erros comprovados
  com bloqueio, minuta/privacidade e POST nativo; oito casos corretos/incertos
  sem conclusão automática, incluindo conflito de leitura e prefixo isolado.
- Regressão anterior de títulos/sufixos repetida: mais 50 verificações nativas,
  totalizando 100 nesta entrega. Uma fixture antes incerta foi trocada por outro
  título realmente ambíguo; o caso agora decidível é coberto nos testes novos.
- Casos em português/inglês/espanhol não recebem idioma presumido; cruzamentos
  concordantes, divergentes, empates, prefixos sucessivos e preservação do texto
  são conferidos. Bytes/estado sintéticos restaurados pelas duas suítes.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  capturas inspecionadas em evidence/stage50. Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --initial-articles
```

Requer laboratório previamente configurado. Modalidade adicionada ao runner
integrado opcional; as 695 verificações da Etapa 48 são da base anterior e não
foram repetidas integralmente. Sem produção, VM Oracle, documentos reais,
mensagens externas, publicação do ambiente ou deploy. CI remoto ainda não
verificado nesta entrega.
