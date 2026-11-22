# Etapa 65 — identificação do trabalho na revisão

Ao entrar por **Visualizar triagem**, a página mostra o número e o título da
submissão, além do nome do manuscrito vinculado à análise. A busca por número
fica recolhida em **Abrir outra submissão**. No acesso sem submissão válida,
a busca continua visível para localizar um trabalho.

O título vem do registro autorizado da revista atual. O nome do arquivo vem
somente do registro que corresponde aos identificadores de arquivo da análise;
se não for possível identificá-lo, a tela informa essa limitação. Dados exibidos
são escapados no template. A identificação não concede acesso nem altera
análise, liberação, histórico ou conferência editorial.

Plugin **0.41.0.0**, motor **0.44.0** inalterado. Português, inglês e espanhol.

## Verificação

- OJS 3.3.0.20 real, PHP 8.1.27 e Chromium, em laboratório sintético isolado:
  20 verificações aprovadas, incluindo identificação, nome do manuscrito,
  busca inicialmente recolhida e abertura da busca alternativa.
- Regressão PHP: 2.120 testes, zero falhas. O objeto sintético ganhou os métodos
  de identificação já oferecidos pelo OJS real.
- Integridade dos pacotes: 15 testes aprovados.

Evidência: [navigation-results.json](evidence/stage65/navigation-results.json).
O ensaio reutiliza o transporte privado e as salvaguardas da Etapa 64.

A VM Oracle não foi atualizada automaticamente. Após revisão/merge, gerar e
verificar novo pacote, guardar o plugin atual e atualizar somente o plugin com
web parado, seguindo [Etapa 64](STAGE64.md#atualização-da-homologação).
A troca da senha do banco exposta anteriormente permanece pendente. Ao terminar
a homologação, encerrar os recursos temporários Oracle e revisar seus discos.
