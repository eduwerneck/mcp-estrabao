# Etapa 67 — orientação de fomento no formulário com um idioma

A homologação ARM64 mostrou Referências e Agências de fomento, mas a orientação
**Sem financiamento** não apareceu. A tela usa um idioma e o OJS gera
`ul#agencies-ID`; a integração anterior reconhecia apenas o contêiner multilíngue.

O plugin **0.42.0.1** reconhece os controles nativos nas duas apresentações e
insere a orientação na mesma seção do campo, com a classe de descrição nativa.
Trocas de etapa por AJAX não eliminam a orientação nem criam cópias. A correção
não preenche campos pelo autor, não muda validações ou decisões, nem altera o
fluxo de e-mail. Motor **0.44.0** inalterado.

## Verificação

`tests/lab/layout/capture.py` captura o HTML real do formulário com um idioma no
laboratório sintético isolado, criando um rascunho e enviando um DOCX pelo fluxo
nativo. Reutiliza as salvaguardas e os dados privados das etapas 63/66. O HTML
multilíngue é a captura do ensaio da Etapa 66; os arquivos ficam fora do Git.

`tests/lab/layout/funding.py` renderiza ambas as capturas em Chromium e verifica:
orientação visível, pertencimento à seção do campo, ausência de duplicatas,
reposição após AJAX e preservação do campo Referências. Rede externa bloqueada.
Evidência em [funding-layout.json](evidence/stage67/funding-layout.json).

Verificação de sintaxe JavaScript e 15 testes de integridade do pacote aprovados.
Não se alterou código PHP ou do motor; seus resultados de regressão da Etapa 66
continuam sendo a referência. A VM Oracle ainda precisa receber este pacote após
revisão/merge. A revista em produção não foi acessada ou modificada.
