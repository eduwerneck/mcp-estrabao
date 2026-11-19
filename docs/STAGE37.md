# Etapa 37 — APA 7: datas sem ano e no prelo

Base 7eb1e2c (merge PR #37), em 19/11/2026. Motor 0.29.0, plugin 0.21.0.0,
APA 14.0.0, correspondência 10.0.0, citações 8.0.0 e formatos 7.0.0.
Contratos preservados; correspondência aplicada ao subconjunto português.

## Comportamento

Reconhece marcadores n.d./s.d. (com espaço interno opcional) e in press/no prelo
em referências pessoais e institucionais delimitadas e em citações narrativas,
parentéticas e fontes secundárias reconhecidas. Obras sem data podem manter
sufixos explícitos como n.d.-a/s.d.-a. As equivalências servem exclusivamente
à busca interna: não modificam o arquivo, não convertem o idioma nem comprovam
que o marcador é apropriado para a fonte.

Uma chave única liga a citação à referência, mas a ausência de data e o estado
no prelo continuam indeterminados, com orientação fixa para conferência na fonte.
A auditoria de formato pessoal também preserva essa revisão em vez de declarar
conformidade da data especial. Duplicatas, sufixos divergentes e estados distintos
para autoria possivelmente relacionada não provam referência ausente. A análise
não decide que uma obra foi publicada nem substitui um marcador pelo ano.
Uma entrada sem data relacionada também impede fortalecer diagnósticos de
sufixo numérico ou desambiguação de et al.; não relaxa a proteção anterior.

Grupos com datas especiais não recebem ordenação lexicográfica como se fossem
anos. A desambiguação de et al. nesses estados permanece humana. Em citações
secundárias, liga só a fonte declarada como consultada; não presume consulta
à obra original. A palavra apud permanece vetada pela política da revista.

## Bloqueio e devolutiva

Falhas independentes comprovadas continuam bloqueando: conjunção inadequada
entre dois autores ligados, apud e citação sem referência correspondente no
subconjunto fechado e sem autoria possivelmente relacionada. Marcador especial
não cria imunidade a essas falhas, nem vira acusação de ABNT por si só.

Mantém a orientação específica na tela do autor e a minuta privada sem copiar
nomes ou bibliografia. Inconclusivos exigem revisão editorial, sem avanço
automático. Versões novas invalidam liberações anteriores. Não envia mensagens.

## Limites

Não confirma identidade/autoria, existência dos dados, ausência real de data,
aceite para publicação ou escolha da obra. Não atribui letras ou anos. Datas
completas por tipo, datas originais/republicações, outras grafias/idiomas,
marcadores malformados, sufixos de obras no prelo e confirmação de ordem especial
seguem fora da gramática ou para revisão. Preserva limites de 20 autores,
512 referências, 2048 citações e os limites anteriores de texto/ZIP/XML.

Não acrescenta exigência normativa de idioma ao manuscrito. Conserva a base
técnica e ressalvas de STAGE11.md/APA7.md, sem nova consulta externa ou anúncio
de certificação APA integral. A matriz APA7.md registra o que ainda falta.

## Verificação

- 1289 testes por PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 97 arquivos PHP, compilação Python e schemas CLI aprovados.
- 32 verificações OJS 3.3.0.20/Chromium: três cenários de bloqueio com minuta,
  privacidade, POST e feedback ao autor; quatro cenários revisáveis sem avanço.
- DOCX e testes de marcador/sufixo, grupos mistos, ambiguidades, fonte secundária,
  et al. e contexto incompleto. Não foram usados documentos reais.
- Rede interna, sem portas públicas, mounts read-only, SMTP desabilitado e
  sendmail neutralizado. Versão instalada alinhada. Bytes e estado restaurados.
- Capturas sintéticas inspecionadas em evidence/stage37; containers desligados.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --dates
```

Requer o laboratório isolado previamente configurado e a versão instalada
alinhada. Não acessa produção ou VM Oracle; sem publicação do ambiente/deploy.
A suíte integrada anterior não foi repetida nesta etapa direcionada.
