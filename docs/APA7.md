# APA 7 na entrada da submissão

Prioridade editorial confirmada em 18/11/2026: ampliar a conferência de APA 7
antes do empacotamento. Referências em ABNT não são aceitas. A regra operacional
é exigir correção quando há incompatibilidade comprovada; conteúdo inconclusivo
fica para conferência editorial, sem avanço automático. A presença de um padrão
misto não autoriza classificar toda a bibliografia como ABNT.

Este mapa distingue o que o motor verifica do que ainda precisa ser implementado
ou conferido. Nenhuma ausência de erro equivale à certificação completa de APA 7.
O adaptador atua na confirmação do wizard; não impede o autor de salvar rascunho.
API, importações e outros caminhos não foram homologados.

| Frente | Cobertura atual | Falta para ampliar |
| --- | --- | --- |
| ABNT e formatos mistos | Padrões compostos de autoria/data após fonte; cidade/editora, rótulos de periódico, DOI antigo e separadores reconhecidos | Padrões fora da gramática, tipos jurídicos e autoria institucional |
| Autores de referências | Sobrenomes simples e compostos, partículas delimitadas, iniciais, separadores, limite de 20 nomes não abreviados; estrutura 19 + reticências + último (Etapa 29) | Sufixos, outras partículas, confirmação de identidade, ordem e total real na fonte |
| Citações no corpo | Formatos autor–ano reconhecidos, & versus conjunção narrativa, espaços e correspondência no subconjunto português | Instituições/siglas, nomes compostos no matching, três ou mais autores e exceções, citações secundárias, múltiplas obras e anos, desambiguação |
| Referências por tipo | Campos e itálicos de subconjuntos reconhecidos de artigos e capítulos; candidatos de obras autônomas com revisão do tipo | Ampliar modelos de livros/edições/traduções, relatórios, teses, páginas, dados/software e tipos especiais |
| Ordem e títulos | Ordem de primeiros sobrenomes ASCII distintos; título integralmente em caixa alta no subconjunto reconhecido | Empates, diacríticos, partículas, obras do mesmo autor, sentence case sem alterar nomes próprios/siglas |
| DOI e URL | Formato de DOI informado; ausência de DOI não reprova | Correspondência com fonte e metadados, links reais, datas de acesso quando aplicáveis |
| Citações diretas | Alguns localizadores reconhecidos lexicalmente | Conferência de transcrição, localizador, extensão, bloco versus aspas e fontes sem paginação |
| Documento e extração | DOCX seguro e perfis parciais explicitados | Casos fora da extração, apresentação renderizada; PDF e .doc não têm triagem automática |

## Ordem de continuidade

1. Autoria pessoal e listas abreviadas: entregue na Etapa 29, incluindo bloqueio
   e orientação ao autor no OJS local.
2. Citações no texto: cobrir primeiro três ou mais autores/et al. e suas exceções,
   instituições e citações secundárias, com testes de falsos positivos.
3. Modelos de referências por tipo e seus campos, sem adivinhar o tipo da obra.
4. Correspondência e desambiguação ampliadas, ordem da lista e localizadores.
5. Repetir o fluxo completo autor–correção–revisão na cópia isolada do OJS.

Cada ampliação precisa provar diagnóstico, devolutiva, bloqueio, tratamento de
incerteza e preservação do manuscrito. Alterar a versão do motor invalida as
liberações anteriores; uma liberação editorial não pode superar erro comprovado.
A interface opt-in existente precisa estar habilitada para exercer o bloqueio.

## Base e limites normativos

O requisito editorial e a regra de 20/21 autores já estão documentados nas Etapas
11 e 21. Mantém-se a referência técnica CSL registrada em STAGE11.md, que não
substitui o manual oficial. Em 18/11/2026, tentativas de consultar APA Style
(elements-list-entry) e Purdue OWL (reference-list-basic-rules) receberam 403 do
proxy; seu conteúdo não foi consultado nem incorporado nesta etapa. Não se
anuncia nova validação normativa externa. O motor não consulta rede.

A revista mantém suas diretrizes próprias de apresentação; não se impõe uma
regra genérica de APA que contradiga o espaçamento editorial documentado.
Identidade de autores, existência/correção bibliográfica, sentido das citações
e fidelidade à fonte permanecem para conferência humana.
