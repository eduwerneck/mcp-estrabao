# Etapa 66 — triagem automática depois do recebimento

Política solicitada: receber a submissão e, havendo descumprimentos comprovados,
registrar uma decisão nativa de rejeição e enviar a carta com os motivos. O autor
corrige e apresenta outra submissão. Não há reabertura automática do trabalho
rejeitado nem conversão de toda inconclusão em infração.

Plugin **0.42.0.0**; motor **0.44.0** inalterado. Novo modo opt-in, desligado por
padrão, que substitui a barreira de confirmação somente quando ativado.

## Comportamento

- A confirmação continua passando pela autorização, pelo POST e pelo CSRF nativos.
  O OJS conclui primeiro o recebimento, com data, designações e confirmação padrão.
- A política usa o snapshot atual e as correções mapeadas da análise documental.
  Acrescenta a presença e o formato reconhecido das referências nos metadados e a
  declaração de financiamento. Resultados inconclusivos não viram motivos de rejeição.
- No formulário de metadados são disponibilizados Referências e Agências de fomento,
  usando os controles nativos. Isso vale por requisição, sem gravar novas configurações
  da revista. A orientação solicita a declaração **Sem financiamento** quando não
  houver apoio. Não se insere essa declaração automaticamente em nome dos autores.
- Com correções comprovadas: status nativo `STATUS_DECLINED`, decisão
  `SUBMISSION_EDITOR_DECISION_DECLINE`, registro explícito da política automática,
  carta no histórico de e-mails e encaminhamento pelo serviço de e-mail do OJS.
- Sem correções comprovadas: recebida e aguardando conferência editorial, sem aceite
  automático. A ausência de motivo de rejeição não certifica toda a APA nem o mérito.
  Os gerentes recebem notificações nativas. Falha do analisador também segue esse caminho.
- A tela de conclusão informa o resultado e mostra a carta. O editor encontra o mesmo
  resultado em Visualizar triagem, sem formulário para liberar um artigo já rejeitado.

O OJS ainda valida seus campos básicos obrigatórios antes de chegar à confirmação.
A política não transforma campos obrigatórios nativos em opcionais.

## Configuração e implantação

Depois de revisar/mergear, gerar e verificar novo release, guardar o plugin anterior
fora da raiz web e instalar o pacote com o web parado, conforme a Etapa 64. Não
alterar o banco ou os arquivos da revista real para testar. Não há mudança de motor.

Na homologação, conferir uma conta editorial ativa com `ROLE_ID_MANAGER` na revista.
Depois configurar em `[estrabao]` (o número abaixo deve ser o ID real dessa conta):

```ini
admission_gate = On
post_submission_triage = On
automation_editor_id = ID_DA_CONTA_EDITORIAL
```

A conta é a responsável pela política registrada; a requisição do autor não é
tratada como uma decisão manual do editor. ID ausente, inválido ou sem o papel
correto impede rejeição automática e encaminha para conferência editorial.

Para voltar ao modo anterior, definir `post_submission_triage = Off`. Isso afeta
confirmações futuras e não desfaz decisões ou mensagens já registradas.

A VM Oracle não foi atualizada por este trabalho. A revista em produção não foi
acessada nem modificada. A homologação ARM64 e a entrega real de e-mail ainda
precisam ser verificadas antes de qualquer implantação em produção.

## Registro, concorrência e falhas

O resultado e o estado do envio são guardados em uma entrada própria de
`submission_settings`, com limite de tamanho e sem nova tabela. O conteúdo não é
publicado pela API de metadados. As telas que o leem usam autorização nativa ou
a autorização editorial já existente.

Um lock de aplicação por revista/submissão serializa confirmações concorrentes.
A decisão, o status, o registro de auditoria e o marcador são gravados em uma
transação, com lock da submissão e nova leitura do snapshot e da análise. POST
repetido não executa novamente o recebimento nem o e-mail automático.

Antes de chamar o mailer, o estado muda para `sending`. Em sucesso, `sent`
significa encaminhado ao serviço de e-mail, não entrega garantida à caixa postal.
Em falha, `failed` preserva a decisão e chama os editores. Não há repetição
silenciosa do envio: um processo interrompido entre o envio e o registro final
pode ter enviado a mensagem. O editor deve conferir o histórico/serviço de e-mail
antes de reenviar manualmente. A carta segue disponível na triagem.

Não há worker ou recuperação automática de interrupção abrupta do processo. Se
ocorrer entre o recebimento nativo e o resultado, o editor deve tratar a submissão
pela fila nativa; não há rejeição presumida. O processamento é síncrono e deve
ser acompanhado na homologação com arquivos representativos. A transação de
upload com a confirmação continua sendo uma pendência das etapas anteriores;
a releitura detecta mudanças observadas, mas não estabelece esse lock conjunto.

## Verificação

- 17 verificações no OJS real 3.3.0.20/PHP 8.1.27: criação e upload nativos, campos
  visíveis, CSRF inválido, conclusão seguida de rejeição, carta ao autor/editor,
  uma decisão sob a conta configurada, um e-mail no histórico, POST repetido,
  analisador indisponível sem rejeição e notificação nativa ao editor.
- 10 verificações da política: presença de referências/financiamento, declaração
  Sem financiamento, agência declarada, padrão ABNT reconhecido, caixa alta
  isolada inconclusiva e teor da carta.
- Chromium com o HTML real do formulário: orientação de financiamento visível
  uma única vez e campo nativo Referências preservado.
- Regressão PHP: 2.120 testes, zero falhas. Integridade do pacote: 15 testes.

Evidências em [automatic-results.json](evidence/stage66/automatic-results.json) e
[guidance-results.json](evidence/stage66/guidance-results.json). O laboratório tem
rede interna, não publica portas e neutraliza o sendmail. A prova verifica o
registro e o encaminhamento ao mailer nativo; não envia e-mails externos.

A senha do banco exposta na sessão manual anterior continua pendente de troca.
Ao encerrar a homologação, desligar/encerrar os recursos temporários Oracle e
revisar os discos remanescentes para evitar cobranças.
