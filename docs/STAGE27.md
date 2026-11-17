# Etapa 27 — homologação integrada em laboratório

Base 112485b (merge do PR #27), confirmada em 17/11/2026. Motor 0.20.0 e plugin
0.12.0.0 preservados: esta etapa acrescenta ensaios e documentação, sem mudar o
comportamento editorial ou os contratos.

O [relatório de homologação](HOMOLOGATION.md) registra 922 testes nas duas versões
de PHP e 72 verificações de navegador em OJS 3.3.0.20 local. Inclui as nove suítes
do painel/artigo e um ciclo novo de submissões sintéticas, com revisão humana,
revogação, CSRF, troca de arquivo, download protegido e feedback ao autor.

Novos utilitários opcionais:

- tests/lab/preflight.php: pré-checagem somente de leitura, com guarda de
  identificação sintética, versões, transporte neutralizado e diretório privado.
- tests/lab/integrated.py: runner sequencial com isolamento de Docker, coerência
  dos mounts/checkouts e resumo que não anuncia sucesso em execução parcial.
- tests/lab/admission-cycle.py: ciclo com IDs de um seed novo, sem reutilizar
  submissões concluídas; só é chamado pela modalidade explícita do runner.
- tests/lab/title-guidance.py: leitura/edição descartada dos campos de título
  no wizard e conferência da publicação, sem salvar metadados.

Os ensaios individuais anteriores continuam disponíveis. A execução integrada
consolida evidências selecionadas em evidence/stage27 e mantém dados privados fora
do repositório. O manifesto SHA-256 cobre 59 arquivos de produto/contratos iguais
à base; não inclui anexos enviados pelo usuário, configurações ou credenciais.

O laboratório foi alinhado ao descritor instalado 0.12.0.0 pela ferramenta nativa
e neutralização de e-mail confirmada para web/CLI. Containers desligados ao final.
Sem produção, VM Oracle, mensagens externas ou publicação/reconfiguração do ambiente.

Homologação aprovada apenas no laboratório: tema e infraestrutura reais não foram
verificados. Limites, achado visual nativo e próximos passos constam do relatório.
