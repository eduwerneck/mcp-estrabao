# Etapa 26 — declaração de uso ou não uso de IA no manuscrito

Base técnica 5fccd40 (Etapa 25), motor 0.20.0 e plugin 0.12.0.0.
A Etapa 25 ainda não estava incorporada a origin/main durante esta preparação;
a branch desta entrega parte dela. Abrir o PR para main após o merge da ética,
para que a comparação contenha apenas a alteração de IA.

## Política confirmada em 13/11/2026

O responsável preferiu o próprio manuscrito como local da declaração e confirmou
que todos devem declarar uso **ou não uso** de IA. Não exige repetição em
Comentários ao Editor, título fixo ou formulário novo. Se houve uso, informar
ferramenta, finalidade e nível de interação. Se foi declarado não uso, não exigir
informações sobre ferramenta inexistente.

As finalidades permitidas continuam as já documentadas: apoio técnico para revisão
linguística, tradução, formatação e figuras ilustrativas. A adequação substancial
é conferência humana. Não infere uso real por estilo de escrita, classificação
probabilística ou simples menção a uma ferramenta.

## Duas verificações

manuscript.ai.declaration 1.0.0 confere somente presença textual no subconjunto
suportado de Word completo:

- Declaração explícita de não uso, em frases fechadas reconhecidas: conforme para
  presença da declaração, sem certificar que o não uso seja verdadeiro.
- Texto sob um único título de declaração reconhecido, com fronteiras resolvidas:
  conforme somente para presença de texto, sem certificar conteúdo/completude.
- Fonte parcial/ausente, títulos repetidos, fronteira incerta, marcadores
  provisórios, normalização/limite técnico ou declaração fora do padrão:
  indeterminado. Não conforme não é emitido por esta regra.

Título vazio também é indeterminado: pode haver declaração válida em outro trecho,
sem título específico. Não é infração automática. Nenhum resultado desta entrega
gera correção automática na minuta; ausência confirmada deve ser tratada pelo editor.

Há frases delimitadas em português, inglês e espanhol para observar declaração
de não uso. Os títulos reconhecidos são localizadores, não modelos obrigatórios.
Menções gerais a IA/LLM/ChatGPT antes da bibliografia são apenas indícios, com
posições limitadas. A sigla IA isolada é sensível à caixa para não confundir o
verbo português “ia” com menção à tecnologia.

Procura títulos de declaração em todo texto Word principal, inclusive após
referências. Menções bibliográficas não certificam declaração. Outras menções,
mesmo depois da bibliografia, tornam a interpretação do título vazio incerta.
A cobertura não é identificação semântica de todas as formas de declarar uso.
Notas, tabelas, imagens e material excluído não são promovidos a texto completo.

manuscript.ai.verification 1.0.0 permanece sempre indeterminada. O editor deve
conferir uso/não uso declarado e, quando houve uso, ferramenta/finalidade/interação
conforme a política. Presença de texto ou declaração de não uso não valida
veracidade, finalidade ou conformidade substancial.

Limites de inspeção: 4096 parágrafos, 8192 bytes por parágrafo e 32 amostras,
com quantidade omitida explícita. Mantidos os controles ZIP/XML do leitor.

## Privacidade, relatório e painel

Evidências contêm perfil/cobertura, hash, contagens e posições; não copiam texto,
nomes de ferramenta declarados, prompts, dados pessoais ou arquivos. Flags de uso
real, conformidade da finalidade e validação de ferramenta/interação ficam falsas.
Não usa serviço externo ou detector de IA. Não lê nem grava Comentários ao Editor;
snapshots 1.0/1.1/1.2 permanecem iguais, sem campos novos de discussão privada.

O painel autorizado tem ficha interna traduzida de IA, com mensagens de não uso
observado, presença de texto, título vazio ou leitura inconclusiva. A ficha não
entra na cópia da carta, não grava decisões e não cria bloqueio por IA. O gate
existente continua sendo de APA, não uma aprovação automática das demais exigências.
Motor novo invalida liberações de motor anterior pelo fingerprint existente.

Gerador de minuta 1.0.0 e schemas de relatório/minuta preservados. As duas regras
levam o relatório a 51 checks sem DOCX e 52 com artigo/comunicação. Atualizar
motor/plugin juntos em homologação e limpar caches de idioma. Nenhuma implantação
ou publicação/reconfiguração do ambiente foi realizada.

## Validação

922 testes sintéticos aprovados (19 novos), zero falhas, em PHP 8.4.27 e 8.1.27;
lint de 84 arquivos PHP. Cobrem não uso observado versus veracidade, texto presente,
título vazio, declaração no corpo/apos bibliografia, citações, fronteiras, placeholders,
cobertura parcial, limites, privacidade e serviço do painel com leitor real sob dublês.

Quatro verificações de leitura no OJS 3.3.0.20/Chromium local: ficha traduzida,
uso/não uso no manuscrito sem duplicação OJS, ausência de marcador sem acusação e
separação da carta/estado editorial preservado. Captura inspecionada em evidence/stage26.
Reproduzir no laboratório da Etapa 15 com PYTHONPATH=tests/lab python tests/lab/ai-panel.py.
O navegador usou registros sintéticos existentes; cenários de declaração foram
exercitados pelos testes sintéticos do leitor/serviço, sem editar dados reais.

Sem produção, VM Oracle, envio a autores, escrita de declarações ou análise de
manuscritos reais. A revisão humana e a homologação na infraestrutura real seguem
necessárias; não se anuncia validação integral ou detecção factual de uso de IA.
