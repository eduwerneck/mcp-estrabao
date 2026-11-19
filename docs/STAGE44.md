# Etapa 44 — ordem bibliográfica no DOCX

Base b289698 (merge PR #44), em 19/11/2026. Motor 0.34.0, plugin 0.27.0.0,
APA 19.0.0 e formatos 12.0.0. Correspondência 11.0.0, citações 9.0.0 e contratos
JSON preservados. A versão nova invalida liberações anteriores.

## Ordenação conferida

ReferenceOrder recebe a lista completa reconhecida de referências pessoais e
compara pares adjacentes. Autoria/ano não analisáveis, instituições ou listas
abreviadas/malformadas impedem afirmar a ordem global. Os formatos individuais
e demais incompatibilidades continuam sendo analisados pelas regras anteriores.

Compara o primeiro sobrenome declarado por trechos literais. Letras ASCII
inequivocamente diferentes decidem; caracteres idênticos, inclusive acentos ou
partículas comuns, podem ser atravessados até uma diferença segura. Exemplo:
Souza antes de García e da Silva antes de da Costa são inversões reconhecidas;
Ávila antes de Álvares também se decide depois do Á comum. Não remove acentos,
partículas ou pontuação para fabricar uma chave de ordenação. Não aplica uma
colação universal/locale ou transliteração por aproximação.

Diferenças que dependem de colação (Alves/Ávila, García/Garcia, espaço versus
letra, pontuações distintas) permanecem indeterminadas. Grafias potencialmente
parciais ou com variantes de partículas (Silva/da Silva, da Silva/de Silva,
Márquez/García Márquez) pedem conferência em vez de provar inversão.

## Mesma autoria declarada

A assinatura preserva a lista completa e sua ordem, sobrenomes, partículas,
acentos e iniciais. Normaliza somente caixa suportada e espaços de apresentação;
não une de Souza e deSouza nem escolhe pessoas por sobrenome. Iniciais ou listas
de coautores diferentes não constituem a mesma autoria para este diagnóstico.
Identidade real ainda precisa de conferência.

Anos numéricos distintos, positivos e sem sufixo são conferidos em ordem crescente
quando os pares têm a mesma assinatura e o conjunto dessa assinatura não contém
metadados que tornem a escolha do ano incerta. Datas especiais, datas completas,
sufixos e indicação de publicação original preservam revisão da cronologia.
Empates não recebem letras, remoção de duplicatas ou desempate pelo título.

Não presume que autor único e coautoria sejam a mesma lista, nem resolve a ordem
entre coautorias diferentes. Não compara cronologicamente entradas não adjacentes.
Datas completas continuam elegíveis para comparação alfabética com autoria
reconhecida, sem reduzir a data ao ano para impor cronologia.

## Resultado e devolutiva

O relatório separa alphabetical_order_status e chronological_order_status,
registra pares de índices de parágrafo e contagem de pares cronológicos elegíveis;
não copia autores/anos para as amostras. Ausência de pares não certifica todas
as demais formas de ordenação. Uma comparação incerta não apaga uma inversão
independente comprovada entre pares elegíveis.

Inversão comprovada reprova o componente APA e bloqueia conclusão/liberação.
O autor recebe orientação específica de ordem alfabética ou cronológica, também
na minuta fixa sem nomes/datas da referência. Inconclusivos permanecem para
revisão humana, sem avanço automático. Não reorganiza, salva ou reescreve o DOCX.
A orientação de Enter do metadado OJS (Etapa 43) permanece; não ordena citationsRaw.

## Limites e verificação

Até 512 entradas, 20 autores pessoais por entrada e 16 pares por categoria de
amostra, preservando limites de texto/ZIP/XML anteriores. Não consulta fontes,
resolve identidade, atribui sufixos ou certifica APA integral. Mantém a base
normativa/ressalvas de STAGE11.md/APA7.md, sem nova consulta externa.

- 1473 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript aprovados.
- Lint de 109 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 50 verificações OJS 3.3.0.20/Chromium: cinco inversões comprovadas com bloqueio,
  minuta/privacidade, POST e feedback ao autor; seis casos revisáveis sem avanço.
- Reversões cronológicas de autoria simples/coautoria e alfabéticas de sobrenome
  acentuado, partícula e acento comum; revisão para iniciais, sufixos, colação
  incerta, original e sem data. Ordem crescente permanece revisável no conjunto.
- Testes incluem listas diferentes, espaços/limites de nomes, grafias parciais,
  datas completas, fonte incompleta e minuta sem dados privados.
- Isolamento, mounts read-only, e-mail neutralizado e versão instalada conferidos;
  bytes/estado restaurados. Capturas vistas em evidence/stage44. Containers desligados.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --ordering
```

Requer laboratório local previamente configurado; modalidade adicionada ao runner
opcional integrado, que não foi repetido integralmente nesta entrega. Sem produção,
VM Oracle, documentos reais, mensagens externas, publicação do ambiente ou deploy.
