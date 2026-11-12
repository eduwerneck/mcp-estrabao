# Etapa 13 — bloqueio da confirmação no adaptador OJS

Plugin 0.2.0.0, motor 0.12.0 e contratos preservados. Base: merge b51b48c do PR #12.
A implementação adiciona o primeiro ponto de integração ao OJS 3.3.0.20.
Não foi instalada nem exercitada em OJS real: validação local usa dublês mínimos
da API e pacotes Word sintéticos. Não é entrega pronta para produção.

## Ponto de execução verificado

Fontes consultadas em revisões exatas:

- [PKPSubmissionHandler::saveStep](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/pages/submission/PKPSubmissionHandler.inc.php): chama validate antes de execute.
- [Form::validate](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/form/Form.inc.php): hook baseado na classe concreta; retorno verdadeiro do callback usa o valor passado por referência como resultado da validação.
- [OJS SubmissionSubmitStep4Form](https://github.com/pkp/ojs/blob/d9c71f2e410830885604b9c35fb09c9dc3b2c821/classes/submission/form/SubmissionSubmitStep4Form.inc.php): conclusão e aviso de recebimento acontecem em execute.
- [PKPSubmissionSubmitStep4Form](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/submission/form/PKPSubmissionSubmitStep4Form.inc.php): grava data/progresso em execute, portanto esse hook seria tarde demais para bloquear.
- [PKPSubmissionFileService](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/services/PKPSubmissionFileService.inc.php): getMany filtra submissionIds/fileStages e inclui path do arquivo atual.

O plugin registra `submissionsubmitstep4form::validate` apenas se estiver
habilitado e `Config::getVar('estrabao', 'admission_gate') === true`. O callback
adiciona erro ao formulário, define retorno falso por referência e encerra o hook.
Não usa execute para tentar desfazer mudanças nem altera status para arquivada.
As validações nativas POST/CSRF rodam antes do hook; não há retorno verdadeiro
que as contorne. A autenticação e autorização da submissão continuam a cargo
do handler OJS; o adaptador também confere usuário, contexto e IDs consistentes.

## Arquivo e decisão

Busca no servidor os arquivos da submissão no estágio de submissão, sem anexos
dependentes. Exige exatamente um arquivo: escolha automática por nome/extensão
poderia analisar o anexo errado. Zero ou vários arquivos exigem revisão.
Confere os IDs novamente. Não aceita caminho, relatório nem decisão do POST.

O caminho vem do registro do OJS e deve resolver dentro de files_dir. Caminhos
absolutos, URLs, travessia, NUL e symlinks para fora da raiz são recusados.
Armazenamento remoto não está coberto. A leitura DOCX mantém limites existentes.
O idioma vem do locale da submissão (pt_BR, en_US, es_ES); outros ficam pendentes.
O hash é comparado antes/depois da análise e com o hash do leitor. Não há cache
ou aprovação reaproveitada após troca de arquivo. Não elimina todas as corridas
entre upload e conclusão; travas/transação ainda precisam de homologação.

`not_accepted` apresenta incompatibilidade APA e a proibição de ABNT.
Qualquer outro resultado ou exceção mantém revisão pendente e impede conclusão.
As mensagens não expõem conteúdo, caminhos ou exceções. Não há e-mail do plugin.
A confirmação nativa bloqueada não deve alcançar seu execute/aviso de recebimento;
isso precisa ser confirmado em instalação real, além do dublê local.

## Ativação futura, só em homologação

Não houve mudança no ambiente nem ativação nesta entrega. No laboratório isolado:

1. Instalar plugin 0.2.0.0 na versão exata do OJS. Colocar o motor src em diretório
   privado e confiável fora da raiz web, com DOM/ZIP e permissões de leitura.
2. Configurar no config.inc.php privado, seção `[estrabao]`, `engine_bootstrap`
   como caminho absoluto para src/bootstrap.php e `admission_gate = On`.
   A configuração e o motor não podem ser graváveis por autores.
3. Habilitar o plugin no contexto sintético. files_dir deve ser local e privado.
4. Exercitar confirmação via POST direto, CSRF inválido, usuários sem acesso,
   outra revista, vários anexos, corrupção, troca de versão e ABNT. Confirmar
   que data/progresso permanecem em rascunho e nenhum aviso externo é enviado.
5. Reverter removendo admission_gate ou desabilitando o plugin no laboratório.

**Limitação deliberada:** ainda não existe interface de revisão/liberação humana.
Como a APA completa permanece indeterminada, até documentos parcialmente
conformes ficam retidos. Não habilitar em produção. Esta etapa entrega o bloqueio
e seu adaptador; o fluxo operacional de revisão, auditoria e liberação autenticada,
proteção contra concorrência e cobertura de outros canais ainda estão pendentes.
Não cobre API, importação, quickSubmit ou mudanças editoriais fora do wizard.
Plugin desabilitado/configuração ausente não registra o bloqueio. Não representa
uma barreira universal de admissão na instalação inteira.
