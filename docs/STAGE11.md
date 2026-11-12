# Etapa 11 — incompatibilidades com APA 7 e padrões semelhantes a ABNT

A prioridade editorial é reconhecer submissões que precisam adequar referências
e citações à APA 7. Esta etapa amplia a auditoria local, sem prometer certificação
completa, reescrever referências ou rejeitar submissões automaticamente.

## Resultado e evidências

Motor 0.11.0. `references.apa7` 3.0.0 retorna não conforme quando há uma
incompatibilidade concreta de formato, citação ou correspondência; nos demais
casos permanece indeterminado (`full_apa_validation=false`).
`references.citation_matching` passa a 3.0.0. As novas regras 1.0.0
`references.apa7.recognized_formats` e `references.apa7.citation_format` podem
ser conformes apenas dentro dos subconjuntos fechados descritos abaixo.
Uma aprovação parcial não certifica APA completa.

Evidências incluem cobertura, perfil de fonte, contagens e até 32 amostras por
posição, com orientações fixas de correção. Ocorrências problemáticas tardias
têm prioridade. Não incluem nomes, títulos, texto de citações, DOI ou caminhos.
Entradas 1.0/1.1 e relatório 1.0 são preservados; são 45 resultados sem DOCX,
47 para artigo e 46 para comunicação.

## Detecção e limites

- Padrão semelhante a ABNT exige combinação de autoria pessoal, ano no final e
  sinais de local/editora, volume/número/páginas ou disponibilidade/acesso.
  Sobrenome em caixa alta sozinho não prova ABNT. Referências jurídicas com
  sinais específicos não recebem essa classificação automática.
- Cabeçalhos de referências em português, inglês e espanhol são reconhecidos.
  Bibliografia/Bibliography/Bibliografía permitem detectar problemas, mas não
  produzem aprovação do formato nem alteram a política editorial das seções.
- Artigo de periódico com volume, número opcional e paginação/identificador:
  título do artigo sem itálico, periódico e volume em itálico, número sem
  itálico; prefixos p./pp. na paginação desse formato são sinalizados.
- Capítulo com editores explicitados: título do capítulo sem itálico, título
  do livro em itálico, editora sem itálico; Ed./Eds. deve corresponder ao
  número de editores no subconjunto reconhecido.
- Obras autônomas e páginas web têm reconhecimento limitado por estrutura e
  formatação. Texto simples ambíguo entre livro e periódico sem volume fica
  indeterminado. Datas completas, sem data e no prelo têm tratamento limitado;
  datas inválidas não recebem aprovação. Autoria institucional exige revisão.
- DOI informado em formato antigo pode gerar orientação para https://doi.org/.
  Ausência de DOI não reprova; pontuação final ambígua fica indeterminada.
  Não verifica existência, registro, resolução ou correspondência do DOI.
- Ordem alfabética só é conclusiva com primeiros sobrenomes pessoais distintos
  no subconjunto ASCII reconhecido. Acentos, empates e instituições exigem revisão.
- Citações com dois autores: & em parênteses e conjunção textual em narrativa;
  espaços obrigatórios são verificados nos formatos reconhecidos e vinculados
  sem ambiguidade. Caixa alta isolada fica indeterminada. Correspondência mantém
  o subconjunto autor–ano de STAGE10.md, ainda limitado a português.

Itálico efetivo usa estilos de parágrafo/caractere, herança e propriedades
diretas do Word. O mapeamento de bytes só é aceito quando coincide exatamente
com o texto normalizado; estilos ambíguos permanecem indeterminados.
Não há renderizador. Não impõe espaçamento duplo genérico de APA sobre o
espaçamento 1,5 documentado pela revista nem inventa requisitos de recuo.

## Leitura segura e perfis parciais

Permanecem os limites ZIP/XML e a recusa de DTD, campos, revisões e conteúdo
não suportado. A análise limita parágrafos (4096), referências (512) e tamanho
de entrada (8192 bytes); os limites de citações da etapa anterior permanecem.

Além do texto principal completo, a regra de referências pode usar corpo com
chamadas de notas cujo grafo foi integralmente validado. O conteúdo das notas
não é analisado. As demais regras continuam vendo extração indisponível.

Se tabelas/figuras anteriores impedirem extração completa, a auditoria de estilo
pode usar uma cauda de referências integralmente verificada, com marcador único
e apenas parágrafos seguros até o fim. Campos, controles, tabelas e outros
elementos nessa cauda impedem seu uso. Esse perfil nunca aprova correspondência
com citações do corpo que não foi extraído. Extração parcial não vira completa.

## Fontes e pendências

O requisito editorial de APA 7 consta das diretrizes do projeto. Como apoio
técnico, foi consultada a implementação comunitária Citation Style Language
[apa.csl, revisão af0aea3](https://github.com/citation-style-language/styles/blob/af0aea3fffd44fe798cce9285598d0d5cf62ef23/apa.csl).
Ela não substitui o manual oficial, não é autoridade normativa nem dependência
de execução; seu código não foi incorporado. A página oficial de exemplos de
livros em apastyle.apa.org foi bloqueada pelo proxy (403) e não foi consultada.

Continuam pendentes certificação completa, tipos não reconhecidos, exceções
de autoria, títulos sem autor, referências jurídicas, fontes secundárias,
identidade dos autores, confirmação de metadados e verificação bibliográfica.
O motor não usa rede. Testes usam apenas dados sintéticos, sem manuscritos
reais, OJS em produção ou VM Oracle.
