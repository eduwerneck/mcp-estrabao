# Ensaio operacional isolado — OJS 3.3.0.20

Procedimento da [Etapa 62](STAGE62.md): PHP **8.1.27**, usuário **100:101**
(apache na imagem), quatro workers do servidor PHP de desenvolvimento, MariaDB
10.6. Motor **0.44.0** e plugin **0.39.0.0**, sem alteração do produto.
Os scripts são utilitários de teste para os nomes fixos estrabao-lab-62; não são
ferramentas de backup ou implantação da revista real.

## Permissões e concorrência

O serviço HTTP executa como UID 100, com no-new-privileges. Somente a preparação
usa docker exec como root para instalar código e atribuir propriedades. Plugin e
motor pertencem a root e não são graváveis pelo serviço. config.inc.php é
root:101, modo **0440**; files_dir e review_dir são do serviço, **0700**.
Cache/public são graváveis; registros e locks privados continuam **0600**.
Outro UID (65534) não consegue ler a configuração, atravessar files_dir ou gravar
nos registros. Não tornar os diretórios privados públicos para resolver falhas.

Os auxiliares PHP ficam em /tmp/, fora da web, com root:101/0640. Cookies e
credenciais sintéticas permanecem privados. Sessões HTTP independentes usam os
mesmos endpoints, formulários, CSRF e autorização nativos do OJS. O cliente não
executa JavaScript, não substitui o servidor por mocks e não publica portas.

O teste dispara requisições simultâneas em workers reais. Duas confirmações com
a mesma revisão geram um único evento; a segunda é recusada por lock/revisão.
Um lock externo impede gravações. Mudanças concorrentes de arquivo/metadados
invalidam a análise correspondente no próximo acesso, preservando o histórico.
A revisão não APA permanece independente da liberação APA.

Um processo CLI que usa o store real também foi morto **dentro do callback,
antes do commit**: o arquivo anterior permaneceu idêntico e o sistema liberou o
lock para a próxima transação. Isso não simula queda de energia durante rename,
falha de disco ou atomicidade de uma transação entre arquivo, banco e registros.

**Limite importante:** o teste de troca de arquivo concorre com a gravação da
revisão; a tentativa de conclusão acontece depois de ambas terminarem. Não há
transação conjunta entre upload e confirmação final do wizard. Não tratar esse
ensaio como prova de ausência de qualquer corrida nesse intervalo. Essa garantia
continua pendente, assim como os canais fora do wizard.

## Backup consistente neste laboratório

1. Aguardar as requisições e os auxiliares de teste terminarem. Desativar tarefas
   e outros escritores; parar a aplicação de origem. O guard recusa origem ativa.
   Apenas parar o HTTP não seria suficiente com outros escritores independentes.
2. Com banco próprio ainda ativo, extrair o dump lógico nativo por
   mariadb-dump --single-transaction, sem data/comentários variáveis e com ordem
   por chave primária. Não publicar argumentos, variáveis de segredo ou o dump.
3. Capturar integralmente files_dir, incluindo manuscritos, registros e locks,
   e public/, preservando proprietários e modos. Incluir configuração e mapa de
   IDs sintéticos. Código plugin/motor é reinstalado pelos pacotes verificados da
   Etapa 61; precisa corresponder à versão do snapshot.
4. Guardar os cinco componentes e manifesto com SHA-256/tamanhos/inventário em
   diretório **0700**, arquivos **0600**, fora do Git e da raiz web. O dump e a
   configuração contêm segredos e dados, mesmo neste laboratório sintético.
5. Conferir o conjunto completo antes de restaurar. Falta, corrupção, link,
   permissão pública ou caminho fora das raízes esperadas são recusados. Hashes
   conferem integridade contra um manifesto confiável, não autenticam sua autoria.

Limites intencionais do utilitário sintético: 64 MiB por componente/TAR,
16 MiB por membro, 4.096 entradas por arquivo TAR. Arquivos e diretórios regulares
sob files/ e public/; sem links ou travessia de caminho. Não é uma política de
retenção, criptografia ou transporte de backups para dados reais.

