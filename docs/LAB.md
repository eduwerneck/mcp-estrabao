# Procedimento futuro — estrabao-lab

Documento de planejamento. Nenhum comando remoto foi executado e a VM não foi
acessada nem configurada nesta etapa.

Infraestrutura informada: Oracle Cloud, Ubuntu 24.04 ARM64, 1 OCPU, 6 GB RAM.
O sistema de desenvolvimento desta tarefa é distinto; os testes locais não
validam ARM64, Ubuntu 24.04 ou uma instalação OJS.

## Antes de provisionar

1. Confirmar acesso administrativo autorizado, orçamento e política de backups.
2. Isolar homologação da produção: host, banco, volumes, credenciais e DNS próprios.
   Usar somente submissões e autores sintéticos. Bloquear envio de e-mail externo.
3. Selecionar uma distribuição exata e confiável do OJS 3.3.0.20 e conferir seus
   requisitos/documentação e integridade. Auditar disponibilidade de pacotes ARM64.
4. Conferir compatibilidade de PHP e dependências: o PHP padrão do Ubuntu 24.04
   não deve ser presumido compatível com todo o OJS legado. Não resolver conflito
   desativando verificações TLS, integridade ou usando runtime exposto sem suporte.
5. Escolher banco e servidor web suportados; manter arquivos privados fora da raiz
   web, com permissões mínimas, TLS e firewall restritivo.
6. Guardar segredos em configuração privada, nunca no repositório. Não reutilizar
   credenciais nem backups reais da revista em produção.

## Validação planejada

- Documentar versões exatas, arquitetura, extensões e origem dos pacotes.
- Instalar laboratório OJS com dados sintéticos e validar login/permissões.
- Instalar o esqueleto conforme DEVELOPMENT.md e verificar logs e ciclo do plugin.
- Confirmar que nenhuma habilitação dispara comunicação ou transição editorial.
- Registrar evidências do ensaio antes de desenvolver o adaptador de submissões.
- Trabalhar com uma tarefa de build/teste de cada vez em 1 OCPU; acompanhar memória,
  disco e tempo antes de ampliar serviços. MCP não é pré-requisito do plugin.

Os comandos de provisionamento dependerão da escolha do runtime e do banco;
este documento não apresenta um procedimento de instalação já validado na VM.
