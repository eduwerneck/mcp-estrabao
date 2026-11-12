# Etapa 4 — estilos e herança dos títulos

Motor 0.4.0, após o merge do PR #3. Mantém os limites ZIP/XML e a cobertura de
texto de STAGE3.md, sem novas dependências, rede no motor ou integração OJS.

`StyleResolver` resolve o subconjunto suportado da formatação efetiva dos
parágrafos extraídos. Usa os relacionamentos internos do documento para localizar
estilos e numeração, inclusive caminhos locais relativos diferentes dos nomes
convencionais. Não segue destinos externos. Partes ausentes, órfãs, relacionamentos
duplicados ou inseguros tornam a formatação indeterminada, preservando a análise
lexical das seções quando a extração do corpo continua completa.

## Negrito

A precedência é: `docDefaults`, cadeia `basedOn` do estilo de parágrafo,
cadeia do estilo de caractere e propriedades diretas do run. Estilos padrão de
parágrafo e caractere são usados quando não há seleção explícita. `basedOn`
precisa apontar para estilo existente do mesmo tipo, sem ciclos, até 32 estilos.
A resolução admite até 1.024 estilos, 4.096 elementos diretos na parte de
numeração e 4.096 runs textuais por parágrafo; excedentes ficam indeterminados.
Não há exigência de nomes de estilo, template ou nível de título.

`w:b` ligado em um estilo alterna o valor herdado; desligado em um estilo o
preserva. Em `docDefaults` e na formatação direta, o valor é absoluto. A ausência
de negrito na hierarquia suportada resulta em falso. Todos os runs textuais do
título precisam estar em negrito; runs só com espaços Unicode são ignorados.
Propriedades de scripts complexos (`cs`/`rtl`), valores inválidos, estilos ausentes,
cíclicos, duplicados ou containers duplicados permanecem indeterminados. Estilos
vinculados por `link` não são aplicados implicitamente como estilos de caractere.

## Numeração

Combina propriedades `numPr` de defaults, estilos e parágrafo, preservando
`numId` e `ilvl` herdados quando a camada seguinte não os substitui. `numId=0`
cancela a numeração. Identifica instância, definição abstrata e nível, incluindo
`lvlOverride`. A associação `lvl/pStyle` seleciona o nível do estilo; `ilvl`
direto tem precedência. `ilvl` de estilo sem associação reconhecida não é tomado
como prova de numeração ou de ausência.

Formatos decimal, romanos e letras constituem numeração detectada. `numFmt=none`
representa ausência de número. Bullets, formatos personalizados, vínculos com
estilos de lista (`numStyleLink`/`styleLink`), associações implícitas sem instância,
definições ausentes, ambíguas ou níveis não encontrados permanecem indeterminados.
Não interpreta bullets como infração editorial sem confirmação da política.
Numeração literal no texto reconhecido continua detectada independentemente dos
estilos.

## Resultado e auditoria

`manuscript.headings.format` passa à versão 2.0.0. Só retorna conforme quando
todas as seções obrigatórias estão reconhecidas e todos os títulos encontrados
estão comprovadamente em negrito e sem numeração. Uma violação comprovada
prevalece sobre propriedades desconhecidas; sem violação, qualquer lacuna produz
indeterminado. Não muda a política lexical, a ordem livre, o tratamento de idiomas
pendentes ou a aplicabilidade condicional de fomento.

A evidência contém índices dos parágrafos, negrito/numeração efetivos e estado da
resolução. Não copia texto, nomes de estilos ou caminhos internos. Os campos
antigos `explicit_numbering`/`explicit_nonbold` do resultado de formatação são
substituídos por `numbering_found`/`nonbold_found`; a versão identifica a mudança.
A evidência de estrutura mantém as observações diretas da Etapa 3 e acrescenta
resolução efetiva. Os schemas continuam 1.0. O hash do documento permanece na
regra de estrutura e o hash do snapshot no relatório.

Execute `php tests/run.php` na raiz; a CLI e seus argumentos continuam iguais.
A Etapa 3 é documentação histórica: sua limitação de toda herança indeterminada
é substituída apenas pelo subconjunto descrito aqui. Texto oculto, revisões,
campos, tabelas e demais limitações de extração continuam como antes. Fonte,
tamanho, página, margens e espaçamento não foram implementados nesta etapa.
