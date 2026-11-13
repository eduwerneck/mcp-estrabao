# Desenvolvimento e instalação

## Ambiente local

Use o checkout existente da tarefa em nuvem; não é necessário criar worktree.
Para DOCX, instalar as extensões DOM/libxml e ZIP além do PHP CLI. Não há
banco de dados ou serviço a iniciar para o motor.
Em uma máquina de desenvolvimento administrada pelo operador, instale PHP CLI
pelo gerenciador oficial da distribuição; use versões suportadas para novas
implantações, respeitando também as restrições da versão exata do OJS.

Na raiz do repositório:

```sh
php --version
php tests/run.php
find src bin plugins tests -name '*.php' -print0 | xargs -0 -n1 php -l
php bin/triage.php tests/fixtures/complete.json
```

O runner termina com 1 se qualquer teste falhar e informa quantos executou.
Os testes da CLI usam `proc_open`, que deve estar disponível no PHP CLI.
Não exigem rede, credenciais, manuscritos nem banco OJS. O teste de carregamento
do plugin usa um dublê explicitamente limitado; não valida instalação real.
A CI foi definida para PHP 7.4 e 8.3; sua execução remota precisa ser confirmada
no Pull Request. PHP 8.4 também foi exercitado localmente (ver VALIDATION.md).

## Esqueleto do plugin OJS 3.3.0.20

A base foi conferida nos commits ligados à tag `3_3_0-20`:

