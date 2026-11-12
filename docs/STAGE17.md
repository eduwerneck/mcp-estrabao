# Etapa 17 — devolutiva no painel editorial OJS

Plugin 0.4.0.0; motor 0.12.0 e gerador 1.0.0 preservados. Base final 7ebf393,
merge do PR #17 (Etapa 16), confirmado e incorporado antes do envio da branch.

## Fluxo para o editor

A área de revisão de admissão passa a oferecer “Gerar devolutiva”. O gerente do
contexto escolhe Artigo ou Comunicação de Pesquisa para aplicar os limites
corretos. Não se infere tipo pelo título, nome do arquivo ou nome da seção OJS;
essa escolha é uma declaração do editor e não altera o tipo no sistema.

Ao gerar, assunto e corpo aparecem em campos editáveis. O botão copia somente
esses dois campos, incluindo ajustes feitos pelo editor. As pendências internas
ficam em um bloco separado, agrupadas por área, sem entrar na cópia. A carta é
em português, como no modelo aprovado, mesmo que a interface esteja em outro
idioma. O aviso esclarece que edições não são salvas e nada é enviado ao autor.

Se a API de clipboard não estiver disponível ou falhar, o corpo é selecionado
para cópia manual, com orientação para conferir/copiar o assunto separadamente.
Não há mensagem de sucesso falso. A geração não aprova APA, não libera o wizard,
não grava texto no ledger, não revoga decisões e não envia email.

## Fonte de metadados e documento

SubmissionSnapshot lê getCurrentPublication do objeto autorizado pelo DAO,
conferindo publicationId/currentPublicationId e submissionId. Usa título,
subtítulo, resumo, keywords e autores carregados pelo PKPPublicationDAO; copia
para o contrato apenas os ORCIDs da autoria, sem nomes, emails ou afiliações.
Keywords vêm da lista localizada do vocabulário controlado, não de texto separado
por delimitadores presumidos. A fonte é a publicação corrente, não a primeira
versão nem campos postados pelo navegador.

Leitura DAO bem-sucedida de campo vazio confirma ausência no campo OJS. Campo
malformado, autoria não disponível ou locale alternativo pt/en/es fora dos três
canônicos fica desconhecido. Não converter variante não normalizada em ausência
de idioma. Normalização adicional permanece pendente. Os locales canônicos
cobertos são pt_BR, en_US e es_ES. Limites técnicos: snapshot até 1 MiB, até 2048
autores normalizados; acima do limite técnico a autoria fica indeterminada,
sem criar um máximo editorial de autores.

O leitor de arquivo reutiliza a resolução privada do adaptador já testado.
AdmissionBridge::analyze inclui documento bruto apenas em opção interna explícita;
o modo padrão e o modelo da tela continuam sem texto bruto. O gerador recebe o
DOCX atual e o snapshot DAO, produz o relatório completo e a minuta da Etapa 16.
A carta não é gerada a partir de relatório, texto ou metadados do POST.

## Autorização e versão

Operação deskDraft explicitamente listada em LoadHandler. Só ROLE_ID_MANAGER no
contexto, plugin/gate habilitados, rascunho na etapa 4 e IDs consistentes.
Exige POST, CSRF válido, tipo editorial permitido e fingerprint da versão aberta.

Após a geração, são relidos o fingerprint documental e o snapshot/publicação.
Alteração detectada de bytes/IDs, metadados ou versão da publicação impede
retornar a carta. A proveniência interna vincula contexto, submissão, publicação,
DOCX, snapshot, tipo e versões do motor/modelo. Erros geram mensagem genérica,
sem exibir caminho, exceção ou dados brutos. A ficha não entra em subject/body.

Essas releituras não são uma transação com uploads e alterações posteriores:
a carta já exibida pode ficar desatualizada se dados mudarem depois. A tela orienta
gerar outra carta nesse caso. Cópia manual não é aprovação nem substitui a
conferência do editor. Nenhuma autorização de envio foi implementada.

## Verificação em OJS real local

Mesmo laboratório internal, imagens/digests e neutralização de email da Etapa 15,
sem produção/Oracle. OJS 3.3.0.20, PHP 8.1.27, MariaDB e Chromium reais. Foram
exercitadas geração, campos editáveis, cópia real ao clipboard, fallback por
negação da API, acesso de autor negado, GET/CSRF rejeitados e rotas com/sem
PATH_INFO. O banco foi comparado antes/depois, sem alteração editorial.

[13 verificações reais](evidence/stage17/results.json) e
[captura da tela inspecionada](evidence/stage17/draft-panel.png).
Somente revista, pessoas e manuscritos sintéticos no laboratório e nas evidências.
O artigo real da conversa não foi copiado nem utilizado nesta etapa.

Script opcional tests/lab/desk-panel.py reutiliza browser.py e o laboratório da
Etapa 15, com rascunho sintético ABNT 1 e credenciais privadas fora do Git.
ESTRABAO_DESK_EVIDENCE_DIR controla a pasta privada das evidências. A primeira
execução foi feita durante o desenvolvimento; o script é apoio ao ensaio, não CI
ou provisionamento da revista. Na atualização do plugin em laboratório, limpar
os caches de idioma/template pela administração: o cache antigo pode manter
chaves novas sem tradução. Apenas cache gerado do laboratório foi atualizado.

Continuam pendentes tema/infraestrutura da revista, outros canais de submissão,
ARM64, normalização adicional de locales e concorrência transacional. Não houve
instalação na revista nem mudança na configuração publicada do ambiente.
