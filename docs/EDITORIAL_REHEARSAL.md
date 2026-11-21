# Ensaio editorial completo no laboratório

Procedimento da [Etapa 63](STAGE63.md), OJS **3.3.0.20**, motor **0.44.0**,
plugin **0.39.0.0**. Usa a cópia sintética restaurada na Etapa 62, com o serviço
100:101 e rede interna, sem portas públicas. Não usa banco, arquivos ou contas
da revista real e não publica artigo nem envia a minuta ao autor.

## O percurso exercitado

1. O autor entra pelo HTTP nativo e inicia a etapa 1 do wizard. O OJS cria a
   submissão e sua autoria; o teste não insere rascunho ou arquivo pelo DAO/SQL.
2. O autor envia um DOCX sintético por **multipart autenticado** ao controlador
   nativo de upload. Confere-se o SHA-256 do arquivo recebido pelo servidor.
3. O autor avança pela etapa 2 e grava título, resumo e referências pela etapa 3.
   Há referências em ABNT, título em caixa alta e metadados EN/ES ausentes.
4. A tentativa de confirmação na etapa 4 fica retida. O editor não recebe botão
   de aprovação APA para ABNT. Gera a minuta privada que aponta incompatibilidades
   APA e os itens de diretrizes não cumpridos, incluindo campos EN/ES ausentes.
5. A ficha humana oferece os 12 grupos em estado pendente. O editor registra
   ajustes de metadados e conferências de declarações. Isso não libera APA nem
   permite a submissão com ABNT. A minuta não modifica o Word nem cria liberação.
6. O autor envia **nova revisão pelo upload nativo**, mantendo o ID lógico de
   submission_file e recebendo outro file_id. O DOCX traz citações/referências no
   subconjunto APA reconhecido, com títulos de livros em itálico. O autor também
   corrige título e preenche metadados em três idiomas no OJS, inclusive palavras-
   chave. As duas referências no campo OJS preservam um Enter entre elas.
7. A ficha antiga não pode ser reaplicada. A nova minuta deixa de apontar ABNT e
   campos EN/ES vazios, mantendo os demais diagnósticos pertinentes. O histórico
   não APA permanece, mas a conferência anterior fica desatualizada. A correção
   sozinha não libera o wizard; registrar a nova ficha também não libera APA.
8. O autor não consulta a área privada. O editor confirma explicitamente a revisão
   APA da versão corrigida. CSRF inválido continua recusado. A revogação retém
   novamente o rascunho; uma nova liberação explícita permite concluir.
9. O OJS grava data de submissão e progresso zero, com status de recebimento e
   **sem data de publicação**. Arquivo corrigido e históricos permanecem iguais
   aos imediatamente anteriores à conclusão. Estados dos rascunhos anteriores
   permanecem iguais aos do início do ensaio.

O resultado é recebimento da submissão, **não aceite editorial ou publicação**.
A ficha não APA pode manter pendências e ajustes; o gate existente é de APA.
O ensaio não transforma a ficha em novo bloqueio nem comprova adequação integral
às diretrizes. As confirmações humanas são ações sintéticas do teste. Declarações
CRediT/fomento/ética/IA são textos fictícios, sem verificação de veracidade.

## Configuração e isolamento

O auxiliar de preparação confere o contato editor@synthetic.invalid e configura
**somente essa revista sintética** para metadados em PT/EN/ES e campos de citações,
palavras-chave e agências. O wizard legado usa supportedSubmissionLocales para
persistir as palavras-chave por idioma: somente supportedFormLocales não basta.
Isso não confirma nem muda a configuração da revista real.

O cliente reutiliza um único namespace de cookies para login, formulários e
multipart, com sessões separadas de autor/editor. Requisições vão ao loopback do
contêiner, sem navegador visual ou portas publicadas. Helpers ficam em /tmp,
fora da raiz web, root:101/0640. Cookies, credenciais, DOCX e minutas ficam fora do
Git, em diretório 0700. O leitor de estado só consulta o novo ID criado pelo autor,
superior ao maior ID do baseline; não muda manuscrito, autoria ou fluxo pelo DAO.

O preflight recusa instância fora da rede interna esperada, portas/binds,
serviço root ou sendmail não neutralizado. Os helpers exigem banco/host sintéticos
fixos e base_url local. SMTP, beacon e tarefas continuam desativados.

## Reprodução

Pré-condição: a instância de restauração da [Etapa 62](OPERATIONS.md) existe com
suas credenciais sintéticas privadas. Em outro workspace, reproduzir primeiro as
Etapas 61–62. Reservar o diretório novo de resultados; não sobrescrever snapshots
ou resultados anteriores. O programa cria outro rascunho, não é replay do mesmo.

```sh
docker --host=unix:///var/run/docker.sock start estrabao-lab-62-restore-db estrabao-lab-62-restore-web
mkdir /workspace/scratch/estrabao-lab-63
chmod 700 /workspace/scratch/estrabao-lab-63
cp /workspace/scratch/estrabao-lab-62/restore/credentials.json /workspace/scratch/estrabao-lab-63/credentials.json
chmod 600 /workspace/scratch/estrabao-lab-63/credentials.json
python tests/lab/editorial/flow.py
python -m py_compile tests/lab/editorial/*.py
php -l tests/lab/editorial/http.php
php -l tests/lab/editorial/inspect.php
python -m unittest discover -s tests/package -v
python -m unittest discover -s tests/operations -v
docker --host=unix:///var/run/docker.sock stop estrabao-lab-62-restore-web estrabao-lab-62-restore-db
```

Guardar somente resultados redigidos no PR. Os DOCX são gerados exclusivamente
pelo código sintético documents.py; não se leem artigos enviados pelo usuário.
O ensaio HTTP não testa botões de copiar, avisos executados em JavaScript,
renderização visual ou depósito Crossref. As suítes anteriores desses recursos
não são contadas como nova execução nesta etapa.

Permanecem as limitações de APA, extração DOCX, .doc/PDF e idiomas já documentadas.
Também continua sem garantia uma transação conjunta upload/confirmação final.
Nenhum desses limites é resolvido apenas por concluir este percurso.
