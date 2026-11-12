# Etapa 18 — cobertura segura de DOCX comuns do Word

Motor 0.13.0, base e1aa1ec (merge do PR #18). Plugin 0.4.0.0 e gerador de carta
1.0.0 preservados. A etapa retoma a primeira pendência da ordem combinada: ampliar
a leitura de DOCX, mantendo explícitas as partes não analisadas.

## Normalização estritamente não textual

Após validar o ZIP e todos os XML, DocxCompatibility trata apenas:

- w:lastRenderedPageBreak vazio, sem atributos, filho direto de w:r: cache de
  paginação, sem significado de espaço/quebra de texto. Não serve para contar páginas.
- w14:ligatures em w:rPr, vazio, com único atributo w14:val e valor reconhecido:
  propriedade de apresentação dos glifos. Não altera caracteres, negrito,
  itálico, tamanho, seção ou conteúdo.

A normalização ocorre em DOM privado, clonando somente partes candidatas.
O arquivo e seu SHA-256 original não mudam. Nós com conteúdo, atributos extras,
valores desconhecidos ou posição diferente não são descartados. Não se remove
markup desconhecido genericamente, não se aplica OCR nem se renderiza o documento.
Estilos também recebem essa normalização específica antes da resolução de herança.

## Perfil parcial de texto Word

O resultado original continua available=false e paragraphs=[] quando há tabela,
figura ou outra cobertura incompleta. O leitor pode acrescentar main_text:

- available=true significa somente parágrafos Word diretos do corpo verificados.
- coverage_complete=false e contagens de tabelas, parágrafos de tabela, desenhos
  e conteúdo de notas excluídos; image_text_read=false, sempre.
- Parágrafos diretos, estilos declarados e propriedades das seções estão
  disponíveis nesse perfil. Texto de tabela não vira título/seção do corpo.

BodyTextProfile exige contêineres suportados, grafo de notas resolvido e ausência
de conteúdo oculto. Aceita tabelas textuais retangulares simples, validadas pelo
mesmo classificador do inventário, mas não analisa seus estilos condicionais ou
seu texto como corpo. Aceita imagens bitmap inline/anchor com estrutura gráfica
permitida e relacionamentos locais para entradas existentes do pacote.

O MIME declarado e a extensão da entrada devem concordar, com unicidade de
relacionamentos e tipos. PNG/JPEG/TIFF/GIF/BMP são tratados como dados opacos, sem
comprovar que satisfaçam a política editorial de formatos ou 300 dpi. Camada
raster de efeito JPEG XR/WDP é admitida somente no contexto a14:imgLayer e com
relacionamento interno image/hdphoto; não é figura extra nem texto lido.
Não decodifica raster, não segue URLs e não carrega recursos externos.

Mantêm o perfil indisponível, entre outros: imagens não resolvidas/externas,
SVG/vetores não suportados, caixas de texto, campos, controles, revisões, equações,
objetos legados, tabelas mescladas/aninhadas e conteúdo gráfico desconhecido.
Texto w:t em propriedades/posições que não representam texto visível não pode
criar corpo ou marcador falso de Referências. Limites adicionais: 4096 parágrafos
incluindo tabelas, 256 desenhos e o limite existente de 1024 tabelas; mantidos os
limites ZIP/XML e estilos anteriores. Limite técnico nunca vira infração editorial.

## Consumo por regra

DocumentSource fornece fonte completa ou verified_main_word_text com
full_document_text_available=false. Não promove perfil parcial à extração global.

- Estrutura 3.0.0: confirma títulos explícitos encontrados no texto Word. Título
  não encontrado em perfil parcial fica indeterminado, sem presumir ausência.
  Títulos de tabelas não são promovidos a seções.
- Formatação dos títulos 3.0.0: avalia os títulos reconhecidos, preservando
  indeterminação de herança/cobertura não resolvidas.
- Página/tipografia 2.0.0: pode concluir A4/margens quando a cobertura das seções
  está verificada. Divergência declarada em trecho Word é sinalizável; tipografia
  global conforme exige cobertura completa. Tabelas/imagens não examinadas não
  são certificadas por seus vizinhos. Scope e exclusões constam das evidências.
- Caixa dos títulos 2.0.0: caixa alta comprovada em título reconhecido pode falhar;
  perfil parcial não recebe aprovação global. Identidade de título ainda exige
  reconhecimento/estilo ou correspondência com metadados, sem adivinhação.
- Extensão: não consome main_text; permanece indeterminada em extração parcial.
  Não se inventa contagem completa ignorando tabelas ou texto em imagens.
- APA: pode retomar a cauda bibliográfica já prevista na Etapa 11 quando a
  normalização torna segura sua leitura. Não libera correspondência com citações
  do corpo incompleto e nunca certifica APA integral.
- Notas/inventário visual: continuam independentes. Marcadores Word reconhecidos
  não impedem a contagem; linhas de notas existentes ainda exigem renderização.

O gerador conhece as novas versões das regras, com o mesmo texto-base aprovado.
Os schemas de entrada/relatório/minuta e quantidades de checks não mudam. O
fingerprint de revisão usa a versão do motor; a mudança para 0.13.0 invalida
liberações ligadas ao leitor anterior, exigindo nova revisão humana.

## Validação e limites

Testes são sintéticos, com pacotes gerados em execução: adornos Word, imagens
locais e efeitos, tabelas, campos hostis, texto em propriedades, Unicode, limites,
privacidade, cauda APA e integração com o gerador/painel. O painel é exercitado
com seus dublês e o motor real; não houve novo ensaio de navegador nesta etapa.

[Relatório sintético](evidence/stage18/synthetic-report.json) e
[minuta sintética](evidence/stage18/synthetic-draft.json) documentam a cobertura
parcial, versões, fonte divergente e referência incompatível reconhecidas.

O arquivo enviado pelo usuário foi retestado separadamente, com autorização já
existente. Nem arquivo, texto, metadados, resultados específicos ou sua carta
integram o Git ou essas evidências. Resultados privados ficam fora do repositório.

Permanecem pendentes leitura integral de conteúdos complexos, .doc, renderização,
OCR, estilos de tabelas, contagem editorial completa e cobertura de exceções APA.
Não houve alteração da política editorial, reescrita de manuscrito, acesso à
produção, VM Oracle, mensagens a autores ou republicação do ambiente.
