# Etapa 61 — pacote e instalação limpa isolada

Base **43eaf8f** (merge PR #61), em **20/11/2026**. Motor **0.44.0**, plugin
**0.39.0.0**, sem alteração de regras, contratos ou código do produto.

## Entrega

- Gerador/verificador offline [tools/release.py](../tools/release.py), com dois
  componentes manuais, origem Git, inventário e SHA-256; 73 arquivos de produto.
- [Guia de instalação e atualização](INSTALLATION.md), incluindo configuração
  privada, pré-condições, comandos e limites da distribuição/recuperação.
- [Laboratório reproduzível](../tests/lab/install), sem montagem do checkout,
  portas públicas ou reutilização de banco/credenciais anteriores.
- Instalação nova 0.39/0.44 e atualização real **0.37/0.42 → 0.39/0.44**, desde
  o commit **7632136**, em outra cópia limpa OJS **3.3.0.20**.
- Testes de integridade no CI. Os testes Docker/navegador permanecem locais,
  identificados nas evidências; não se afirma execução desses ensaios pelo CI.

O plugin não é um pacote autossuficiente de galeria. O motor e os registros
precisam de configuração fora da raiz web; CLI/testes/contratos não foram copiados
para a web para suprir dependências. Manifestos:
[atual](evidence/stage61/current-manifest.json) e
[anterior](evidence/stage61/previous-manifest.json).

## Validação executada

| Suíte | Verificações aprovadas |
| --- | ---: |
| Pacote determinístico, limites, integridade e exclusões | 15 |
| Instalação nova pelo pacote | 21 |
| Falhas de armazenamento/POST/opt-in nessa cópia | 10 |
| Checkbox e confirmação nativos do administrador | 3 |
| Atualização desde pacote anterior real | 26 |
| Falhas de armazenamento/POST/opt-in após atualização | 10 |
| Outra revista, apud, correção/revisão/conclusão e UI | 14 |
| Total no navegador OJS desta etapa | **84** |

Zero falhas nas execuções finais dessas seis suítes de navegador. Testes
exploratórios/correções do harness não são somados ao total. Não somar os 1.138
checks históricos da Etapa 59 como nova execução nesta etapa.

Confirmados: versões pelo instalador/DAO nativos; DOM/ZIP/PDO; gate inicialmente
Off; ABNT e apud no subconjunto reconhecido sem oferta de aprovação; inconclusivo
retido; CSRF e revisão antiga recusados; autor e gerente de outra revista sem
acesso/mutação privada; download recusado a esse gerente; motor ausente/diretório
inseguro/registro corrompido sem avanço ou exposição de caminhos. Configuração
intencionalmente inativa conserva a conclusão nativa em outros rascunhos.

Os históricos APA e não APA foram criados pelo navegador **na versão anterior**.
A atualização preservou seus bytes/hashes; a nova análise invalidou a liberação
e tornou pendente a ficha não APA. Nova revisão humana permitiu concluir. O
arquivo corrigido é uma nova versão do servidor e seus bytes não mudaram com a
revisão/conclusão. Isso não certifica o conteúdo nem a integralidade de APA 7.

Resultados detalhados, preflight, versões e regressão local:
[validation.json](evidence/stage61/validation.json). Captura conferida visualmente:
[plugin habilitado no painel nativo](evidence/stage61/plugin-enabled.png).

## Achados resolvidos e diagnóstico

O primeiro TAR determinístico usava mtime zero: o Smarty desse OJS interpretou
os templates como indisponíveis. O pacote passou a usar o timestamp do commit,
com teste próprio. Não houve correção do produto nem mudança de versão editorial.

O instalador CLI dessa imagem também exigiu charset explícito e allowed_hosts
local adequado. Os erros iniciais e três ocorrências HTTP de falha de template
ficaram restritos à preparação; não foram omitidos como se toda tentativa tivesse
passado. As execuções finais usaram os pacotes corrigidos. O disco lotou ao
iniciar navegadores simultâneos; removeu-se somente uma instância vazia criada
nesta etapa e os navegadores passaram a executar em sequência.

A abertura do painel nativo tenta carregar a galeria remota. A rede interna
bloqueou essas consultas (quatro registros de conexão recusada na cópia nova e
dois na de atualização); não são acesso à produção nem requisito do plugin. Os
logs completos permanecem privados, sem credenciais/IPs internos no PR. Não houve
PHP Warning nos logs HTTP das duas cópias; os erros de preparação acima continuam
registrados separadamente da aprovação final.

## Limites e sequência

As instâncias/contas/arquivos/bancos são novos e sintéticos; os serviços foram
encerrados após o ensaio. SMTP/beacon/tarefas desligados, sendmail neutralizado,
sem portas públicas. Produção, VM Oracle, envio externo e publicação do ambiente
não foram acessados/executados.

O servidor PHP de desenvolvimento rodou como root no contêiner. Isso não homologa
usuário de serviço, TLS/proxy, ARM64, concorrência com uploads, OPcache persistente,
retenção ou backup/restauração. Essas verificações pertencem à **Etapa 62**.
Depois, a **Etapa 63** ensaia o fluxo editorial amplo nessa cópia. Canais fora do
wizard e APA fora da gramática mantêm os limites já documentados.
