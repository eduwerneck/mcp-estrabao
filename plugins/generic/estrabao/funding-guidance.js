(function () {
    'use strict';
    function attach() {
        document.querySelectorAll('[id^="agencies-localization-popover-container-"]').forEach(function (field) {
            var parent = field.closest('.section') || field.parentElement;
            if (!parent || parent.querySelector('.estrabaoFundingGuidance')) { return; }
            var hint = document.createElement('p');
            hint.className = 'estrabaoFundingGuidance';
            hint.textContent = 'Informe as agências e os recursos recebidos. Se não houve apoio, inclua “Sem financiamento”.';
            parent.appendChild(hint);
        });
    }
    new MutationObserver(attach).observe(document.documentElement, {childList:true,subtree:true});
    attach();
}());
