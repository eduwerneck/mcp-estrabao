# Etapa 32 — citações secundárias e veto editorial a apud

Base dfd4e9d (merge PR #32), em 18/11/2026. Motor 0.24.0, plugin 0.16.0.0,
APA 9.0.0, correspondência 6.0.0 e formato de citações 4.0.0.
Formatos de referências 4.0.0 e schemas preservados.

## Decisão editorial

O responsável determinou explicitamente em 18/11/2026: negar o uso de apud.
A política estrabao-no-apud-2026-11-18 proíbe apud em citações secundárias;
não é uma alegação de que a palavra isolada prove ABNT ou de que exista uma
proibição universal da expressão pela APA. A correção solicita como citado em
e a identificação da fonte efetivamente consultada.

A expressão reconhecida bloqueia a conclusão mesmo se a obra consultada tiver
referência presente, ausente ou inconclusiva. Não admite liberação humana como
se estivesse conforme. O motivo estruturado distingue incompatibilidade editorial
de incompatibilidade APA; as mensagens nos três idiomas e a minuta foram
ajustadas. Menções explicativas à palavra fora da gramática de citação não são
tratadas como veto editorial.

## Correspondência da citação secundária

O subconjunto reconhece formas parentéticas ou narrativas com como citado em,
as cited in ou apud, com ano original opcional e ano da fonte consultada obrigatório.
Admite localizadores simples e combina os nomes consultados com as gramáticas
anteriores, inclusive instituições/siglas explicitamente vinculadas.

Apenas a fonte consultada entra na correspondência. A obra original não consultada
não precisa estar na lista; se também estiver, isso não comprova erro ou leitura
independente. A veracidade do histórico de consulta permanece humana. Mesmo com
vínculo reconhecido, a apresentação secundária fica para conferência editorial;
nenhum resultado libera automaticamente.

Uma fonte consultada sem referência pode gerar falha apenas no subconjunto fechado
já documentado. Ambiguidade, referências não reconhecidas e instituições sem vínculo
mantêm as proteções anteriores. Cadeias secundárias múltiplas, autores fora da
gramática e formas incompletas ficam inconclusivos; não se inventa a fonte. Perfis
parciais e limites de tamanho/segmentação continuam explícitos.

O adaptador acrescenta orientação fixa para citações sem referência correspondente,
incluindo a distinção da fonte consultada. Notas internas inconclusivas são filtradas
da correção ao autor. Mensagens e minuta não copiam nomes ou
trechos. Arquivo e metadados não são reescritos; liberações antigas ficam inválidas
pela versão do motor.

## Verificação

- 1087 testes sintéticos em PHP 8.4.27 e 8.1.27, zero falhas.
- 17 verificações OJS 3.3.0.20/Chromium local: veto a apud, fonte consultada ausente,
  minuta/privacidade, bloqueio, feedback concreto ao autor e caso correto que continua
  exigindo revisão. Estado e bytes originais restaurados, sem liberação salva.
- Lint de 91 arquivos PHP, compilação Python, schemas CLI e diff aprovados.
- Evidências selecionadas em evidence/stage32; capturas sintéticas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --secondary
```

Requer laboratório isolado preparado e versão instalada alinhada conforme
HOMOLOGATION.md. Containers desligados ao final. Sem produção, VM Oracle,
documentos reais, mensagens externas ou deploy. Não houve nova consulta normativa
externa. O veto se fundamenta na instrução editorial explícita desta etapa;
continuam os limites gerais e outras lacunas da matriz APA7.md.
