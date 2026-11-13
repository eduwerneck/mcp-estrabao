<?php
declare(strict_types=1);
namespace Estrabao\Reporting;
use Estrabao\Contracts\CheckResult;
use Estrabao\Rules\AbstractLabels;

/** Editorial draft from trusted engine results; never copies free-form source strings. */
final class DeskResponse
{
    const VERSION = '1.0.0';
    private const LOCALES = ['pt_BR' => 'português', 'en_US' => 'inglês', 'es_ES' => 'espanhol'];
    private const VERSIONS = [
        'metadata.title.presence'=>'1.0.0', 'metadata.abstract.presence'=>'1.0.0', 'metadata.keywords.presence'=>'1.0.0',
        'metadata.title.case'=>'1.0.0', 'metadata.subtitle.case'=>'1.0.0', 'metadata.keywords.count'=>'1.0.0',
        'abstract.labels'=>'2.0.0', 'abstract.word_limit'=>'1.0.0',
        'author.orcid.presence'=>'1.0.0', 'author.orcid.syntax'=>'1.0.0', 'author.orcid.identity'=>'1.0.0',
        'manuscript.docx.structure'=>'3.0.0', 'manuscript.headings.format'=>'3.0.0', 'manuscript.funding'=>'2.0.0',
        'manuscript.page.a4'=>'2.0.0', 'manuscript.page.margins'=>'2.0.0', 'manuscript.body.font'=>'2.0.0',
        'manuscript.body.font_size'=>'2.0.0', 'manuscript.body.line_spacing'=>'2.0.0',
        'manuscript.word_limit'=>'1.0.0', 'manuscript.visuals.count'=>'1.0.0', 'manuscript.headings.case'=>'3.0.0',
        'manuscript.ethics.review'=>'1.0.0', 'manuscript.credit.content'=>'1.0.0', 'manuscript.credit.verification'=>'1.0.0', 'manuscript.footnotes.count'=>'1.0.0', 'manuscript.footnotes.lines'=>'2.0.0',
        'references.apa7'=>'5.0.0', 'references.citation_matching'=>'3.0.0',
        'references.apa7.recognized_formats'=>'2.0.0', 'references.apa7.citation_format'=>'1.0.0',
    ];

