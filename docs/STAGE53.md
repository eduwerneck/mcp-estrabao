# Etapa 53 — regressão integrada com títulos e datas completas

Base aa855f8 (merge PR #53), em 19/11/2026. Motor 0.41.0 e plugin 0.34.0.0
preservados. Não muda regras, política editorial, contratos ou versões.
O manifesto em evidence/stage53 comprova 70 arquivos de src/plugins/contracts
idênticos à base integrada.

## Ensaio combinado

O runner reúne dez suítes de painel/artigo/metadados e 21 modalidades APA,
incluindo títulos/sufixos, artigos iniciais, subtítulos/pontuação e datas completas.
As suítes reversíveis conferem diagnóstico, bloqueio, mensagens, privacidade,
tratamento da incerteza e ausência de conclusão automática. Resultado parcial
não anuncia aprovação da regressão.

O ciclo final cria cinco rascunhos novos. Confere bloqueio ABNT no POST e na
interface, correção por nova versão do arquivo no servidor, análise atual,
revisão humana explícita e conclusão nativa. Confere também revogação, CSRF,
invalidação por troca de arquivo, download protegido e restrições ao autor.

A fixture corrigida combina nove referências e sete citações ligadas: anos e
sufixos, expansão de et al., data ausente, narrativa de 39 palavras com página,
autor único/coautorias e títulos com artigo inicial/subtítulo. Acrescenta duas
referências da mesma autoria com datas completas em ordem crescente.

As duas referências de datas completas continuam fora da gramática autor–ano do
matcher; o teste exige sete referências analisadas e duas não analisadas nessa
camada, sem fingir cobertura integral. A auditoria separada confere nove entradas,
um par de datas completas, dois pares de listas pessoais, um conjunto seguro de
títulos e um par com artigo/pontuação. Sufixos de datas completas permanecem
para revisão humana. Não afirma contexto completo de desambiguação após adicionar
referências fora da gramática do matcher.

O painel exibe orientação privada de datas/sufixos e de citação direta sem copiar
valores da fonte ou a transcrição. A correção não cria liberação automática.
Só a revisão humana expressa permite concluir; revisão e conclusão preservam
os bytes do arquivo corrigido. O WebDriver simula a confirmação explícita do
editor no laboratório; não substitui a conferência humana real. A substituição
usa API local/DAO, sem homologar
upload pela interface ou concorrência de uploads. Conclusão sintética não
constitui aceite editorial ou publicação.

## Resultado

- 1716 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 119 arquivos PHP, compilação dos runners Python, dois schemas CLI e
  diff aprovados.
- 31 suítes reversíveis: 883 verificações aprovadas no navegador.
- Ciclo novo: 27 verificações aprovadas; total **910 verificações**.
- Registros originais preservados após as suítes e o ciclo novo; apontador de
  estado restaurado e cópia temporária de credenciais removida em finally.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  capturas selecionadas inspecionadas. Containers desligados ao final.

Novos rascunhos, conclusões e revisões permanecem apenas na base sintética local.
Evidências versionadas excluem credenciais, configurações, state.json e logs
privados. Não há manuscritos reais como fixtures. Resumo e resultados por suíte
estão em evidence/stage53; a regressão atual é documentada em HOMOLOGATION.md.

## Reprodução e limites

```sh
ESTRABAO_INTEGRATED_EVIDENCE_DIR=/workspace/scratch/estrabao-lab-53 \
  PYTHONPATH=tests/lab python tests/lab/integrated.py \
  --with-apa-regression --with-submission-cycle
```

Exige laboratório previamente configurado: OJS 3.3.0.20/Tema Padrão, rede Docker
interna, fontes read-only e envio de e-mail neutralizado. O runner não provisiona,
instala ou atualiza serviços.

Sem produção, VM Oracle, documentos reais, mensagens externas, publicação do
ambiente ou deploy. Infraestrutura real, API/importações e lacunas de APA7.md
permanecem pendentes. Fidelidade, existência das fontes, identidade/edição,
aplicabilidade de datas por tipo e demais ambiguidades exigem revisão humana.
Não amplia cobertura normativa nem certifica APA integral.
