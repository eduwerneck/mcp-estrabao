# Etapa 46 — citações narrativas com localizador

Base 93abfe5 (merge PR #46), em 19/11/2026. Motor 0.36.0, plugin 0.29.0.0,
APA 21.0.0, correspondência 13.0.0 e formato de citações 11.0.0.
Formatos bibliográficos 12.0.0 e contratos JSON preservados. A nova versão
invalida liberações anteriores.

## Cobertura

Reconhece localizador junto ao ano na narrativa, como Silva (2020, p. 10),
com a gramática anterior de autoria no início do parágrafo e seus introdutores
reconhecidos (Segundo, Conforme, According to, Según). Não procura autorias
arbitrárias no meio da prosa. Vários anos com um único localizador continuam
indeterminados, sem escolher a obra à qual o localizador pertenceria.

Associa também o localizador depois de uma transcrição inteira, como
Silva (2020) afirma “trecho” (p. 10). O restante do parágrafo precisa conter
somente o par de aspas duplas correspondente, um localizador opcional e a
pontuação final. Antes das aspas admite espaços, dois-pontos e, opcionalmente,
um dos verbos afirma, declara, escreve, observa, states ou writes.
A lista é um limite técnico de reconhecimento, não uma exigência editorial.

Localizador junto ao ano pode acompanhar a transcrição sem localizador terminal.
Dois localizadores simultâneos não são conciliados automaticamente. Texto
intermediário ou posterior, múltiplos trechos, aspas mistas/aninhadas e grupos
incompletos impedem a nova associação do localizador terminal.

## Resultado e proteção

Mantém as condições da Etapa 45: página final anterior à inicial e troca
inequívoca de p./pp. reprovam apenas com citação completa e referência única,
fora de transcrição interna e citação secundária. O autor recebe orientação
específica na confirmação da submissão; a minuta mantém instruções fixas.
Não presume citação direta para conferir um localizador junto ao ano.

Parágrafo, marca temporal, zero ou intervalo de páginas iguais exigem revisão.
Candidatos entre aspas recebem orientação editorial privada de fidelidade,
localização e extensão (39/40/41 palavras e intervalo técnico no limite).
Ausência de localizador reconhecido ou possível necessidade de bloco não
constitui falha automática. Não copia transcrições/autores/localizadores para
essas orientações e não modifica o manuscrito.

Citações parentéticas reproduzidas dentro das aspas conservam a proteção de
proveniência. Narrativas internas fora da gramática permanecem não analisadas;
não se presume sua referência ausente nem se corrige a transcrição. Não há
associação entre parágrafos nem inferência de fonte por proximidade.

## Limites e verificação

Preserva limite de 65536 bytes para o reconhecedor de transcrição e demais
limites anteriores. Não verifica recuo, layout renderizado, fidelidade, fonte
consultada ou existência bibliográfica. Não amplia blocos sem aspas nem citações
em vários parágrafos. Mantém espaçamento editorial de STAGE11.md e ressalvas de
APA7.md, sem nova consulta normativa externa ou certificação de APA integral.

- 1548 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 112 arquivos PHP, compilação Python, dois schemas CLI e diff aprovados.
- 70 verificações OJS 3.3.0.20/Chromium: quatro erros com bloqueio, minuta,
  privacidade e POST nativo; nove casos revisáveis sem avanço automático.
- Casos incluem localizador junto ao ano, ao fim da transcrição, 40 palavras,
  fronteira com hífen, ausência, parágrafo, tempo e citação interna reproduzida.
  Texto intermediário sem ligação clara não recebe associação nem guia de bloco.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  bytes/estado restaurados. Capturas inspecionadas em evidence/stage46.
  Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --narratives
```

Requer laboratório local previamente configurado. Modalidade adicionada ao runner
integrado opcional; a regressão histórica de 287 verificações não foi repetida
integralmente. Sem produção, VM Oracle, documentos reais, mensagens externas,
publicação do ambiente ou deploy. CI remoto ainda não verificado nesta entrega.
