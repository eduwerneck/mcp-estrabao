# Etapa 57 — citações em inglês e espanhol

Base 3254cdd (merge PR #57), em 20/11/2026. Motor 0.42.0, plugin 0.37.0.0.
Regras references.apa7 27.0.0, citation_matching 14.0.0 e citation_format 12.0.0;
recognized_formats permanece 17.0.0. Schemas, política e modelo da minuta preservados.

## Cobertura

A regra passa a aplicar a conferência autor–ano/citações também a `en` e `es`,
antes limitada a `pt`. O idioma é informado pelo contexto CLI ou pelo locale
da submissão OJS (`en_US`/`es_ES`); não é inferido dos nomes, títulos ou referências.
Chamadas diretas antigas a ReferenceAnalysis mantêm português como padrão.

A análise reconhece, no subconjunto disponível, autoria pessoal literal,
parentéticas com `&`, narrativas com `and` ou `y`/`e`, et al., anos/sufixos,
localizadores e citação secundária. Os mesmos controles de ambiguidade,
transcrições, duplicatas, limites e contexto incompleto continuam válidos.

Erros inequívocos, ligados à referência reconhecida e em segmentação suficiente,
como `&` narrativo, conjunção textual parentética, espaçamento ou localizador
p./pp. incorreto, compõem o bloqueio APA existente. Não há liberação automática
quando não são encontrados erros; a revisão humana continua necessária.

Uma conjunção narrativa de outro idioma fica para conferência, sem provar erro
nem traduzir o texto. Espanhol aceita `y` e `e` nessa gramática, sem decisão
fonética sobre a pronúncia dos nomes. Maiúsculas isoladas não provam ABNT.
Palavras genéricas como Figure/Table/Tabla não fundamentam referência ausente.

`As cited in` e, em espanhol, `como se citó en` ligam somente a fonte consultada;
a obra original não é exigida na lista por essa relação. Apud continua proibido
pela instrução editorial já vigente, com orientação para a expressão do idioma
analisado. Variantes secundárias fora da gramática permanecem para revisão.
Narrativas espanholas delimitadas com señala/sostiene/escribe também podem
produzir orientação privada de transcrição, sem incluir seu texto nas evidências.

## Delimitação lexical, sem nova política de seções

Os rótulos abaixo identificam regiões para conferir citações. **Não aprovam
traduções das seções obrigatórias nem criam exigência de usar estes títulos.**
As regras editoriais de estrutura/extensão em idiomas diferentes do português
continuam inconclusivas no escopo já documentado.

| Região técnica | Inglês | Espanhol |
| --- | --- | --- |
| Início do corpo | Introduction | Introducción |
| Método | Methodology ou Methods | Metodología ou Métodos |
| Resultados/discussão combinados | Results and Discussion | Resultados y Discusión |
| Encerramento | Conclusions, Conclusion ou Final Considerations | Consideraciones Finales, Conclusiones ou Conclusión |
| Lista | References | Referencias |
| Declarações fora do corpo/lista | Funding; CRediT author statement | Financiación; Contribuciones de autoría (CRediT) |

Faltas/duplicatas desses marcadores, sinais de heading não reconhecido no outline
ou negrito ambíguo mantêm o contexto incompleto. Texto sem sinal de heading pode
escapar dessa identificação; o motor não resolve a função semântica do parágrafo. Abstract/Resumen e palavras-chave
são excluídos quando delimitáveis. Não se procuram citações dentro de resumos
como se fossem o corpo. O inventário lexical não substitui leitura editorial.
Bibliography/Bibliografía, resultados/discussão em seções separadas e títulos
mistos podem exigir conferência; sua presença não prova infração editorial.

A ausência de referência só é concluída no subconjunto fechado já exigido pelo
matcher: referências reconhecidas, delimitação completa, sem grupos não lidos,
chaves ambíguas ou sinais numéricos. Fora desse contexto não se presume obra
faltante. Arquivo parcial, institucional/siglas, nomes abreviados, tradução,
fidelidade e identidade das fontes conservam as ressalvas anteriores.

## OJS e registros

O bridge lê o idioma no servidor e exige a nova versão da regra. Minuta e
confirmação usam a mesma análise. Uma incompatibilidade comprovada não pode ser
superada por liberação editorial; corrigir o arquivo exige nova análise e revisão.

A mudança de motor invalida liberações anteriores vinculadas a 0.41.0, inclusive
na via manual `.doc`. Os eventos continuam no histórico; não são apagados nem
convertidos em aprovações da análise atual. O checklist não APA também continua
vinculado à análise e mantém sua independência.

`.doc` permanece sem verificação automática de texto/APA, com a via humana limitada
da Etapa 56. Nenhuma ampliação desta etapa altera os formatos aceitos, envia
cartas, edita referências ou traduz nomes/textos/metadados.

## Validação e limites

Testes sintéticos em PHP 8.4.27/8.1.27 incluem gramática segura, erros comprovados,
secundárias, apud, idioma incompatível, limites de contexto, ausência/duplicatas,
fontes parciais, datas fora do subconjunto e contratos bridge/minuta. O ciclo OJS
cria rascunhos novos em inglês/espanhol e exercita bloqueio, correção, revisão,
troca de arquivo, CSRF e conclusão. Preserva registros/ponteiro originais.

```sh
php tests/run.php
# Somente no laboratório interno existente, após preflight:
PYTHONPATH=tests/lab python tests/lab/integrated.py --with-apa-regression --with-submission-cycle --with-doc-cycle --with-language-cycle
```

Resultados e contagens atuais em
[evidence/stage57/validation.json](evidence/stage57/validation.json). A regressão
inclui os 21 modos APA anteriores, painéis e os ciclos DOCX/DOC/EN/ES. Fixtures
continuam sintéticas; cópias de credenciais, estado e configurações privadas não
integram as evidências versionadas.

Não certifica APA integral. Datas completas no matcher, `s.f.`/`en prensa` e
outras formas fora da gramática, variantes linguísticas/estruturais e homologação
operacional continuam pendentes. Reconhecer datas completas na auditoria de
referências não as liga automaticamente a citações autor–ano. Não houve acesso
a produção/VM Oracle, envio externo, publicação do ambiente ou deploy.
