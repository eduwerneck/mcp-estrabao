# Etapa 36 — APA 7: desambiguação de et al.

Base 5989c05 (merge PR #36), em 18/11/2026. Motor 0.28.0, plugin 0.20.0.0,
APA 13.0.0, correspondência 9.0.0 e formato de citações 7.0.0. Formatos de
referências 6.0.0 e schemas preservados. Aplicação no subconjunto português.

## Chaves abreviadas e nomes adicionais

Confere referências pessoais com o mesmo primeiro sobrenome e ano, incluindo
sufixo. A inicialização completa declarada do primeiro autor precisa coincidir.
Não presume que iniciais distintas ou formas parciais identifiquem a mesma pessoa.
Os dados completos de autoria são internos; nomes/trechos não entram nas correções.

Uma forma com et al. que ainda coincide com várias listas distintas, distinguíveis
por sobrenomes, produz incompatibilidade de desambiguação. A orientação solicita
os nomes necessários e conferência da autoria; não escolhe a obra nem preenche
coautores. Listas de sobrenomes indistinguíveis, iniciais diferentes, duplicatas
e sufixos pendentes continuam para revisão, sem exigir nomes que não resolveriam
a ambiguidade.

Et al. precisa substituir pelo menos dois autores. Na expansão necessária que
deixaria apenas um, é preciso apresentar os nomes por extenso. Se a chave básica
já é única, a regra anterior continua orientando primeiro sobrenome seguido de
et al. Não exige escrever todos para depois reprovar a lista desnecessária.

A correspondência continua literal ao prefixo: vínculo reconhecido não aprova sua
forma APA. Quando quantidades diferentes permitem mais de uma interpretação,
mantém revisão em vez de escolher a referência maior por contagem. A extensão
adequada que realmente distingue uma fonte pode atender ao formato; uma lista
integral necessária também. Prefixos/nomeações maiores que o necessário ficam
para revisão da extensão, sem redução automática. O mínimo global não é imposto
em todos os casos especiais.

## Contexto e limites

Exige corpo e referências seguros, referências analisáveis, citações reconhecidas,
vocabulário composto dentro do limite e ausência de contexto secundário não
bibliografado. Notas/corpo parcial, inventário de notas não confirmado e citações
secundárias impedem afirmar desambiguação completa; as demais falhas independentes,
incluindo o veto a apud, continuam ativas.

Considera até 32 candidatos por primeiro autor/ano. Ao exceder, retorna revisão.
Preserva até 20 autores por entrada para correspondência, 512 referências, 2048
citações e limites anteriores de texto/ZIP/XML. Memoriza diagnósticos por chave
apenas durante uma análise, sem compartilhar dados ou decisões entre arquivos.
O indicador et_al_context_complete descreve disponibilidade de contexto, não
conformidade integral nem identidade real.

## Entrada e devolutiva

As falhas concretas bloqueiam conclusão e liberação, com orientação fixa na tela
e minuta. Inconclusivos não viram acusações ao autor. Sem incompatibilidade
comprovada, continua revisão, sem avanço automático. O fingerprint da nova versão
invalida liberações antigas; não reescreve o arquivo e não envia mensagens.

## Verificação

- 1249 testes em PHP 8.4.27 e 8.1.27, zero falhas.
- Lint de 96 arquivos PHP, compilação Python, schemas CLI e diff aprovados.
- 32 verificações OJS 3.3.0.20/Chromium: abreviação básica ambígua, prefixo ainda
  ambíguo e único nome omitido, com bloqueio/minuta/privacidade/POST/feedback.
- Quatro casos revisáveis, sem conclusão automática: prefixo suficiente, lista
  integral necessária, iniciais diferentes e listas indistinguíveis/duplicadas.
- DOCX confirma expansão suficiente. Testes cobrem 32/33 candidatos, contextos
  secundários/notas, formas parciais, alias institucional, anos/sufixos e revisão.
- Rede interna, mounts read-only, e-mail neutralizado e versão instalada conferidos.
  Estado e bytes originais restaurados; capturas sintéticas inspecionadas.

```sh
PYTHONPATH=tests/lab python tests/lab/apa-authors.py --etal
```

Requer laboratório isolado e versão instalada alinhada conforme HOMOLOGATION.md.
Evidências em evidence/stage36. Containers desligados ao final. Sem produção,
VM Oracle, documentos reais, envio externo ou deploy.

A tentativa de consultar APA Style author-date em 18/11/2026 recebeu 403 do proxy;
o conteúdo não foi consultado. Mantém a base técnica anterior e as ressalvas de
STAGE11.md. Não anuncia consulta ao manual oficial, certificação integral de APA
ou confirmação de identidade. Próximos casos na matriz APA7.md.
