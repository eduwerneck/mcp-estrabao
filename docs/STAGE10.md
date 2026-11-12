# Etapa 10 — pré-checagem de referências e citações

Motor 0.10.0 após o merge do PR #9. Substitui as pendências de referências por
análise local de um subconjunto explícito de autor–ano. Não certifica APA 7 nem
consulta DOI, editoras, bases bibliográficas ou serviços externos. Todo relatório
continua exigindo revisão humana, sem decisão editorial automática.

## Dois resultados — versão 2.0.0

`references.apa7` registra uma pré-checagem lexical e **continua indeterminado**,
mesmo quando todos os formatos reconhecidos parecem consistentes. A evidência
explicita `full_apa_validation=false` e `external_lookup_performed=false`.
Reconhecer autoria/data não valida título, fonte, ordem alfabética, itálico,
pontuação completa, tipo documental, existência da obra ou exatidão dos dados.
A versão 2.0.0 distingue esta análise da pendência 1.0.0, que não analisava texto.

`references.citation_matching` verifica chaves literais no sentido
**citações reconhecidas → referências reconhecidas**, dentro do subconjunto:

- Conforme: há pelo menos uma citação reconhecida, o subconjunto é fechado e
  todas têm exatamente uma correspondência.
- Não conforme: o subconjunto é fechado e há chave autor–ano reconhecida sem
  correspondência. É uma inconsistência lexical a conferir, não prova de inexistência
  bibliográfica ou rejeição editorial.
- Indeterminado: fonte/delimitação incompleta, formatos não reconhecidos,
  ambiguidade, ausência de citações reconhecidas ou limite técnico alcançado.

Referências não observadas nas citações suportadas são registradas como informação,
não reprovação. A direção reversa e a identificação semântica de todas as citações
não são afirmadas. Não se conclui conformidade APA 7 a partir de correspondência.

## Delimitação do texto

Usa apenas extração DOCX completa das etapas anteriores, em manuscrito declarado
em português. Não libera texto parcial de tabelas, notas, campos ou outros
conteúdos não suportados. Títulos oficiais do corpo em inglês/espanhol continuam
pendentes; esses idiomas ficam indeterminados nesta etapa.

Reconhece os títulos canônicos de Introdução, Metodologia, Resultados e Discussão,
Considerações Finais, Referências, Créditos (CRediT) e Fontes de Fomento, ignorando
caixa e prefixos numéricos já suportados. A zona de Referências termina em outro
marcador reconhecido. Não exige ordem; referências intermediárias e corpo posterior
são admitidos. Não confunde os rótulos aprovados de resumo com traduções aprovadas
das seções do corpo.

Para conclusão, os quatro marcadores principais e Referências precisam ocorrer
uma vez cada. Cabeçalho adicional, negrito isolado ou outline desconhecido tornam
a fronteira ambígua e impedem conclusão; não criam uma reprovação de estrutura.
Marcador principal dentro de zona de resumo sem fronteira técnica de palavras-chave
também impede conclusão. A regra de presença de seções continua separada.

A varredura de citações considera somente parágrafos das quatro zonas principais.
Prefixo, resumos, palavras-chave, créditos, fomento e a própria bibliografia não
são varridos como corpo citado. Referências nesses trechos podem, portanto, não
aparecer nas observações desta checagem. Marcadores e formatação são indícios
lexicais: citações de exemplo, código, transcrições ou prosa sob título errado
precisam de interpretação humana.

## Subconjunto de referência reconhecida

Uma entrada por parágrafo, com estrutura `Sobrenome, A. (2020). Texto da obra.`:

- Sobrenome de um token, admitindo letras Unicode, hífens e apóstrofos; iniciais
  maiúsculas pontuadas. Partículas e sobrenomes em vários tokens não são adivinhados.
- Um autor, dois autores com `, &`, ou lista de até 20 autores com o último
  precedido por `, &`. O parser não implementa truncamento por reticências.
- Ano de quatro dígitos com sufixo ASCII opcional, por exemplo 2020a. Não verifica
  autenticidade do ano ou a atribuição editorial das letras.
- Texto não vazio depois do bloco autor/data, sem interpretar o restante da
  entrada como validação completa de referência.

