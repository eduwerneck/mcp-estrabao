# Etapa 23 — conteúdo e revisão da declaração CRediT

Base ae27b68 (merge do PR #23), motor 0.17.0 e plugin 0.9.0.0.
Primeira entrega do bloco de contribuições/declarações; fomento fica para a
etapa seguinte, sem pressupor aplicabilidade.

## Duas verificações distintas

manuscript.credit.content 1.0.0 confere somente a presença de texto sob o título
Créditos (CRediT), já exigido pelas diretrizes. Não basta encontrar esse título.
No subconjunto fechado do DOCX completo em português:

- Título único, seguido de outra seção canônica ou fim do documento, sem texto
  visível intermediário: não conforme, credit_section_empty.
- Texto visível em parágrafos com corpo/estilos resolvidos: conforme somente para
  presença de texto. Não certifica a descrição como adequada à taxonomia.
- Fonte parcial/ausente, título repetido, tradução não aprovada ou fronteira de
  seção/estilo inconclusiva: indeterminado, nunca ausência presumida.

Reconhece os mesmos títulos canônicos do corpo, Referências e Fontes de Fomento,
com prefixo numérico opcional para delimitar a seção. Não usa conteúdo de seção
posterior para preencher CRediT. Um cabeçalho desconhecido, outline não resolvido
ou parágrafo em negrito pode indicar fronteira não suportada e exige revisão.
Declarações legítimas com formatação complexa podem, portanto, ficar inconclusivas.

A ausência de título continua tratada por manuscript.docx.structure, sem outra
acusação duplicada na regra nova. Há limite de 4096 parágrafos, como no motor;
cobertura parcial, tabelas, notas e objetos não são promovidos à leitura completa.
Não há interpretação semântica de texto, detecção de placeholders, inferência de
papéis a partir de palavras soltas ou reescrita de contribuições.

manuscript.credit.verification 1.0.0 fica sempre indeterminada. Exige conferência
humana da taxonomia CRediT, atribuição aos autores, cobertura de todos os autores e
veracidade, sob responsabilidade do correspondente. Essa pendência permanece mesmo
se a presença de texto for conforme. Não impõe todos os 14 papéis, quantidade de
autores, percentuais, nomes identificáveis ou novo formulário obrigatório.

## Relatório, minuta e painel

As evidências incluem hash do documento, perfil/cobertura, quantidade de seções,
índice do título e contagem de parágrafos quando conclusiva. Não copiam conteúdo,
nomes de autores, atribuições ou caminhos. Registram explicitamente que taxonomia,
atribuição, cobertura da autoria e veracidade não foram verificadas automaticamente.

O gerador 1.0.0 conhece as regras novas. Apenas seção comprovadamente vazia gera
correção específica: preencher descrição das contribuições, preservando anonimização.
A seção ausente conserva o tratamento estrutural existente. Pendências humanas
não viram infrações ou exigências adicionais na carta aos autores.

O painel agrupa as pendências de CRediT separadamente na ficha interna. Não grava
verificação, comunica autores ou libera submissão. Gate APA e suas regras continuam
inalterados; motor novo invalida liberações ligadas à versão anterior pelo
fingerprint existente. Atualizar motor e plugin conjuntamente em homologação e
limpar caches de idioma, conforme DEVELOPMENT.md. Não houve implantação.

Schemas de entrada 1.0/1.1 e relatório/minuta 1.0 preservados. Duas regras novas
acrescentam dois resultados: 47 sem DOCX, 49 com artigo, 48 com comunicação.
Testes existentes de contagem e versão foram atualizados.

## Validação e limites

852 testes sintéticos aprovados (15 novos), zero falhas, em PHP 8.4.27 e 8.1.27;
lint de 78 arquivos PHP. Cobrem seção vazia, presença limitada, seção posterior,
fonte parcial, idiomas, duplicação, fronteira incerta, privacidade, minuta e serviço
real do painel com dublês OJS. Conferência humana continua pendente após presença
positiva de texto.

Quatro verificações no OJS 3.3.0.20/Chromium local, somente leitura: grupo interno
traduzido, seção ausente não chamada de vazia, pendência fora da carta e estado
editorial inalterado. Captura inspecionada e JSON em evidence/stage23.
Reprodução no laboratório da Etapa 15: PYTHONPATH=tests/lab python
tests/lab/credit-panel.py, com os registros sintéticos já existentes.

Sem manuscritos reais, produção, VM Oracle, envio a autores, criação de atribuições
ou publicação do ambiente. A entrega não valida integralmente CRediT. Fomento,
ética e IA continuam nos próximos trabalhos, conforme a aplicabilidade documentada.
