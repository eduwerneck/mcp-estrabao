# Plano da Etapa 61 — pacote e instalação limpa isolada

Plano revisável da Etapa 60 (20/11/2026, base 6a117b1). Não é relatório de execução
nem procedimento aprovado para a revista real. Alvo inicial: **OJS 3.3.0.20** em
cópia local limpa, somente dados sintéticos, sem produção ou VM Oracle.

## Escopo concreto a entregar

1. Artefato de plugin 0.39.0.0 e motor 0.44.0 com inventário, hashes, origem e
   dependências mínimas documentadas; formato final de distribuição a validar.
2. Procedimento reproduzível de instalação/configuração nessa cópia, incluindo
   o motor e os registros fora da raiz web. Não distribuir credenciais, dados
   editoriais, fixtures/manuscritos, relatórios ou backups privados no pacote.
3. Ensaio de instalação nova e de atualização controlada de uma versão anterior
   sintética identificada, com compatibilidade dos registros e necessidade de
   nova revisão por mudança do motor. Não editar eventos antigos para liberá-los.
4. Evidências do registro instalado, habilitação/desabilitação e opt-in do gate,
   com diagnóstico claro de configuração ausente/insegura e sem avanço indevido.

O plugin experimental depende de engine_bootstrap e review_dir privados e do
admission_gate explícito, conforme [DEVELOPMENT.md](DEVELOPMENT.md). O plano não
presume que copiar só a pasta web seja uma instalação completa. Não copiar a
CLI, testes ou documentos para a raiz web para contornar essa dependência.

## Pré-condições do laboratório

- Instância, banco, arquivos e credenciais próprios; não clonar banco/manuscritos
  reais. Conferir origem/integridade e compatibilidade da distribuição exata OJS.
- Escolher runtime compatível com essa distribuição e verificar DOM/libxml, ZIP,
  JSON/hash/PCRE e permissões. Os testes do motor em PHP 8.4 não homologam o OJS
  legado nesse runtime. Não expor runtime sem suporte à internet.
- Rede interna e ausência de portas públicas; SMTP desabilitado e caminho de envio
  neutralizado, com preflight verificável antes de qualquer fluxo de conclusão.
- Não publicar o ambiente. Acesso à revista/VM Oracle e uso de configurações ou
  segredos reais não fazem parte da entrega.

## Matriz de aceitação proposta

| Cenário | Evidência esperada |
| --- | --- |
| Pacote/origem | Manifesto confere conteúdo e versões; sem arquivos privados ou dependência implícita do checkout de desenvolvimento |
| Instalação nova | OJS reconhece o plugin/versão corretos; motor/configuração privada resolvidos por caminhos autorizados |
| Inativo / gate desativado | Opt-in e controles nativos preservados; nenhuma política é ativada por suposição |
| Ativo / gate configurado | Erro APA/ABNT ou apud reconhecido impede confirmação; inconclusivo não conclui sem revisão expressa |
| Motor/configuração/registro indisponíveis | Diagnóstico sem revelar caminhos/conteúdo; recusa técnica sem acusação editorial ou liberação por fallback |
| Controle de acesso | Autor/gerente de outra revista não consulta ou altera revisão privada; POST/CSRF/revisão antiga continuam exigidos |
| Atualização sintética | Registros anteriores permanecem legíveis/preservados; assinaturas antigas não aprovam análise de motor novo |
| Desabilitar/reabilitar | Registro/configuração e histórico não são apagados silenciosamente; comportamento nativo/opt-in conferidos |
| Fluxo mínimo | Corrigir arquivo, reanalisar, revisar e concluir um novo rascunho sintético, sem mensagem externa |
| Artefatos/logs | Resultados redigidos sem segredos/dados reais; nenhum novo aviso/erro do instalador ocultado por contagem de testes |

A ficha editorial não APA, a via manual .doc e as orientações privadas devem
continuar separadas da liberação APA. Desinstalação/remoção destrutiva de dados
não é presumida como parte do ciclo de habilitar/desabilitar.

## Limites e continuidade

A Etapa 61 deve produzir uma conclusão revisável: aprovado no alvo isolado,
reprovado com achados concretos ou escopo remanescente claramente indicado.
Não é sinal verde para produção. Concorrência com uploads, backup/restauração,
usuários de serviço e infraestrutura representativa pertencem à Etapa 62;
o ensaio editorial amplo nessa cópia, à Etapa 63. Não prometer rollback/recuperação
operacional a partir de um simples registro de versão do instalador.
