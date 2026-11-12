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
