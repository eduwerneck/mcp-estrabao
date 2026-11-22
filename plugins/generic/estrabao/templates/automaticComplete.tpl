<h2>{translate key="submission.submit.submissionComplete"}</h2>
<section aria-labelledby="estrabaoAutomaticResult">
    <h3 id="estrabaoAutomaticResult">{if $automaticTriage.outcome == 'declined'}{translate key="plugins.generic.estrabao.autoDeclined"}{else}{translate key="plugins.generic.estrabao.autoPending"}{/if}</h3>
    <p>{translate key="plugins.generic.estrabao.autoMailStatus"}: {if $automaticTriage.mail == 'sent'}{translate key="plugins.generic.estrabao.autoMailSent"}{elseif $automaticTriage.mail == 'failed'}{translate key="plugins.generic.estrabao.autoMailFailed"}{else}{translate key="plugins.generic.estrabao.autoMailPending"}{/if}</p>
    <pre style="white-space: pre-wrap">{$automaticTriage.body|escape}</pre>
</section>
<p><a href="{$reviewSubmissionUrl|escape}">{translate key="submission.submit.whatNext.review"}</a></p>
