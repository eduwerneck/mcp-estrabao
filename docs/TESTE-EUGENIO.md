# Eugenio — instalação e teste de uma única submissão

Objetivo: validar, no OJS da revista Estrabão, uma rejeição automática de teste e
a entrega da carta a **eduwerneck@gmail.com**, usando o envio de e-mail existente.
Não alterar SMTP, senha ou transporte de e-mail da revista. Nenhuma credencial
precisa ser enviada pelo chat.

Ambiente informado: OJS **3.3.0.20**, PHP web **8.2.36**, Linux e MySQL. Produto:
plugin **0.43.0.0**, motor **0.44.0**. O teste nativo anterior foi em PHP 8.1.27;
validar PHP 8.2, extensões e permissões antes da ativação. A revista pode continuar
recebendo outros trabalhos: o escopo deve permanecer restrito durante todo o teste.

## 1. Preparação

- Fazer backup recuperável do banco, do código/configuração, dos arquivos
  editoriais privados e de `public`. Guardar os backups fora da raiz web.
- Anotar os caminhos e o usuário real do processo PHP. Usar o mesmo PHP 8.2
  configurado para o site também nos comandos de instalação e pré-verificação.
- Eduardo criará um rascunho chamado **TESTE — homologação da triagem automática**.
  Obter seu ID pela URL `submissionId=…`. Deve permanecer incompleto. Os autores
  e o contato devem usar somente Gmail de Eduardo ou a caixa controlada da revista.
- Identificar o ID de uma conta editorial ativa com papel `ROLE_ID_MANAGER` na
  revista `estrabao`. Não presumir que seja o ID 2 usado na Oracle.
- Antes de habilitar o plugin, inserir no `config.inc.php` a seção abaixo,
  preservando as configurações existentes. Neste primeiro momento, usar ID zero:

```ini
[estrabao]
admission_gate = Off
post_submission_triage = Off
test_submission_id = 0
test_recipient = eduwerneck@gmail.com
automation_editor_id = ID_EDITOR_REAL
engine_bootstrap = /CAMINHO_PRIVADO/estrabao-engine/src/bootstrap.php
review_dir = /DIRETORIO_PRIVADO_DE_ARQUIVOS/estrabao-review
```

A chave `test_submission_id` deve existir desde antes da ativação. ID zero impede
selecionar qualquer trabalho; não apagar a chave para encerrar o teste.

## 2. Pacotes e instalação

Gerar o release a partir do commit revisado/mergeado com `tools/release.py build`,
e conferir com `tools/release.py verify`. O diretório contém `plugin.tar.gz`,
`engine.tar.gz`, `manifest.json` e `SHA256SUMS`. Conferir plugin 0.43.0.0,
motor 0.44.0 e 80 arquivos no manifesto. Nunca extrair arquivos de teste/credenciais
ou copiar o `config.inc.php` da Oracle para a produção.

- O botão **Enviar novo plugin** do OJS recebe somente `plugin.tar.gz`.
- `engine.tar.gz` deve ser extraído pelo operador **fora da raiz web**, preservando
  seu diretório `estrabao-engine`. O PHP precisa de leitura, não de permissão geral
  de escrita sobre o motor.
- Criar `review_dir` dentro do `files_dir` privado, com acesso somente ao usuário
  PHP (modo 0700). Não criar essa pasta sob `public` ou sob os diretórios do plugin.
- Conservar o plugin desabilitado durante a instalação. Se for necessário extrair
  manualmente, coordenar uma breve manutenção e preservar proprietário/permissões.
  Registrar a versão com o PHP do site, a partir da raiz OJS:

```bash
CAMINHO_PHP_82 lib/pkp/tools/installPluginVersion.php plugins/generic/estrabao/version.xml
```

## 3. Pré-verificação e ativação

Substituir ID zero pelo ID real do rascunho e preencher o ID editorial correto.
Manter `admission_gate` e `post_submission_triage` desligados nessa conferência.
Obter `tools/ojs-test-preflight.php` do mesmo commit revisado, guardar fora da raiz
web e executar como usuário PHP, tendo a raiz do OJS como diretório de trabalho:

```bash
CAMINHO_PHP_82 /CAMINHO_PRIVADO/ojs-test-preflight.php
```

O script só lê dados e informa PHP, extensões, caminhos privados, escopo,
destinatário, rascunho e papel editorial. Não imprime senhas, não ativa o plugin e
não envia mensagens. Todos os itens devem estar OK. Conferir também a versão OJS
na tela de informações do sistema e que o motor é 0.44.0 no release verificado.

Depois habilitar o plugin na revista e mudar **somente**:

```ini
admission_gate = On
post_submission_triage = On
```

Manter `test_submission_id` com o ID combinado e `test_recipient` com o Gmail de
Eduardo. Não habilitar envio geral ou alterar SMTP. Guardar os logs PHP durante o
ensaio e conferir a interface de um trabalho fora do escopo.

## 4. Ensaio com Eduardo

1. No rascunho selecionado, enviar o DOCX fictício usado na homologação e usar
   dados de teste, preservando pelo menos uma não conformidade verificável.
   Não usar manuscrito real de outro autor.
2. Conferir os campos Referências e Agências de fomento no formulário e a
   orientação Sem financiamento. Título, resumo e palavras-chave são verificados
   em português, inglês e espanhol; ausência deve constar da devolutiva.
3. Eduardo confirma uma vez. Conferir recebimento nativo, rejeição na etapa de
   submissão, carta exibida ao autor/editor e registro na lista de arquivados.
4. Confirmar que a decisão está associada à conta editorial configurada e
   identificada como automática. Conferir um e-mail do plugin no histórico, com
   destinatário exclusivo `eduwerneck@gmail.com` e assunto `[TESTE HOMOLOGAÇÃO]`.
5. Eduardo confere a chegada ao Gmail e a legibilidade da carta. Encaminhado ao
   serviço de e-mail não significa entrega comprovada; não repetir confirmações
   ou reenviar automaticamente se houver resultado incerto.

O OJS pode enviar também sua confirmação nativa de recebimento, como já fazia.
Isso é diferente da carta automática de rejeição. Não chamar esse ensaio de
ativação geral nem de validação científica/APA integral do manuscrito.

## 5. Encerramento e rollback

Após o ensaio, desligar `post_submission_triage` e `admission_gate`, definir
`test_submission_id = 0` e desabilitar o plugin. Guardar a evidência do resultado
e os logs pertinentes. Reativação geral exige decisão posterior do responsável.

Em erro PHP ou mudança inesperada, desabilitar o plugin/configuração e restaurar
os componentes anteriores conforme o backup. Não restaurar todo o banco sobre
submissões legítimas recebidas durante o ensaio sem avaliar os dados novos.
Desabilitar o modo não desfaz decisão nem e-mail já enviado: o trabalho de teste
pode permanecer arquivado, marcado como teste.

As limitações de recuperação de envio interrompido e da transação conjunta entre
upload e confirmação continuam descritas em [STAGE66.md](STAGE66.md). Um envio
incerto exige conferência do histórico/servidor, não repetição automática.