Autores institucionais, nomes sem iniciais, datas completas, sem data/n.d./s.d.,
obras com mais de 20 autores, referências repartidas entre parágrafos e várias
entradas reconhecíveis no mesmo parágrafo ficam fora do subconjunto. Isso não
significa que sejam proibidos ou errados em APA 7. Entrada não reconhecida impede
conclusão de ausência de referência; não é descartada silenciosamente.

## Formas de citação reconhecidas

- Parentética: `(Silva, 2020)`, dois autores `(Silva & Souza, 2020)` e três ou mais
  com `(Silva et al., 2020)`. Lista por ponto-e-vírgula é aceita.
- Localizador simples `p. 12` ou `pp. 12–14` é reconhecido, sem validar paginação.
- Narrativa no início do parágrafo: `Silva (2020)` e formas equivalentes de dois
  autores/et al. Prefixos simples Segundo, Conforme, According to e Según são
  suportados. Narrativas no meio de prosa arbitrária continuam indeterminadas.
- Conectores `&`, e, and e y são reconhecidos para comparar dois autores; isso
  não certifica a forma de apresentação da citação segundo APA 7.

Nomes com uma única letra ficam ambíguos com símbolos/variáveis e não são
reconhecidos como autoria conclusiva de citação. Compara nomes literalmente, ignorando caixa Unicode, sem remover acentos ou
partículas e sem consultar identidade de autores. Dois autores precisam coincidir
em ordem; et al. exige referência com pelo menos três autores e o mesmo primeiro.
Mesmo primeiro autor/ano com outra forma de autoria ou múltiplas correspondências
fica ambíguo, sem escolher automaticamente uma obra. Ano e sufixo precisam coincidir.

Comunicação pessoal, citação secundária, múltiplos anos na mesma chave, datas
sem ano, autoria institucional, parênteses aninhados/desbalanceados e grupos não
reconhecidos com sinais de data impedem conclusão. Colchetes numéricos são apenas
candidatos a citação numérica: geram indeterminado, não uma infração APA automática.
O detector não interpreta o significado científico de cada parêntese; revisão
humana continua necessária, inclusive diante de uma chave literal não encontrada.

## DOI e limites

Registra candidatos a URL `https://doi.org/10...`, prefixos antigos como `doi:10...`
e URLs dx.doi.org/http, sem expor DOI na saída. Sinais moderno/antigo podem
coexistir em uma entrada. **DOI não detectado não reprova**: a política recebida
recomenda DOI quando disponível e não exige DOI incondicional. Não há resolução,
validação de existência, geração ou correção de identificadores.

Limites técnicos: 4.096 parágrafos, 512 entradas, 2.048 citações, 1 MiB de texto
nas zonas principais e 64 KiB por parágrafo do corpo. Entradas acima de 8 KiB,
autoria acima de 2 KiB ou grupos parentéticos acima de 2 KiB não são interpretados
como formatos reconhecidos. Exceder limite nunca produz conclusão por leitura
truncada. Os limites anteriores de arquivo/ZIP/XML continuam preservados.

## Evidência, execução e continuidade

Registra hash do DOCX, cobertura, totais reconhecidos/não reconhecidos, ambiguidades,
sinais DOI e até 32 amostras de referências e 32 de citações. Prioriza entradas
não reconhecidas e vínculos inconclusivos nas amostras limitadas. Até oito referências
candidatas por amostra de citação, com total omitido explícito. Não copia autores,
anos, títulos, DOI, trechos de citação ou caminhos.

Índices de parágrafo são base zero no texto normalizado pelo extrator; offset da
citação é em bytes no parágrafo e aponta o grupo parentético. Índices de referência
são base zero na coleção delimitada; posições dos parágrafos correspondentes também
são informadas. Não são coordenadas do XML original ou da página renderizada.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/with-subtitles.json manuscrito-sintetico.docx article pt
```

Snapshots 1.0/1.1, relatório schema 1.0 e argumentos CLI preservados. As regras
ocupam os mesmos dois IDs anteriores, agora 2.0.0: continuam 43 resultados sem DOCX,
45 com artigo e 44 com comunicação. Sem documento, ambos permanecem indeterminados.

Sem novas dependências, renderizador, bibliografia real, OJS, produção, VM Oracle
ou publicação/reconfiguração do ambiente. O indicador Submetido em continua adiado.
