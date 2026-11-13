# Etapa 24 — consistência de fomento entre Word e OJS

Base 9b75673 (merge do PR #24), motor 0.18.0 e plugin 0.10.0.0.
Implementa a comparação autorizada pelo responsável, sem presumir financiamento
quando nenhuma fonte o informa, nem transformar agradecimento em fomento.

## Campo nativo e disponibilidade

No OJS 3.3.0.20, Publication.supportingAgencies é um mapa multilíngue de listas.
O schema nativo descreve financiamento **ou outro apoio institucional**. A opção
Context.agencies aceita 0, enable, request e require. request/require oferecem o
campo durante a submissão; enable o oferece só no fluxo editorial.

O adaptador lê exclusivamente a publicação corrente, já vinculada à submissão e
revista autorizadas. Só interpreta vazio como ausência confirmada quando o campo
está disponível ao autor na submissão (request/require). Configuração ausente,
desabilitada ou enable, forma inesperada e locales não normalizados produzem
fonte indisponível. Não solicita nem usa metadados postados pelo cliente. Não
habilita o campo nem altera sua obrigatoriedade. A configuração real da revista
continua desconhecida: não foi acessada produção.

## Snapshot 1.2 e compatibilidade

Snapshot 1.2 acrescenta supporting_agencies, mapa opcional de listas textuais por
locale, como keywords. Campo ausente/null significa indisponível; mapa/listas
vazias significam vazio confirmado. O produtor deve estabelecer a disponibilidade,
sem fabricar ausência. Um operador não deve preencher [] para fonte não lida.

Snapshots 1.0/1.1 continuam aceitos e não admitem o campo novo. 1.0 mantém a
restrição de subtítulo. Não exige fomento em três idiomas. O adaptador aceita
pt_BR/en_US/es_ES e fica inconclusivo em variantes ainda não normalizadas.
Alteração de supporting_agencies muda o digest; a geração revalida o snapshot da
publicação atual, incluindo esse campo, antes de entregar a minuta.

## Manuscrito e classificação

FundingConsistency usa apenas texto Word completo e português. Cauda bibliográfica
ou perfil parcial com imagens/tabelas não comprova presença/ausência global.
Na seção única Fontes de Fomento, coleta texto até a próxima seção canônica ou fim,
com fronteiras/estilos conhecidos. Seção vazia, duplicada, formato incerto, texto
extenso, negação complexa, placeholders, recursos próprios/autofinanciamento ou
apoio institucional/técnico/logístico explícito exigem revisão.

Fora dessa seção reconhece apenas frases explícitas e delimitadas de autoria do
próprio estudo, como “Este estudo foi financiado pela Agência Sintética.”, e
negações financeiras fechadas, como “Não houve financiamento.”. Não usa declarações
em referências. Sinais financeiros fora dos padrões reconhecidos ficam inconclusivos;
agradecimentos genéricos não são classificados como financiamento. É cobertura
lexical limitada, não interpretação semântica de todas as formas de declarar apoio.

Reconhece algumas declarações portuguesas fechadas de ausência/não aplicabilidade
nos campos adequados, sem equipará-las a vazio. Declarações mistas ou negativas
não reconhecidas permanecem inconclusivas. A redação não é convertida ou reescrita.

## Resultado manuscript.funding 2.0.0

| Word | OJS | Tratamento |
| --- | --- | --- |
| Fomento declarado | Campo disponível vazio | Não conforme; orientar preenchimento no OJS. |
| Fomento declarado | Declaração explícita de ausência/não aplicabilidade | Não conforme; conferir a informação correta e harmonizar. |
| Sem declaração reconhecida/ausência explícita | Sem apoio declarado/ausência explícita | Conforme apenas para ausência de conflito observado; não certifica ausência de financiamento real. |
| Sem declaração reconhecida/ausência explícita | Apoio registrado | Indeterminado; confirmar natureza financeira e, se aplicável, completar Word. Apoio não financeiro não comprova omissão de fomento. |
| Declaração nos dois | Apoio registrado | Indeterminado; comparar agências, projetos e números de financiamento manualmente. |
| Fonte indisponível ou declaração ambígua | Qualquer | Indeterminado; não presumir omissão. |

Não faz correspondência automática de siglas/nomes completos, validação de projetos,
identidade da agência ou consulta externa. Nomes, declarações e identificadores não
aparecem nas evidências: somente estados, contagem de entradas, motivo fixo e hash.
128 entradas por locale e 8192 bytes por entrada/bloco financeiro são limites
adicionais de inspeção; mantidos os limites ZIP/XML e 4096 parágrafos anteriores.

## Devolutiva e fluxo

O gerador 1.0.0 conhece a regra nova. Só os dois conflitos comprovados geram
correções fixas aos autores, sem expor agência ou projeto. Os demais têm orientação
interna no painel: comparação entre fontes, natureza do apoio ou disponibilidade.
Não cria bloqueio automático por fomento, não grava declarações ou decisões e
não muda o gate APA. O motor novo invalida liberações de motor anterior pelo
fingerprint existente. Atualizar motor e plugin conjuntamente em homologação e
limpar caches de idioma. Nenhuma implantação foi realizada.

A regra substitui a antiga pendência condicional dentro de ManuscriptStructure;
agora compara fontes independentemente de artigo/comunicação. Não exige seção de
fomento em todo texto nem declaração de não uso universal. São 48 resultados sem
DOCX e 49 com artigo ou comunicação. Schemas de relatório/minuta permanecem 1.0;
schema de entrada 1.2 preserva as versões anteriores.

## Validação

882 testes sintéticos, zero falhas, em PHP 8.4.27 e 8.1.27; 30 novos.
Lint de 80 arquivos PHP. Cobrem direções da comparação, ausência explícita,
apoio não financeiro, recursos próprios, referências, fonte parcial, contrato,
locales, configuração, DAO contra request, privacidade e integração com DOCX/minuta.

Quatro verificações no OJS 3.3.0.20/Chromium local: orientação traduzida, campo
indisponível sem acusação, ficha interna fora da carta e estado editorial inalterado.
O ensaio foi apenas leitura de registros sintéticos já existentes, sem habilitar
campos ou editar metadados. Captura inspecionada em evidence/stage24. Reproduzir no
laboratório da Etapa 15 com PYTHONPATH=tests/lab python tests/lab/funding-panel.py.
As direções com declaração/campo disponível foram exercitadas por testes sintéticos
do serviço e do leitor, não por edição de dados no navegador.

Sem dados reais, produção, VM Oracle, comunicação com autores ou publicação do
ambiente. Equivalência entre agências e comparação de números continuam humanas.
