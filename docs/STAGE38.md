# Etapa 38 — regressão integrada após as ampliações APA

Base ad82d23 (merge PR #38), em 19/11/2026. Motor 0.29.0 e plugin 0.21.0.0
preservados. Não muda produto, política editorial, contratos ou versões.
O manifesto em evidence/stage38 comprova 61 arquivos de src/plugins/contracts
idênticos à base integrada.

## Escopo do ensaio

O runner opcional --with-apa-regression reúne as nove suítes anteriores do
painel/artigo e nove modalidades APA direcionadas: autoria, citações,
instituições, fontes secundárias, modelos, nomes compostos, anos/sufixos,
et al. e datas especiais. Cada modalidade continua isolada e restaura os
arquivos sintéticos após o uso. Um resultado parcial não anuncia aprovação.

--with-submission-cycle acrescenta cinco rascunhos sintéticos novos. Confere
ABNT bloqueada no POST e na interface, revisão humana explícita, conclusão
nativa, revogação, CSRF, troca de arquivo, download protegido e acesso do autor.
Não reutiliza submissões concluídas nem reseta os registros antigos.

A ampliação do ciclo substitui o documento ABNT por um novo arquivo do servidor
com anos/sufixos, expansão necessária de et al. e n.d./s.d. combinados. Confere
seis referências reconhecidas e cinco citações ligadas sem erro de formato
comprovado. A versão corrigida permanece bloqueada até revisão humana expressa;
a conclusão preserva o SHA-256 do documento corrigido. A consulta interna lê
apenas o arquivo da submissão sintética indicada pelo estado do seed.

A troca usa a API local de arquivos/DAO como simulação de nova versão no servidor;
não homologa o upload pela interface nem concorrência de uploads. A conclusão
sintética não equivale a aceite editorial ou publicação de artigo.

## Resultado

1289 testes em cada PHP (8.4.27 e 8.1.27), zero falhas. Lint de 97 arquivos PHP,
compilação Python, schemas CLI e diff aprovados. As 18 suítes reuniram 266
verificações; o ciclo novo acrescentou 21, totalizando 287 verificações de
navegador aprovadas. Matriz em HOMOLOGATION.md e no resumo JSON desta etapa.

Os registros originais de submissão são conferidos após as suítes reversíveis e
o ciclo novo. O apontador /tmp/lab-state.json é restaurado e a cópia temporária
de credenciais é removida em finally. Os novos rascunhos, conclusões e registros
de revisão existem apenas na base sintética local e não são publicados.
Evidências selecionadas excluem credenciais, configurações e state.json.

## Reprodução

```sh
ESTRABAO_INTEGRATED_EVIDENCE_DIR=/workspace/scratch/estrabao-lab-38 \
  PYTHONPATH=tests/lab python tests/lab/integrated.py \
  --with-apa-regression --with-submission-cycle
```

Exige o laboratório previamente configurado: OJS 3.3.0.20 em rede Docker interna,
sem portas públicas, mounts do produto read-only, SMTP desabilitado e sendmail
neutralizado. O runner não provisiona/instala/atualiza serviços. Containers são
desligados ao término desta execução. Sem produção, VM Oracle, envio externo,
publicação do ambiente ou deploy.

Aprovação limitada ao laboratório/Tema Padrão. Instalação/infraestrutura real,
API/importações, concorrência e demais lacunas da matriz APA7.md permanecem
pendentes. Narrativas posteriores a outras frases no mesmo parágrafo podem permanecer
indeterminadas na gramática atual; o ensaio combinado usa parágrafos separados.
Esse limite não é corrigido nem certificado nesta etapa. A regressão não
acrescenta cobertura normativa nem certificação integral de APA 7. Próxima ampliação: tipos bibliográficos, um PR por vez.
