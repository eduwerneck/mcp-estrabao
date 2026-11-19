# Etapa 47 — ordem entre autor único e coautorias

Base 8e9cc4e (merge PR #47), em 19/11/2026. Motor 0.37.0, plugin 0.30.0.0,
APA 22.0.0 e formatos reconhecidos 13.0.0. Correspondência 13.0.0, formato de
citações 11.0.0 e contratos JSON preservados. Invalida liberações anteriores.

## Comparação da autoria declarada

Amplia ReferenceOrder sobre pares adjacentes da lista pessoal completa reconhecida.
Com primeiro sobrenome igual, exige também iniciais iguais antes de decidir entre
autor único e coautoria: a obra de autor único precede a obra em coautoria,
independentemente dos anos. Com primeiros autores declarados iguais, compara
os sobrenomes dos coautores seguintes, na posição em que diferirem.
Todos os autores anteriores à diferença precisam ter sobrenome e iniciais iguais.

Usa a comparação literal segura da Etapa 44: pode decidir após prefixos idênticos,
inclusive acentos/partículas comuns, por uma diferença ASCII inequívoca.
Não remove acentos, partículas ou pontuação nem presume equivalência de grafias.
Espaços de apresentação das iniciais são normalizados, sem completar iniciais,
atribuir identidade ou renomear autores. Iniciais distintas antes do ponto de
desempate impedem usar um coautor posterior como prova de inversão.

Listas diferentes não recebem comparação cronológica entre si. A cronologia
anterior permanece exclusiva de listas inteiras declaradas idênticas e anos
numéricos elegíveis. Colação incerta, grafias parciais, autoria institucional,
lista abreviada/incompleta e prefixos idênticos de duas ou mais pessoas com
comprimentos diferentes continuam para revisão. Comparações não adjacentes,
empates por título/sufixos e ordenação por iniciais distintas não são ampliados.

## Diagnóstico e devolutiva

O relatório acrescenta author_list_order_status, author_list_pairs_checked e
pares de índices em author_list_order_samples. A inversão comprovada integra
alphabetical_order_status e reprova os formatos/APA, impedindo conclusão e
liberação no adaptador opt-in. Sem pares elegíveis, o novo diagnóstico é
indeterminado; não certifica ordenação global. Incerteza posterior não apaga
uma inversão independente comprovada.

A minuta e o feedback ao autor orientam autor único antes de coautoria e a
comparação pelos coautores seguintes, sem copiar nomes/anos das referências.
Explicitam que a ordem interna dos autores de cada obra deve ser preservada.
Não reorganiza, reescreve ou salva bibliografia. O aviso de Enter do metadado
OJS continua separado; esta etapa confere a lista extraída do DOCX.

## Limites e verificação

Mantém limites anteriores: 512 entradas, até 20 autores pessoais não abreviados
por entrada, 16 pares por categoria de amostra e limites de extração ZIP/XML/texto.
Mantém ressalvas de APA7.md/STAGE11.md; não houve nova consulta normativa externa,
consulta de fontes/DOI ou certificação de APA integral.

- 1577 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 113 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 50 verificações direcionadas OJS 3.3.0.20/Chromium: cinco inversões com bloqueio,
  mensagem específica, minuta/privacidade e POST nativo; seis casos corretos ou
  incertos sem conclusão automática. Bytes e estado sintéticos restaurados.
- Mais 32 verificações nativas de et al. aprovadas, após ordenar as referências
  das duas fixtures positivas afetadas; 82 verificações de navegador nesta entrega.
- Casos incluem segundo/terceiro coautor, autor único, prefixos comuns acentuados
  e partículas, iniciais divergentes, grafias parciais e listas de comprimentos
  diferentes. Testes anteriores de citação tiveram apenas referências reordenadas
  para não misturar seus objetivos com a nova inversão reconhecida.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos.
  Capturas inspecionadas em evidence/stage47. Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --author-order
```

Requer laboratório local previamente configurado. Modalidade adicionada ao runner
integrado opcional; a regressão histórica de 287 verificações não foi repetida
integralmente. Sem produção, VM Oracle, documentos reais, mensagens externas,
publicação do ambiente ou deploy. CI remoto ainda não verificado nesta entrega.
