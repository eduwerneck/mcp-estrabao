{include file="frontend/components/header.tpl" pageTitle="plugins.generic.estrabao.reviewTitle"}
<div class="page page_estrabao_review">
    <h1>{translate key="plugins.generic.estrabao.reviewTitle"}</h1>
    <p>{translate key="plugins.generic.estrabao.reviewScope"}</p>
    {if $reviewMessage}<p role="status">{translate key=$reviewMessage}</p>{/if}
    <form method="get" action="{$reviewUrl|escape}">
        {foreach from=$reviewLookupParams key=param item=value}
            <input type="hidden" name="{$param|escape}" value="{$value|escape}">
        {/foreach}
        <label for="estrabaoSubmissionId">{translate key="plugins.generic.estrabao.reviewSubmission"}</label>
        <input id="estrabaoSubmissionId" name="submissionId" type="number" min="1" max="999999999" required>
        <button type="submit">{translate key="plugins.generic.estrabao.reviewOpen"}</button>
    </form>
    {if $reviewModel}
        <h2>{translate key="plugins.generic.estrabao.reviewSubmission"} {$reviewModel.submission_id|escape}</h2>
        {if $reviewModel.analysis.decision == 'not_accepted'}
            <p>{translate key="plugins.generic.estrabao.apaNotAccepted"}</p>
        {elseif $reviewModel.release_active}
            <p>{translate key="plugins.generic.estrabao.reviewActive"}</p>
        {else}
            <p>{translate key="plugins.generic.estrabao.apaReviewRequired"}</p>
        {/if}
        <p>{translate key="plugins.generic.estrabao.reviewReadFile"}</p>
        {if $downloadReviewUrl}<p><a href="{$downloadReviewUrl|escape}">{translate key="plugins.generic.estrabao.reviewDownload"}</a></p>{/if}
        {if $reviewModel.analysis.reviewable}
        <form method="post" action="{$saveReviewUrl|escape}">
            {csrf}
            <input type="hidden" name="submissionId" value="{$reviewModel.submission_id|escape}">
            <input type="hidden" name="fingerprint" value="{$reviewModel.analysis.fingerprint|escape}">
            <input type="hidden" name="revision" value="{$reviewModel.revision|escape}">
            {if $reviewModel.analysis.reviewable}
                <label><input type="checkbox" name="apaReviewed" value="1"> {translate key="plugins.generic.estrabao.reviewDeclaration"}</label>
                <button type="submit" name="reviewAction" value="approve">{translate key="plugins.generic.estrabao.reviewApprove"}</button>
            {/if}
        </form>
        {/if}
        {if $reviewModel.revocation_fingerprint}
        <form method="post" action="{$saveReviewUrl|escape}">
            {csrf}
            <input type="hidden" name="submissionId" value="{$reviewModel.submission_id|escape}">
            <input type="hidden" name="fingerprint" value="{$reviewModel.revocation_fingerprint|escape}">
            <input type="hidden" name="revision" value="{$reviewModel.revision|escape}">
            <button type="submit" name="reviewAction" value="revoke">{translate key="plugins.generic.estrabao.reviewRevoke"}</button>
        </form>
        {/if}
        {if $reviewModel.analysis.fingerprint}
        <h3>{translate key="plugins.generic.estrabao.deskTitle"}</h3>
        <form method="post" action="{$deskDraftUrl|escape}">
            {csrf}
            <input type="hidden" name="submissionId" value="{$reviewModel.submission_id|escape}">
            <input type="hidden" name="fingerprint" value="{$reviewModel.analysis.fingerprint|escape}">
            <label for="estrabaoManuscriptType">{translate key="plugins.generic.estrabao.deskType"}</label>
            <select id="estrabaoManuscriptType" name="manuscriptType" required>
                <option value="">{translate key="plugins.generic.estrabao.deskChooseType"}</option>
                <option value="article">{translate key="plugins.generic.estrabao.deskArticle"}</option>
                <option value="research_communication">{translate key="plugins.generic.estrabao.deskCommunication"}</option>
            </select>
            <button type="submit">{translate key="plugins.generic.estrabao.deskGenerate"}</button>
        </form>
        {/if}
        {if $deskDraftModel}
        <section aria-labelledby="estrabaoDeskDraftTitle">
            <h3 id="estrabaoDeskDraftTitle">{translate key="plugins.generic.estrabao.deskTitle"}</h3>
            <p>{translate key="plugins.generic.estrabao.deskNotice"}</p>
            <p>{translate key="plugins.generic.estrabao.deskLanguage"}</p>
            <label for="estrabaoDeskSubject">{translate key="plugins.generic.estrabao.deskSubject"}</label>
            <input id="estrabaoDeskSubject" type="text" value="{$deskDraftModel.draft.subject|escape}" style="width:100%">
            <label for="estrabaoDeskBody">{translate key="plugins.generic.estrabao.deskBody"}</label>
            <textarea id="estrabaoDeskBody" rows="20" style="width:100%">{$deskDraftModel.draft.body|escape}</textarea>
            <button id="estrabaoCopyDesk" type="button">{translate key="plugins.generic.estrabao.deskCopy"}</button>
            <p id="estrabaoDeskCopyStatus" role="status" aria-live="polite"></p>
            <span id="estrabaoDeskCopySuccess" hidden>{translate key="plugins.generic.estrabao.deskCopied"}</span>
            <span id="estrabaoDeskCopyFallback" hidden>{translate key="plugins.generic.estrabao.deskCopyManual"}</span>
            <details>
                <summary>{translate key="plugins.generic.estrabao.deskInternal"} ({$deskDraftModel.draft.pending_review_count|escape})</summary>
                <p>{translate key="plugins.generic.estrabao.deskPendingNotice"}</p>
                <ul>
                {foreach from=$deskDraftModel.pending_groups key=group item=count}
                    <li>{translate key="plugins.generic.estrabao.$group"}: {$count|escape}</li>
                {foreachelse}
                    <li>{translate key="plugins.generic.estrabao.deskNoPending"}</li>
                {/foreach}
                </ul>
                {if $deskDraftModel.ethics_review}
                <section id="estrabaoEthicsReview">
                    <h4>{translate key="plugins.generic.estrabao.deskPendingEthics"}</h4>
                    <p>{translate key="plugins.generic.estrabao.{$deskDraftModel.ethics_review.message}"}</p>
                    <p>{translate key="plugins.generic.estrabao.ethicsReviewInstructions"}</p>
                    <ul>{foreach from=$deskDraftModel.ethics_review.samples item=sample}
                        <li>{translate key="plugins.generic.estrabao.ethicsReviewPosition"} {$sample.paragraph_index+1|escape}</li>
                    {/foreach}</ul>
                    {if $deskDraftModel.ethics_review.samples_omitted}<p>{translate key="plugins.generic.estrabao.ethicsReviewMore"} {$deskDraftModel.ethics_review.samples_omitted|escape}</p>{/if}
                </section>
                {/if}
                {if $deskDraftModel.funding_review}
                <p id="estrabaoFundingReview">{translate key="plugins.generic.estrabao.{$deskDraftModel.funding_review}"}</p>
                {/if}
                {if $deskDraftModel.footnote_review}
                <section id="estrabaoFootnoteReview">
                    <h4>{translate key="plugins.generic.estrabao.deskPendingFootnotes"}</h4>
                    <p>{translate key="plugins.generic.estrabao.footnoteReviewInstructions"}</p>
                    {if $downloadReviewUrl}<p><a href="{$downloadReviewUrl|escape}">{translate key="plugins.generic.estrabao.footnoteReviewDownload"}</a></p>{/if}
                    {if $deskDraftModel.footnote_review.inventory_complete}
                    <p>{translate key="plugins.generic.estrabao.footnoteReviewCount"} {$deskDraftModel.footnote_review.note_count|escape}</p>
                    <ul>
                    {foreach from=$deskDraftModel.footnote_review.samples item=note}
                        <li>{translate key="plugins.generic.estrabao.footnoteReviewCall"} {$note.reference_index+1|escape}.</li>
                    {/foreach}
                    </ul>
                    {if $deskDraftModel.footnote_review.samples_omitted}<p>{translate key="plugins.generic.estrabao.footnoteReviewMore"} {$deskDraftModel.footnote_review.samples_omitted|escape}</p>{/if}
                    {else}<p>{translate key="plugins.generic.estrabao.footnoteReviewUnresolved"}</p>{/if}
                    {if $deskDraftModel.footnote_review.endnote_indicators}<p>{translate key="plugins.generic.estrabao.footnoteReviewEndnotes"}</p>{/if}
                    <p>{translate key="plugins.generic.estrabao.footnoteReviewManual"}</p>
                </section>
                {/if}
            </details>
        </section>
        <script>
        {literal}
        (function () {
            var button = document.getElementById('estrabaoCopyDesk');
            button.addEventListener('click', function () {
                var body = document.getElementById('estrabaoDeskBody');
                var subject = document.getElementById('estrabaoDeskSubject');
                var status = document.getElementById('estrabaoDeskCopyStatus');
                var text = subject.value + '\n\n' + body.value;
                function fallback() {
                    body.focus(); body.select();
                    status.textContent = document.getElementById('estrabaoDeskCopyFallback').textContent;
                }
                if (!navigator.clipboard || !navigator.clipboard.writeText) { fallback(); return; }
                navigator.clipboard.writeText(text).then(function () {
                    status.textContent = document.getElementById('estrabaoDeskCopySuccess').textContent;
                }).catch(fallback);
            });
        }());
        {/literal}
        </script>
        {/if}
        <h3>{translate key="plugins.generic.estrabao.reviewHistory"}</h3>
        <ul>
        {foreach from=$reviewModel.events item=event}
            <li>{$event.display_time|escape} — {translate key="plugins.generic.estrabao.reviewEditor"} {$event.reviewer_id|escape}:
                {if $event.action == 'approve'}{translate key="plugins.generic.estrabao.reviewApproved"}{else}{translate key="plugins.generic.estrabao.reviewRevoked"}{/if}
            </li>
        {foreachelse}
            <li>{translate key="plugins.generic.estrabao.reviewNoHistory"}</li>
        {/foreach}
        </ul>
    {/if}
</div>
{include file="frontend/components/footer.tpl"}
