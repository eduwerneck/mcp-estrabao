# Etapa 34 — APA 7: correspondência de sobrenomes compostos

Base 9333e6e (merge PR #34), em 18/11/2026. Motor 0.26.0, plugin 0.18.0.0,
APA 11.0.0, correspondência 7.0.0, formatos 6.0.0 e citações 5.0.0.
Schemas e quantidade de regras preservados; veto editorial a apud mantido.

A gramática de nomes já usada para diagnóstico passa à leitura de referências
pessoais canônicas com até 20 autores. Admite partículas e componentes definidos
em ApaPersonalAuthors, sem abreviar a lista, inferir identidade ou reescrever nomes.
Sufixos fora da gramática e entradas malformadas continuam inconclusivos.

Citações compostas só usam nomes efetivamente encontrados nas referências
pessoais analisadas. O vocabulário admite até 64 nomes compostos e 4096 bytes de
alternativas de nomes escapados. Ao exceder, mantém o reconhecedor anterior de sobrenomes simples
e a conferência manual para as formas compostas. compound_name_lookup_complete
indica apenas a cobertura desse vocabulário local; não certifica a bibliografia.

Espaços entre componentes são normalizados apenas na comparação. Os offsets
continuam sendo os do texto original, inclusive acentos e nomes em runs Word.
Não tenta transformar prosa, como Estudo de Silva, em um nome ausente. Fonte
bibliográfica e segmentos seguros reconhecem a mesma gramática, inclusive no
tratamento já existente de referências em negrito.

Um componente significativo compartilhado pode indicar forma parcial do nome.
Nesses casos, como Silva para da Silva ou Silva Neto, produz ambiguidade para
conferência: não cria alias e não prova ausência de referência. Partículas
isoladas não são tratadas como equivalência de autoria. Nomes diferentes sem
vínculo podem comprovar chave ausente somente no subconjunto fechado documentado.
Não confirma que dois nomes pertençam à mesma pessoa.

Vínculos compostos admitem um/dois autores, et al., listas extensas e as fontes
consultadas de citações secundárias. As regras anteriores de conjunção, espaços,
sufixo de ano e desambiguação continuam aplicáveis. A primeira apresentação de
siglas e outras exceções institucionais continuam para revisão. Não acrescenta
ordenação Unicode nem resolve todas as colisões de identidade.

## Admissão e devolutiva

Falhas comprovadas chegam ao bloqueio e à orientação fixa ao autor. A minuta não
copia nomes ou referências. Formas parciais, vocabulário excedido, corpo ausente
e perfis inseguros continuam para conferência sem avanço automático. Versão nova
invalida liberações anteriores; falha comprovada não admite liberação editorial.

## Verificação

- 1179 testes sintéticos em PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 94 arquivos PHP, compilação Python, schemas CLI e diff aprovados.
- 20 verificações OJS 3.3.0.20/Chromium local: conjunção incorreta e chave ausente
  com bloqueio/minuta/privacidade/POST/feedback; formas literal e parcial seguem
  revisáveis, sem conclusão automática ou liberação salva.
- DOCX sintético real confirma o vínculo literal com caracteres Unicode. Casos
  de vocabulário excedido, prosa, formas parciais, anos/sufixos e fonte secundária.
- Rede interna, mounts read-only, e-mail neutralizado e versão instalada conferidos.
  Estado e bytes originais restaurados; capturas sintéticas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --matching
```

Requer laboratório isolado preparado e versão instalada alinhada conforme
HOMOLOGATION.md. Evidências em evidence/stage34. Containers desligados ao final.
Sem produção, VM Oracle, documentos reais, envio externo ou deploy. Não houve
nova consulta normativa externa; a etapa amplia o vínculo literal e aplica as
regras documentadas, sem certificar APA integral ou identidade bibliográfica.
Continuidade na matriz APA7.md.
