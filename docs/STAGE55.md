# Etapa 55 — registro privado da conferência editorial

Base 9f9dace (merge PR #55), em 19/11/2026. Plugin 0.35.0.0; motor 0.41.0,
regras, schemas e política de admissão preservados.

## Uso e alcance

No painel privado, gere a minuta com o tipo de manuscrito. A nova seção permite
registrar 12 grupos: metadados, resumos/palavras-chave, autoria/ORCID, estrutura,
extensão, apresentação/títulos, elementos visuais, notas, CRediT, fomento,
ética e IA. Os estados são **Pendente**, **Conferido pelo editor** e
**Necessita ajustes**. **Não se aplica** existe somente para elementos visuais,
notas, fomento e ética, por decisão humana expressa.

Todos começam pendentes. O motor não preenche os estados, não transforma uma
falha em conformidade nem certifica a veracidade da conferência. Salvar requer
confirmação explícita. Gerar ou copiar a minuta não salva este registro.

A conferência não libera, revoga ou substitui a revisão APA, não altera o
resultado da triagem, não conclui a submissão nem cria novos impedimentos
na confirmação. ABNT/apud reconhecidos continuam bloqueados pelo mecanismo
existente, mesmo quando o editor registra itens como conferidos. Um estado
“Necessita ajustes” também não revoga, por si, uma liberação APA existente.

## Vínculo, histórico e proteção

O registro usa o vínculo da análise existente: contexto/submissão, arquivo,
snapshot dos metadados, publicação corrente e versão, tipo, motor e modelo da
minuta. Acrescenta versão do checklist e digest privado da autoria disponível
(id, ordem, nomes, afiliação, ORCID, país e email); esses valores não são copiados
para o registro. A conferência cobre os dados capturados, não canais/importações
ou metadados que o adaptador não lê.

Somente o último evento pode fornecer os estados atuais. Se o vínculo mudou,
os 12 itens voltam a pendentes e o histórico continua consultável. Um evento
antigo coincidente não substitui o último registro de outra análise. Cada evento
contém estados, tipo, versão do checklist, responsável, horário UTC e digest;
a interface mostra o horário de São Paulo. Não há texto livre, anexos de prova,
comentários ao autor ou manuscrito armazenado nesse histórico.

A rota exige o acesso editorial já existente (papel gerente no contexto),
submissão ainda no passo 4, POST, CSRF, confirmação e todos os estados válidos.
Valida novamente arquivo, metadados e autoria no servidor; revisões concorrentes
ou formulários antigos são recusados. O lock é do registro privado: não é uma
transação atômica conjunta do banco OJS com uploads. Alterações posteriores
exigem nova análise; concorrência operacional real continua por homologar.

O arquivo separado `editorial-{contexto}-{submissão}.json` fica no review_dir
privado existente (diretório 0700, arquivos/locks 0600), sem participação no
ledger APA. Escrita temporária/rename, lock não bloqueante, validação estrita e
somente acréscimo protegem o histórico. Limites: 100 eventos e 256 KiB; ao atingir
o limite não se sobrescrevem eventos. Retenção/exportação operacional não foram
implementadas. Configuração insegura ou registro inválido indisponibilizam essa
seção, preservando a minuta e a revisão APA.

## Validação

Resultados consolidados em [evidence/stage55/validation.json](evidence/stage55/validation.json).
Testes sintéticos PHP cobrem vínculo, persistência, histórico, concorrência,
estados inválidos, acesso e independência de APA. O runner nativo exercita o
painel OJS 3.3.0.20, inclusive restauração exata da fixture após alterações.
A regressão integrada verifica os painéis existentes e um novo ciclo sintético
com correção, revisão APA e conclusão; não reexecuta os 21 modos APA específicos
da Etapa 53 nem amplia sua cobertura normativa.

Somente laboratório interno, sem portas públicas, produto montado para leitura
e envio de mensagens desativado. Evidências selecionadas não incluem credenciais,
configurações privadas ou artigos reais. Sem acesso a produção/VM Oracle,
instalação na revista, publicação do ambiente ou deploy. `.doc`, cobertura
multilíngue e homologação operacional continuam pendentes.
