# Etapa 6 — extensão do manuscrito por tipo

Motor 0.6.0 após o merge do PR #5. Nova regra `manuscript.word_limit` 1.0.0,
sem mudança de argumentos CLI, schemas 1.0 ou dependências. Nenhum acesso a
produção, VM Oracle, OJS ou serviços de análise externos. O ambiente publicado
não foi alterado nem publicado novamente.

## Política e decisão

| Tipo declarado na CLI | Faixa inclusiva |
| --- | --- |
| `article` | 4.000 a 8.000 palavras |
| `research_communication` | 1.000 a 3.500 palavras |

Excluem-se títulos, resumos, palavras-chave e referências conforme a diretriz
recebida. A convenção oficial de palavra e o tratamento de legendas, notas,
CRediT e fomento ainda não foram confirmados. A implementação produz um
**intervalo técnico**, não uma contagem editorial oficial ou aprovação final.
Não infere o tipo pela extensão ou pelo conteúdo do texto.

Com extração e delimitação suportadas, o resultado é:

- Conforme: todo o intervalo está dentro da faixa do tipo declarado.
- Não conforme: todo o intervalo está abaixo do mínimo ou acima do máximo.
- Indeterminado: o intervalo cruza uma fronteira ou há lacuna de fonte,
  normalização, idioma, tipo ou delimitação. Uma contagem vazia não aprova nem
  reprova automaticamente a extensão.

Por enquanto, a delimitação conclusiva é suportada apenas para corpo em
português, cujos títulos estão documentados. Inglês/espanhol continuam
indeterminados até confirmar os títulos do corpo. Isso não proíbe esses idiomas.

## Segmentação e exclusões

`ManuscriptSegments` percorre os parágrafos já extraídos do corpo principal.
Usa os títulos exatos de Introdução, Metodologia, Resultados e Discussão,
Considerações Finais, Referências, Créditos (CRediT) e Fontes de Fomento.
A comparação ignora caixa e normaliza espaços. Prefixos numéricos reconhecidos
na Etapa 3 não entram na contagem do título; isso não aprova sua formatação.

Não exige ordem das seções. Um marcador reconhecido reabre sua zona mesmo que
apareça depois de Referências. Para decisão conclusiva, os quatro marcadores
principais e Referências precisam ocorrer uma vez cada. Ausência ou duplicata
produz indeterminado nesta regra, não uma nova reprovação de estrutura. A regra
separada de presença de seções continua responsável por essa verificação.

Antes do primeiro marcador de corpo, os títulos do artigo que correspondem
literalmente a um parágrafo e ao snapshot nos locales pt_BR/en_US/es_ES são
excluídos. Normaliza HTML somente no campo do snapshot, sem remover símbolos ou
pseudotags do texto do DOCX. Para comparação sem distinção de caixa, o título do
snapshot é limitado a 4 KiB; títulos maiores só são excluídos por igualdade exata.
Não procura nem remove palavras do título no meio de prosa. Prefixos não
identificados, inclusive títulos sem metadados correspondentes, entram apenas
no limite superior; não se exige template ou presença de um rótulo de título.

Reconhece `Resumo`, `Abstract`, `Resumen`, `Palavras-chave`, `Keywords` e
`Palabras clave`, isolados ou seguidos de dois-pontos, antes do corpo. São
marcadores técnicos de extração, não novos rótulos editoriais obrigatórios ou
traduções aprovadas de seções. O marcador e a zona seguinte são excluídos até
outro marcador reconhecido. Ausência desses rótulos não reprova: o prefixo fica
incerto. Rótulos de resumo/keywords depois do corpo não justificam exclusão
comprovada; entram na faixa incerta.

Um título principal dentro da zona de resumo pode ser rótulo do próprio resumo.
Sem fronteira reconhecida de palavras-chave, essa ambiguidade impede decisão
conclusiva, em vez de confundir resumos com introdução do corpo.

Referências são excluídas a partir do marcador reconhecido até outro marcador
reconhecido ou candidato a nova seção. Não verifica APA, citações, adequação do
conteúdo científico ou se o autor colocou prosa sob um título errado. A segmentação
é lexical e precisa de revisão humana quando os marcadores não representam a
organização real do texto.

## Incerteza mantida no intervalo

Prosa nas zonas principais, sem indício de título, entra nas duas contagens.
Parágrafos em negrito, níveis de outline 0–8, ou estilos/outline desconhecidos
podem ser títulos e iniciar seção adicional: entram apenas no limite superior e
reabrem zona incerta até um marcador reconhecido. `outlineLvl` é resolvido por
defaults, `basedOn` e override direto; 9 ou ausência significa texto de corpo.
Não inferimos significado de nomes de estilos. Parágrafos vazios não mudam a zona.

Conteúdo de Créditos e Fomento entra apenas no limite superior, pois sua inclusão
na contagem não foi definida. Legendas com início reconhecível de Figura, Tabela,
Quadro ou Gráfico seguido de número também ficam incertas. Esses sinais não
confirmam a existência ou quantidade de objetos nem sua conformidade.

As tabelas, campos, revisões, caixas de texto e demais lacunas da extração DOCX
continuam indeterminados conforme STAGE3.md. A implementação não usa uma extração
parcial para provar falta ou excesso. Cabeçalhos e rodapés não fazem parte desta
contagem do corpo. Notas e materiais não cobertos não recebem contagens inventadas.

## Convenções técnicas — `body_conservative_interval_v1`

`WordInterval` aplica ao texto simples, sem remover nomes de rótulos em prosa:

- Contagem inferior de cada trecho: tokens separados por espaços Unicode,
  contendo ao menos uma letra ou número.
- Contagem superior: sequências contíguas de letras/números, separando hífens,
  apóstrofos e pontuação; por exemplo, `socio-econômico` vale 1 a 2.
- Trecho incerto: contribuição inferior zero e superior igual à contagem superior
  desse trecho. Trecho excluído: contribuição zero nas duas contagens.

Números e siglas contam; a segmentação de decimais pode ampliar o intervalo.
Pontuação isolada não conta. UTF-8 inválido ou Unicode decomposto ainda sem
normalização suportada permanece indeterminado. Não se assume que uma dessas
convenções foi aprovada pela revista; resultados limítrofes exigem conferência.

A evidência inclui tipo/idioma, faixa publicada, método, intervalo, cobertura,
hash do DOCX, títulos de política reconhecidos e até 32 regiões com categoria e
índices de parágrafo, mais o total de regiões omitidas. Categorias contam
parágrafos, não palavras. `segmentation_complete` descreve os marcadores
necessários únicos e a ausência da ambiguidade resumo/corpo; não afirma que todo
trecho foi resolvido. Não copia texto, título particular do artigo, nome de estilo
ou caminho local. O hash do snapshot continua separado no relatório.

## Executar

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json manuscrito-sintetico.docx article pt
```

Sem DOCX, a fixture padrão produz 33 resultados (22 conformes, 11 indeterminados).
Com DOCX de artigo, há 35 resultados. Os testes de contagem criam DOCX sintéticos
com membros armazenados sem compressão quando há longas repetições; não desativam
CRC nem limites de ZIP/XML. Contagem de palavras não substitui verificação de
seções, formatação, qualidade científica, decisão editorial ou revisão humana.