## Restauração e recuperação ensaiadas

O destino é outra instalação sintética, com banco, volumes e credenciais de banco
novos, sem compartilhar volumes com a origem. Aplicação de destino parada.
O conjunto inteiro é verificado **antes de qualquer alteração** no destino.
Um dump adulterado foi recusado sem mudar o banco de destino.

No destino exclusivo desse ensaio, restaurar o SQL e reconstruir files_dir/public
pelos arquivos completos, removendo somente os resíduos do destino de laboratório.
Não misturar banco antigo com manuscrito/registro novo. A configuração preserva
os segredos/salt da aplicação restaurada e usa as novas credenciais do banco de
destino. Aplicar proprietário/modo antes de iniciar HTTP; conservar o usuário 100.
As senhas de teste das contas OJS são mantidas privadamente pelo harness.

Antes de qualquer login, o dump do banco restaurado foi comparado byte a byte ao
snapshot, e os arquivos foram comparados por conteúdo, proprietário e modo.
Depois foram exercitados login, revisão privada, CSRF e wizard nativos. A revisão
APA e a ficha não APA voltaram atuais porque banco, arquivos e versão do motor
correspondiam à mesma análise — não por editar ou fabricar eventos antigos.

Também foram corrompido e removido um registro no destino: a submissão ficou
retida. Uma **segunda recuperação completa** restaurou o registro original,
removeu um arquivo residual sintético e permitiu concluir com a revisão válida;
ABNT continuou bloqueada. Não se afirma recuperação por restaurar só um JSON,
nem que qualquer backup antigo possa liberar um manuscrito posterior.

Erros durante a reconstrução exigem manter o destino fora de atendimento e
repetir a restauração completa verificada. Este ensaio não certifica rollback
transacional de falhas de I/O a qualquer instrução, hardware/power-loss,
OPcache persistente, TLS/proxy, ARM64 ou servidores HTTP de produção.

## Reprodução

Requer as imagens por digest já presentes, Docker local, pacotes da Etapa 61
verificados e espaço livre. Reservar ao menos **8 GiB**, sem outro laboratório
ativo. Os nomes/diretórios de instalação devem ser novos; os scripts recusam
reutilização. Não apagar recursos de terceiros para contornar essa recusa.

```sh
python -m unittest discover -s tests/operations -v
python -m unittest discover -s tests/package -v
mkdir -p /workspace/scratch/estrabao-lab-62
chmod 700 /workspace/scratch/estrabao-lab-62
docker --host=unix:///var/run/docker.sock network create --internal estrabao-lab-62
python tools/release.py verify /workspace/scratch/estrabao-lab-61/release-current
python tests/lab/operations/setup.py source
python tests/lab/operations/concurrency.py source concurrency
docker --host=unix:///var/run/docker.sock stop estrabao-lab-62-source-web
python tests/lab/operations/backup.py snapshot
python tests/lab/operations/setup.py restore
python tests/lab/operations/recover.py
python tests/lab/operations/restored_flow.py restore restored
python tests/lab/operations/permissions.py restore permissions
python tests/lab/operations/quiesce.py
docker --host=unix:///var/run/docker.sock stop estrabao-lab-62-restore-web estrabao-lab-62-source-db estrabao-lab-62-restore-db
```

Na ausência dos pacotes, seguir [INSTALLATION.md](INSTALLATION.md), usando o commit
43eaf8f para reconstruir release-current no diretório esperado. Os 73 arquivos do
produto são idênticos na base 528c655. Ordem obrigatória: os ensaios alteram apenas
rascunhos sintéticos próprios e não são idempotentes. Não repetir em uma revista.
SQL, configurações, cookies, credenciais, snapshots e volumes ficam privados;
somente resultados redigidos entram no PR. Nenhum acesso à produção, à VM Oracle,
nenhuma mensagem externa e nenhuma publicação de ambiente fazem parte da entrega.
