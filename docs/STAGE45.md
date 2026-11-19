# Etapa 45 — localizadores e candidatos a citações diretas

Base d67a3da (merge PR #45), em 19/11/2026. Motor 0.35.0, plugin 0.28.0.0,
APA 20.0.0, correspondência 12.0.0 e formato de citações 10.0.0.
Formatos bibliográficos 12.0.0 e contratos JSON preservados. A nova versão
invalida liberações anteriores.

## Diagnóstico e devolutiva

Localizadores p./pp. são conferidos apenas em citação reconhecida, completa,
ligada a uma referência única e fora de transcrição/citação secundária.
Página final anterior à inicial e troca inequívoca de p./pp. reprovam o
componente APA e bloqueiam a conclusão com orientação específica ao autor.
Zero, intervalo com páginas iguais, parágrafo e marca temporal ficam para
conferência. Não consulta a fonte nem confirma a localização real.

O modelo de transcrição reconhece um parágrafo inteiro entre aspas duplas
retas ou curvas correspondentes, seguido imediatamente de uma única citação
parentética completa, admitindo pontuação final. Registra somente contagens,
intervalo técnico de palavras e orientações fixas no painel editorial privado.
Não copia a transcrição, autores, anos ou localização para as orientações.

Candidatos com pelo menos 40 palavras, ou intervalo técnico que atravesse esse
limite, pedem conferência de bloco/aspas. Ausência de localizador reconhecido
pede conferência da forma apropriada à fonte, sem exigir página de toda obra.
Esses sinais não constituem reprovação automática. O intervalo de palavras
usa a tokenização técnica existente, sem certificação de contagem normativa.

Citações reproduzidas dentro da transcrição não são presumidas obras consultadas
pelo autor: ausência de referência e grafia interna não geram correção automática.
Sua presença impede presumir contexto completo para desambiguação de et al.
A análise continua privada, sem reescrita do manuscrito ou mensagem externa.

## Limites

Narrativas com localizadores, aspas aninhadas/mistas, múltiplos parágrafos,
blocos sem aspas e fontes não reconhecidas permanecem fora do novo modelo.
Não verifica fidelidade, recuo, layout renderizado nem fonte efetivamente
consultada. Preserva o espaçamento editorial documentado em STAGE11.md;
não impõe espaçamento genérico nem inventa recuo. O reconhecedor de parágrafo
entre aspas limita-se a 65536 bytes; demais limites ZIP/XML/texto permanecem.
Mantém as ressalvas de APA7.md, sem nova consulta normativa externa ou
certificação de APA integral.

## Validação

- 1506 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 111 arquivos PHP, compilação Python, dois schemas CLI e diff aprovados.
- 55 verificações OJS 3.3.0.20/Chromium: três erros de página com bloqueio,
  minuta/privacidade e POST nativo; sete candidatos revisáveis sem avanço
  automático, incluindo proteção de citação interna; restauração final.
- Casos sintéticos de 39/40/41 palavras, hífen no limite, aspas retas/curvas,
  falta de localizador, parágrafo, tempo, grupo incompleto e vínculo ambíguo.
- Isolamento, mounts read-only, e-mail neutralizado e versão instalada conferidos;
  bytes/estado restaurados. Evidências em evidence/stage45. Containers desligados.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --quotes
```

Requer laboratório local previamente configurado. Modalidade adicionada ao runner
integrado opcional; a regressão histórica de 287 verificações não foi repetida
integralmente. Sem produção, VM Oracle, documentos reais, mensagens externas,
publicação do ambiente ou deploy. CI remoto ainda não verificado nesta entrega.
