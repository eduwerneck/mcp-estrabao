# Etapa 42 — APA 7: tradução e publicação original

Base 02f0844 (merge PR #42), em 19/11/2026. Motor 0.33.0, plugin 0.25.0.0,
APA 18.0.0, formatos/correspondência 11.0.0 e citações 9.0.0. Contratos JSON
preservados; a versão nova invalida liberações anteriores.

## Metadados de tradução

ReferenceRevisions reconhece nomes com iniciais e função Trans., Trad. ou Trads.
entre parênteses após o título, com fonte simples posterior. Confere nomes/função
sem itálico e, quando presente, edição inglesa no mesmo parêntese após ponto e
vírgula, sem itálico e com concordância do sufixo ordinal. Usa os limites/ranges
UTF-8 e o vocabulário pessoal já reconhecido.

No modelo inglês Trans., verifica & entre dois nomes e vírgulas com , & antes
do último para três ou mais. Não impõe esse modelo a Trad./Trads.; função,
contagem, tradutor efetivo e convenção portuguesa permanecem para conferência.

Tradução não comprova que a obra seja livro. Título/fonte e tipo são revisáveis,
sem impor itálico de monografia a um possível artigo traduzido. A edição é a
informada, não uma edição identificada ou completada pelo motor. Ausência de
edição ou ano original não exige informação inventada.

## Publicação original e correspondência

Reconhece indicação terminal Original work published YYYY, Obra original
publicada em YYYY ou Trabalho original publicado em YYYY entre parênteses.
Confere a indicação sem itálico e, quando os dois anos são numéricos declarados,
ano original positivo e não posterior ao da edição consultada. Anos iguais são
permitidos. A análise confere coerência declarada, não a data histórica real.

A indicação pode acompanhar URL HTTP antes/depois no subconjunto reconhecido;
a posição editorial exata exige conferência. Se a indicação for ambígua,
repetida, incompleta ou sem parênteses, permanece para revisão, sem escolher
um ano. Não adivinha ano original de uma edição sem data.

O ano original associado a autoria possivelmente relacionada impede afirmar
referência ausente só pela divergência com o ano da edição. Isso não confirma
a identidade da obra nem transforma o ano original em alias da edição. Protege
também provas de sufixo, et al. e ordenação em grupos quando a associação depende
dessa escolha. A correspondência literal existente continua; citações com barra,
como 1900/2020, permanecem indeterminadas, sem preenchimento automático.

Republicação sem tradutor mantém o modelo anterior do tipo/campos reconhecidos,
com a indicação original conferida separadamente. Não presume monografia pela
presença desse ano. Separar a indicação preserva os offsets dos campos anteriores.

## Entrada, devolutiva e limites

Falhas comprovadas de itálico, separadores/ordinal inglês e cronologia declarada
bloqueiam conclusão/liberação com mensagem fixa no wizard e minuta privada.
Não copia tradutor, título ou datas para a minuta. Inconclusivos exigem revisão
humana, sem avanço automático, reescrita do arquivo ou envio de carta.

Reconhece até 20 tradutores no vocabulário de nomes existente, título de até
1024 caracteres e fonte simples de até 256 caracteres. Edições usam o modelo
numérico inglês limitado a nove dígitos e st/nd/rd/th ed. Preserva os limites
anteriores de texto/8192 bytes por referência, 512 entradas, 2048 citações e ZIP/XML.

Outras descrições de tradutor/editor, abreviações, idiomas, fonte complexa,
edição/reimpressão, anos não numéricos, anotações fora do subconjunto e obras
com mais de uma declaração original exigem revisão. Não identifica tradutor,
obra original ou edição; não consulta catálogos/links nem certifica APA integral.
Mantém a base técnica/ressalvas de STAGE11.md/APA7.md, sem nova consulta normativa.

## Verificação

- 1435 testes por PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 105 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 47 verificações OJS 3.3.0.20/Chromium: tradutor, edição e indicação original em
  itálico; cronologia impossível; separador inglês inadequado. Cada falha confere
  bloqueio/minuta/privacidade/POST e feedback ao autor.
- Cinco cenários revisáveis sem conclusão automática: tradução reconhecida,
  republicação sem tradutor, citação pelo ano original, citação com barra e
  indicação original incompleta.
- Testes do motor incluem papéis portugueses, nomes compostos, ano igual/zero/
  posterior, URL antes/depois, campos ausentes, ordinal, ambiguidade/duplicatas,
  sufixos, et al., grupo ordenável indevidamente e minuta sem dados privados.
- Rede interna, mounts read-only, e-mail neutralizado e versão instalada alinhada.
  Arquivo/estado sintéticos restaurados. Capturas vistas em evidence/stage42;
  containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --revisions
```

Requer laboratório isolado previamente configurado. Modalidade incluída no
runner opcional integrado; a suíte completa de 287 casos da Etapa 38 não foi
repetida nesta ampliação. Sem produção, VM Oracle, documentos reais, mensagens
externas, publicação do ambiente ou deploy.
