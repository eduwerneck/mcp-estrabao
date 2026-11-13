# Etapa 20 — conferência editorial das notas

Base f706ea9 (merge do PR #20), motor 0.15.0, plugin 0.6.0.0.
Retoma o terceiro item da ordem combinada, sem mudar a diretriz: até três notas
de rodapé, cada uma com no máximo duas linhas. A contagem estrutural permanece;
a contagem visual continua exigindo conferência humana no documento paginado.

## Contagem e notas fora do escopo

A regra manuscript.footnotes.count 1.0.0 mantém o inventário da Etapa 9: notas Word
normais vinculadas, separadores excluídos, grafo íntegro para conclusão. Quatro
notas confirmadas continuam não conformes e entram na devolutiva. Vínculos
inconclusivos nunca são convertidos em zero ou em excesso comprovado.

O inventário acrescenta endnote_indicators: detecta estruturas Word, chamadas,
relacionamentos e declarações de content type de notas de fim, inclusive quando
a estrutura está incompleta. É um indicador, não uma contagem de notas de fim,
validação de vínculo ou infração editorial. Não segue relacionamentos externos.
manual_notes_inspected=false registra que a contagem não identifica notas escritas
manualmente ou em imagens.

A regra manuscript.footnotes.lines passa a 2.0.0. Com zero rodapés confirmados,
mas indicadores de notas de fim, agora exige conferência em vez de emitir conforme
sem ressalva. Com zero confirmado e sem esses indicadores, mantém conforme no
escopo restrito de rodapés Word. Isso não certifica ausência de anotações manuais.
Com qualquer rodapé ou fonte inconclusiva, permanece indeterminada.

## Plano de conferência e painel

A evidência de linhas contém SHA-256 do arquivo e review_plan: completude do
inventário, quantidade confirmada, indicador de notas de fim e até 32 posições
de chamadas resolvidas. Índices vêm do inventário validado; não incluem texto,
IDs internos, caminho ou nome de arquivo. Inventário inconclusivo não fornece
lista parcial como se fosse completa. Informa quantas chamadas foram omitidas.

Após gerar a minuta, o painel autorizado de gerente/editor mostra seção específica
nas pendências internas. Orienta baixar o arquivo atual pelo vínculo já protegido,
abrir em layout de impressão com fontes/configuração corretas e conferir no máximo
duas linhas visuais por nota, incluindo continuações em outra página. As posições
listadas seguem a leitura XML e não são números impressos das notas. Há aviso
separado para notas de fim, fonte inconclusiva e anotações não verificadas.

A ficha é orientação, não formulário de aprovação: não grava decisões, altera
aceite ou declara conclusão humana. Não implementa renderizador, OCR ou estimativa
de linhas por caracteres, parágrafos ou quebras XML. Certificação automática das
duas linhas e registro auditável de uma revisão visual permanecem pendentes.

O texto da ficha não entra no assunto/corpo copiados aos autores. A minuta mantém
somente descumprimentos comprovados, sem tratar linhas indeterminadas ou notas de
fim como infrações. Gerador 1.0.0 e schemas de entrada/relatório/minuta preservados;
catálogo reconhece a regra nova. O motor novo invalida liberações do motor anterior
pelo fingerprint existente, exigindo nova revisão da versão.

## Validação e reprodução

779 testes sintéticos (14 novos), zero falhas, em PHP 8.4.27 e PHP 8.1.27;
lint de 71 arquivos PHP. Nove verificações em OJS 3.3.0.20 local com Chromium:
lista de três chamadas, traduções, download protegido disponível, linhas fora da
carta, cópia sem ficha interna, quatro notas na correção, alerta de notas de fim,
zero sem ficha e estado editorial inalterado. JSON e captura em evidence/stage20.

No laboratório isolado da Etapa 15, copiar tests/lab/footnote-fixture.php para
/tmp/footnote-fixture.php no container web e executar tests/lab/footnote-panel.py
com PYTHONPATH=tests/lab. A fixture exige ESTRABAO_SYNTHETIC_LAB=1 e verifica URL e
banco sintéticos. Troca temporariamente os bytes do arquivo sintético de teste;
restaura o original em finally, sem mudar DAO, metadados ou estado editorial.
Não executar esses utilitários fora do laboratório. O manuscrito real da conversa
não foi usado. Atualizar os arquivos completos do motor/plugin e limpar caches de
idioma em homologação conforme DEVELOPMENT.md; nenhuma implantação foi realizada.

Sem produção, VM Oracle, mensagens aos autores ou publicação do ambiente.
Próxima pendência combinada: ampliar tipos e exceções APA 7 ainda não reconhecidos.
