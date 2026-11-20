# Etapa 62 — permissões, concorrência e recuperação isoladas

Base **528c655** (merge PR #62), **20/11/2026**. Produto preservado: motor
**0.44.0**, plugin **0.39.0.0**, 73 arquivos idênticos. OJS **3.3.0.20**,
PHP **8.1.27**, MariaDB **10.6**, rede interna sem portas publicadas.

## Entrega e resultados

[Guia operacional e reprodução](OPERATIONS.md);
[scripts do laboratório](../tests/lab/operations);
[validação](evidence/stage62/validation.json).

| Suíte executada nesta etapa | Aprovados |
| --- | ---: |
| Serviço não root, gravações simultâneas, locks e alterações de origem | 17 |
| Backup íntegro e restauração para outro banco/volumes | 8 |
| Fluxo nativo após corrupção/perda e recuperação completa repetida | 14 |
| Outro UID e interrupção do processo antes do commit | 5 |
| Recusa de restauração com aplicação ativa | 1 |
| Total operacional no OJS real/ambiente isolado | **45** |
| Testes unitários offline de integridade do backup | **11** |
| Regressão do empacotamento da Etapa 61 | **15** |

Zero falhas finais. Não são 45 testes de navegador: são HTTP autenticado, CLI
nativo, arquivos/processos reais e verificações Docker. Não se repetiram as 84
verificações visuais/HTTP da Etapa 61, nem se atribuem os 2.120 testes PHP e 16 JS
da etapa anterior a esta execução. O CI passa a executar os 11 testes offline;
os ensaios Docker continuam identificados como locais.

## O que foi confirmado

O serviço 100:101 não grava em código/motor/configuração, mas consegue escrever
em cache/public e na área privada autorizada. Outro UID não lê configuração ou
manuscritos nem grava em registros. Diretórios privados 0700; registros/locks
0600; configuração root:101/0440. Helpers fora da raiz web.

Duas confirmações simultâneas da mesma revisão produziram um evento. Locks
externos impediram gravações sem mutação. A troca de arquivo concorrente com a
revisão deixou a aprovação anterior inválida; a conclusão posterior foi retida.
A mudança de metadados tornou a ficha não APA pendente, sem substituir a revisão
APA independente. O histórico anterior foi mantido como prefixo. Um processo
morto antes do commit preservou os bytes do registro e liberou o lock no sistema.

O backup, com aplicação parada, foi restaurado em outra cópia com banco/volumes
novos. Antes de login, SQL idêntico e conteúdo/propriedade/modos dos arquivos
conferidos. Um backup adulterado foi recusado antes de mudar o banco. Na cópia
restaurada, corrupção e ausência de registro retiveram a submissão, sem divulgar
caminhos/conteúdo. A recuperação completa foi repetida com êxito e retirou resíduo
sintético do destino. Depois: autorização privada, CSRF, revisão válida e conclusão
nativa preservados; ABNT continuou bloqueada.

## Preparação e limites observados

O primeiro auxiliar copiado estava com dono/permissão do usuário do workspace,
inacessível ao UID do serviço. A preparação passou a atribuir root:101/0640 aos
helpers. Não se abriu a área privada nem se mudou o produto para resolver isso.

O navegador esgotou o disco ao criar sua camada Docker VFS. O ensaio seguiu por
HTTP nativo, suficiente para estes cenários operacionais. Somente contêineres
parados e reconstruíveis da Etapa 61 foram removidos para liberar espaço; seus
volumes, configurações privadas e dados sintéticos foram preservados. Não houve
limpeza geral do Docker nem remoção de recursos de terceiros.

Os serviços da Etapa 62 foram encerrados após os testes. Backup/SQL/configurações,
credenciais e manuscritos ficaram privados; o PR contém resultados e hashes,
sem os dados. [Preflight](evidence/stage62/preflight.json),
[resumo do backup](evidence/stage62/backup-summary.json) e
[encerramento](evidence/stage62/postflight.json).

**Permanece sem certificação:** transação conjunta entre upload e confirmação
final, queda de energia durante gravação/rename, falha de I/O a qualquer ponto da
restauração, TLS/proxy, OPcache persistente, ARM64 e canais fora do wizard. O
servidor é PHP de desenvolvimento com múltiplos workers, não a infraestrutura da
revista. Backup pressupõe ausência de todos os escritores independentes; somente
parar HTTP em um ambiente real não comprovaria essa condição.

Próxima entrega: **Etapa 63**, ensaio editorial amplo nessa cópia, conforme as
pendências documentadas. Não há autorização de instalação/publicação real por
esta homologação limitada. Produção e VM Oracle não foram acessadas; nenhuma
mensagem externa nem publicação do ambiente foi realizada.
