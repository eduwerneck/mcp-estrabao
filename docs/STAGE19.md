# Etapa 19 — títulos e subtítulos no OJS e no Word

Base e149d16 (merge do PR #19). Motor 0.14.0, regra
manuscript.headings.case 3.0.0 e plugin 0.5.0.0. Gerador de minuta 1.0.0 e
schemas preservados. A política continua evitar caixa alta integral, sem impor
sentence case, converter texto automaticamente ou proibir siglas.

## Preenchimento no OJS 3.3.0.20

Com o plugin habilitado na revista, os campos título e subtítulo do formulário
Título e Resumo recebem orientação em português, inglês ou espanhol. Usa o hook
Form::config::after, restrito ao formulário titleAbstract; preserva descrições
anteriores, valores e demais campos. Independe da ativação do bloqueio APA.

O wizard legado recebe a mesma orientação, inserida junto aos campos por script
local carregado no backend. MutationObserver atende às etapas carregadas por AJAX;
marca campos atendidos para não repetir a ajuda. O texto usa textContent e
aria-describedby, preservando descrições acessíveis existentes. Não muda input,
CSS de capitalização, obrigatoriedade, validação ou salvamento. Subtítulo continua
opcional. Não cria bloqueio adicional de submissão por caixa alta.

Os callbacks conferem a habilitação na revista atual. Sem revista ou com plugin
desabilitado, não inserem orientação. Configuração enviada ao navegador contém
somente texto público traduzido, sem dados do manuscrito. A apresentação pública
pode depender do tema: não há mudança de tema/CSS ou implantação na revista.

## Reconhecimento no manuscrito

Além das seções e outlines já suportados, reconhece título e subtítulo juntos no
prefixo, correspondentes aos metadados do mesmo idioma, separados por dois-pontos,
meia-risca ou travessão. Normaliza espaços para comparar; não adivinha o primeiro
parágrafo nem combina idiomas diferentes. A caixa é verificada no texto efetivo
do Word, nunca substituída pela grafia dos metadados. Cada componente é analisado
separadamente: um título misto não esconde um subtítulo integralmente maiúsculo.

Usa o idioma do metadado correspondente para o classificador, mesmo quando o
manuscrito tem outro idioma principal. Siglas possíveis permanecem indeterminadas;
casos sem correspondência segura conservam os limites anteriores. Perfis parciais
da Etapa 18 não passam a leitura integral. Não há inferência semântica de siglas,
OCR, alteração de arquivos ou consulta externa.

O relatório registra quantidade de componentes correspondentes, sem texto dos
títulos. A minuta reconhece a versão nova da regra. O motor novo invalida, pelo
fingerprint existente, liberações associadas ao motor anterior; exige nova revisão.

## Validação

765 testes sintéticos aprovados em PHP 8.4.27 e 8.1.27, incluindo 15 novos;
69 arquivos PHP com lint aprovado. Cobrem componentes combinados, grafia efetiva,
idioma, siglas, posição no documento, não combinação entre idiomas, minuta,
orientação sem gate APA e isolamento dos hooks por revista/formulário.

Conferência manual no Chromium do laboratório OJS 3.3.0.20, somente registros
sintéticos: orientação traduzida apareceu nos dois campos do wizard carregado
por AJAX e nos dois campos Título/Resumo da publicação, no fluxo editorial.
Não foi acionado Salvar ou Finalizar Submissão. A conferência foi realizada antes
da suspensão do ambiente; os testes PHP foram repetidos após a retomada.
Não é homologação no tema/infraestrutura real da revista.

Sem produção, VM Oracle, envio a autores, publicação ou reconfiguração do ambiente.
Próxima pendência da ordem combinada: notas de rodapé e conferência das duas linhas.
