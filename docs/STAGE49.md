# Etapa 49 — títulos e sufixos da mesma autoria e ano

Base f3c7a03 (merge PR #49), em 19/11/2026. Motor 0.38.0, plugin 0.31.0.0,
APA 23.0.0 e formatos reconhecidos 14.0.0. Correspondência 13.0.0, formato de
citações 11.0.0 e contratos JSON preservados. Invalida liberações anteriores.

## Conjuntos reconhecidos

ReferenceTitleOrder confere conjuntos completos de duas a 26 entradas com a
mesma lista pessoal declarada (sobrenomes e iniciais, na mesma ordem) e o mesmo
ano numérico positivo. A assinatura conserva as proteções da Etapa 47. Não une
pessoas por sobrenome, não escolhe identidade e não mistura listas de coautores.

Reutiliza o campo de título dos modelos reconhecidos de artigo de periódico e
obra autônoma candidata. Não usa o nome do periódico/editora como título. A
comparação nova restringe-se a títulos de até 512 bytes compostos por letras e
espaços simples. Compara todos os pares do conjunto: diferença ASCII inequívoca
ou prefixos idênticos, inclusive acentos comuns, podem estabelecer a ordem.
Não remove acentos nem translitera para decidir uma diferença incerta.

Artigos iniciais reconhecidos em português/inglês/espanhol (como A, The e El),
pontuação, números, títulos duplicados ou qualquer comparação incerta impedem
decidir a ordem e as letras de todo o conjunto. Não presume idioma nem elimina
artigos automaticamente. Uma terceira entrada não reconhecida impede atribuir
letras aos títulos restantes por amostragem.

## Diagnóstico e orientação

Em conjunto inteiramente elegível, confere a ordem alfabética dos títulos na
bibliografia e se os sufixos declarados correspondem à sequência a, b e seguintes.
Ausência de letras, repetição, lacunas ou associação inversa das letras reprovam
essa conferência. A mesma regra não exige sufixo de uma obra isolada.

Os diagnósticos são separados: title_order_status e year_suffix_order_status,
com pares/índices de parágrafo em amostras e contagem de conjuntos/pares.
Inversão de títulos ou sufixos comprovadamente incoerentes reprova o componente
APA e bloqueia conclusão/liberação com orientação específica. Não troca letras,
reorganiza a lista, modifica citações ou salva o documento. A conferência anterior
de correspondência literal dos sufixos entre citação e referência permanece.

Títulos, autores, anos e chaves técnicas não são copiados para as novas amostras
ou orientações. O editor/autor recebe instrução fixa para conferir o conjunto e
alinhar citações, sem atribuir letras por aproximação. Incerteza em outro conjunto
não apaga um defeito independente comprovado.

## Proteções e limites

Datas completas, datas sem ano/no prelo e títulos fora dos dois modelos não são
ampliados. Uma declaração de publicação original torna sua edição inelegível;
também protege o conjunto do mesmo autor no ano original declarado. Marcador
original ambíguo impede decidir as letras dos conjuntos daquela autoria.
Não escolhe o ano/edição consultada para resolver uma ambiguidade.

Até 512 entradas e 20 autores pessoais não abreviados por entrada, preservando
limites de extração anteriores. Até 26 entradas por conjunto, 512 bytes por título
e 16 amostras por categoria; não inventa letras depois de z. Cobertura não implica
identidade real, completude bibliográfica, existência das fontes nem APA integral.
Mantém APA7.md/STAGE11.md, sem nova consulta normativa externa.

## Validação

- 1607 testes por PHP 8.4.27 e 8.1.27, zero falhas; 16 testes JavaScript.
- Lint de 115 arquivos PHP, Python, dois schemas CLI e diff aprovados.
- 50 verificações OJS 3.3.0.20/Chromium: cinco incompatibilidades com bloqueio,
  minuta, privacidade e POST nativo; seis casos corretos/incertos sem conclusão
  automática. Inclui proteção de ano original e títulos duplicados.
- Testes de artigo distinguem título da obra e nome do periódico; casos de três
  títulos, iniciais distintas, mais de 26 obras e conjuntos incertos são cobertos.
- Isolamento, e-mail neutralizado, mounts read-only e versão instalada conferidos;
  bytes/estado restaurados. Capturas inspecionadas em evidence/stage49.
  Containers desligados ao final.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --title-order
```

Requer laboratório previamente configurado. Modalidade adicionada ao runner
integrado opcional; as 695 verificações da Etapa 48 são da base anterior e não
foram repetidas integralmente nesta entrega. Sem produção, VM Oracle, documentos
reais, mensagens externas, publicação do ambiente ou deploy. CI remoto ainda não
verificado nesta entrega.
