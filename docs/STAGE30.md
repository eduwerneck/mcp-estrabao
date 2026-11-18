# Etapa 30 — APA 7: três ou mais autores nas citações

Base 33171d1 (merge PR #30), em 18/11/2026. Motor 0.22.0, plugin 0.14.0.0,
regra APA 7.0.0, correspondência 4.0.0 e formato de citações 2.0.0.
Formatos de referências 3.0.0 e schemas mantidos.

O reconhecedor autor–ano português admite listas explícitas de três a vinte
sobrenomes simples e prefixos com dois ou mais nomes seguidos de et al. Continua
usando a gramática anterior de sobrenomes simples para correspondência; não
transfere automaticamente a ampliação diagnóstica de autoria da Etapa 29.

Quando uma citação é ligada sem ambiguidade a uma obra e a lista inteira de
referências é reconhecida, o motor conta as obras com o mesmo primeiro autor e
ano, incluindo o sufixo do ano. Se só houver uma, uma lista extensa ou um prefixo
expandido deve ser reduzido ao primeiro sobrenome seguido de et al. A regra vale
desde a primeira citação; não se infere primeira ocorrência pela ordem do texto.

Se houver outra obra com a mesma chave abreviada, nomes adicionais podem ser
necessários. O motor não decide o menor prefixo suficiente: produz revisão de
desambiguação, sem reprovar esses nomes. Referências não reconhecidas também
impedem descartar a exceção. Citação ambígua não comprova obra ausente.

A ausência de ponto em al é conferida apenas quando a citação tem vínculo único
com uma referência reconhecida. Não se tenta corrigir expressões inesperadas,
citações secundárias ou instituições. Caixa alta isolada continua inconclusiva.
Os limites de tamanho/quantidade, segmentação e perfis parciais são preservados.

## Admissão e devolutiva

Falhas comprovadas chegam ao bloqueio do wizard e à minuta fixa, sem reproduzir
nomes ou trechos do manuscrito. Não há liberação manual para erro comprovado;
nenhuma ausência de erro libera automaticamente. A versão da regra é reconhecida
pelo adaptador, e o fingerprint do motor invalida liberações da versão anterior.
Não há reescrita do arquivo ou envio ao autor.

## Validação

- 1023 testes em PHP 8.4.27 e 8.1.27, zero falhas.
- 14 verificações OJS 3.3.0.20/Chromium local: listas extensas e ponto ausente,
  bloqueio sem liberação, minuta com correção específica, privacidade, POST
  nativo e notificações visíveis ao autor. Bytes e estado originais restaurados.
- Lint de 88 arquivos PHP, compilação Python, schemas de relatório/minuta e diff.
- Evidências selecionadas em evidence/stage30; capturas sintéticas inspecionadas.

O runner reutiliza as guardas da Etapa 29, agora com cenários de citações:

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --citations
```

Requer laboratório interno preparado e versão instalada alinhada conforme
HOMOLOGATION.md. A fixture contém as seções documentadas da revista; sem elas,
a segmentação inconclusiva não é tratada como corpo completo. Containers foram
desligados ao final. Sem produção, VM Oracle, documentos reais ou deploy.

Não houve nova consulta normativa externa: a regra técnica de três ou mais
autores e a exceção de desambiguação são aplicadas neste subconjunto, sem
anunciar consulta ao manual nem certificação completa. O [mapa APA7.md](APA7.md)
registra instituições, fontes secundárias e demais lacunas para continuidade.
