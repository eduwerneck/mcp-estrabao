"""Check the running-target guard after the recovery flow, without modifying it."""
import json
import backup
try:
    backup.restore()
    raise AssertionError('Running target accepted')
except ValueError as error:
    assert 'stopped' in str(error)
(backup.BASE / 'quiescence-results.json').write_text(json.dumps([{'case':'restoration refuses a running application before mutation','passed':True}],indent=2))
print('PASS restore refuses running target')
