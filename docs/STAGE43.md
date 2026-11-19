# Etapa 43 — separação de referências no metadado do OJS

Base 30a670d (merge PR #43), em 19/11/2026. Motor 0.33.0 preservado e plugin
0.26.0.0. Regras APA, contratos, relatório CLI, minuta e bloqueio de admissão
preservados. Esta entrega é uma orientação local no formulário, não uma nova
regra de reprovação ou certificação bibliográfica.

## Comportamento

No campo citationsRaw da etapa 3 do wizard e da aba Publicação → Referências,
orienta uma referência inteira por linha, com Enter entre referências. A quebra
automática na tela não individualiza entradas. Enter dentro de uma referência
longa também deve ser evitado, pois pode criar itens incompletos. Linhas em branco
adicionais não são exigidas. Não se altera o espaçamento da bibliografia no Word.

O código nativo OJS 3.3.0.20 foi lido no laboratório: CitationListTokenizerFilter
normaliza CR/LF, elimina quebras vazias e divide a lista por quebras de linha;
CitationDAO importa os itens não vazios. Isso fundamenta a orientação de Enter,
sem afirmar que espaçamento visual ou a verificação garante identificação,
correspondência ou registro no Crossref. Não foi realizado depósito Crossref.

O inspector reconhece indícios quando a linha começa por autoria/data do
subconjunto e contém outro início reconhecido depois de pontuação. Padrões
pessoais com iniciais, sobrenomes simples/compostos e algumas instituições;
ano de quatro dígitos/sufixo, n.d./s.d. e in press/no prelo. Vários autores de uma
mesma obra não são tratados como várias referências. O aviso pede conferência,
sem dividir, inserir Enter, reformatar ou salvar automaticamente.

Continuações/URL em linha própria e padrões não reconhecidos recebem orientação
de conferência manual. Sem indício não há rótulo de conformidade. Texto vazio
não cria exigência nova de preenchimento. Campos desabilitados/ausentes não são
habilitados pelo plugin; a configuração da revista controla sua disponibilidade.

## Alcance e limites

Apenas textarea do metadado de referências no backend com plugin/contexto ativos.
Funciona com inserção AJAX do wizard e componente da publicação; respeita textos
originais, aria-describedby existente e aviso aria-live. Mensagens pt_BR/en_US/
es_ES usam textContent, sem inserir bibliografia no HTML de ajuda. O script não
usa rede, telemetria, armazenamento ou chamadas de salvamento.

Formas ABNT, datas completas, descrições/idiomas fora do subconjunto e limites
ambíguos exigem revisão. Um título que reproduz uma citação bibliográfica completa
pode provocar um aviso: é indício, não veredicto. Não reorganiza referências,
não valida cada campo/identificador e não promete detectar toda concatenação.
A regra de ordenação bibliográfica segue pendente para a próxima etapa.

Limites: 131072 unidades UTF-16, 1024 linhas e 8192 unidades por linha, 32 índices
de linhas no resultado local. Limite excedido, HTML ou separador Unicode fora do
comportamento CR/LF do tokenizer retornam revisão, sem truncar como conforme.
Browsers sem suporte ao reconhecedor Unicode mantêm a orientação/manual.
Sem JavaScript esta nova orientação dinâmica não é executada; o OJS mantém seus
controles nativos. O relatório e a minuta do motor não incluem esse aviso local.

## Verificação

- 1438 testes por PHP 8.4.27 e 8.1.27, zero falhas.
- 16 testes JavaScript em Node 24.22.1; CI configurado com Node 20.
- Lint de 107 arquivos PHP, sintaxe JS, compilação Python, schemas CLI e diff aprovados.
- 19 verificações OJS 3.3.0.20/Chromium: ajuda nos dois formulários, aviso,
  acessibilidade, Enter/linha vazia/quebra visual, continuação incerta, ausência
  de duplicação, preservação do valor, recarga sem persistência e estado restaurado.
- Metadados citationsRaw e citações já individualizadas foram comparados antes/
  depois; nenhum salvamento automático. A exposição do campo ao autor foi
  habilitada apenas temporariamente no laboratório e a configuração foi restaurada.
- Rede interna, mounts read-only e e-mail neutralizado; versão instalada alinhada.
  Capturas sintéticas vistas em evidence/stage43. Containers desligados ao final.

```sh
node tests/js/reference-guidance.test.js
PYTHONPATH=tests/lab python tests/lab/reference-guidance.py
```

O ensaio de navegador exige laboratório previamente configurado e restaura a
configuração em finally. Incluído no plano integrado, que não foi repetido
integralmente nesta entrega. Sem produção, VM Oracle, documentos reais,
mensagens externas, depósito Crossref, publicação do ambiente ou deploy.
