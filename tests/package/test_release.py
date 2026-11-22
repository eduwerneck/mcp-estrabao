"""Package boundary/integrity tests; no OJS or network required."""
import importlib.util, io, json, shutil, sys, tarfile, tempfile, unittest
from pathlib import Path
spec = importlib.util.spec_from_file_location('release', Path(__file__).resolve().parents[2] / 'tools/release.py')
r = importlib.util.module_from_spec(spec)
spec.loader.exec_module(r)

class ReleaseTest(unittest.TestCase):

    @classmethod
    def setUpClass(cls):
        cls.tmp = tempfile.TemporaryDirectory()
        cls.base = Path(cls.tmp.name)
        cls.original = cls.base / 'original'
        cls.info = r.build('HEAD', cls.original)

    @classmethod
    def tearDownClass(cls):
        cls.tmp.cleanup()

    def copy(self):
        d = self.base / self._testMethodName
        shutil.copytree(self.original, d)
        return d

    def resign(self, d, m):
        m['archives'] = {n: r.digest((d / n).read_bytes()) for n in ['plugin.tar.gz', 'engine.tar.gz']}
        (d / 'manifest.json').write_bytes(r.encode(m))
        (d / 'SHA256SUMS').write_text(''.join((r.digest((d / n).read_bytes()) + '  ' + n + '\n' for n in ['plugin.tar.gz', 'engine.tar.gz', 'manifest.json'])))

    def test_deterministic_from_git(self):
        other = self.base / 'again'
        r.build('HEAD', other)
        for p in self.original.iterdir():
            self.assertEqual(p.read_bytes(), (other / p.name).read_bytes())

    def test_template_timestamp_is_nonzero_and_from_commit(self):
        m = json.loads((self.original / 'manifest.json').read_text())
        with tarfile.open(self.original / 'plugin.tar.gz') as tf:
            self.assertTrue(all((t.mtime == m['source_epoch'] and t.mtime > 0 for t in tf)))

    def test_exact_product_inventory(self):
        m = json.loads((self.original / 'manifest.json').read_text())
        self.assertEqual(len(m['files']), 79)
        self.assertEqual((m['plugin_version'], m['engine_version']), ('0.42.0.1', '0.44.0'))
        for f in m['files']:
            self.assertEqual(r.digest(r.git('show', m['source_commit'] + ':' + f['source'])), f['sha256'])

    def test_refuse_overwrite(self):
        with self.assertRaises(ValueError):
            r.build('HEAD', self.original)

    def test_corrupt_archive(self):
        d = self.copy()
        (d / 'engine.tar.gz').write_bytes(b'corrupt')
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_symlink_release(self):
        d = self.copy()
        (d / 'plugin.tar.gz').unlink()
        (d / 'plugin.tar.gz').symlink_to(self.original / 'plugin.tar.gz')
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_missing_member(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        (d / 'plugin.tar.gz').write_bytes(r.archive({}))
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_unexpected_member(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        (d / 'plugin.tar.gz').write_bytes(r.archive({'estrabao/config.inc.php': b'secret'}))
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_manifest_traversal_even_with_matching_hashes(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        m['files'][0].update(path='estrabao-engine/src/../../escape.php', source='src/../../escape.php')
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_manifest_wrong_root(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        m['files'][0]['path'] = 'webroot/file.php'
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_tar_symlink(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        name = next((f['path'] for f in m['files'] if f['component'] == 'plugin'))
        with tarfile.open(d / 'plugin.tar.gz', 'w:gz') as tf:
            t = tarfile.TarInfo(name)
            t.type = tarfile.SYMTYPE
            t.linkname = '/etc/passwd'
            tf.addfile(t)
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_duplicate_manifest(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        m['files'].append(m['files'][0])
        self.resign(d, m)
        with self.assertRaises(ValueError):
            r.verify(d)

    def test_expanded_archive_limit(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        (d / 'engine.tar.gz').write_bytes(r.gzip.compress(b'\0' * (16 * 1024 * 1024 + 1)))
        self.resign(d, m)
        with self.assertRaisesRegex(ValueError, 'Expanded archive size limit'):
            r.verify(d)

    def test_zero_template_timestamp_rejected(self):
        d = self.copy()
        m = json.loads((d / 'manifest.json').read_text())
        with tarfile.open(d / 'plugin.tar.gz') as tf:
            files = {t.name: tf.extractfile(t).read() for t in tf}
        (d / 'plugin.tar.gz').write_bytes(r.archive(files, timestamp=0))
        self.resign(d, m)
        with self.assertRaisesRegex(ValueError, 'Unexpected archive metadata'):
            r.verify(d)

    def test_no_development_or_private_files(self):
        m = json.loads((self.original / 'manifest.json').read_text())
        for row in m['files']:
            self.assertFalse(any((x in row['source'].split('/') for x in ['tests', 'bin', 'docs', 'vendor', '.env', 'config.inc.php'])))
if __name__ == '__main__':
    unittest.main()
