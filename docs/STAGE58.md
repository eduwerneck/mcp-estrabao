# Etapa 58 — correspondência com datas completas

Base 7632136 (merge PR #58), em 20/11/2026. Motor 0.43.0, plugin 0.38.0.0.
Regras references.apa7 28.0.0, citation_matching 15.0.0 e citation_format 13.0.0;
recognized_formats permanece 17.0.0. Contratos e política editorial preservados.

## Resultado

Uma referência pessoal como `Silva, A. (2020, May 2). ...` agora pode fornecer
uma chave literal para `(Silva, 2020)`. Antes, essas entradas não eram lidas pelo
matcher, embora a auditoria de formato/calendário já as reconhecesse.

A projeção usa **o ano declarado na referência**, sem alterar sua data, conteúdo,
ordem ou metadados. É exclusiva do matcher; o parseEntry público usado pela
auditoria de formato continua inalterado. Não consulta fontes nem confirma que
se trata da obra efetivamente lida. Mesmo com ligação única, a citação conserva
orientação de conferência da fonte/data e a liberação APA exige revisão humana.

## Subconjunto e ambiguidade

São aceitos dia, mês e ano explícitos e válidos no calendário já implementado:
`2020, May 2` ou `2020, 2 de maio`, com nomes completos de meses reconhecidos em
inglês/português. Não se infere idioma nem se completa um dia ausente. A regra
pode operar nos perfis de manuscrito pt/en/es, mas **não amplia o dicionário de
meses para espanhol**; `2020, 2 de mayo` continua fora desta projeção.

| Situação | Correspondência |
| --- | --- |
| Autoria pessoal literal, ano sem sufixo, uma única candidata e contexto completo | Chave ligada; conferir fonte/data, sem certificação APA |
| Duas datas, duplicatas ou mistura de ano simples/data completa da autoria/ano relacionados | Ambígua, sem escolher a obra |
| Citação com sufixo e data completa sem sufixo | Revisão, sem inventar letras ou afirmar divergência comprovada |
| Autoria com iniciais diferentes, nomes parciais ou declaração de edição original | Preserva as ressalvas de identidade/edição; não seleciona a obra |
| Referência não lida ou delimitação incompleta junto da candidata | Não conclui ligação única segura nem referência ausente |
| Mês sem dia, calendário impossível/desconhecido, sufixo dentro da data completa ou instituição | Fora do novo parser pessoal; sem falsa ausência por essa leitura incompleta |
| Duas referências com cabeçalhos de data identificáveis coladas no mesmo parágrafo | Contexto aberto; não tratar a segunda obra como conteúdo da primeira |

A coorte usada para essa cautela considera primeiro sobrenome literal/relacionado
e ano. Pode reter casos que uma leitura humana conseguiria distinguir pelos
coautores. A intenção é não atribuir identidades ou sufixos por aproximação.
Marcadores de edição original continuam protegidos, inclusive quando a edição
consultada tem data completa.

Ao existir data completa projetada, o novo reconhecimento não fortalece provas
de et al., atribuição de sufixos ou ordenação dos grupos de citações: essas
comparações conservam revisão humana nesse contexto. Os erros locais comprovados
que não dependem dessa inferência, como `&` narrativo numa citação ligada, continuam
bloqueantes. A auditoria anterior de formatos/calendário/ordem de referências é
independente e pode apontar outra incompatibilidade comprovada.

Uma chave realmente ausente só falha quando todos os requisitos anteriores de
subconjunto fechado são satisfeitos. Chave ligada não significa identidade
bibliográfica confirmada; nenhum estado produz avanço automático.

Secundárias continuam ligando apenas a fonte consultada, e apud permanece
proibido pela política existente. Datas completas escritas na própria citação,
traduções, precisão mista, variantes institucionais e formas fora da gramática
não são normalizadas automaticamente.

## OJS, minuta e compatibilidade

A confirmação e a minuta usam as novas versões. A ficha privada de datas já
existente continua orientando a conferência. A nova evidência registra contagem
de referências com data completa e perfil `unique_personal_author_year_v1`,
sem acrescentar texto de manuscrito às mensagens de revisão.

O motor 0.43.0 exige nova revisão para liberações assinadas em versões anteriores,
inclusive `.doc` manual. Os registros antigos são preservados. O checklist não
APA continua separado e vinculado à análise; não vira uma liberação de APA.
A via `.doc` não passa a extrair ou verificar texto automaticamente.

## Validação e reprodução

Testes PHP cobrem ligações únicas nos três perfis, calendário, casos ambíguos,
sufixos, edição original, referências coladas, secundárias/apud, prova local e
preservação do conteúdo. O caso antigo que tratava toda data completa como não
reconhecida foi substituído pela nova cobertura.

O ciclo nativo usa novos rascunhos pt/en/es: bloqueio local, minuta, ambiguidades,
correção, revisão humana, novo arquivo com bytes iguais, CSRF e conclusão.
Preserva os registros e o ponteiro originais; não apaga os novos registros
sintéticos do ensaio. O ciclo combinado anterior agora reconhece suas nove
referências, em vez de deixar as duas com datas completas como não lidas.

```sh
php tests/run.php
# Somente no laboratório interno existente, com preflight:
PYTHONPATH=tests/lab python tests/lab/integrated.py --with-apa-regression --with-submission-cycle --with-doc-cycle --with-language-cycle --with-date-match-cycle
```

Contagens/evidências em [evidence/stage58/validation.json](evidence/stage58/validation.json).
Inclui regressão dos 21 modos APA anteriores e dos fluxos DOCX/DOC/EN/ES.
Sem certificação integral de APA, implantação na revista, produção/VM Oracle,
envio externo, manuscritos reais ou publicação do ambiente.

Próxima prioridade proposta: datas especiais em espanhol (`s.f.`/`en prensa`).
Meses espanhóis, outras autorias/edições/datas e homologação operacional continuam
pendentes; esta entrega não declara encerrada a cobertura de APA.
