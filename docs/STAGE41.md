# Etapa 41 — APA 7: modelos declarados de conjuntos de dados e software

Base b597a79 (merge PR #41), em 19/11/2026. A main já contém as Etapas 39 e 40.
Motor 0.32.0 e plugin 0.24.0.0; APA 17.0.0 e formatos 10.0.0. Correspondência
10.0.0 e citações 8.0.0 preservadas; contratos JSON preservados. A versão nova
invalida liberações anteriores.

## Modelos reconhecidos

ReferenceDigitalWorks exige uma descrição explícita entre colchetes: Data set,
Conjunto de dados, Computer software ou Software. Não presume tipo digital pelo
nome do produto, extensão de arquivo, URL ou DOI. A versão, quando informada,
aparece antes da descrição, entre parênteses com Version ou Versão e identificador
não vazio. A fonte/editora/repositório pode aparecer após a descrição.

Confere título em itálico e versão, descrição e fonte sem itálico. Campos opcionais
ausentes não produzem falha: a revisão precisa estabelecer se há versão, se a
fonte pode ser omitida e quais dados devem ser informados para a obra usada.
Não cria versão, editora, DOI ou URL. Descrição/versão/fonte parcialmente
reconhecida não é convertida em livro ou artigo para impor outro modelo.

Mantém conferência humana de autoria, identidade, tipo real, versão efetivamente
usada, fonte e disponibilidade dos dados. Reconhecimento visual não confirma
esses itens nem certifica a referência em APA integral.

## Grafia e devolutiva

Títulos digitais declarados têm conferência específica de grafia oficial, sem
reprovação automática por caixa alta. Isso protege siglas e produtos como QGIS,
R e SPSS. Não altera a política de títulos/subtítulos do artigo ou do manuscrito.
Não reescreve o documento nem converte a capitalização do produto.

Falhas de itálico comprovadas bloqueiam conclusão/liberação, com orientação fixa
no wizard e minuta. Não copia título, versão ou repositório para a minuta.
Casos inconclusivos exigem revisão, sem avanço automático ou envio de carta.

## Limites

Título de até 1024 caracteres, etiqueta de versão de até 64 caracteres do
subconjunto alfanumérico/pontuação simples e fonte de até 256 caracteres do
vocabulário de letras/números, espaços, &, apóstrofos e hífens. Preserva 8192
bytes por referência e os limites anteriores de entradas, texto e ZIP/XML.

Outras descrições (aplicativos móveis, código-fonte, dados brutos não publicados,
coleções/idiomas fora da gramática), fontes com pontuação complexa, versões com
metadados adicionais, formatos fora da cobertura e a pertinência de incluir
software comum na lista continuam para revisão. Ausência de versão/fonte não
comprova conformidade; também não autoriza exigir informação inexistente.

Não abre, baixa ou consulta dados, executa o software ou segue URLs/DOIs. As
conferências anteriores de calendários/URLs e autoria/citações permanecem ativas.
Conserva a base técnica/ressalvas de STAGE11.md e APA7.md, sem nova consulta
normativa externa ou anúncio de certificação integral.

## Verificação

- 1399 testes por PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 103 arquivos PHP, compilação Python, dois schemas CLI e diff aprovados.
- 38 verificações OJS 3.3.0.20/Chromium: título sem itálico, versão em itálico,
  descrição em itálico e fonte em itálico, com bloqueio, minuta/privacidade,
  POST nativo e orientação específica ao autor.
- Quatro casos revisáveis: conjunto com metadados reconhecidos, software sem
  versão/fonte, sigla QGIS e versão fora da gramática. Sem conclusão automática.
- Testes do motor incluem descritores português/inglês, opcionais ausentes,
  metadados ambíguos, QGIS/R/SPSS, URLs/DOI sem acesso, formatação desconhecida,
  ligação autor–ano e minuta sem conteúdo privado.
- Rede interna, mounts read-only, e-mail neutralizado e versão instalada alinhada.
  Arquivo/estado sintéticos restaurados; capturas vistas em evidence/stage41.
  Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --digital
```

Requer laboratório isolado previamente configurado. Modalidade adicionada ao
runner opcional integrado; os 287 casos da Etapa 38 não foram repetidos nesta
ampliação. Sem produção, VM Oracle, documentos reais, mensagens externas,
publicação do ambiente ou deploy.
