# Etapa 40 — APA 7: referências com URL e campos de páginas

Base e91e0c2 (branch da Etapa 39, PR #40), em 19/11/2026. A main consultada
continuava em aec02aa (Etapa 38); esta branch preserva a dependência da Etapa 39.
Motor 0.31.0 e plugin 0.23.0.0; APA 16.0.0, formatos 9.0.0. Correspondência
10.0.0 e citações 8.0.0 preservadas; contratos JSON preservados.

## Diagnósticos seguros

Uma URL não comprova que a obra é uma página: pode acompanhar artigo, livro,
tese ou relatório. Mantém os modelos anteriores e não impõe itálico de página
nem campos bibliográficos só por encontrar um endereço HTTP. A identificação
de tipo/site, autoria, disponibilidade de data e necessidade de data de consulta
exigem revisão; modelo visual reconhecido não certifica a fonte.

WebReference confere, localmente e sem rede:

- Dia/mês/ano impossíveis nas datas de referência com URL HTTP reconhecida.
  Vocabulário de meses em português/inglês, calendário gregoriano e anos bissextos;
  reconhece ano–mês–dia em inglês e ano–dia de mês em português. Mês/ano sem
  dia também pode ser reconhecido. Data real de publicação continua humana.
- Data de consulta quando declarada nas formas Retrieved Month D, YYYY, from
  ou Recuperado em D de mês de YYYY, de. Data impossível exige correção;
  mês/grafia fora do vocabulário continua inconclusivo. Não inventa uma data.
- URL HTTP terminal sem host ou com estrutura/porta recusada por parse_url.
  Host não ASCII, codificado ou fora do subconjunto ASCII fica para revisão;
  não rejeita por um filtro limitado a domínios ASCII. IPv4/IPv6 e HTTP são
  aceitos lexicalmente, sem conexão. Sintaxe reconhecida não confirma destino.

Datas com apenas ano ou sem data e ausência de consulta/nome de site não geram
reprovação por campo supostamente obrigatório. Não exige data de consulta em
toda página, nem repete o site quando sua necessidade não foi estabelecida.
Separar o trecho de consulta reconhecido preserva os offsets dos campos iniciais.

## Entrada e devolutiva

Datas impossíveis e URLs estruturalmente inválidas bloqueiam conclusão e
liberação, com orientação fixa no wizard e minuta privada. Não copia título,
site, endereço ou data do manuscrito para a minuta. Conclusão inconclusiva
permanece sujeita à revisão humana. A versão nova invalida liberações antigas.
Não altera o arquivo e não envia mensagens ao autor.

## Limites

Não resolve DNS nem segue links, testa credenciais, consulta DOI ou acessa a
página. Não confirma autoria/site, data real, disponibilidade/estabilidade do
conteúdo, necessidade de consulta ou correção de todo o formato APA de página.
URLs DOI seguem a conferência anterior e não entram no calendário web desta
etapa. URLs sem esquema HTTP, fragmentadas, com espaços ou outras estruturas fora do
reconhecedor e datas/idiomas fora da gramática exigem conferência. URLs acima de
2048 bytes ficam indeterminadas; os limites anteriores de texto/referência e
ZIP/XML são preservados. A correspondência de citações com datas completas não
é ampliada nesta entrega; esses casos ainda podem ficar inconclusivos no corpo.

Conserva a base técnica/ressalvas de STAGE11.md e APA7.md, sem nova consulta
normativa externa ou anúncio de certificação APA integral. Não converte idioma.

## Verificação

- 1361 testes por PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 101 arquivos PHP, compilação Python, dois schemas CLI e diff aprovados.
- 38 verificações OJS 3.3.0.20/Chromium: data de publicação impossível,
  consulta impossível, URL com host ausente e URL só com esquema; cada caso
  confere bloqueio, minuta/privacidade, POST e orientação específica ao autor.
- Quatro cenários revisáveis: calendário/consulta válidos, só ano, site omitido
  e mês desconhecido. Sem liberação ou conclusão automática.
- Testes incluem anos bissextos, meses portugueses/ingleses, portas inválidas,
  host numérico, IPv6, IDN, sufixos de URL, site/consulta ausentes, tipo de artigo
  e tese preservado e minuta sem texto privado. Nenhum destino é acessado.
- Rede interna, mounts read-only e e-mail neutralizado; versão instalada
  alinhada. Arquivo/estado sintéticos restaurados e capturas inspecionadas.
  Evidências em evidence/stage40; containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --web
```

Requer laboratório isolado previamente configurado. Nova modalidade adicionada
ao plano opcional integrado; a regressão completa da Etapa 38 não foi repetida.
Sem produção, VM Oracle, documentos reais, envio externo, publicação do ambiente
ou deploy. Integrar a Etapa 39 antes desta branch se o PR #40 ainda estiver aberto.
