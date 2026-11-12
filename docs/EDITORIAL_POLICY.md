# Diretrizes editoriais — base para a próxima etapa

Registro documental `diretrizes-fornecidas-2026-11-11`, recebido do responsável
pelo projeto nesta conversa. Fonte pública indicada:
https://revista.estrabao.press/index.php/estrabao/about/submissions

O texto foi fornecido pelo usuário; a página não pôde ser consultada por causa
do bloqueio de rede deste ambiente. A data acima é de recebimento, não data de
vigência ou versão oficial da política. Confirmar a vigência antes da homologação.
O usuário informou expressamente que a revista **não possui template oficial**.
Não exigir template, estilos específicos de Word ou modelos não publicados.

Este documento registra requisitos. O motor da Etapa 2 implementa presença,
ORCID, marcadores e extensão dos resumos e quantidade de palavras-chave;
os critérios técnicos e limites estão em STAGE2.md.

## Exigências explícitas

| Área | Artigos | Comunicações de Pesquisa |
| --- | --- | --- |
| Extensão | 4.000 a 8.000 palavras | 1.000 a 3.500 palavras |
| Exclusões da contagem | Títulos, resumos, palavras-chave e referências | Mesmas exclusões |
| Seções | Introdução; Metodologia; Resultados e Discussão; Considerações Finais; Fontes de Fomento (quando houver); Créditos (CRediT); Referências | Introdução; Metodologia; Resultados e Discussão; Considerações Finais; Créditos (CRediT); Referências |
| Figuras e/ou tabelas | Limite numérico não informado | Máximo de 6 figuras e/ou tabelas |

Não exigir duas seções separadas de Resultados e Discussão, nem substituir
Considerações Finais por um título inventado. A ordem de apresentação da lista
não estabelece, por si só, uma regra automática de ordenação. A fonte não fornece
traduções oficiais dos títulos para manuscritos em inglês e espanhol.

| Área | Diretriz fornecida | Tratamento previsto |
| --- | --- | --- |
| Idioma principal | Português, inglês ou espanhol | Identificação declarada e eventual conferência humana; não confundir idioma com locale |
| Metadados | Título, resumo e palavras-chave nos três idiomas, independentemente do idioma principal | Presença já implementada; normalização de locales OJS pendente |
| Resumo | Até 250 palavras; marcadores explícitos Contexto, Metodologia(s), Resultados ou Conclusões | Implementados na Etapa 2; decisões e convenções técnicas em STAGE2.md |
| Palavras-chave | 3 a 4 | Quantidade implementada por idioma; duplicatas registradas sem reprovação por unicidade |
| Autoria | Sem limite numérico de autores; contribuição significativa de todos | Não impor máximo; contribuição significativa exige análise humana |
| ORCID | Informado no sistema para todos os autores | Presença/formato/checksum já implementados; identidade não verificada |
| Contribuições | Descrição de contribuições conforme taxonomia CRediT; autor correspondente responde pela veracidade | Presença futura separada de avaliação humana da veracidade |
| Referências | Exclusivamente APA 7 | Análise preliminar futura; ambiguidades exigem revisão humana |
| Arquivo | Microsoft Word .doc ou .docx | Aceitar ambos na política; parser .doc não implementado, não rejeitar por ser .doc |
| Página | A4 e margens de 3 cm | Extração futura de todas as seções relevantes, sem exigir template |
| Tipografia | Times New Roman, tamanho 12; espaçamento 1,5 | Resolver estilos/herança antes de concluir divergência; exceções não publicadas exigem confirmação |
| Seções | Não numeradas; títulos em negrito | Reconhecimento e evidência futura, com atenção à formatação efetiva |
| Unidades | Sistema Internacional de Unidades (SI) | Casos sem evidência objetiva ou ambíguos para análise humana |
| Figuras | 300 dpi; JPEG, TIFF ou EPS | Extração futura; esclarecer se 300 dpi é mínimo ou valor exato e quais dimensões de exibição usar |
| Tabelas e gráficos | Formato editável | Identificar objetos/imagens sem presumir editabilidade só pela extensão |
| Imagens | Não inserir títulos ou autoria dentro delas | Inspeção humana ou análise específica autorizada; não inferir de metadados ausentes |
| Notas de rodapé | Até 3, com máximo de 2 linhas | Contagem futura; linhas dependem da renderização e não só do XML |
| Ética | Quando aplicável, aprovação por comitê, com número do parecer, ou declaração formal de não aplicabilidade | Não inferir aplicabilidade nem validade da aprovação automaticamente |
| IA generativa | Permitida apenas como apoio técnico: revisão linguística, tradução, formatação e figuras ilustrativas | Finalidade e conformidade substancial dependem de revisão humana |
| Declaração de IA | Obrigatória, informando ferramenta, finalidade e nível de interação, em Comentários ao Editor no OJS | Confirmar tratamento quando não houve uso; nunca concluir ausência com extração indisponível |
| Revisão profissional | Após aprovação editorial, comprovar revisão gramatical e textual profissional; sem novos autores após essa etapa | Controle posterior à triagem inicial; depende do estado editorial e do histórico de autoria |