- [OJS d9c71f2](https://github.com/pkp/ojs/tree/d9c71f2e410830885604b9c35fb09c9dc3b2c821).
- [GenericPlugin no pkp-lib 1a8ca71](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/plugins/GenericPlugin.inc.php).
- [LazyLoadPlugin e registro herdado](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/classes/plugins/LazyLoadPlugin.inc.php).
- [DTD pluginVersion](https://github.com/pkp/pkp-lib/blob/1a8ca71a8b9edc14eaad6bf861659f7737ac44f9/dtd/pluginVersion.dtd).
- [Plugin staticPages 8c97bd0](https://github.com/pkp/staticPages/tree/8c97bd09d4a732f6eb572ecc9237c9cde57efd40), referência de estrutura do `index.php`, XML e import legado.

O plugin usa `import('lib.pkp.classes.plugins.GenericPlugin')`, herda registro,
carregamento de locale e nome e fornece `getDisplayName()` e `getDescription()`.
Não usa a API namespaced de versões posteriores. `version.xml` declara plugin
genérico lazy-load, versão 0.1.0.0 e classe `EstrabaoPlugin`.

**Procedimento futuro, exclusivamente em laboratório isolado:**

1. Confirmar instalação funcional exatamente OJS 3.3.0.20 e backup do laboratório.
2. Copiar apenas `plugins/generic/estrabao/` para `<OJS>/plugins/generic/estrabao/`.
   Não copiar a CLI, fixtures ou contratos para a raiz web do OJS.
3. Pelo administrador do laboratório, verificar descoberta na lista de plugins
   genéricos, nome/descrição em pt_BR e habilitar/desabilitar nesse laboratório.
4. Inspecionar logs e confirmar ausência de rotas, relatórios ou ações editoriais
   novas. Documentar resultado e versões de PHP, banco e dependências.
5. Reverter desabilitando o plugin e removendo somente a pasta instalada, após
   conferir que não contém alterações locais.

Não foi instalado em OJS real nesta etapa. O motor não está ligado ao plugin,
e habilitar o esqueleto não executará triagem. Não há migradores, tabelas, hooks,
UI de relatórios ou adaptadores de submissão. A API de extensão foi conferida
em fontes exatas; compatibilidade operacional depende do teste acima.

## Segurança de desenvolvimento

Não usar produção nem arquivos editoriais reais. Não incluir credenciais no Git,
em fixtures ou exemplos. Saídas de dados reais exigirão armazenamento privado,
autorização e política de retenção. Não executar DOCX fornecidos como código.
A leitura DOCX implementada usa os limites de ZIP, XML e tamanho de STAGE3.md.
Não há chamadas de rede no motor ou CLI. Nenhum endpoint HTTP é disponibilizado.

Faça alterações em branch e submeta Pull Request; não integre automaticamente.

## Adaptador de admissão — Etapa 13

As descrições de esqueleto acima registram a base inicial. O plugin 0.2.0.0
adiciona um hook de validação opt-in, documentado em [STAGE13.md](STAGE13.md),
com engine_bootstrap privado e configuração explícita de homologação. Copiar
só a pasta do plugin não instala o motor. Não ativar em produção: ainda não
há interface de revisão/liberação e o bloqueio retém todos os casos pendentes.

## Revisão humana — Etapa 14

O plugin 0.3.0.0 acrescenta tela de revisão para gerentes do contexto e ledger
privado com liberação por versão. Seguir [STAGE14.md](STAGE14.md): preparar
review_dir 0700 dentro de files_dir, além das configurações da Etapa 13.
Não foi criado diretório operacional, instalado OJS nem ativada a integração.
O alerta anterior sobre ausência de interface é histórico da Etapa 13; agora
a interface existe no código, mas ainda exige homologação real/visual.

## Homologação local — Etapa 15

O plugin 0.3.1.0 foi exercitado em OJS 3.3.0.20 real com PHP 8.1, MariaDB e
Chromium em Docker internal, só com dados sintéticos. Procedimento, scripts
opcionais e capturas estão em [STAGE15.md](STAGE15.md). Os registros anteriores
de ausência de instalação descrevem as etapas anteriores. Sem produção ou VM.

## Minuta de devolutiva — Etapa 16

`php bin/desk.php snapshot.json [manuscrito.docx article|research_communication pt|en|es]`
gera minuta local revisável e ficha interna conforme STAGE16.md. O runner e a CI
exercitam a nova CLI sem OJS, banco ou rede. Gerar JSON não autoriza envio; para
comunicação, revisar apenas subject/body e conferir a versão dos dados analisados.

## Painel de devolutiva — Etapa 17

Plugin 0.4.0.0 acrescenta gerar/revisar/copiar no painel já autorizado, sem envio.
Atualizar também src/ privado para incluir o gerador da Etapa 16. Limpar caches
de idioma/template no laboratório após atualização. Procedimento e evidências
locais em STAGE17.md; sem implantação na revista ou configuração na VM.

## Leitor DOCX — Etapa 18

Atualizar src/ privado como conjunto para o motor 0.13.0, incluindo as novas
classes DocxCompatibility, BodyTextProfile e DocumentSource. Não misturar
classes de leitores diferentes. A versão nova exige nova revisão de liberações
que estavam vinculadas ao motor anterior. Schemas, CLI e plugin preservados;
parcialidade e limites em STAGE18.md. Sem atualização da revista nesta entrega.

Na Etapa 21, motor 0.16.0 e plugin 0.7.0.0 devem ser atualizados juntos em
homologação: o adaptador reconhece references.apa7 5.0.0 e o gerador reconhece
recognized_formats 2.0.0. Liberações de versão anterior exigem nova revisão.
Não houve implantação na revista.

Na Etapa 22, plugin 0.8.0.0 acrescenta o indicador público de submissão sem mudar
o motor 0.16.0. Instalar todos os arquivos do plugin, inclusive helper e template,
e limpar caches de template/idioma em homologação. Confirmar tema e versão reais:
o perfil de inserção foi validado apenas no markup do tema padrão OJS 3.3.0.20.
Data ausente/incoerente ou markup incompatível não é substituído por data estimada
ou posição alternativa. Nenhuma implantação/publicação foi realizada.

Na Etapa 23, atualizar juntos motor 0.17.0 e plugin 0.9.0.0 em homologação.
O gerador conhece as duas regras CRediT e o painel agrupa pendências com traduções
novas; limpar caches de idioma. Liberações de motor anterior exigem nova revisão.
Não houve implantação nem mudança de política de fomento ou validação integral
de contribuições.
