# Etapa 33 — APA 7: modelos de artigos, obras autônomas e capítulos

Base ec726d6 (merge PR #33), em 18/11/2026. Motor 0.25.0, plugin 0.17.0.0,
regra APA 10.0.0 e formatos reconhecidos 5.0.0. Correspondência 6.0.0,
formato de citações 4.0.0 e schemas preservados. Veto editorial a apud mantido.

## Campos ampliados

Artigos de periódico no formato já reconhecido passam a conferir a ordem de
intervalos numéricos de páginas: a final não pode anteceder a inicial. Isso não
consulta a paginação real. Página única e identificador de artigo não recebem
um intervalo inventado. Não se exige DOI ausente nem paginação em tipos não
identificados, como publicações antecipadas.

Capítulos com In e Ed./Eds. admitem até 20 editores, com iniciais e sobrenomes
simples/compostos da gramática delimitada. A editoria conserva a ordem iniciais–
sobrenome, diferente da lista de autores. Entre dois editores usa &, sem vírgula;
para três ou mais, vírgulas e vírgula seguida de & antes do último. O rótulo Ed.
ou Eds. é conferido pela quantidade reconhecida. Separadores legados reconhecidos
são diagnosticados sem afirmar que toda a bibliografia esteja em ABNT.

Além de título do capítulo/livro e editora, confere ausência de itálico nos nomes
dos editores, rótulo Ed./Eds., indicação de páginas e edição. Admite a edição no
mesmo parêntese das páginas, por exemplo (2nd ed., pp. 10–20), e confere o sufixo
ordinal inglês. Não confirma editoria, edição ou páginas na fonte.

Obras autônomas com edição explícita, como Livro (2nd ed.). Editora, são candidatas
a um modelo visual, não livros bibliograficamente certificados. Só há diagnóstico
de edição quando o título está comprovadamente em itálico e a fonte sem itálico.
Confere edição sem itálico e ordinal inglês (2nd, 3rd, 11th etc.). Não impõe edição
positiva: 0th é um ordinal formalmente válido, cuja existência depende da fonte.
Quando tipo/formatação não estão confirmados, mantém revisão em vez de exigir
itálico de um material que poderia ser artigo ou outro tipo.

Os formatos de capítulos/edições ampliados são os modelos em inglês indicados;
Org./Orgs., 2a ed., traduções, volumes e outras formas não são declarados infrações
por estarem fora dessa gramática. Nomes inesperados, mais de 20 editores, entradas
ambíguas e editoras fora do subconjunto ficam inconclusivos. O helper limita
editoria a 2048 bytes e intervalos/edições a nove dígitos. Mantém os limites de
parágrafos, referências, ZIP/XML e normalização anteriores.

## Entrada e devolutiva

Falhas concretas chegam ao bloqueio do wizard, painel e minuta com orientações
fixas por campo. A tipografia da edição é explicada ao autor sem detalhes do
classificador. Não copia referências, não reescreve o arquivo e não envia mensagens.
Sem falha comprovada não há avanço automático. O fingerprint da versão nova
invalida liberações anteriores, e falha comprovada não admite liberação editorial.
A cauda bibliográfica segura pode comprovar erros de campo sem aprovar o corpo.

## Verificação

- 1140 testes sintéticos em PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 93 arquivos PHP, compilação Python, schemas CLI e diff aprovados.
- 23 verificações OJS 3.3.0.20/Chromium local: edição em itálico, separadores de
  editoria e páginas invertidas; bloqueio, minuta privada, POST e feedback ao autor.
- Caso de capítulo com três editores, edição e formatação corretas permanece
  revisável, sem conclusão automática nem liberação salva.
- DOCX sintéticos com runs de itálico reais, incluindo caracteres acentuados e &
  escapado no XML. Estado e bytes originais restaurados; capturas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --models
```

Requer laboratório interno preparado e versão instalada alinhada conforme
HOMOLOGATION.md. Evidências em evidence/stage33. Containers desligados ao final.
Sem produção, VM Oracle, documentos reais, mensagens externas ou deploy.
Não houve nova consulta normativa externa: amplia as regras de apresentação já
adotadas, sem anunciar consulta ao manual oficial ou certificação integral.

Continuam pendentes outros tipos/campos, omissão/inclusão apropriada de edição,
metadados e identidade reais, semântica, traduções e ambiguidades documentadas
na matriz APA7.md. Esta etapa amplia os três modelos; não fecha toda a APA 7.
