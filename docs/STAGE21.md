# Etapa 21 — APA 7 em referências com formatos mistos

Base 52f5417 (merge do PR #21), motor 0.16.0, plugin 0.7.0.0.
references.apa7 5.0.0 e references.apa7.recognized_formats 2.0.0;
correspondência autor–ano 3.0.0 e formato de citações 1.0.0 preservados.
Amplia incompatibilidades verificáveis, sem certificar APA completa.

## Autoria pessoal

ApaPersonalAuthors inspeciona apenas listas compostas integralmente de sobrenome
simples e uma a quatro iniciais, com separadores reconhecidos. Confere vírgulas
entre autores e vírgula seguida de & antes do último na lista de referências.
Ponto e vírgula, conjunções narrativas e falta da vírgula antes de & produzem
personal_author_separators não conforme no subconjunto fechado. Não aplica essa
regra a citações narrativas no corpo, cuja conjunção textual continua correta.

Listas não abreviadas com até 20 autores atendem ao limite desse campo. Com 21
ou mais nomes explicitamente reconhecidos, personal_author_list_length sinaliza
que APA 7 exige os 19 primeiros, reticências e o último, sem &. Não consulta a
fonte para verificar se os nomes e a quantidade estão corretos.

A gramática admite até 64 nomes e 2048 bytes no bloco de autoria, dentro dos
limites anteriores de entrada. Prefixos com nomes compostos/partículas, sufixos,
texto inesperado, reticências, autoria institucional ou outras formas ficam fora
desse novo classificador. Não inventa identidade, não expande et al. e não aprova
reticências sem saber quantos autores existem. Os demais reconhecedores mantêm
seus limites anteriores; fora da cobertura permanece revisão humana.

## Periódicos com formato misto

Mesmo com data no início, detecta um sufixo numérico inequívoco como:

`Silva, A. (2020). Estudo da paisagem. Revista Sintética, v. 8, n. 2, p. 10–20.`

Exige autoria pessoal reconhecida, separação entre título e fonte e um único
sufixo ancorado no fim: fonte, volume com v., número opcional com n. e localização
com p./pp., com possível ano repetido no final. Não procura esses sinais
indiscriminadamente dentro do título ou em referências institucionais/jurídicas.
Formas fora desse padrão não são inferidas por semelhança.

journal_legacy_labels solicita a estrutura APA de volume(número), páginas ou
identificador. O tipo/campos ainda podem ser inconclusivos para itálicos; isso não
apaga a incompatibilidade estrutural comprovada. O DOI informado em prefixo antigo
continua recebendo orientação específica; ausência de DOI não reprova.

mixed_format_entries conta entradas com esses separadores ou rótulos mistos.
Não aumenta abnt_like_entries, que mantém o detector composto da Etapa 11.
Um formato misto não autoriza afirmar que toda a bibliografia esteja em ABNT.

## Segurança, admissão e devolutiva

A identificação recupera o bloco de autoria para diagnóstico, sem modificar o
arquivo nem os offsets dos campos originais. ReferenceAnalysis e o conjunto
fechado de correspondência de citações não foram ampliados: uma lista malformada
não passa a provar ausência de referência citada.

Incompatibilidade comprovada produz not_accepted e continua sem opção de
liberação humana no painel. O adaptador reconhece a regra nova. A mudança do
motor invalida liberações vinculadas à versão anterior pelo fingerprint existente.
Campos e tipos ambíguos não são chamados de infração; ausência de falhas continua
sem permitir avanço automático. O perfil de cauda bibliográfica segura pode
bloquear formato comprovado sem aprovar o corpo incompleto.

A minuta 1.0.0 reconhece as versões novas e usa orientações fixas para os três
códigos novos. Não copia nomes, referências, DOI ou texto enviado pelo cliente.
Os schemas 1.0 de relatório/minuta e snapshots 1.0/1.1 permanecem compatíveis.

## Validação e limites

806 testes sintéticos aprovados (27 novos), em PHP 8.4.27 e 8.1.27;
lint de 73 arquivos PHP. Cinco verificações no OJS 3.3.0.20 local/Chromium:
formato misto sem botão de aprovação, correções da minuta, ausência de acusação
genérica de ABNT, privacidade e POST real do wizard bloqueado antes da conclusão.
Estado da submissão comparado antes/depois; bytes da fixture sintética restaurados.
Evidências em evidence/stage21.

Reproduzir no laboratório isolado da Etapa 15: copiar o utilitário atualizado
tests/lab/footnote-fixture.php para /tmp/footnote-fixture.php no container web e
executar PYTHONPATH=tests/lab python tests/lab/apa-mixed.py. O modo mixed usa
somente dados sintéticos e exige a identificação do laboratório; restaura o arquivo
em finally. Não executar fora desse laboratório.

Não foi feita nova consulta normativa externa. Mantém a base técnica e as
ressalvas de fontes de STAGE11.md, aplicando a regra APA 7 de autoria pessoal.
Continuam pendentes nomes compostos, instituições, referências jurídicas, listas
abreviadas, fontes secundárias, tipos adicionais e conferência bibliográfica real.
Nenhuma validação completa de APA é anunciada. O PDF/manuscritos reais da conversa
não foram usados como fixtures nem enviados ao laboratório.

Sem produção, VM Oracle, envio a autores, reescrita de referências ou publicação
do ambiente. Atualizar motor e plugin conjuntamente em homologação; não houve deploy.
