# Estado atual da homologação — Etapa 61

Ensaio em 20/11/2026, base 43eaf8f. Motor **0.44.0**, plugin **0.39.0.0**,
produto preservado. A [Etapa 61](STAGE61.md) executou instalação nova e atualização
0.37/0.42 → 0.39/0.44 em duas cópias limpas OJS 3.3.0.20, sem checkout montado.

- **15 testes de pacote** e **84 verificações OJS/navegador**, zero falhas finais.
- **2.120 testes PHP 8.4.27**, zero falhas; 462 avisos preexistentes de fixtures.
- **16 testes JavaScript**, zero falhas. Lint dos PHP e compilação Python conferidos.
- Código/motor/configuração privada separados; versões nativas e 73 hashes
  instalados conferidos; históricos preservados, liberação antiga invalidada.
- Serviços de ensaio encerrados, rede interna sem portas; dados/credenciais novos.
- [Evidências desta execução](evidence/stage61/validation.json),
  [guia](INSTALLATION.md) e [achados resolvidos/limites](STAGE61.md).

Os erros iniciais de configuração/mtime e as consultas da galeria bloqueadas
pela rede são documentados na Etapa 61, separados dos resultados finais.

## Regressão integrada anterior: Etapa 59

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

- A instalação/atualização e o ciclo de habilitação da Etapa 61 valem para as
  cópias sintéticas. Distribuição autossuficiente por galeria e instalação na
  infraestrutura representativa da revista continuam sem homologação.
- Tema Padrão e perfil visual informado foram exercitados localmente; isso não
  confirma arquivos/configuração da revista, seus campos, locales ou infraestrutura.
- Permissões/usuário de serviço reais, concorrência com uploads e recuperação de
  backup não são certificados pelo lock privado dos registros.
- ARM64, TLS/proxy, API/importações/quickSubmit e outros canais seguem sem ensaio
  operacional representativo. Os dois runtimes do motor não certificam o OJS em PHP 8.4.
- APA integral, veracidade de declarações, fontes e edição/identidade continuam
  fora da certificação automática. .doc é via humana limitada; PDF não tem leitor.

## Sequência proposta

[Etapa 61 concluída](STAGE61.md): pacotes e instalação/atualização em cópias
limpas isoladas. Etapa 62: operação,
concorrência e backup/recuperação. Etapa 63: ensaio editorial nessa cópia.
Não instalar, ativar ou publicar na revista por causa deste balanço. Produção e
VM Oracle permanecem fora do escopo. O ambiente não foi publicado novamente.

## Histórico

O [histórico até a Etapa 59](HOMOLOGATION_HISTORY.md) preserva execuções, capturas,
ajustes e comandos do laboratório. Versões/contagens antigas não descrevem o
produto atual e os comandos históricos não autorizam instalação em produção.
