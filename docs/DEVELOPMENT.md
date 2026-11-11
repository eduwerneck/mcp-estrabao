# Desenvolvimento e instalação

## Ambiente local

Use o checkout existente da tarefa em nuvem; não é necessário criar worktree.
Não há pacotes PHP adicionais, banco de dados ou serviço a iniciar para o motor.
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
autorização e política de retenção. Não executar DOCX fornecidos como código,
nem instalar parsers antes de definir limites de ZIP, XML, tamanho e extração.
Não há chamadas de rede no motor ou CLI. Nenhum endpoint HTTP é disponibilizado.

Faça alterações em branch e submeta Pull Request; não integre automaticamente.
