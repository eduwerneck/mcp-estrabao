# Instalação e atualização — laboratório OJS 3.3.0.20

Procedimento ensaiado na [Etapa 61](STAGE61.md), com dados sintéticos. Plugin
**0.39.0.0**, motor **0.44.0**. Distribuição **manual em dois componentes**;
enviar somente o arquivo do plugin pela galeria não configura o motor nem os
registros privados. A [Etapa 62](OPERATIONS.md) acrescenta ensaio de permissões,
concorrência e recuperação, com limites operacionais explícitos. Não é procedimento
de implantação da revista.

## Pacotes e origem

Na raiz do repositório, Python 3.11 e Git, sem rede nem Composer:

```sh
python tools/release.py build --ref 43eaf8f --out /tmp/estrabao-release-61
python tools/release.py verify /tmp/estrabao-release-61
python -m unittest discover -s tests/package -v
```

O diretório de saída deve ser novo. O gerador lê **blobs do commit**, ignorando
alterações locais, e produz:

| Arquivo | Destino/conteúdo |
| --- | --- |
| plugin.tar.gz | estrabao/ dentro de plugins/generic/ do OJS; PHP, templates, JS, locales e version.xml |
| engine.tar.gz | estrabao-engine/ fora da raiz web; src/ e contracts/ |
| manifest.json | Commit completo, data do commit, versões e SHA-256 de cada um dos 73 arquivos |
| SHA256SUMS | SHA-256 dos dois arquivos compactados e do manifesto |

Para outro commit revisado, fornecer seu identificador explicitamente. Os hashes
publicados nesta etapa se referem a **43eaf8f**, não ao commit futuro que incorporar
os scripts. Arquivos iguais, metadados fixos e gzip sem nome/data de execução
produzem bytes iguais na reconstrução com o mesmo gerador/runtime. O timestamp
TAR é o do commit: **zero impede o Smarty desse OJS de encontrar os templates**.

O verificador recusa adulteração, links, membros extras/ausentes/duplicados,
caminhos fora das raízes permitidas, permissões inesperadas e conteúdo acima dos
limites (256 entradas; 2 MiB por arquivo; 16 MiB por TAR expandido). Não extrai.
Os hashes conferem integridade relativa ao manifesto; **não são assinatura de
autoria**. Obter o manifesto/hashes de uma origem confiável e conferir o commit
revisado antes de extrair. Não executar pacotes de origem desconhecida.

Não entram nos pacotes CLI, testes, documentos, bibliografias, credenciais,
config.inc.php, relatórios ou registros editoriais. O gerador também não instala
OJS, não envia arquivos a servidores e não publica releases no GitHub.

## Configuração da cópia limpa

1. Instalar OJS **3.3.0.20** em instância/banco/arquivos novos. No ensaio: PHP
   **8.1.27**, MariaDB **10.6**, imagem e dependências identificadas no
   [preflight](evidence/stage61/preflight.json). DOM/libxml, ZIP, JSON/hash/PCRE e
   PDO MySQL precisam estar disponíveis. Não homologamos OJS em PHP 8.4 nem um
   runtime/servidor de produção por esse teste.
2. Usar rede interna sem portas publicadas. Neutralizar sendmail e manter SMTP,
   beacon e tarefas agendadas desativados. Criar somente contas/manuscritos
   sintéticos, com endereços synthetic.invalid. Não reutilizar banco, volume,
   configuração, credenciais ou manuscritos da revista ou do laboratório anterior.
3. Declarar charset de conexão **utf8** no instalador CLI e allowed_hosts com
   **127.0.0.1** no laboratório. O instalador CLI dessa imagem deixou charset
   vazio/allowed_hosts inadequado quando usados os defaults; conferir o arquivo
   resultante sem exibir seus segredos.
4. Verificar os dois pacotes e extrair plugin.tar.gz em
   /var/www/html/plugins/generic/ e engine.tar.gz em /opt/. A instância de ensaio
   recebe somente esses artefatos; não há montagem do checkout como código ativo.
   O motor fica em /opt/estrabao-engine/, sem cópia de src/contracts/CLI na web.
5. Criar /var/www/files/estrabao-review com modo **0700**, dentro de files_dir e
   fora da raiz web, sob o usuário do PHP. Registros/locks usam **0600**. Não usar
   links simbólicos. Na cópia, acrescentar a seção abaixo a config.inc.php,
   conservando a linha inicial de proteção PHP e as demais configurações:

