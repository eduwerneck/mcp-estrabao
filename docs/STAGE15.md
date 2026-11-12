# Etapa 15 — homologação local real e feedback visível ao autor

Base 0bd0a1e (merge do PR #14), plugin 0.3.1.0, motor 0.12.0.
A instalação OJS 3.3.0.20 foi criada e exercitada localmente em Docker, com
banco MariaDB e navegador Chromium reais. Nenhum acesso à revista ou à VM Oracle.

## Ambiente e isolamento

Imagens oficiais obtidas e digests observados:

- `pkpofficial/ojs:3_3_0-20` — `sha256:87198f196f9f396ae0342a21dba6b9a9cf7afe35c58576758ee37998adf2c9e1`.
  A versão 3.3.0.20 foi confirmada no dbscripts/xml/version.xml; PHP 8.1.27.
- `mariadb:10.6` — `sha256:c7d726a1c0d29e486a1ab4ef0c76c75431a11428e76c50e99ba32106cea04114`.
- `selenium/standalone-chromium` — `sha256:b211549a4c35f11826bc1a7559b9f23ff055198517f8cb7d49ef74df01c5e38d`.
  Selenium 4.52.0 e Chromium 156.0.8078.79.

Rede Docker `estrabao-lab-15` com `Internal=true`, sem saída externa. Banco
sem publicação de porta; app vinculado apenas a loopback, navegador compartilha
a rede da aplicação. Neste daemon, as portas não ficam acessíveis pelo host na
rede internal: o driver chama WebDriver via docker exec e loopback interno.
Não se abriu rede externa para contornar essa restrição.

O processo PHP usa `sendmail_path=/bin/true`. Não há credenciais SMTP; os endereços
sintéticos usam synthetic.invalid. Não houve envio de email externo. Credenciais
aleatórias do laboratório ficam em diretório privado fora do repositório.
O navegador Playwright direto não estava instalado e seu CDN retornou 403;
a imagem oficial Selenium permitiu o ensaio sem alterar a política do ambiente.

## Problemas encontrados e corrigidos

1. **Mensagens ausentes:** Plugin::getLocaleFilename exige um catálogo mestre
   en_US existente antes de registrar o catálogo localizado. Havia só pt_BR,
   produzindo chaves `##plugins.generic.estrabao...##`. Foram acrescentados
   catálogos completos en_US e es_ES com as mesmas chaves de pt_BR.
2. **Bloqueio sem explicação:** o callback adicionava erro e retornava true,
   fazendo Form::validate sair antes de criar a notificação nativa. Agora adiciona
   os erros e retorna false para o hook, permitindo ao validador nativo criar a
   notificação e concluir isValid=false. Não significa liberar a submissão.
3. **Correções acionáveis:** incompatibilidades bibliográficas comprovadas agora
   incluem até cinco orientações distintas já produzidas pela auditoria, sem
   copiar nomes, títulos, DOI ou caminhos. Inconclusivo não recebe acusação ABNT.

O ensaio real confirmou o feedback visível após clicar Finalizar Submissão e
confirmar o diálogo nativo. A carta de desk consolidando todas as áreas do motor
continua um fluxo editorial distinto: este adaptador integra as referências APA,
não todos os metadados e requisitos formais. Não envia mensagens automaticamente.

## Evidências

São 17 verificações HTTP/UI com sessões reais de autor e gerente, com checagem
independente do estado no banco. [Resultado JSON](evidence/stage15/e2e-results.json):

- ABNT sem botão de liberação e POST direto impedido, mantendo progresso 4 e
  date_submitted nulo; motivo e correções visíveis na UI.
- Traduções carregadas e liberação ativa mostrada. A versão liberada concluiu
  o wizard: progresso 0, data registrada e evento setStep=5.
- Revogação impediu conclusão. Liberação não contornou CSRF nativo inválido.
- Autor não acessou revisão nem download editorial; gerente baixou bytes DOCX.
- Nova revisão sintética, com novo fileId, invalidou liberação e impediu conclusão.
  A troca usou serviços/DAO no seed local, não o componente visual de upload.
- Rota com PATH_INFO e rota por query funcionaram; formulário de busca preservou
  journal/page/op quando disable_path_info estava On.

Capturas inspecionadas:
[feedback ABNT](evidence/stage15/author-abnt-feedback.png),
[liberação editorial](evidence/stage15/editorial-release.png) e
[rota por query](evidence/stage15/review-query-route.png).
Todas contêm somente pessoas, revista e manuscritos sintéticos.

## Repetição do ensaio

Scripts opcionais em tests/lab, fora do runner padrão. Exigem exclusivamente o
laboratório local isolado descrito acima; nunca reutilizar dados/credenciais reais.

1. Criar a rede internal e containers com nomes estrabao-lab-15-db,
   estrabao-lab-15-web e estrabao-lab-15-browser. Montar src privado em
   /opt/estrabao/src e plugin no diretório OJS, ambos read-only. Configurar OJS
   com base_url http://127.0.0.1:18080, DB host db e nome estrabao_synthetic.
2. Instalar OJS com conta sintética. Configurar admission_gate, engine_bootstrap
   e review_dir segundo STAGE13/14; criar review_dir 0700 no files_dir privado.
3. Criar, fora da raiz web, `/tmp/lab-credentials.json` com admin_username,
   admin_password, author_username e author_password exclusivamente sintéticos.
   Copiar tests/lab/seed.php ao container e executá-lo com
   ESTRABAO_SYNTHETIC_LAB=1. O seed também confere URL e nome do banco. Ele cria
   cinco novos rascunhos e grava /tmp/lab-state.json. Não é migrador de produção.
4. Copiar state-query.php, http-bridge.php e replace.php para /tmp no container
   web, com estes mesmos nomes. Copiar lab-state.json para state.json no
   ESTRABAO_LAB_DIR privado do host. Ali também deve existir credentials.json.
5. Importar tests/lab/browser.py, criar Browser(), fazer login('admin') e guardar
   seu id em admin-session.txt; repetir para author/author-session.txt. O browser
   usa o WebDriver em loopback através do container web; não publica segredos.
6. Rodar tests/lab/smoke.py com ESTRABAO_LAB_DIR configurado. Exige seed novo:
   a conclusão do rascunho review é irreversível dentro desse ensaio. Gerará
   evidências e JSON no diretório privado. Conferir logs e estado do banco.
7. Desligar os containers do laboratório ao terminar. Não publicar nem exportar
   config, credenciais, cookies ou IDs de sessão. Somente evidências sintéticas
   sem segredos podem ser adicionadas ao repositório.

Os scripts documentam a configuração exercitada; não são um instalador universal
ou um provisionador da VM. OJS não foi adicionado à configuração publicada do chat.

## Limites restantes

Homologação local do wizard e tema padrão, sem provar funcionamento no tema da
revista, ARM64, proxies reversos, importação/API/quickSubmit, concorrência com
upload, durabilidade/backup ou implantação operacional. O texto nativo da etapa
4 ainda diz “pronta para ser finalizada” sob o aviso de bloqueio; o aviso prevalece
no servidor. Há uma chave de navegação não traduzida no próprio núcleo dessa
imagem, distinta dos catálogos do plugin, registrada nas capturas.

A leitura de DOCX complexos continua conservadora: cobertura incompleta retém o
arquivo e não comprova descumprimento editorial. Um teste documental solicitado
pelo usuário foi feito separadamente, fora do repositório e fora do laboratório;
seu texto/arquivo não integra testes, capturas, fixtures ou commits.
