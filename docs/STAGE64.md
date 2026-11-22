# Etapa 64 — acesso contextual à triagem

Problema observado na homologação: depois de trocar para a conta editorial, era
necessário conhecer o endereço da revisão e digitar o número da submissão.

O plugin **0.40.0.0** acrescenta **Visualizar triagem** às ações expandidas de cada
linha em Submissões (setinha ao lado de Visualizar). O destino leva o identificador
da própria linha. A ação reaparece após mudanças de aba, busca e renderização da
lista, sem substituir Visualizar nem alterar os dados do manuscrito. Traduções
em português, inglês e espanhol. Motor **0.44.0**, inalterado.

O servidor fornece o script apenas no dashboard, para usuários que satisfazem a
mesma autorização da revisão (`ROLE_ID_MANAGER` na revista), com plugin e
`admission_gate` habilitados. Ocultar o botão não concede/revoga acesso: a rota
continua verificando a autorização independentemente da interface. IDs inválidos
não recebem link e o navegador recusa uma base de destino de outra origem.

A triagem de admissão continua restrita a rascunhos na etapa 4 (Confirmação).
Submissões em outras etapas recebem uma explicação específica, sem formulário
de liberação. Não reabrimos submissões concluídas nem ampliamos a política de
admissão para avaliar artigos já recebidos. O novo acesso não muda liberação,
revogação, registros editoriais ou a pendência de transação conjunta entre upload
e confirmação documentada anteriormente.

## Verificação

`tests/lab/navigation/browser.py` usa Chromium/Playwright e o OJS real da cópia
sintética isolada da Etapa 62. O transporte auxiliar em `/tmp/` encaminha as
requisições pelo loopback interno, preservando a ausência de portas públicas;
não é instalado na raiz web. Credenciais e cookies sintéticos permanecem privados.
Os acessos são autenticados pelo login nativo antes do ensaio do navegador.

Resultados redigidos: [navigation-results.json](evidence/stage64/navigation-results.json).
O teste cobre o destino por linha, acessibilidade, reabertura sem duplicação,
identificador inválido, recarga da lista, ausência para autor, negação do acesso
direto, explicação de etapa, navegação pelo botão real e ausência com o gate
desativado: 15 verificações aprovadas. A regressão PHP passou com 2.120 testes
e zero falhas. Os testes de integridade dos pacotes complementam a verificação.

Não houve atualização automática da VM Oracle nem teste ARM64 desta alteração.
A homologação manual nessa VM é o passo seguinte, após revisão/merge.

## Atualização da homologação

1. Obter o commit revisado e gerar/verificar um novo diretório de release com
   `tools/release.py build --ref COMMIT --out DIRETORIO_NOVO` e `verify`.
2. Preservar uma cópia do plugin anterior fora da raiz web. Com o serviço web
   parado e sem ações editoriais concorrentes, substituir somente o diretório
   `plugins/generic/estrabao` pelo pacote verificado. Não trocar banco, arquivos
   editoriais, `config.inc.php` ou os registros privados.
3. O motor não mudou; manter sua montagem privada. Iniciar o web e registrar
   a versão com `php lib/pkp/tools/installPluginVersion.php
   plugins/generic/estrabao/version.xml`, como usuário do PHP.
4. Conferir plugin habilitado, gate ativo e editor autenticado. Em Submissões,
   abrir a setinha de um rascunho na confirmação e clicar Visualizar triagem.
   Conferir o número, retornar à lista e repetir com outra linha. Uma submissão
   concluída deve mostrar a explicação de etapa, sem botão de liberação.
5. Ao terminar toda a homologação, encerrar os recursos temporários Oracle e
   revisar discos remanescentes para evitar cobranças. Parar a VM não remove
   armazenamento. A troca da senha do banco exposta na sessão manual anterior
   ainda está pendente; não incluir senhas em capturas ou no Git.
