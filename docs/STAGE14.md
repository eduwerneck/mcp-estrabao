# Etapa 14 — revisão e liberação editorial por versão

Plugin 0.3.0.0, motor 0.12.0 e contratos de relatório preservados. Base: merge
197ad29 do PR #13. A revisão humana permite concluir o wizard de uma submissão
inconclusiva, após conferência efetiva pelo gerente editorial da própria revista.
Não representa aceite do artigo para publicação e não certifica APA automaticamente.

## Acesso e interface

Com plugin e admission_gate habilitados em homologação, a lista de plugins passa
a oferecer “Revisão editorial de APA 7”. A página pede o número da submissão,
exige login e ROLE_ID_MANAGER no contexto e recusa submissões de outra revista,
inexistentes ou fora da etapa 4 de rascunho. Nesta etapa, subeditores, autores e
administradores sem papel de gerente no contexto não recebem essa autorização.

A página oferece download autenticado da versão atual, declaração de conferência
APA, liberação da conclusão, revogação e histórico. O download também verifica
permissão, submissão e fingerprint atual; nunca aceita caminho ou fileId do POST.
O gerente deve baixar/conferir o manuscrito antes de declarar adequação; o sistema
não pode comprovar que houve leitura humana. Apenas campos fixos são registrados.

As únicas operações roteadas são review, saveReview e download, pelo LoadHandler
na página estrabaoAdmission. O handler também aplica as verificações nativas da
classe base. Serviço repete a autorização em leitura, download e gravação.
Gravar exige POST e token CSRF não vazio validado pelo OJS; aprovar exige também
declaração explícita. Revogação mira a última decisão registrada e permanece
possível mesmo se o arquivo atual se tornar ilegível, evitando reativação ao
restaurar os bytes antigos.
Não há mensagens a autores ou mudanças editoriais durante a revisão.

## Vinculação e resultado

O servidor reanalisa o arquivo corrente. O fingerprint vincula contexto,
submissão, submissionFileId, fileId, SHA-256 do DOCX, idioma e versões do motor,
da regra e da política. A versão do motor é obtida da mesma constante usada no
relatório. Fingerprint e revisão enviados pelo navegador devem corresponder ao
estado recalculado no servidor. Uma aba desatualizada não sobrescreve outra decisão.

A liberação só pode ocorrer com fonte disponível, IDs válidos e resultado
inconclusivo sem incompatibilidade comprovada. **ABNT reconhecida e qualquer
outra falha APA comprovada continuam bloqueadas, mesmo para o gerente.** Arquivo
ilegível, ambíguo ou não suportado permanece retido; assinatura sem análise não
libera. Não há substituição do resultado original por “APA conforme”.

Na confirmação pelo autor, a análise é refeita. Só a última decisão de aprovação
com mesmo fingerprint e gerente ainda autorizado permite devolver controle às
validações nativas do formulário. O callback não retorna validação verdadeira:
POST, CSRF e erros nativos continuam podendo impedir conclusão. Revogação,
troca de bytes, IDs de arquivo, idioma ou versões invalidam a liberação.

## Registro privado

Configuração nova, apenas no laboratório: review_dir deve apontar para um
subdiretório dedicado existente dentro do files_dir privado, com modo 0700 e
acesso exclusivo do processo OJS. Não usar raiz pública, diretório compartilhado
com autores nem armazenamento remoto. A entrega não cria nem configura esse
diretório no ambiente ou na VM.

Arquivos JSON separados por contexto/submissão guardam somente versão de
registro, IDs, sequência, ação approve/revoke, responsável, instante UTC e
fingerprint. A interface exibe horários em America/Sao_Paulo. Não guardam nomes,
títulos, texto do manuscrito, justificativas livres ou caminhos. Diretório 0700,
registro e lock 0600; symlinks e permissões abertas são recusados.

Há lock local não bloqueante, revisão otimista e substituição por rename a partir
de temporário privado no mesmo diretório. Falha ou corrupção retém a submissão.
O registro comporta 100 eventos/256 KiB: a última posição é reservada à revogação;
no limite, nenhuma liberação é considerada ativa. Não se apagam eventos antigos
para abrir espaço. Reter/arquivar registros futuramente exige procedimento de
auditoria próprio. O ledger não é inviolável contra administradores do servidor.
Backup e durabilidade após falha de energia ainda exigem homologação.

## Compatibilidade conferida e limites

Fontes públicas da revisão pkp-lib 1a8ca71a8b9edc14eaad6bf861659f7737ac44f9:
[PKPHandler](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/handler/PKPHandler.inc.php),
[PKPPageRouter/LoadHandler](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/core/PKPPageRouter.inc.php),
[RoleDAO::userHasRole](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/security/RoleDAO.inc.php),
[PKPRequest::checkCSRF](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/core/PKPRequest.inc.php),
[Plugin/actions/templates](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/plugins/Plugin.inc.php),
[PKPFileService::download](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/services/PKPFileService.inc.php).

Testes locais usam dublês de handler, sessão, DAO, template manager e serviços,
com DOCX sintéticos reais. Não há OJS instalado, template Smarty renderizado,
ensaio em navegador, banco ou e-mail real nesta entrega. O teste do handler
comprova o modelo e as chamadas, não o comportamento completo da plataforma.
Não alegar homologação ou ativar em produção a partir desses testes.

Continuam pendentes validação visual/funcional no OJS exato, autorização real,
rotas com e sem PATH_INFO, revisão de traduções/tema e condições de concorrência
com upload. O lock serializa decisões locais, não o upload do OJS: não elimina
todas as corridas entre conferência, download e execute. Não cobre importação,
API, quickSubmit ou operações fora do wizard. Sem produção ou VM Oracle.
