# Etapa 39 — APA 7: modelos declarados de teses, dissertações e relatórios

Base aec02aa (merge PR #39), em 19/11/2026. Motor 0.30.0 e plugin 0.22.0.0;
APA 15.0.0 e formatos 8.0.0. Correspondência 10.0.0 e citações 8.0.0 preservadas.
Schemas JSON preservados. A nova versão invalida liberações anteriores.

## Subconjunto entregue

ReferenceLongWorks reconhece descrições explícitas em português/inglês:

- Tese de doutorado/Dissertação de mestrado, Doctoral dissertation e Master's
  thesis: descrição entre colchetes com instituição e fonte posterior, no modelo
  declarado de repositório/base de dados. Não presume publicação real.
- Declarações não publicada/Unpublished entre colchetes: instituição posterior,
  sem inferir essa situação pela ausência de URL.
- Report No., Relatório nº/n°/n. e Relatório No. com identificador declarado entre
  parênteses: título e fonte posterior. Não exige número em todo relatório.

Confere título em itálico, descrição/numeração sem itálico e fonte posterior sem
itálico, usando spans e offsets UTF-8 do DOCX. A conferência de fonte/grau,
instituição, tipo, situação de publicação e repositório/editora permanece
indeterminada. Mesmo o modelo visual reconhecido não certifica a referência.

Falhas de formatação comprovadas bloqueiam conclusão/liberação, com mensagem
específica ao autor e minuta fixa sem copiar título, instituição ou bibliografia.
Inconclusivos continuam para revisão, sem avanço automático e sem envio de carta.
Descrições acadêmicas parcialmente reconhecidas não são tratadas como livro ou
periódico para impor formatação de um tipo adivinhado.

## Limites

Sem descrição explícita não presume tese ou relatório. Ausência de número,
instituição, repositório ou URL fora do modelo reconhecido não vira reprovação
por campo supostamente obrigatório. Não valida a identidade/autoria, o grau,
a existência da obra ou do número, a fonte, um link real ou o estado de publicação.

Título limitado a 1024 caracteres no modelo; instituição e fonte com vocabulário
limitado de letras/números, espaços, &, apóstrofos e hífens. Abreviações com
pontos, múltiplos campos/locais, número de publicação de base de teses, outros
graus/idiomas, fontes complexas, tipos de relatório e descritores não cobertos
ficam para revisão. Preserva limites de 8192 bytes por referência, 512 entradas,
2048 citações e limites ZIP/XML anteriores. Não reescreve documentos.

A interpretação não consulta rede. Conserva a base técnica/ressalvas de
STAGE11.md e APA7.md; não anuncia nova consulta ao manual ou validação integral.

## Validação

- 1323 testes em PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 99 arquivos PHP, compilação Python, dois schemas CLI e diff aprovados.
- 38 verificações OJS 3.3.0.20/Chromium: título de tese sem itálico, descrição
  em itálico, número de relatório em itálico e fonte em itálico, com bloqueio,
  minuta/privacidade, POST nativo e orientação ao autor.
- Quatro cenários revisáveis, sem conclusão automática: modelo de repositório,
  dissertação não publicada, relatório numerado e descrição incompleta.
- Testes do motor cobrem português/inglês, acentos/offsets, URL/DOI informado,
  ausência de número/URL, formatação indisponível, descrição ambígua e minuta.
- Rede interna, sem portas públicas, mounts read-only e e-mail neutralizado;
  versão instalada alinhada. Estado e bytes sintéticos restaurados. Capturas
  vistas e evidências selecionadas em evidence/stage39. Containers desligados.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --longworks
```

Requer laboratório isolado previamente configurado. A modalidade foi adicionada
ao plano opcional --with-apa-regression; as 287 verificações integradas da Etapa
38 não foram repetidas nesta ampliação. Sem produção, VM Oracle, documentos
reais, mensagens externas, publicação do ambiente ou deploy.
