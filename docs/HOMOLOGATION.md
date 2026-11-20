# Estado atual da homologação — Etapa 63

Base **a6bde90**, **20/11/2026**. A [Etapa 63](STAGE63.md) aprovou **40 verificações**
do fluxo HTTP nativo: criação do rascunho pelo autor, multipart, metadados, bloqueio
ABNT, minuta, revisão do Word, nova análise, revisão humana, CSRF e conclusão.
O resultado é recebimento da submissão, sem aceite ou publicação automática.

Produto inalterado: motor **0.44.0**, plugin **0.39.0.0**, 73 hashes conferidos.
Reexecutados **15 testes de pacote e 11 de backup**, lint de dois helpers PHP e
compilação Python. Zero falhas finais. Não são novos testes de navegador visual
nem nova regressão de 2.120 PHP/16 JS. [Resultados](evidence/stage63/validation.json),
[reprodução](EDITORIAL_REHEARSAL.md) e achados preparatórios registrados.

Cópia sintética restaurada da Etapa 62, sem produção/Oracle ou envio da minuta.
Core temporariamente instrumentado no diagnóstico foi restaurado byte a byte.
Logs HTTP da janela final sem PHP Fatal/Warning. Serviços encerrados ao terminar.
A transação conjunta upload/confirmação e demais limites abaixo seguem pendentes.

## Ensaio operacional anterior — Etapa 62

Ensaio em **20/11/2026**, base **528c655**. Produto sem alteração: motor
**0.44.0**, plugin **0.39.0.0**. A [Etapa 62](STAGE62.md) executou **45 verificações
operacionais** no OJS real por HTTP autenticado/CLI, **11 testes offline de backup**
e **15 de empacotamento**, sem falhas finais. PHP 8.1.27, quatro workers sob
UID 100:101, bancos/volumes novos e sintéticos, rede interna sem portas públicas.

Confirmados: permissões, locks e revisão concorrente, troca de arquivo/metadados,
backup com aplicação parada, restauração coerente em outra cópia, recuperação
completa repetida e conclusão nativa com CSRF. Nenhum PHP Warning/Fatal nos logs
HTTP das duas cópias. Serviços encerrados após o ensaio. [Evidências](evidence/stage62/validation.json)
e [procedimento e limites](OPERATIONS.md).

Isso não certifica transação conjunta upload/conclusão, power-loss/rename,
rollback de qualquer falha de I/O, TLS/proxy, OPcache persistente ou infraestrutura
real. Não houve nova suíte visual nem nova execução manual dos 2.120 PHP/16 JS.
Os resultados abaixo pertencem às etapas identificadas.

## Instalação e regressão anteriores — Etapa 61


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
- UID 100, concorrência e recuperação foram exercitados na cópia da Etapa 62;
  isso não certifica o usuário de serviço real, transação upload/conclusão ou
  recuperação diante de qualquer falha de hardware/I/O.
- ARM64, TLS/proxy, API/importações/quickSubmit e outros canais seguem sem ensaio
  operacional representativo. Os dois runtimes do motor não certificam o OJS em PHP 8.4.
- APA integral, veracidade de declarações, fontes e edição/identidade continuam
  fora da certificação automática. .doc é via humana limitada; PDF não tem leitor.

## Sequência proposta

[Etapa 61](STAGE61.md): pacotes e instalação/atualização.
[Etapa 62](STAGE62.md): ensaio operacional limitado e recuperação isolada.
[Etapa 63](STAGE63.md): percurso editorial nativo concluído nessa cópia.
Próxima proposta: Etapa 64, garantia entre mudança do arquivo e confirmação final.
Não instalar, ativar ou publicar na revista por causa deste balanço. Produção e
VM Oracle permanecem fora do escopo. O ambiente não foi publicado novamente.

## Histórico

O [histórico até a Etapa 59](HOMOLOGATION_HISTORY.md) preserva execuções, capturas,
ajustes e comandos do laboratório. Versões/contagens antigas não descrevem o
produto atual e os comandos históricos não autorizam instalação em produção.
