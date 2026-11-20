"""Native administration enable/disable confirmation dialog."""
from common import *
b = None
try:
    b = browser.Browser()
    b.login('admin')
    b.get(base + '/management/settings/website#plugins')
    wait(b, 'return Array.from(document.querySelectorAll("tr")).some(x=>x.textContent.includes("Estrabão") && x.querySelector("input[type=checkbox]"))')
    selector = 'Array.from(document.querySelectorAll("tr")).find(x=>x.textContent.includes("Estrabão") && x.querySelector("input[type=checkbox]"))'
    b.js('let r=' + selector + ';r.scrollIntoView({block:"center"})')
    b.screenshot('plugin-enabled')
    ledgers = inspect()['ledgers']
    b.js('(' + selector + ').querySelector("input[type=checkbox]").click()')
    for _ in range(60):
        b.js('let c=Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.offsetParent!==null);if(c)c.click()')
        if not inspect()['enabled']:
            break
        time.sleep(0.1)
    check('administrator checkbox disables plugin', not inspect()['enabled'])
    b.js('(' + selector + ').querySelector("input[type=checkbox]").click()')
    for _ in range(60):
        b.js('let c=Array.from(document.querySelectorAll("button.pkpModalConfirmButton")).find(x=>x.offsetParent!==null);if(c)c.click()')
        if inspect()['enabled']:
            break
        time.sleep(0.1)
    check('administrator checkbox re-enables plugin', bool(inspect()['enabled']))
    check('UI toggles preserve histories', inspect()['ledgers'] == ledgers)
    (folder / 'ui-summary.json').write_text(json.dumps({'overall_passed': True, 'checks': len(results)}, indent=2))
finally:
    inspect('enable')
    if b:
        b.call('DELETE', '')
