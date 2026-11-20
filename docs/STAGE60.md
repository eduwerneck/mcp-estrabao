# Etapa 60 — balanço APA e da triagem

Base **6a117b1** (merge PR #60, Etapa 59), em **20/11/2026**.
Motor **0.44.0**, plugin **0.39.0.0**, regras/contratos/configuração preservados.
Entrega documental; não instala, habilita, publica ou modifica a revista.

## Entrega revisável

- [TRIAGE_COVERAGE.md](TRIAGE_COVERAGE.md): leitura atual do bloqueio APA,
  diagnósticos, via humana .doc, idiomas/datas e registro editorial independente.
- [APA7.md](APA7.md): mapa de cobertura e exceções, sem promessa de APA integral.
- [PENDING_REQUIREMENTS.md](PENDING_REQUIREMENTS.md): incorpora as entregas 55–59
  e distingue pendências humanas/técnicas da sequência futura 61–63.
- [HOMOLOGATION.md](HOMOLOGATION.md): última execução identificada corretamente
  como Etapa 59 e limites da operação/instalação real.
- [INSTALLATION_PLAN.md](INSTALLATION_PLAN.md): escopo e critérios concretos
  propostos para pacote/instalação/atualização em cópia limpa isolada.
- [Histórico APA](APA7_HISTORY.md) e [histórico de homologação](HOMOLOGATION_HISTORY.md):
  cópias integrais dos arquivos na base, com aviso de leitura histórica; não
  apagam resultados, comandos ou limites das entregas anteriores.

O mapa antigo misturava a auditoria da Etapa 54 com atualizações até a 59. A
nova leitura não anuncia .doc, EN/ES, datas completas ou registro não APA como
totalmente ausentes, mas mantém seus limites específicos e a revisão humana.

## Conclusões do balanço

1. ABNT/APA incompatíveis e apud reconhecidos impedem concluir no wizard opt-in;
   uma aprovação humana não supera falha comprovada. Fonte inconclusiva continua
   pendente, sem aceitação automática nem certificação de APA integral.
2. O registro de 12 grupos não APA é separado da liberação APA e do aceite/
   publicação. Não acrescenta novos impedimentos automáticos na confirmação.
3. .doc tem via humana para subconjunto identificável, sem texto/APA automático;
   variantes não identificadas continuam pendentes, sem obrigação de converter.
4. Citações pt/en/es, datas completas pessoais EN/PT e s.f./en prensa têm
   coberturas delimitadas. Não conferem identidade/edição/estado real da obra.
5. Testes do laboratório não certificam pacote/instalação limpa, operação real,
   uploads concorrentes, backup/recuperação ou todos os canais OJS.

O balanço não redefine diretrizes, aprova traduções obrigatórias, impõe template,
exige DOI ausente, restringe autores numericamente ou amplia a política de bloqueio.

## Verificação desta etapa

- Leitura de política recebida, regras/matcher/auditoria, hooks, serviços de
  revisão/minuta/checklist e evidências das etapas 55–59.
- Manifesto de **73 arquivos de produto/contratos**, todos idênticos à base e ao
  manifesto de produto efetivamente testado na Etapa 59.
- CLI de snapshot completo sintético sem DOCX/tipo/idioma: relatório válido no
  schema; inventário de **51 instâncias / 32 IDs distintos**. Não é porcentagem,
  cobertura integral ou lista exaustiva dos resultados condicionais — por exemplo,
  manuscript.headings.format não aparece nessa entrada sem documento.
- Constatações estáticas ligadas aos arquivos-fonte, integridade das cópias
  históricas, JSON, links relativos, tabelas e diff documentais conferidos.

Evidências em [evidence/stage60](evidence/stage60/validation.json): manifesto,
inventário limitado e auditoria vinculada às fontes. Não contêm credenciais,
configurações privadas ou manuscritos reais.

Os **2.120 testes por PHP**, **16 JavaScript** e **1.138 verificações OJS** são
resultados anteriores da [Etapa 59](evidence/stage59/validation.json), não novos
ensaios funcionais desta etapa. Não foram reexecutados manualmente para o balanço;
não se iniciaram containers ou serviços nem se certificou runtime novo.

## Próximo marco proposto

A Etapa 61 deve tornar pacote/instalação/atualização concretos e verificáveis em
cópia limpa isolada do OJS 3.3.0.20. Etapas 62 e 63 tratam operação e ensaio
editorial nessa cópia. São propostas com critérios, não implementações executadas.
Produção, VM Oracle, mensagens externas e publicação do ambiente permanecem fora
do escopo. A vigência pública da política não foi confirmada por nova consulta.