```ini
[estrabao]
admission_gate = Off
engine_bootstrap = /opt/estrabao-engine/src/bootstrap.php
review_dir = /var/www/files/estrabao-review
```

6. Dentro da cópia OJS e a partir de sua raiz, registrar pelo utilitário nativo:

```sh
php lib/pkp/tools/installPluginVersion.php plugins/generic/estrabao/version.xml
```

Conferir a versão **0.39.0.0** em versions e a descoberta em Website → Plugins
instalados. Habilitar pela caixa de seleção e confirmar o diálogo nativo. O gate
continua desligado enquanto admission_gate for Off. Só no laboratório preparado,
mudar para On e testar os cenários da Etapa 61. Desativar o gate/plugin restaura
o comportamento nativo: não constitui enforcement permanente em outros canais.

## Atualização ensaiada

Base anterior real: **7632136**, plugin **0.37.0.0**, motor **0.42.0**. Criou-se
outra instalação limpa OJS, instalou-se esse pacote e registraram-se revisões APA
e não APA pelo navegador, antes de substituir qualquer código.

Na cópia isolada, sem requisições editoriais concorrentes, verificar o novo par
plugin/motor e substituir **somente os dois diretórios de código pertencentes ao
pacote**. Não substituir config.inc.php, files_dir, banco ou registros. Registrar
a nova versão pelo mesmo utilitário nativo. Conferir a versão atual e a linha
anterior no histórico de versions, os 73 hashes instalados e os registros privados.

Habilitar/desabilitar não apaga o histórico. A mudança do motor altera a identidade
da análise: a liberação antiga deixa de valer e a ficha não APA volta a pendente,
com histórico preservado. Exigir nova análise/revisão explícita; não editar eventos
nem marcar versões antigas como aprovadas. O teste confirmou isso antes de uma
nova liberação e conclusão pelo wizard.

Em atualizações futuras, limpar caches de dados/templates pelo administrador da
cópia e recarregar o runtime quando houver cache persistente de PHP. Não remover
arquivos editoriais para limpar cache. O ensaio usou PHP sem OPcache persistente;
cache/invalidação sob infraestrutura representativa continua pendente após o
ensaio limitado da Etapa 62.

Esse procedimento **não demonstra rollback** do banco e dos registros.
Backup/restauração foram ensaiados na cópia isolada da [Etapa 62](OPERATIONS.md).
Não interpretar a preservação
dos hashes como prova de recuperação operacional ou transação conjunta com uploads.

## Reprodução automatizada do laboratório

Requer Docker local, as três imagens por digest do preflight já disponíveis e
espaço livre suficiente (reservar ao menos 8 GiB). Os scripts usam exclusivamente
o socket local, a rede/nome estrabao-lab-61 e scratch fora do Git. Recusam diretórios
ou contêineres de instalação existentes; executar em workspace limpo, sem apagar
recursos anteriores para contornar essa recusa. Não há pull automático ou portas.

```sh
mkdir -p /workspace/scratch/estrabao-lab-61
chmod 700 /workspace/scratch/estrabao-lab-61
docker --host=unix:///var/run/docker.sock network create --internal estrabao-lab-61
python tools/release.py build --ref 43eaf8f --out /workspace/scratch/estrabao-lab-61/release-current
python tools/release.py build --ref 7632136 --out /workspace/scratch/estrabao-lab-61/release-previous
python tests/lab/install/prepare.py fresh
python tests/lab/install/browser.py fresh start
python tests/lab/install/cycle.py fresh
python tests/lab/install/negative.py fresh negative
python tests/lab/install/ui.py fresh ui
python tests/lab/install/browser.py fresh stop
python tests/lab/install/prepare.py upgrade
python tests/lab/install/browser.py upgrade start
python tests/lab/install/cycle.py upgrade
python tests/lab/install/negative.py upgrade negative
python tests/lab/install/extended.py upgrade extended
python tests/lab/install/browser.py upgrade stop
docker --host=unix:///var/run/docker.sock stop estrabao-lab-61-fresh-web estrabao-lab-61-fresh-db estrabao-lab-61-upgrade-web estrabao-lab-61-upgrade-db
```

Executar os scripts na ordem: os rascunhos são novos e os testes de conclusão
não são idempotentes. Um navegador por vez, com duas sessões autorizadas. O
instalador gera segredos novos em diretório 0700; configurações/logs privados não
entram no PR. Helpers PHP de teste ficam em /tmp/ do contêiner, nunca na web.
Encerrar serviços depois do ensaio e conservar os dados sintéticos privados
somente para diagnóstico. O relatório público contém apenas resultados redigidos.
