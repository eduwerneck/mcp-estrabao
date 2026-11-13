# Etapa 22 — data de submissão na página do artigo

Base b11c063 (merge do PR #22), plugin 0.8.0.0. Motor 0.16.0 e schemas de
triagem/minuta preservados. Implementa o quinto item da ordem combinada: mostrar
Submetido em antes de Publicado, no mesmo box HTML da página do artigo.

## Fonte e significado

A data vem exclusivamente de Submission.dateSubmitted, atribuída pelo fluxo do
OJS. Não usa a criação do rascunho, data do arquivo, publicação ou parâmetros da
requisição para inventar a submissão. Valida artigo e publicações pertencentes à
revista/submissão atual, status publicado e fluxo concluído. Não adiciona o
indicador em prévias não publicadas, galerias ou páginas de edições.

Exige datas reais, no formato de calendário ou timestamp sem zona usado pelo
DAO, e submissão não posterior à primeira publicação. Primeira publicação não
pode ser posterior à versão exibida. Datas ausentes, inválidas ou inconsistentes
omitem somente o indicador novo. Não corrige dados históricos/importados nem os
substitui por outra data. Registros incoerentes devem ser conferidos no OJS.

Mostra apenas o dia, preservando a data de calendário armazenada no sistema:
não presume que um timestamp sem zona esteja em UTC nem aplica fuso do navegador.
Usa o dateFormatShort nativo do contexto, igual ao campo Publicado, e time com
datetime ISO para marcação semântica. O rótulo tem traduções pt_BR/en_US/es_ES.
A data não é a do aceite editorial nem uma nova data para versões atualizadas.

## Integração e compatibilidade

Com plugin habilitado na revista, TemplateManager::display identifica a landing
page frontend/pages/article.tpl, obtém os objetos já autorizados pelo handler e
renderiza uma seção escapada. Um filtro de saída Smarty insere a seção antes do
conteúdo existente de Publicado. Não depende de JavaScript nem sobrescreve template
do núcleo; o HTML nativo, inclusive conteúdo de versões, permanece intacto.

A posição usa perfil explícito de markup do tema padrão OJS 3.3.0.20: um único
article.obj_article_details, div.entry_details e div.item.published nessa ordem,
com Published dentro do artigo. Âncoras ausentes, duplicadas, fora de ordem,
marcação já inserida ou HTML acima de 16 MiB deixam a saída intacta. Isso evita
inserção ambígua em texto de resumo ou em layout incompatível. O método não é um
adaptador genérico de temas. Mudanças de classes, templates ou plugins podem
impedir a exibição; exigem homologação específica, sem fallback em outra posição.

O tema e sua versão na revista real ainda não foram confirmados. A captura da
conversa orientou a posição, não comprova a identidade/compatibilidade do tema.
Implementação validada somente no tema padrão do laboratório. Sem acesso à revista
ou à VM Oracle para descobrir o tema. Antes de qualquer implantação, homologar com
uma cópia do tema/configuração real; nenhuma implantação foi realizada.

O indicador independe do gate APA e não muda liberação ou rejeição de submissão.
O plugin só lê os objetos e modifica a saída HTML da resposta; não altera datas,
publicações, PDFs, metadados ou decisões no banco.

## Validação e reprodução

837 testes sintéticos (31 novos), zero falhas, em PHP 8.4.27 e PHP 8.1.27;
lint de 76 arquivos PHP. Cobrem fonte da data, versões, calendário, horários,
status, isolamento por revista, propriedade da publicação, cronologia, perfil de
markup, duplicação, posicionamento e preservação byte a byte do HTML existente.

Dez verificações em OJS 3.3.0.20 local com Chromium: visitante anônimo, posição no
box, publicação original preservada, sem duplicação, ausência de escrita pelo
indicador, resposta renderizada no servidor com JavaScript desativado, ausência
ou incoerência da data, prévia e restauração da fixture. JSON e captura inspecionada
em evidence/stage22. As traduções visuais foram conferidas em português.

No laboratório isolado da Etapa 15, copiar tests/lab/article-date-fixture.php para
/tmp/article-date-fixture.php no container web e executar
PYTHONPATH=tests/lab python tests/lab/article-dates.py. O utilitário exige
ESTRABAO_SYNTHETIC_LAB=1 e verifica URL e banco sintéticos. Simula temporariamente
um artigo publicado por SQL, sem chamar rotinas de publicação ou envio de e-mail,
e restaura status/datas originais em finally. Não executar fora do laboratório.

Sem dados reais, produção, VM Oracle, comunicação com autores, publicação externa
ou publicação/reconfiguração do ambiente. CI remoto ainda não verificado.
