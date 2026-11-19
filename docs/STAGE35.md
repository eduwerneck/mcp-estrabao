# Etapa 35 — APA 7: anos, sufixos e múltiplas obras

Base 8e3b021 (merge PR #35), em 18/11/2026. Motor 0.27.0, plugin 0.19.0.0,
APA 12.0.0, correspondência 8.0.0 e formato de citações 6.0.0. Formatos de
referências 6.0.0, schemas e quantidade de regras preservados.

## Anos explícitos e organização

Reconhece até 20 anos explícitos, separados por vírgula, sob a mesma autoria:
(Silva, 2019, 2020a, 2020b) ou Silva (2019, 2020a, 2020b). Cada ano produz um
vínculo separado, sem truncar silenciosamente a lista. As formas narrativas
seguem a posição e os introdutores já aceitos; a autoria precisa ter candidato
nas referências para não converter prosa como Resultados (2019, 2020) em autor.
Não amplia arbitrariamente a leitura de nomes no meio de parágrafos.

A ordem cronológica só é conclusiva quando o grupo está completo, cada vínculo
é único e a autoria pessoal declarada nas referências coincide, incluindo iniciais,
lista e ordem dos nomes. Anos com iniciais ou listas diferentes, duplicações e
ambiguidades ficam para revisão. Não confirma identidade real nem agrupa pessoas.

Em citações parentéticas com itens separados por ponto e vírgula, confere ordem
alfabética apenas entre primeiros sobrenomes pessoais ASCII distintos, todos
vinculados sem ambiguidade. Acentos, partículas, empates, instituições, citações
secundárias e itens não interpretados mantêm conferência manual. Não modifica
a ordenação bibliográfica nem resolve todas as exceções de agrupamento.

## Sufixos autor–ano

Compara a chave citada com as chaves declaradas das referências no mesmo ano-base.
Se a autoria pessoal declarada coincide e não há referência com aquele sufixo,
sinaliza a divergência de formato. Por exemplo, Silva, 2020 ou Silva, 2020c diante
de referências Silva, A. (2020a) e Silva, A. (2020b) exige conferir e alinhar as
chaves. Não escolhe a ou b pela ordem do texto ou pelo título.

A correspondência fica inconclusiva quando a obra não pode ser escolhida, mas a
divergência entre chaves pode ser comprovada e bloquear pelo formato. Iniciais
ou listas de autores diferentes, instituições, fonte não analisável e sintaxe
incerta impedem esse diagnóstico automático. Chaves duplicadas sem sufixo ficam
para revisão, sem inventar letras ou presumir se há duas obras ou duplicatas.
A atribuição/ordenação das letras por título e a identidade real seguem humanas.

## Limites e devolutiva

Corpo e referências precisam estar segmentados com segurança. Intervalos de anos,
formas abreviadas como 2020a,b, listas maiores e localizadores misturados a vários
anos permanecem inconclusivos. Preserva o limite global de 2048 citações, incluindo
as expandidas, e os limites anteriores de texto/ZIP/XML. Cauda bibliográfica não
passa a conferir citações de um corpo ausente.

Falhas concretas bloqueiam a conclusão e a liberação editorial; tela do autor e
minuta usam mensagens fixas, sem copiar nomes ou escolher a obra consultada.
Notas inconclusivas não viram correções ao autor. Amostras limitadas priorizam
falhas sobre casos inconclusivos para preservar uma correção tardia. O veto a apud
continua ativo. A versão nova invalida liberações anteriores pelo fingerprint.

## Verificação

- 1220 testes sintéticos em PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 95 arquivos PHP, compilação Python, schemas CLI e diff aprovados.
- 38 verificações OJS 3.3.0.20/Chromium local: sufixo divergente, anos invertidos e
  ordem alfabética com bloqueio/minuta/privacidade/POST/feedback específico,
  incluindo erro tardio após 40 grupos inconclusivos.
- Quatro casos revisáveis sem conclusão automática: anos corretos, iniciais diferentes
  entre anos, sufixos com autoria ambígua e forma parcial combinada com sufixo.
  Nenhuma liberação salva.
- DOCX real confirma os vínculos expandidos. Testes cobrem limites de 20/21 anos,
  limite global, prosa, sintaxe incerta, duplicatas e falha tardia na amostra.
- Rede interna, mounts read-only, transporte neutralizado e versão instalada
  conferidos. Bytes e estado originais restaurados; capturas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --years
```

Requer laboratório isolado preparado e versão instalada alinhada conforme
HOMOLOGATION.md. Evidências em evidence/stage35. Containers desligados ao final.
Sem produção, VM Oracle, documentos reais, envio externo ou deploy. Não houve nova
consulta normativa externa; amplia regras de organização/chaves sem anunciar
certificação integral de APA 7. Continuidade e exceções na matriz APA7.md.
