# Estado atual da homologação — Etapa 60

Balanço documental em 20/11/2026, base 6a117b1 (merge PR #60). Motor 0.44.0,
plugin 0.39.0.0; OJS 3.3.0.20 em laboratório interno sintético. Nesta etapa não
se iniciaram serviços, instalaram pacotes ou executaram novas suítes funcionais.

## Última execução funcional: Etapa 59

| Evidência anterior | Resultado |
| --- | --- |
| PHP 8.4.27 / 8.1.27 | 2.120 testes por runtime, zero falhas |
| Estático | Lint de 132 PHP, Python e dois schemas CLI aprovados |
| JavaScript | 16 testes aprovados |
| 11 painéis + 21 modos APA | 909 verificações nas 32 suítes |
| Ciclos DOCX / DOC / EN–ES / datas completas / marcadores espanhóis | 27 / 25 / 32 / 47 / 98 |
| Navegador integrado | 1.138 verificações aprovadas |
| Ensaio isolado espanhol, fora desse total | 92 verificações, sem somar novamente |

Fonte: [validação da Etapa 59](evidence/stage59/validation.json) e
[resumo integrado](evidence/stage59/integrated-summary.json). Os 462 avisos PHP
preexistentes de fixtures de estrutura são documentados; zero falhas não quer
dizer ausência desses avisos. Não são observação de defeito na revista real.
A Etapa 60 comprova identidade do produto com a base/manifesto testados e confere
documentação/contrato CLI; não apresenta esses números como nova regressão.

## O que esse laboratório não homologa

- Instalação em cópia limpa, empacotamento/atualização/distribuição e ciclo
  operacional de habilitação/desabilitação ainda precisam de ensaio específico.
- Tema Padrão e perfil visual informado foram exercitados localmente; isso não
  confirma arquivos/configuração da revista, seus campos, locales ou infraestrutura.
- Permissões/usuário de serviço reais, concorrência com uploads e recuperação de
  backup não são certificados pelo lock privado dos registros.
- ARM64, TLS/proxy, API/importações/quickSubmit e outros canais seguem sem ensaio
  operacional representativo. Os dois runtimes do motor não certificam o OJS em PHP 8.4.
- APA integral, veracidade de declarações, fontes e edição/identidade continuam
  fora da certificação automática. .doc é via humana limitada; PDF não tem leitor.

## Sequência proposta

[Plano da Etapa 61](INSTALLATION_PLAN.md): pacote e instalação/atualização em
cópia limpa isolada, com critérios e evidências definidos. Etapa 62: operação,
concorrência e backup/recuperação. Etapa 63: ensaio editorial nessa cópia.
Não instalar, ativar ou publicar na revista por causa deste balanço. Produção e
VM Oracle permanecem fora do escopo. O ambiente não foi publicado novamente.

## Histórico

O [histórico até a Etapa 59](HOMOLOGATION_HISTORY.md) preserva execuções, capturas,
ajustes e comandos do laboratório. Versões/contagens antigas não descrevem o
produto atual e os comandos históricos não autorizam instalação em produção.
