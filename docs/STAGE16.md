# Etapa 16 — geração local da devolutiva editorial

Base 73b13c9, após aprovação e merge do modelo definitivo. Gerador 1.0.0;
motor permanece 0.12.0, plugin permanece 0.3.1.0. O gerador transforma resultados
confirmados em uma minuta conforme DESK_RESPONSE.md, sem enviar comunicação.

## Uso

```sh
php bin/desk.php tests/fixtures/complete.json
php bin/desk.php snapshot.json manuscrito.docx article pt
```

Mesmos argumentos e limites de entrada da triagem: snapshot local de até 1 MiB,
contratos de entrada 1.0/1.1, DOCX e contexto opcionais. O código zero significa
minuta gerada, não aceite, autorização de envio ou conformidade. Entrada inválida
retorna 1, sem minuta e com erro genérico. Não usa rede nem grava a saída.

A CLI gera o relatório no próprio processo e o entrega ao gerador. Não recebe
um relatório externo arbitrário ou orientações livres para compor a carta.
DeskResponse::generate é uma API interna para resultados do motor; suas guardas
não substituem uma autenticação do relatório nem um validador geral de JSON Schema.

## Saída

Contrato novo contracts/desk-response.schema.json, separado do relatório antigo:

- subject e body: assunto e texto da minuta destinada à revisão do editor.
- corrections: itens estruturados com área, exigência, achado e correção.
- pending_review: lista interna de regras/localizações conhecidas e motivo de
  pendência; regra ou localização não reconhecida não é copiada livremente.
- pending_review_count e unmapped_check_count: contagens internas. A segunda
  inclui regra/versão desconhecida ou falha sem evidência suficiente para redigir.
- source_input_sha256, source_document_sha256 (ou null) e source_engine_version:
  proveniência interna. Hashes de dois DOCX diferentes no mesmo relatório são
  recusados. Hash de snapshot não substitui hash do DOCX.
- decision: corrections_required, editorial_review_required ou no_issues_detected.
  Nenhuma delas é aceite editorial. Mesmo sem problemas detectados, o editor
  precisa conferir o resultado.
- editor_review_required=true e send_authorized=false, invariáveis.

Somente subject/body compõem a comunicação. Não encaminhar ao autor o envelope
JSON com a ficha interna. Os hashes não aparecem no texto da carta. A resposta
aplica-se à versão analisada; nova versão exige nova triagem e revisão.

## Redação e cobertura

Só status não conforme de regras/versões reconhecidas pode produzir cobrança.
Fonte explicitamente indisponível, status indeterminado, regra/versão desconhecida
ou evidência insuficiente fica na ficha de revisão. Nenhuma pendência é convertida
em ausência de espanhol, falta de fomento ou ABNT por inferência.

Há blocos para presença trilíngue, caixa de títulos/subtítulos, marcadores e
extensão dos resumos, 3–4 palavras-chave, ORCID, seções, formatação, extensão do
manuscrito, limite visual das comunicações, notas e APA/correspondência.

- Seção ausente é descrita como falta do título explícito, não inexistência do
  método ou da análise. CRediT menciona preservação da anonimização.
- Fomento e linhas das notas continuam pendências porque o motor atual não os
  conclui. Não há preenchimento automático de fatos científicos/editoriais.
- Extensão usa faixas do tipo declarado e intervalos técnicos, sem alegar
  contagem oficial do Word. Artigo não recebe máximo de seis figuras.
- Resumo/palavras-chave ausentes geram a cobrança de presença, suprimindo
  cobranças derivadas repetidas de marcadores ou quantidade do mesmo campo.
- Falhas ORCID repetidas são consolidadas por motivo, sem copiar nomes ou números.
- Regras agregadas/detalhadas de APA são consolidadas. Códigos comprovados
  recebem ações específicas de DOI, itálico, autoria/data, numeração e citação.
  ABNT só é mencionada como achado quando o código correspondente a comprova;
  problema isolado de DOI não vira acusação de ABNT nem sua ausência é proibida.

O gerador usa frases fixas e listas permitidas; não copia recommendation, textos
de amostras, nomes/títulos/DOI, caminhos ou instruções arbitrárias das evidências.
Marcadores e nomes de seções vêm das listas aprovadas do projeto. Limite de
8192 resultados; saída determinística para os mesmos resultados e versões.

## Limites

A minuta ainda precisa de revisão do editor; não é carta automaticamente enviada.
Não há botão de emissão no OJS, email, integração de todos os metadados no plugin,
mudança de estados, nova aprovação de submissão ou alteração da política APA.
O adaptador já existente continua exibindo apenas as correções APA previstas.

O formato português é o modelo aprovado; isso não implementa tradução automática
da carta. O gerador informa os idiomas dos campos examinados. O DOCX complexo
retido por cobertura incompleta gera pendências, não uma carta inventando infrações.
O artigo real da conversa não integra testes, fixtures ou commits desta etapa.

## Exemplo sintético

```sh
php bin/desk.php tests/fixtures/desk-corrections.json
```

O fixture contém somente título em espanhol ausente, título em português em
caixa alta e ORCID não preenchido como problemas confirmados. A minuta resulta
em três pedidos, além da ficha de pendências de leitura/identidade. Não contém
dados do artigo enviado pelo usuário.

[Texto para revisão](evidence/stage16/example-author-message.md) e
[envelope JSON com ficha interna](evidence/stage16/example-desk-response.json).
