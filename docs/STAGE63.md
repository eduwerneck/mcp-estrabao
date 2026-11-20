# Etapa 63 — fluxo editorial completo sintético

Base **a6bde90** (merge PR #63), **20/11/2026**. Motor **0.44.0**, plugin
**0.39.0.0**, 73 arquivos de produto preservados. Sem novos requisitos ou bloqueios.

## Entrega

- [Percurso, configuração e reprodução](EDITORIAL_REHEARSAL.md).
- [Cliente HTTP e DOCX sintéticos](../tests/lab/editorial), com autenticação,
  formulários, multipart, revisão de arquivo e wizard nativos do OJS 3.3.0.20.
- [40 verificações aprovadas](evidence/stage63/flow-results.json), cobrindo início
  da submissão, autoria, upload, metadados, ABNT, minuta, correção, reanálise,
  ficha não APA, revisão explícita, revogação, CSRF e conclusão.
- Regressão offline: **15 testes de pacote e 11 de backup**, zero falhas.
  Dois helpers PHP com lint e scripts Python com compilação conferidos.

O ensaio final criou outro rascunho pelo autor; não o pré-semeou na etapa 4.
Upload e correção usam o controlador nativo, sem inserir arquivo/submissão por
SQL ou DAO. O helper de leitura só verifica estado, IDs, hashes e metadados do
novo rascunho. A preparação configura idiomas/campos da revista sintética via
ContextDAO, separadamente das ações do autor.

A minuta inicial aponta ABNT, metadados EN/ES ausentes e orientações de título/
manuscrito. Depois da correção, não repete os campos vazios já preenchidos nem o
ABNT removido. Outros diagnósticos e pendências continuam pertinentes. A revisão
APA é expressa e vinculada à nova versão; a ficha não APA permanece independente.

O desfecho testado é **submissão recebida, não artigo aceito/publicado**. Não se
certifica APA integral, veracidade de declarações ou atendimento humano dos itens
que permanecem pendentes. A minuta fica privada e não é enviada externamente.

## Preparação, diagnóstico e limites

O primeiro cliente novo fazia login pelo helper da Etapa 62 e enviava multipart
pelo helper da Etapa 63, usando cookies separados. O OJS recusou corretamente o
upload sem sessão. Corrigiu-se o cliente para usar o mesmo helper/namespace em
todas as requisições; não se contornou autorização ou se alterou o produto.

Durante o diagnóstico houve logs temporários em três arquivos do core isolado.
Eles foram removidos e os bytes foram comparados aos mesmos arquivos da cópia de
origem não modificada, da mesma imagem: [comprovação](evidence/stage63/core-restoration.json).
Não entram no repositório patches de core, diagnósticos ou bypass de permissão.
Uma tentativa exploratória de upload sem arquivo gerou erro nativo no core; ela
não integra o ensaio final nem foi apresentada como sucesso.

A correção inicial dos metadados também revelou que o wizard legado só persistia
palavras-chave no idioma de submissão habilitado. Configuraram-se PT/EN/ES nesse
contexto sintético e repetiu-se o percurso em novo rascunho. Tentativas preparatórias
ficaram privadas e não são somadas às 40 verificações finais. Não houve mudança
nas regras de metadados ou no estado dos rascunhos preexistentes ao ensaio final.

Os logs HTTP da janela final não têm PHP Fatal/Warning. Não se repetiram as suítes
visuais anteriores, os 2.120 testes PHP nem os 16 JavaScript como nova regressão.
[Validação](evidence/stage63/validation.json) e [preflight](evidence/stage63/preflight.json).
Os serviços foram encerrados ao terminar; [postflight](evidence/stage63/postflight.json).

## Continuidade

As Etapas 61–63 fecharam o ciclo planejado de pacote, recuperação operacional
limitada e ensaio editorial nessa cópia. Isso não homologa a infraestrutura real.

Próxima proposta, **Etapa 64**: projetar a garantia entre mudança de arquivo e
confirmação final do wizard, com teste determinístico da corrida e limites de
integração OJS explícitos. Hoje não há transação conjunta entre ambos. Outras
pendências — falhas de I/O/power-loss, TLS/proxy/OPcache, canais fora do wizard e
exceções APA — permanecem no [planejamento](PENDING_REQUIREMENTS.md).
Produção/VM Oracle, publicação de ambiente e envio externo seguem fora do escopo.
