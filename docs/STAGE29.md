# Etapa 29 — APA 7: autoria composta e listas abreviadas

Base 3c81b3a (merge PR #29), em 18/11/2026. Motor 0.21.0, plugin 0.13.0.0,
references.apa7 6.0.0, recognized_formats 3.0.0; contratos preservados.
A prioridade volta a APA 7 na entrada conforme solicitação editorial.
[Mapa de cobertura e continuidade](APA7.md).

ApaPersonalAuthors admite sobrenomes com até quatro componentes capitalizados,
partículas de um conjunto fechado e até quatro iniciais por pessoa. Conserva
nomes simples, hífen e apóstrofo. O limite continua em 2048 bytes e 64 nomes;
Unicode decomposto e conteúdo inesperado permanecem inconclusivos.
Não altera nomes, não confirma identidade e não amplia o matching autor–ano.

A mesma gramática amplia o detector composto semelhante a ABNT com data após
a fonte: iniciais ou prenome, cidade/editora/ano ou os sinais anteriores. Não
reprova sobrenome em maiúsculas isoladamente. Tipos jurídicos mantêm a exclusão.

Para listas abreviadas reconhece reticências Unicode, três pontos juntos ou
espaçados. A estrutura requer 20 nomes visíveis (19 primeiros e último), sem &.
Outra quantidade ou & nesse padrão é incompatibilidade comprovada; não tenta
reconstruir nomes omitidos. Mesmo com estrutura correta, a quantidade real de
21 ou mais autores e a ordem dos nomes precisam ser confirmadas na fonte.
Estrutura válida permanece inconclusiva no resultado global e não libera avanço.

Os diagnósticos chegam à regra APA, painel, minuta fixa e validação do wizard.
A minuta não reproduz nomes ou texto da bibliografia. A nova versão de regra é
reconhecida pelo adaptador; o fingerprint do motor torna liberações antigas
inválidas. Erro comprovado não admite liberação manual como se estivesse conforme.

## Verificação

- 986 testes sintéticos em PHP 8.4.27 e 8.1.27, zero falhas.
- 14 verificações no OJS 3.3.0.20/Chromium local: duas fixtures incompatíveis,
  impossibilidade de liberação, correção na minuta, privacidade, ausência de
  acusação genérica de ABNT, POST nativo e orientação visível ao autor.
- Estado editorial e bytes originais restaurados. Fixtures usam apenas dados
  sintéticos e não alteram DAO/publicação. E-mail neutralizado, rede interna e
  mounts do produto somente de leitura, conferidos pelo runner.
- A resposta nativa status=true pode indicar renderização do formulário, mesmo
  quando a conclusão foi impedida; o teste usa estado e notificações visíveis.
- Lint PHP, compilação do runner Python, schemas e diff conferidos.

Para repetir no laboratório isolado já preparado, alinhar a versão instalada
pelo utilitário nativo conforme HOMOLOGATION.md e executar:

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py
```

Evidências sintéticas em evidence/stage29. Containers desligados ao final.
Sem produção, VM Oracle, documentos reais, mensagens externas ou deploy.
Ainda não é certificação completa de APA: demais tipos, autoria institucional,
citações especiais e validação bibliográfica seguem o mapa APA7.md. As tentativas
de consulta normativa externa foram bloqueadas pelo proxy; base anterior mantida.
