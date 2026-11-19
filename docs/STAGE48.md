# Etapa 48 — regressão integrada das ampliações APA

Base 9e16c78 (merge PR #48), em 19/11/2026. Motor 0.37.0 e plugin 0.30.0.0
preservados. Não muda regras de triagem, política editorial, contratos ou versões.
O manifesto em evidence/stage48 comprova 68 arquivos de src/plugins/contracts
idênticos à base integrada.

## Ensaio combinado

O runner reúne dez suítes de painel/artigo/metadados e dezessete modalidades APA:
autoria, citações, instituições, fontes secundárias, modelos, nomes compostos,
anos/sufixos, et al., datas especiais, teses/relatórios, web, dados/software,
traduções/republicações, ordenação, citações diretas, narrativas e coautorias.
Cada suíte restaura as fixtures sintéticas. Resultado parcial não anuncia aprovação.

O ciclo final cria cinco rascunhos sintéticos novos. Confere bloqueio ABNT no POST
e na interface, correção em uma nova versão do arquivo do servidor, análise atual,
revisão humana explícita e conclusão nativa. Também verifica revogação, CSRF,
invalidação por troca de arquivo, download protegido e acesso do autor.

A fixture corrigida passa a combinar sete referências e sete citações ligadas:
anos/sufixos, expansão necessária de et al., n.d./s.d., narrativa de 39 palavras
com localizador de página e autor único antes de coautorias ordenadas pelos
coautores seguintes. Asserções conferem dois pares de listas pessoais, candidato
entre aspas com intervalo técnico [39,39], orientação privada sem transcrição e
ausência de erro de formato comprovado. A conclusão permanece bloqueada até
revisão humana expressa e preserva o SHA-256 do documento corrigido.

A substituição usa API local de arquivos/DAO, não o upload pela interface.
Não homologa concorrência de uploads. Conclusão sintética não equivale a aceite
editorial ou publicação. Não altera os registros antigos para simular sucesso.

## Resultado

- 1577 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 113 arquivos PHP, compilação de todos os runners Python, dois schemas
  CLI e diff aprovados.
- 27 suítes reversíveis: 671 verificações de navegador aprovadas.
- Ciclo novo: 24 verificações aprovadas; total **695 verificações**.
- Registros originais preservados após as suítes e o ciclo novo; apontador de
  estado restaurado e cópia temporária de credenciais removida em finally.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos.
  Containers desligados ao final. Capturas selecionadas inspecionadas.

Novos rascunhos, conclusões e registros de revisão permanecem apenas na base
sintética local. Evidências versionadas excluem credenciais, configurações,
state.json e logs privados; os resumos e resultados selecionados não contêm
manuscritos reais. A suíte integrada atual é documentada em HOMOLOGATION.md.

## Reprodução e limites

```sh
ESTRABAO_INTEGRATED_EVIDENCE_DIR=/workspace/scratch/estrabao-lab-48 \
  PYTHONPATH=tests/lab python tests/lab/integrated.py \
  --with-apa-regression --with-submission-cycle
```

Requer laboratório local previamente configurado (OJS 3.3.0.20/Tema Padrão,
rede Docker interna, fontes read-only e envio de e-mail neutralizado).
O runner não provisiona, instala ou atualiza serviços.

Sem produção, VM Oracle, documentos reais, mensagens externas, publicação do
ambiente ou deploy. Instalação/infraestrutura real, API/importações e as lacunas
de cobertura APA7.md continuam pendentes. Fidelidade de transcrições, existência
das fontes, identidade/edição efetiva e layout renderizado exigem revisão humana.
Não há nova consulta normativa externa nem certificação integral de APA 7.
