# Word binário sintético

`synthetic.rtf` é texto inventado, com uma referência fictícia e parágrafos
repetidos. `synthetic.doc` foi exportado como **MS Word 97**, usando
LibreOfficeDev 26.8.0.0.alpha0 (build 2c87e51eeaa2b413ff4ae097b2705eea1995d8e5).
Foi reaberto/exportado para texto e conferido: título, referência fictícia e
350 ocorrências da frase repetida foram preservados. Não é artigo real nem prova de APA.

Reprodução opcional, somente desta fonte sintética, com saída em diretório
privado de teste:

```sh
soffice --headless --convert-to 'doc:MS Word 97' --outdir /tmp/estrabao-doc-fixture synthetic.rtf
```

Metadados/datas do conversor podem mudar os bytes; os testes usam a fixture
versionada. Não exige LibreOffice para executar testes PHP nem para instalar o
plugin. O produto identifica um subconjunto do contêiner e exige conferência
humana, sem converter/abrir o documento. As mutações de erro são geradas pelos
testes, sem executar macros ou incluir conteúdo ativo.
