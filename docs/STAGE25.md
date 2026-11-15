# Etapa 25 — conferência editorial de ética

Base 2c05671 (merge do PR #25), motor 0.19.0 e plugin 0.11.0.0.
Acrescenta apoio à revisão ética, sem inferir aplicabilidade a partir de método,
participantes, área científica ou ausência de declaração.

## Regra e escopo

manuscript.ethics.review 1.0.0 é sempre indeterminada. Orienta o editor a conferir
aplicabilidade, aprovação por comitê com número do parecer quando cabível ou
declaração formal de não aplicabilidade. Não aprova, rejeita ou certifica ética.
Não afirma que todo estudo exige comitê; tampouco dispensa estudos bibliográficos
ou documentais automaticamente. A base é a diretriz já registrada, sem nova norma
jurídica ou exigência de formulário/campo adicional.

EthicsReview localiza somente possíveis menções no texto Word disponível antes do
primeiro cabeçalho bibliográfico reconhecido. Há marcadores em português, inglês
e espanhol, como comitê/comité de ética, institutional review board, ethical
approval, aspectos éticos e CAAE. São indícios lexicais: podem ocorrer em discussão,
citação, negação ou declaração inadequada. Não equivalem a aprovação ou dispensa.
As abreviações CEP/IRB isoladas não são usadas como prova ou marcador.

A identificação independe dos títulos oficiais de seções do corpo: não aprova novas
traduções nem impõe seção chamada Ética. Conteúdo depois do primeiro cabeçalho
bibliográfico, notas, imagens, tabelas excluídas, outros anexos e campos do OJS não
são examinados por esse localizador. O revisor deve conferir os materiais fornecidos
no fluxo editorial. Ausência de marcador não prova ausência de documento.

Pode localizar indícios em verified_main_word_text parcial, mantendo explicitamente
full_document_text_available=false. marker_scan_complete refere-se apenas ao trecho
Word disponível, anterior à bibliografia, não a todo material da submissão.
Fonte ausente, normalização não suportada ou limite técnico continuam inconclusivos.
Limites: 4096 parágrafos, 8192 bytes por parágrafo e até 32 amostras, com total de
amostras omitidas; controles ZIP/XML anteriores preservados.

## Privacidade e apresentação

Evidências contêm SHA-256, perfil/cobertura, motivo fixo, contagem de parágrafos com
marcadores e posições. Não copiam nomes, texto de declaração, dados de participantes,
número do parecer ou identificador CAAE. Flags de aplicabilidade, aprovação,
autenticidade de identificador e não aplicabilidade permanecem falsas.
Não há consulta externa ou validação de autenticidade/documentação.

O painel autorizado mostra ficha interna de ética, com instruções e, quando
observadas, posições de parágrafos extraídos (não páginas). A lista é um auxílio
para localizar trechos, não um resultado da revisão. Existem mensagens diferentes
para possíveis menções, nenhuma menção observada e fonte indisponível.

O gerador 1.0.0 reconhece a regra como pendência; nenhum dos seus resultados gera
correção aos autores. A ficha fica fora de assunto/corpo copiados. Não grava
parecer, dispensa, aprovação ou novo evento no ledger, não comunica autores e
não adiciona bloqueio por ética. A liberação existente continua relacionada a APA;
não significa que a ética foi conferida. Motor novo invalida liberações do motor
anterior pelo fingerprint existente.

Snapshots 1.0/1.1/1.2, relatório/minuta 1.0 preservados. Uma regra nova leva os
resultados a 49 sem DOCX e 50 para artigo/comunicação. Atualizar motor e plugin
juntos em homologação, limpar caches de idioma conforme DEVELOPMENT.md.
Não houve implantação ou publicação/reconfiguração do ambiente.

## Validação e limites operacionais

903 testes sintéticos aprovados (19 novos), zero falhas, em PHP 8.4.27 e 8.1.27;
lint de 82 arquivos PHP. Incluem afirmação/negação/citação como meros indícios,
idiomas, CEP postal, fonte parcial/ausente, bibliografia, limites, Unicode,
privacidade, minuta e serviço do painel com leitor real sob dublês OJS.

Quatro verificações no OJS 3.3.0.20/Chromium local, apenas leitura: ficha traduzida,
nenhuma menção sem presumir ausência/dispensa, separação da carta e estado editorial
inalterado. Captura inspecionada e JSON em evidence/stage25. Reproduzir no laboratório
da Etapa 15 com PYTHONPATH=tests/lab python tests/lab/ethics-panel.py.
O cenário com indícios foi exercitado sinteticamente pelo leitor e serviço; o
ensaio no navegador usou os registros sintéticos já existentes, sem edição.

Sem manuscritos reais, produção, VM Oracle, mensagens aos autores ou análise de
dados sensíveis em serviços externos. Conferência ética e declaração de IA
continuam humanas; a integração específica da declaração de IA é a próxima etapa.
