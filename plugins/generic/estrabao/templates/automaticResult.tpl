<section aria-labelledby="estrabaoAutomaticResult">
    <h2 id="estrabaoAutomaticResult">{if $automaticTriage.outcome == 'declined'}{translate key="plugins.generic.estrabao.autoDeclined"}{else}{translate key="plugins.generic.estrabao.autoPending"}{/if}</h2>
    <p>{translate key="plugins.generic.estrabao.autoMailStatus"}: {if $automaticTriage.mail == 'sent'}{translate key="plugins.generic.estrabao.autoMailSent"}{elseif $automaticTriage.mail == 'failed'}{translate key="plugins.generic.estrabao.autoMailFailed"}{else}{translate key="plugins.generic.estrabao.autoMailPending"}{/if}</p>
    <pre style="white-space: pre-wrap">{$automaticTriage.body|escape}</pre>
</section>
