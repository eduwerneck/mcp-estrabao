"""Render the actual native metadata form captured by the synthetic HTTP rehearsal."""
import json
from pathlib import Path
from playwright.sync_api import sync_playwright
root=Path(__file__).resolve().parents[3]
with sync_playwright() as p:
    browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
    page=browser.new_page()
    page.route('**/*',lambda route:route.abort())
    page.set_content(Path('/workspace/scratch/estrabao-lab-63/automatic-metadata.html').read_text())
    script=str(root/'plugins/generic/estrabao/funding-guidance.js')
    page.add_script_tag(path=script)
    assert page.locator('.estrabaoFundingGuidance').count()==1
    assert 'Sem financiamento' in page.locator('.estrabaoFundingGuidance').inner_text()
    page.add_script_tag(path=script)
    assert page.locator('.estrabaoFundingGuidance').count()==1
    assert page.locator('textarea[name="citationsRaw"]').count()==1
    browser.close()
(root/'docs/evidence/stage66/guidance-results.json').write_text(json.dumps({'funding_hint_visible':True,'no_duplicate_hint':True,'native_bibliography_field_present':True})+'\n')
print('PASS native form displays funding guidance once and includes bibliography')
