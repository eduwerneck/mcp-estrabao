# Etapa 31 — APA 7: instituições e siglas declaradas

Base 7f2ca90 (merge PR #31), em 18/11/2026. Motor 0.23.0, plugin 0.15.0.0,
APA 8.0.0, correspondência 5.0.0, formatos 4.0.0 e citações 3.0.0.
Os schemas e a quantidade de regras foram preservados.

InstitutionalAuthors reconhece candidatos por um conjunto fechado de prefixos
institucionais (por exemplo Instituto, Universidade, Organização, Ministério e
algumas formas em espanhol/inglês). Uma referência deve ter nome completo,
ponto, ano entre parênteses e conteúdo. Duas entradas aparentes no parágrafo,
Unicode decomposto, formas inesperadas e tamanhos excessivos são inconclusivos.
Isso não consulta nem confirma a existência, natureza ou autoria da instituição.

A correspondência admite o nome completo literal na citação. Siglas só entram
no vínculo quando há declaração explícita com esse nome: Nome [SIGLA] ou
Nome (SIGLA, ano). A introdução deve começar o parágrafo/citação, com possível
Segundo/Conforme. Não deriva siglas das iniciais e não recorta um nome contido
em outra instituição. O subconjunto continua limitado ao corpo português e à
segmentação segura; não interpreta todas as maneiras de introduzir siglas.

Uma mesma sigla declarada para instituições diferentes, colisão com sobrenome,
referências duplicadas e vínculos ausentes ficam inconclusivos. A presença de
instituições reconhecidas impede que uma citação sem vínculo seja chamada de
referência comprovadamente ausente: pode ser uma sigla não interpretada. Essa
restrição é deliberadamente conservadora e também alcança citações pessoais
não ligadas em bibliografias mistas. Não reduz os demais erros comprovados.

Um vínculo literal pode atender à correspondência observada, mas não valida a
identidade da fonte. Siglas mantêm orientação de revisão da primeira apresentação,
inclusive quando a declaração aparece depois do uso. Não se reprova automaticamente
esse ordenamento, pois o subconjunto não interpreta toda a prosa de introdução.
A análise de formato da autoria institucional permanece para conferência humana.
Nenhum desses resultados permite avanço automático ou envia mensagens ao autor.

## ABNT institucional e entrada

A detecção composta de ABNT agora admite esses nomes institucionais no início.
Continua exigindo o ano após a fonte e sinais adicionais (como cidade/editora),
preservando exclusões jurídicas e evitando reprovar maiúsculas ou cidade isoladas.
O DOI informado com prefixo antigo também segue o diagnóstico existente.
Falhas comprovadas bloqueiam a conclusão e a liberação; minuta e tela do autor
usam orientações fixas, sem copiar nomes/referências. O fingerprint da versão nova
invalida liberações anteriores.

## Verificação

- 1055 testes em PHP 8.4.27 e 8.1.27, zero falhas.
- 17 verificações OJS 3.3.0.20/Chromium local: dois erros institucionais com
  bloqueio e feedback, e um caso de sigla vinculada que continua exigindo revisão.
- Rede interna, mounts read-only e e-mail neutralizado conferidos. Estado e bytes
  originais restaurados; nenhuma liberação foi salva no ensaio positivo.
- Lint de 90 arquivos PHP, compilação Python, schemas de relatório/minuta e diff.
- Evidências selecionadas em evidence/stage31; capturas sintéticas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --institutions
```

Requer laboratório isolado preparado e versão instalada alinhada pelo utilitário
nativo conforme HOMOLOGATION.md. Containers desligados ao final. Sem produção,
VM Oracle, documentos reais, mensagens externas ou deploy. Não houve nova
consulta normativa externa nem se anuncia validação integral de APA 7.

Próximas lacunas: citações secundárias e outros formatos da [matriz APA7.md](APA7.md).