## Recomendações e possibilidades, sem reprovação automática

- DOI sempre que disponível: recomendação, não exigência incondicional de DOI.
- Gestores bibliográficos (Zotero ou Mendeley): recomendação, não requisito.
- Depósito de dados suplementares em repositórios como Zenodo: possibilidade;
  não exigir Zenodo nem depósito de toda submissão sem outra política aplicável.
- Linguagem clara, acessível e inclusiva: encorajamento; análise humana, sem
  converter preferência de linguagem em descumprimento formal automático.

## Marcadores de resumo aprovados pelo responsável

A tabela proposta nesta conversa foi aprovada expressamente pelo responsável.
Cada coluna representa um grupo obrigatório; basta uma alternativa de cada grupo
no resumo do idioma correspondente. Não exigir ambas as alternativas de um grupo.

| Idioma | Primeiro grupo | Segundo grupo | Terceiro grupo |
| --- | --- | --- | --- |
| Português | Contexto ou Introdução | Metodologia ou Método | Resultados ou Conclusões |
| Inglês | Background ou Introduction | Methodology ou Method | Results ou Conclusions |
| Espanhol | Contexto ou Introducción | Metodología ou Método | Resultados ou Conclusiones |

“Metodologias” não é alternativa aprovada. Não estender a lista automaticamente
com plurais, sinônimos ou traduções adicionais. Esta tabela se refere aos
**resumos**; não modifica os títulos obrigatórios das seções do corpo do manuscrito.
O reconhecimento automático está implementado com os limites descritos em STAGE2.md.

## Confirmações antes de automatizar

1. Marcadores dos resumos: alternativas e traduções aprovadas pelo responsável
   conforme a tabela acima. O reconhecimento técnico está em STAGE2.md; formatos
   ambíguos exigem revisão humana, sem criar exigência editorial de pontuação.
2. Títulos das seções em inglês/espanhol, variantes e seções adicionais permitidas;
   obrigatoriedade de ordem e tratamento de Resultados/Discussão separados.
3. Convenção para contar palavras (hífens, números, siglas, tabelas, legendas,
   notas, CRediT e fomento) e delimitar as exclusões do corpo do manuscrito. A Etapa 2 aplica 250 palavras
   e 3 a 4 palavras-chave em cada idioma; métodos de contagem ficam explícitos.
4. Se toda submissão deve declarar uso ou não uso de IA; quais campos permitem
   distinguir “não utilizado” de ausência de informação.
5. Como declarar ausência de fomento e aplicabilidade ética; não presumir que a
   falta de uma seção condicional comprove descumprimento.
6. Contagem de figuras compostas, objetos e tabelas em Comunicações; regra de
   resolução das figuras, estilos com exceções e renderização de notas.
7. Data/versão de vigência e texto das Condições para submissão, ainda não fornecido.

## Sequência de desenvolvimento sugerida

Marcadores, quantidade de palavras-chave e extensão de resumos estão implementados
com testes sintéticos, regras versionadas e limites documentados em STAGE2.md.
Na sequência, desenvolver extração segura
local de .docx e estratégia para .doc, identificação do tipo de manuscrito e
seções. Falha ou cobertura incompleta de extração será indeterminado.

Não haverá envio de textos a serviços externos, acesso à produção, alterações
editoriais nem acesso remoto à VM por causa deste registro documental. A avaliação
automática continuará sendo recomendação sujeita à decisão humana.

## Adendo confirmado em 12/11/2026 — caixa de títulos

O responsável acrescentou nesta conversa a preferência editorial de evitar
**caixa alta integral em títulos e subtítulos**, confirmando o escopo tanto nos
metadados do artigo no OJS quanto nos títulos do manuscrito. É uma orientação
adicional recebida do responsável; não afirma alteração ou nova consulta da fonte
pública registrada acima. Não exige uma convenção específica de Title Case ou
sentence case, não proíbe siglas e não autoriza reescrita automática de textos.

A sinalização local está descrita em STAGE8.md. Siglas e casos ambíguos exigem
revisão humana; capitaletes não foram definidos como infração. O preenchimento e
a apresentação efetivos no OJS continuam dependendo de integração futura.
