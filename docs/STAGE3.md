# Etapa 3 — DOCX local e seções

Motor 0.3.0, após o PR #2. Não acessa produção, VM Oracle, OJS ou serviços
externos. Usa exclusivamente fixtures sintéticas. Não instala o plugin.

## Execução

PHP CLI 7.4+ com JSON, hash e PCRE; leitura DOCX requer também DOM/libxml e ZIP
(libzip com metadados de criptografia). Use PHP suportado em desenvolvimento.
Sem Composer, banco, servidor ou conversor Word.

```sh
php tests/run.php
php bin/triage.php tests/fixtures/complete.json
php bin/triage.php snapshot.json manuscrito.docx article pt
php bin/triage.php snapshot.json manuscrito.docx research_communication pt
```

Os três argumentos opcionais são inseparáveis: arquivo local, tipo declarado
(`article` ou `research_communication`) e idioma principal (`pt`, `en`, `es`).
Não inferimos tipo ou idioma do texto. O snapshot 1.0 permanece inalterado e não
recebe caminhos, texto Word ou uma declaração de extração fornecida pelo usuário.
O caminho só entra pela CLI local. Nenhuma integração com uploads do OJS existe.
Sem documento, a regra de estrutura continua indeterminada. `.doc` é aceito pela
política editorial, mas não tem parser: sua análise é indeterminada, sem reprovar
a extensão. Extensões ausentes, arquivo inexistente, inválido ou recusado geram
indeterminado, não prova de descumprimento. Contexto inválido falha como entrada.

## Limites de segurança

`DocxReader` lê no máximo 10 MiB + 1 byte, sem wrappers de streams nem URLs.
Congela os bytes em arquivo temporário privado (0600), removido em `finally`;
não descompacta membros em disco. O hash refere-se aos bytes efetivamente lidos.
Valida consistência ZIP, CRC e comprimentos, até 256 membros, 8 MiB por membro,
32 MiB no total descomprimido e razão máxima 200:1 por membro. Só admite métodos
store/deflate sem criptografia. Recusa nomes absolutos, segmentos `.`/`..`,
backslashes, controles, dois-pontos, nomes duplicados (inclusive diferenças de
caixa), symlinks, macros e objetos incorporados. Esses são limites técnicos,
não exigências editoriais sobre tamanho do manuscrito.

Exige tipo de conteúdo DOCX e vínculo raiz para `word/document.xml`. Valida todos
os membros XML e `.rels` como XML UTF-8, sem DTD/entidades, com `LIBXML_NONET`,
sem substituição de entidades nem resolução externa. Não executa macros,
XInclude, XSLT, objetos ou comandos do documento. Nunca segue relacionamentos,
mesmo hyperlinks externos. Não usa conversores nem envia texto a terceiros.
Os limites padrão de profundidade do libxml são preservados.

Escopo suportado: parágrafos diretos do corpo principal, concatenando runs de
texto, tabs e quebras. Cabeçalhos e rodapés não contam como seções do corpo.
Tabelas, campos, revisões, controles de conteúdo, texto oculto, caixas de texto,
objetos, símbolos, conteúdo alternativo e containers do corpo não suportados
invalidam a cobertura desta análise. Marcas Unicode combinantes também exigem
revisão. Nesses casos, toda a estrutura fica indeterminada; não há extração
parcial usada para comprovar ausência. Essa restrição não proíbe tais recursos
na revista. Estilos com texto oculto também tornam a cobertura indeterminada.

## Regras e evidências

`manuscript.docx.structure` 2.0.0 substitui a pendência anterior. Para ambos os
tipos em português, verifica Introdução; Metodologia; Resultados e Discussão;
Considerações Finais; Créditos (CRediT); Referências. O título deve ocupar um
parágrafo inteiro, com comparação sem distinguir caixa e espaços normalizados.
Runs fragmentados são concatenados. Não exige template, estilo de título Word,
ordem das seções, nem proíbe seções adicionais.

Cada título exato encontrado tem índice de parágrafo (base zero). Sem título e
sem menção ambígua, a ausência lexical é não conforme; uma menção em prosa ou
Resultados/Discussão separados fica indeterminada. Qualquer ausência comprovada
prevalece no resultado agregado; caso contrário, ambiguidades prevalecem sobre
conformidade. A verificação é lexical, sem validar conteúdo científico. Sinônimos,
variantes e traduções não foram aprovados e exigem revisão; inglês e espanhol
sempre ficam indeterminados. A regra não usa os rótulos aprovados para resumos
como equivalentes de títulos do corpo.

`manuscript.headings.format` registra numeração literal e `numPr/numId` direto
nos títulos reconhecidos. `numId=0` cancela numeração, não produz reprovação.
Numeração explícita ou negrito explicitamente desligado em um run textual de
título reconhecido produz não conforme. Registra negrito direto, mas não conclui
conformidade sem resolver herança de estilos e numeração. Formatação herdada,
negrito efetivo e títulos ambíguos continuam sujeitos à revisão humana.

`manuscript.funding` é condicional apenas para artigos, sempre indeterminado
até conhecer a aplicabilidade. A falta de Fontes de Fomento não reprova sozinha.
Presença de Créditos não confirma completude ou veracidade da taxonomia CRediT.

O relatório mantém schema 1.0 e `input_sha256` do snapshot; evidência da estrutura
inclui separadamente SHA-256 do arquivo, tipo e idioma declarados, cobertura,
rótulos da política e posições, sem texto bruto nem caminho local. O escopo
`local_snapshot_only` abrange os insumos locais desta chamada, sem leitura OJS.
Hash isolado não autentica origem. Retenção e auditoria permanecem futuras.

Ainda não implementados: estilos/herança completos, formatação de página e
fonte, extensão do corpo, figuras/notas, APA, ética/IA, `.doc`, adaptador OJS e
MCP. Toda recomendação requer revisão humana.