    public static function generate(array $report): array
    {
        if (($report['schema_version'] ?? null) !== '1.0' || ($report['scope'] ?? null) !== 'local_snapshot_only'
            || ($report['human_review_required'] ?? null) !== true || !is_string($report['input_sha256'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/D', $report['input_sha256'])
            || !isset($report['checks']) || !is_array($report['checks']) || count($report['checks']) > 8192) {
            throw new \InvalidArgumentException('Relatório inválido para devolutiva.');
        }
        if (!is_string($report['engine_version'] ?? null) || !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D', $report['engine_version'])) {
            throw new \InvalidArgumentException('Versão de motor inválida.');
        }
        $issues = []; $pending = 0; $passed = 0; $unmapped = 0; $documentHashes = []; $pendingItems = [];
        $absentFields = [];
        foreach ($report['checks'] as $candidate) {
            if (is_array($candidate) && in_array($candidate['rule_id'] ?? null, ['metadata.abstract.presence','metadata.keywords.presence'], true)
                && ($candidate['rule_version'] ?? null) === '1.0.0' && ($candidate['status'] ?? null) === CheckResult::FAIL
                && ($candidate['evidence']['source_available'] ?? false) === true && is_string($candidate['location'] ?? null)) {
                $absentFields[$candidate['location']] = true;
            }
        }
        foreach ($report['checks'] as $check) {
            if (!is_array($check) || !is_string($check['rule_id'] ?? null) || !is_string($check['rule_version'] ?? null)
                || !in_array($check['status'] ?? null, [CheckResult::PASS, CheckResult::FAIL, CheckResult::UNKNOWN], true)
                || !is_array($check['evidence'] ?? null) || !is_string($check['location'] ?? null)) {
                throw new \InvalidArgumentException('Resultado inválido para devolutiva.');
            }
            $hash = $check['evidence']['document_sha256'] ?? null;
            if ($hash !== null) {
                if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) { throw new \InvalidArgumentException('Hash documental inválido.'); }
                $documentHashes[$hash] = true;
            }
            $id = $check['rule_id'];
            if ((self::VERSIONS[$id] ?? null) !== $check['rule_version']) { $pending++; $unmapped++; $pendingItems[]=self::pending($check,'unmapped_rule'); continue; }
            if ($check['status'] === CheckResult::UNKNOWN) { $pending++; $pendingItems[]=self::pending($check,'indeterminate'); continue; }
            if ($check['status'] === CheckResult::PASS) { $passed++; continue; }
            // An explicitly unavailable source cannot justify an accusation.
            if (($check['evidence']['source_available'] ?? true) === false) { $pending++; $pendingItems[]=self::pending($check,'source_unavailable'); continue; }
            if ((in_array($id, ['abstract.labels','abstract.word_limit'], true) && isset($absentFields[$check['location']]))
                || ($id === 'metadata.keywords.count' && isset($absentFields[$check['location']]))) { continue; }
            $issue = self::issue($check);
            if ($issue === null) { $pending++; $unmapped++; $pendingItems[]=self::pending($check,'insufficient_evidence'); continue; }
            $key = json_encode($issue, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $issues[$key] = $issue;
        }
        if (count($documentHashes) > 1) { throw new \InvalidArgumentException('Relatório mistura versões documentais.'); }
        $issues = array_values($issues);
        $body = "Prezados(as) autores(as),\n\n";
        if ($issues) {
            $body .= "Na triagem inicial, identificamos ajustes necessários para que o manuscrito possa seguir à avaliação:\n\n";
            foreach ($issues as $i => $issue) {
                $body .= ($i + 1) . '. ' . $issue['area'] . ': ' . $issue['requirement'] . ' '
                    . $issue['finding'] . ' ' . $issue['correction'] . "\n\n";
            }
            if ($pending) { $body .= "Há também itens que dependem de conferência editorial; eles não foram classificados como descumprimentos comprovados.\n\n"; }
            $body .= "Após os ajustes, o material passará por nova triagem editorial.";
        } elseif ($pending || !$passed) {
            $body .= "A triagem ainda depende de conferência editorial. As verificações disponíveis não confirmaram ajustes a solicitar nesta etapa; isso não significa aprovação da submissão.";
        } else {
            $body .= "As verificações automáticas disponíveis não identificaram ajustes a solicitar. O resultado depende de conferência editorial e não representa aceite para publicação.";
        }
        $body .= "\n\nAtenciosamente,\nEquipe editorial da Revista Estrabão\n";
        return ['schema_version'=>'1.0', 'template_version'=>self::VERSION, 'editor_review_required'=>true,
            'send_authorized'=>false, 'source_input_sha256'=>$report['input_sha256'],
            'source_document_sha256'=>$documentHashes ? key($documentHashes) : null, 'source_engine_version'=>$report['engine_version'],
            'decision'=>$issues ? 'corrections_required' : (($pending || !$passed) ? 'editorial_review_required' : 'no_issues_detected'),
            'subject'=>'Adequação às diretrizes editoriais', 'body'=>$body, 'corrections'=>$issues,
            'pending_review_count'=>$pending, 'pending_review'=>$pendingItems, 'unmapped_check_count'=>$unmapped];
    }

    private static function issue(array $check): ?array
    {
        $id=$check['rule_id']; $e=$check['evidence']; $location=$check['location'];
        $locale=basename($location); $language=self::LOCALES[$locale] ?? null;
        if (preg_match('/^metadata\.(title|abstract|keywords)\.presence$/D', $id, $m) && $language) {
            $name=['title'=>'título','abstract'=>'resumo','keywords'=>'palavras-chave'][$m[1]];
            return self::item('Metadados em '.$language, 'A revista exige título, resumo e palavras-chave nos três idiomas.',
                'Não foi encontrado conteúdo visível no campo de '.$name.' em '.$language.' nos dados da submissão examinados.',
                'Preencher esse campo na submissão e conferir sua apresentação no OJS.');
        }
        if (in_array($id,['metadata.title.case','metadata.subtitle.case','manuscript.headings.case'],true)) {
            if (strpos($id,'metadata.')===0 && !$language) { return null; }
            return self::item('Títulos'.($language?' em '.$language:''), 'Os títulos e subtítulos devem evitar caixa alta integral.',
                'Foi identificada caixa alta integral em título ou subtítulo '.($id==='manuscript.headings.case'?'do manuscrito.':'nos metadados.'),
                'Usar capitalização convencional, preservando nomes próprios e siglas.');
        }
        if ($id==='metadata.keywords.count' && $language && self::number($e['count']??null)) {
            return self::item('Palavras-chave em '.$language,'São exigidas de 3 a 4 palavras-chave.',
                'Foram contadas '.$e['count'].' palavras-chave não vazias.','Ajustar a quantidade neste idioma.');
        }
        if ($id==='abstract.labels' && $language) {
            $missing=[];
            foreach($e['groups']??[] as $group){
                $n=$group['group']??null;
                if(($group['state']??null)==='absent' && is_int($n) && isset(AbstractLabels::LABELS[$locale][$n-1])){
                    $missing[]=implode(' ou ',AbstractLabels::LABELS[$locale][$n-1]);
                }
            }
            if(!$missing){return null;}
            return self::item('Resumo em '.$language,'O resumo deve usar os marcadores aprovados pela revista.',
                'Não foram reconhecidos marcadores dos grupos: '.implode('; ',$missing).'.',
                'Incluir uma das alternativas de cada grupo indicado como rótulo explícito.');
        }
        if ($id==='abstract.word_limit' && $language) {
            $interval=self::interval($e);if($interval===null){return null;}
            return self::item('Resumo em '.$language,'O resumo deve ter até 250 palavras.',
                'A contagem técnica ficou entre '.$interval[0].' e '.$interval[1].' palavras.',
                'Reduzir o resumo ao limite e conferir a contagem; o intervalo técnico não é contagem oficial do Word.');
        }
        if(in_array($id,['author.orcid.presence','author.orcid.syntax'],true)) {
            return self::item('ORCID','Todos os autores devem informar ORCID nos dados da submissão.',
                $id==='author.orcid.presence'?'Há autor com ORCID não preenchido.':'Há ORCID com formato ou dígito verificador inválido.',
                'Conferir e completar os ORCIDs no cadastro da submissão, preservando a anonimização do manuscrito.');
        }
        if($id==='manuscript.docx.structure'){
            $allowed=['Introdução','Metodologia','Resultados e Discussão','Considerações Finais','Créditos (CRediT)','Referências'];$missing=[];
            foreach($e['sections']??[]as$section){
                if(($section['status']??null)===CheckResult::FAIL && in_array($section['required_label']??null,$allowed,true)){$missing[]=$section['required_label'];}
            }
            if(!$missing){return null;}
            return self::item('Estrutura','O manuscrito deve apresentar as seções previstas nas diretrizes.',
                'Não foram identificados os títulos explícitos: '.implode('; ',array_unique($missing)).'.',
                'Organizar o conteúdo sob os títulos exigidos; ausência de seção não comprova ausência de método. Observar a anonimização na declaração CRediT.');
        }
        if($id==='manuscript.funding' && ($e['word_state']??null)==='declared'){
            if(($e['reason']??null)==='word_declared_ojs_empty' && ($e['ojs_state']??null)==='absent'){
                return self::item('Consistência do fomento','Manter coerentes as declarações de fomento no manuscrito e nos metadados do OJS.',
                    'Há declaração de fomento no Word, mas o campo disponível de agências de apoio está vazio no OJS.',
                    'Completar o campo correspondente no OJS e conferir os dados com a declaração do manuscrito.');
            }
            if(($e['reason']??null)==='word_declared_ojs_explicit_none' && ($e['ojs_state']??null)==='none'){
                return self::item('Consistência do fomento','Manter coerentes as declarações de fomento no manuscrito e nos metadados do OJS.',
                    'O Word declara fomento, enquanto o campo do OJS contém declaração explícita de ausência ou não aplicabilidade.',
                    'Conferir qual informação corresponde ao estudo e harmonizar as duas fontes, sem criar financiamento ou agências.');
            }
        }
        if($id==='manuscript.credit.content' && ($e['reason']??null)==='credit_section_empty'
            && ($e['full_document_text_available']??false)===true && ($e['section_count']??null)===1
            && ($e['content_paragraph_count']??null)===0){
            return self::item('Contribuições CRediT','Descrever as contribuições dos autores conforme a taxonomia CRediT.',
                'Foi identificado o título da seção, mas não texto de contribuições sob ele.',
                'Preencher a descrição das contribuições, preservando a anonimização na versão de avaliação. O autor correspondente responde pela veracidade.');
        }
        if($id==='manuscript.headings.format'){
            return self::item('Formatação dos títulos','Os títulos das seções devem estar em negrito e sem numeração.',
                'Foi identificada numeração ou ausência de negrito em título reconhecido.','Ajustar os títulos das seções ao padrão exigido.');
        }
        $format=[
            'manuscript.page.a4'=>['Página','O formato exigido é A4.','Foi identificado tamanho de página diferente de A4.','Ajustar o tamanho das páginas.'],
            'manuscript.page.margins'=>['Margens','As quatro margens devem medir 3 cm.','Foi identificada margem divergente.','Configurar margens superior, inferior, esquerda e direita em 3 cm.'],
            'manuscript.body.font'=>['Fonte','A fonte exigida é Times New Roman.','Foi identificada outra fonte declarada no texto verificado.','Conferir a fonte dos trechos e estilos do documento.'],
            'manuscript.body.font_size'=>['Tamanho da fonte','O tamanho exigido é 12 pontos.','Foi identificado tamanho de fonte divergente.','Conferir o tamanho dos trechos e estilos do documento.'],
            'manuscript.body.line_spacing'=>['Espaçamento','O espaçamento exigido é 1,5 linha.','Foi identificado espaçamento declarado divergente.','Ajustar o espaçamento dos parágrafos verificados para 1,5.'],
        ];
        if(isset($format[$id])){return self::item(...$format[$id]);}
        if($id==='manuscript.word_limit'){
            $limits=\Estrabao\Rules\ManuscriptLength::LIMITS[$e['manuscript_type']??'']??null;
            $interval=self::interval($e);if(!$limits||!$interval){return null;}
            return self::item('Extensão','O tipo de submissão informado exige de '.$limits[0].' a '.$limits[1].' palavras.',
                'A contagem técnica do corpo ficou entre '.$interval[0].' e '.$interval[1].' palavras.',
                'Ajustar a extensão observando as exclusões previstas nas diretrizes; o intervalo técnico não é contagem oficial do Word.');
        }
        if($id==='manuscript.visuals.count' && ($e['manuscript_type']??null)==='research_communication'){
            return self::item('Figuras e tabelas','Comunicações de Pesquisa admitem no máximo 6 figuras e/ou tabelas.',
                'O inventário confirmado ultrapassou esse limite.','Rever a quantidade conjunta de figuras e tabelas.');
        }
        if($id==='manuscript.footnotes.count' && self::number($e['inventory']['note_count']??null)){
            return self::item('Notas de rodapé','São permitidas até 3 notas de rodapé.',
                'Foram confirmadas '.$e['inventory']['note_count'].' notas de rodapé.','Reduzir as notas ao limite e conferir sua apresentação.');
        }
        if(strpos($id,'references.')===0){
            // Only fixed messages for supported diagnostic codes; never source recommendations.
            $messages = [
                'author_date_position'=>'posicionar a data após a autoria, no início da referência',
                'style_family'=>'substituir os padrões ABNT reconhecidos pela estrutura APA 7',
                'numbered_reference_list'=>'retirar a numeração da lista de referências',
                'doi_url_format'=>'apresentar o DOI informado como https://doi.org/ (a ausência de DOI não é infração)',
                'article_title_plain'=>'retirar o itálico do título do artigo de periódico',
                'journal_italic'=>'aplicar itálico ao nome do periódico',
                'volume_italic'=>'aplicar itálico ao volume do periódico',
                'issue_plain'=>'retirar o itálico do fascículo entre parênteses',
                'personal_author_separators'=>'usar vírgulas entre autores e vírgula seguida de & antes do último autor, sem ponto e vírgula ou conjunção narrativa na referência',
                'personal_author_list_length'=>'listar todos os autores até 20; com 21 ou mais, apresentar os 19 primeiros, reticências e o último, sem &',
                'journal_legacy_labels'=>'substituir rótulos v., n. e p./pp. pelo formato APA 7 de volume(número), páginas ou identificador',
                'journal_page_prefix'=>'retirar p./pp. da paginação da referência de artigo de periódico',
                'chapter_title_plain'=>'retirar o itálico do título do capítulo',
                'book_title_italic'=>'aplicar itálico ao título do livro que contém o capítulo',
                'chapter_publisher_plain'=>'retirar o itálico do nome da editora',
                'chapter_editor_label'=>'conferir Ed./Eds. no formato de capítulo reconhecido',
                'work_title_full_uppercase'=>'evitar caixa alta integral no título da obra',
            ];
            $actions=[];
            foreach($e['style_audit']['samples']??[]as$sample){foreach($sample['checks']??[]as$entry){
                $code=$entry['check']??'';
                if(($entry['status']??null)===CheckResult::FAIL && isset($messages[$code])){$actions[]=$messages[$code];}
            }}
            $citationMessages=[
                'parenthetical_authors_require_ampersand'=>'usar & entre dois autores na citação parentética',
                'narrative_authors_require_conjunction'=>'usar conjunção textual entre autores na narrativa, em lugar de &',
                'citation_spacing'=>'ajustar espaços após a vírgula e antes do parêntese do ano',
            ];
            foreach($e['analysis']['citation_style_samples']??[]as$sample){
                if(($sample['status']??null)!==CheckResult::FAIL){continue;}
                foreach($sample['issues']??[]as$code){if(isset($citationMessages[$code])){$actions[]=$citationMessages[$code];}}
            }
            if(($e['style_audit']['alphabetical_order_status']??null)===CheckResult::FAIL){$actions[]='reordenar as referências alfabeticamente';}
            if(($e['analysis']['closed_subset']??false) && ($e['analysis']['unresolved_citations']??0)>0){$actions[]='conferir as chaves autor–ano das citações não ligadas a referências';}
            $actions=array_values(array_unique($actions));
            return self::item('Referências e citações','Citações e referências devem seguir APA 7.',
                'Foram identificadas incompatibilidades comprovadas de formato ou correspondência entre citações e referências.',
                $actions ? 'Correções identificadas: '.implode('; ',$actions).'.' : 'Adequar os itens não conformes à APA 7 e conferir a correspondência autor–ano.');
        }
        return null;
    }

    private static function pending(array $check,string $reason): array
    {
        $location=$check['location'];
        $safe=preg_match('~^/(?:manuscript|(?:title|subtitle|abstract|keywords)/(?:pt_BR|en_US|es_ES)|authors(?:/[0-9]{1,8}/orcid)?)$~D',$location)===1;
        return ['rule_id'=>isset(self::VERSIONS[$check['rule_id']])?$check['rule_id']:null,
            'location'=>$safe?$location:null,'reason'=>$reason];
    }

    private static function item(string $area,string $requirement,string $finding,string $correction): array
    { return compact('area','requirement','finding','correction'); }
    private static function number($value): bool { return is_int($value) && $value>=0 && $value<=1000000000; }
    private static function interval(array $e): ?array
    {
        $a=$e['minimum_count']??null;$b=$e['maximum_count']??null;
        return self::number($a)&&self::number($b)&&$a<=$b?[$a,$b]:null;
    }
}
