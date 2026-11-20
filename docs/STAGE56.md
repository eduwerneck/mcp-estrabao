# Etapa 56 — encaminhamento verificável de Word .doc

Base 3409742 (merge PR #56), em 20/11/2026. Plugin 0.36.0.0; motor 0.41.0,
regras APA, contratos JSON e política editorial preservados.

## Comportamento

A diretriz aceita `.doc` e `.docx`. Até a Etapa 55, um `.doc` não conseguia obter
uma análise vinculada/revisável no adaptador, ficando retido sem via equivalente
de conferência. Agora existe uma **via humana explícita para um subconjunto
identificável de Word binário**, sem exigir conversão ou mudar o formato aceito.

O arquivo continua pendente inicialmente. No painel privado, o gerente vê que
não houve extração de texto nem verificação APA automática, baixa a versão
servidor e declara separadamente:

1. Que abriu essa versão e conseguiu ler integralmente referências e citações.
2. Que conferiu APA/diretrizes de citações, incluindo ausência de ABNT e apud.

Se não conseguir ler, encontrar incompatibilidade ou tiver dúvida, não deve
liberar. O sistema registra a declaração; não comprova a abertura no editor de
texto nem a veracidade da conferência. **APA/ABNT em `.doc` não são detectadas
automaticamente por esta entrega.** A obrigação editorial de APA continua válida,
com conferência humana nessa via. A detecção e o bloqueio já existentes em
`.docx` não são substituídos por uma declaração de `.doc`.

O autor recebe orientação de formato aceito e pendência de revisão humana,
sem acusação de ABNT por falta de leitura. A liberação retira somente a retenção
adicional de APA nessa versão; as validações nativas do OJS continuam exigidas.
Não é aceite editorial, publicação ou aprovação da triagem completa.

## Identificação limitada e vínculo

[LegacyDocSource](../plugins/generic/estrabao/LegacyDocSource.inc.php) lê no máximo
10 MiB locais, sem conversor, subprocesso, extração de texto, rede ou execução de
conteúdo. Identifica somente CFB v3 com setores 512/64, DIFAT no cabeçalho
(até 109 setores FAT), diretório de até 256 setores e árvore de profundidade
até 32, sem ciclos/sobreposição nas cadeias lidas. Requer stream raiz
WordDocument regular (mínimo 4096 bytes), identificador FIB e versão Word
97–2007 reconhecidos, além da entrada 0Table/1Table indicada pelo FIB.

CFB v4, DIFAT estendida, WordDocument em mini-stream, RTF renomeado, limites
excedidos, contêiner não reconhecido, flags de criptografia/ofuscação e alguns
indicadores conhecidos de macros/objetos permanecem pendentes, sem liberação
por esta tela. Isso não rejeita a extensão nem resolve todos os `.doc` aceitos
pela política: esses casos ainda precisam de encaminhamento editorial futuro.
A sonda não valida integralmente streams de tabela/mini-FAT, editabilidade,
malware ou ausência universal de conteúdo ativo. O gerente deve abrir com macros
desativadas; nenhum conteúdo é aberto automaticamente pelo plugin.

São reaplicados os controles de contexto, submissão, arquivo único e estágio,
caminho privado resolvido no servidor, IDs positivos e idioma conhecido. A
assinatura inclui bytes SHA-256 conferidos após a leitura, IDs de contexto,
submissão e arquivos, idioma, motor, política e modo `doc_manual_v1`. Não se
aceitam caminho, tipo/mode, relatório ou decisão declarados pelo cliente.
DOCX apenas renomeado não habilita esta via.

POST, CSRF, permissão e revisão otimista do histórico continuam obrigatórios.
O ledger privado APA registra também `review_mode: doc_manual_v1` nos eventos
manuais de aprovação/revogação e a interface distingue essa origem. Os dois
consentimentos são exigidos no servidor para aprovação. Histórico anterior sem
modo continua válido para DOCX, cujas assinaturas foram preservadas. Modo ausente
ou diferente não ativa uma liberação DOC. Registro vazio é tratado como corrupção,
sem reiniciar histórico; erros retêm o fluxo sem expor conteúdo ou caminhos.

Troca dos bytes, IDs ou idioma invalida a liberação; revogação e perda da permissão
do responsável continuam efetivas. Não há transação conjunta com uploads;
concorrência e operação na instalação real ainda não foram homologadas.

## Minuta e conferência não APA

A minuta pode usar os metadados disponíveis do OJS, mas o conteúdo Word continua
inconclusivo. Falta de extração não vira ausência de referências, seções, notas,
ética, fomento ou IA. O checklist privado da Etapa 55 permanece separado: não
preenche estados automaticamente nem concede a liberação APA.

## Testes e reprodução

A fixture Word 97 contém somente texto sintético. Sua fonte RTF está em
[tests/fixtures/legacy](../tests/fixtures/legacy/README.md); foi exportada e
reaberta localmente com LibreOffice apenas na preparação do teste. O produto
não usa nem depende desse conversor. Casos adversos são mutações sintéticas.

```sh
php tests/run.php
# Somente no laboratório interno já provisionado e aprovado pelo preflight:
PYTHONPATH=tests/lab python tests/lab/integrated.py --with-submission-cycle --with-doc-cycle
```

O ciclo DOC cria novos rascunhos sintéticos, registra a via manual, exercita
conclusão, revogação, substituição, CSRF e proteção do download. Restaura o
ponteiro do laboratório e preserva todos os registros anteriores. Não desfaz os
novos rascunhos/ciclos sintéticos. Testa controles e declarações, não uma revisão
semântica humana feita por um editor real. Resultados selecionados estão em
[evidence/stage56/validation.json](evidence/stage56/validation.json).

Sem produção, VM Oracle, dados de manuscritos reais, envio de mensagens,
publicação do ambiente ou deploy. Permanecem pendentes as variantes `.doc` acima,
cobertura multilíngue/datas, APA integral e homologação operacional representativa.
