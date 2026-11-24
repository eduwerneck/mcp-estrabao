# Etapa 68 — teste restrito na revista real

A revista real usa OJS 3.3.0.20/PHP 8.2.36 e já envia e-mails. Para testar a
triagem nesse ambiente, o plugin **0.43.0.0** acrescenta um escopo opcional por
ID de rascunho e um destinatário controlado. Motor **0.44.0** inalterado.

## Escopo

Quando `test_submission_id` está presente, somente esse trabalho recebe a
triagem, seus controles adicionais e suas telas. ID zero ou inválido não
seleciona nenhum trabalho. Parâmetros da requisição não ampliam esse escopo.
O serviço também verifica o ID antes de concluir a submissão.

Outros trabalhos passam pelo fluxo nativo: não recebem bloqueio legado,
decisão, carta automática ou novos campos do plugin. No modo de teste, as
orientações globais do formulário editorial e a data adicional nas páginas de
artigos publicados ficam desativadas. A lista oferece Visualizar triagem somente
na linha selecionada; o acesso direto às outras revisões também é recusado.

A carta automática do trabalho selecionado vai somente para `test_recipient`,
validado como endereço, e recebe `[TESTE HOMOLOGAÇÃO]` no assunto. O recebimento
nativo do OJS continua usando os seus destinatários normais; por isso o rascunho
deve conter apenas autores em caixas controladas. Sem destinatário válido, a
confirmação de teste é recusada antes de executar o recebimento.

Sem a chave `test_submission_id`, o modo de homologação anterior continua
inalterado. Não remover essa chave durante o teste na revista real: sua ausência
representa o modo geral, e não um escopo vazio.

## Verificação

- 17 verificações da configuração: ID selecionado, demais IDs, ID ausente,
  configurações inválidas e validação do destinatário.
- 9 verificações no OJS real do laboratório: rejeição do selecionado, destinatário
  exclusivo, assunto de teste, leitura editorial e conclusão nativa de outro
  trabalho sem decisão, marcador ou carta do plugin.
- Chromium autenticado: link somente na linha selecionada e ausência em outra.
- Regressão PHP: 2.120 testes, zero falhas. Integridade do pacote: 15 testes.

Evidências em [scoped-results.json](evidence/stage68/scoped-results.json) e
[scoped-browser-results.json](evidence/stage68/scoped-browser-results.json).
Ensaios com rede isolada e sendmail neutralizado; não houve mensagens externas
nem acesso ao servidor de produção.

O laboratório nativo usa PHP 8.1.27, e a regressão offline usa PHP 8.4.27. Ainda
não foi executado teste nativo em PHP 8.2.36. A execução controlada pelo operador
na revista real, com pré-verificação e rollback, é necessária para validar esse
ambiente e a entrega ao Gmail. Não se declara compatibilidade plena antes disso.

Procedimento para o operador: [TESTE-EUGENIO.md](TESTE-EUGENIO.md).
