"""Funding help in actual OJS metadata HTML captured in the isolated synthetic lab."""
import json
from pathlib import Path
from playwright.sync_api import sync_playwright
ROOT=Path(__file__).resolve().parents[3]
private=Path('/workspace/scratch/estrabao-lab-63')
results=[]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
    for case,source in [('single_locale','single-metadata.html'),('multiple_locales','automatic-metadata.html')]:
        page=browser.new_page();page.route('**/*',lambda route:route.abort())
        html=(private/source).read_text();page.set_content(html)
        if case=='single_locale':
            assert page.locator('[id^="agencies-localization-popover-container-"]').count()==0
        page.add_script_tag(path=str(ROOT/'plugins/generic/estrabao/funding-guidance.js'))
        hint=page.locator('.estrabaoFundingGuidance');assert hint.count()==1 and hint.is_visible()
        assert 'Sem financiamento' in hint.inner_text()
        assert page.locator('.section').filter(has=hint).locator('ul[id*="-agencies-"], ul[id^="agencies-"]').count()>0
        assert page.locator('textarea[name="citationsRaw"]').input_value()==''
        page.add_script_tag(path=str(ROOT/'plugins/generic/estrabao/funding-guidance.js'))
        assert hint.count()==1
        # Wizard content is replaced by AJAX when switching steps.
        page.locator('body').evaluate('(el,html)=>el.innerHTML=html',html)
        page.wait_for_function('document.querySelectorAll(".estrabaoFundingGuidance").length === 1')
        assert page.locator('.estrabaoFundingGuidance').is_visible()
        results.append({'case':case,'visible_help':True,'native_field_section':True,'no_duplicate':True,'ajax_replacement':True,'bibliography_unchanged':True})
        print('PASS '+case,flush=True);page.close()
    browser.close()
out=ROOT/'docs/evidence/stage67';out.mkdir(parents=True,exist_ok=True)
(out/'funding-layout.json').write_text(json.dumps(results,indent=2)+'\n')
