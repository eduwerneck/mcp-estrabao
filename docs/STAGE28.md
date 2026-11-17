# Etapa 28 — perfil informado do Tema Padrão

Validação local em 17/11/2026, base 038aa84 (merge do PR #28). Motor 0.20.0
e plugin 0.12.0.0 preservados. Esta etapa acrescenta ensaios e evidências,
sem alterar produto, contratos, opções do tema ou CSS.

O responsável informou Tema Padrão, Noto Sans, cor #1E6292 e ausência de folha
de estilos adicional. As capturas mostram a seleção do tema e a área de upload
vazia em Português (Brasil). Esses dados descrevem o perfil informado; arquivos
e versão do tema real não foram inspecionados. Capturas pessoais não entram no Git.

O laboratório OJS 3.3.0.20 já apresentava esse perfil. Seu tema nativo é 1.0.0.0;
essa versão pertence exclusivamente à imagem local. Não houve reconfiguração.

Foram aprovadas 25 verificações, incluindo cinco larguras: 1440, 1024, 768,
390 e 320 px. Conferem carregamento de Noto Sans, cor do cabeçalho, Submetido em
antes de Publicado no mesmo box, ausência de duplicação, sobreposição e rolagem
horizontal. Capturas sintéticas de desktop e celular foram inspecionadas.
O formato de data é o configurado no laboratório, sem inferência sobre o da revista.

O runner tests/lab/theme-responsive.py exige rede Docker interna, mounts do checkout
somente de leitura, transporte de e-mail neutralizado e pré-checagem nativa.
O helper tests/lab/theme-profile.php lê apenas o perfil necessário, com guarda de
laboratório sintético. A fixture temporária de publicação restaura datas e estado
original em finally. O teste confirmou essa restauração e o perfil inalterado.

Evidências: [resumo](evidence/stage28/summary.json),
[25 verificações](evidence/stage28/results.json),
[perfil e medidas](evidence/stage28/profile.json),
[desktop](evidence/stage28/article-1440x1000.png) e
[celular](evidence/stage28/article-320x700.png).

Os 59 hashes de produto/contratos coincidem com o manifesto da Etapa 27. Seus
922 testes por versão PHP e 72 verificações integradas são o histórico da base,
não uma nova execução nesta etapa. Aqui foram executados os 25 casos de perfil,
lint do helper PHP, compilação Python e checagem do diff.

Para repetir no laboratório isolado já preparado:

```sh
PYTHONPATH=tests/lab python tests/lab/theme-responsive.py
```

Containers desligados ao final. Sem produção, VM Oracle, documentos reais,
envio externo ou publicação do ambiente. Arquivos/versão reais do tema,
configuração dos campos e infraestrutura continuam fora desta homologação.
