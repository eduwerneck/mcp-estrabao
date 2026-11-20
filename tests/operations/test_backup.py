"""Offline integrity/boundary tests. No Docker, network or editorial data."""
import io, json, sys, tarfile, tempfile, unittest
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'lab/operations'))
import backup as b

class BackupTest(unittest.TestCase):

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.root = Path(self.tmp.name)
        self.make()

    def tearDown(self):
        self.tmp.cleanup()

    def archive(self, root, name=None, kind=None):
        buf = io.BytesIO()
        with tarfile.open(fileobj=buf, mode='w') as tf:
            t = tarfile.TarInfo(name or root + '/synthetic.txt')
            t.size = 4
            t.uid = 100
            t.gid = 101
            t.mode = 0o600
            if kind is not None:
                t.type = kind
                t.linkname = '/etc/passwd'
                t.size = 0
            tf.addfile(t, io.BytesIO(b'data') if kind is None else None)
        return buf.getvalue()

    def make(self):
        for n, raw in [('database.sql', b'-- synthetic only'), ('files.tar', self.archive('files')), ('public.tar', self.archive('public')), ('config.inc.php', b'; synthetic config'), ('state.json', b'{}')]:
            (self.root / n).write_bytes(raw)
            (self.root / n).chmod(0o600)
        self.resign()

    def resign(self):
        m = {'schema': 1, 'quiesced': True, 'ojs': '3.3.0.20', 'plugin': '0.39.0.0', 'engine': '0.44.0', 'lab': 'estrabao-lab-62', 'components': {n: {'sha256': b.sha((self.root / n).read_bytes()), 'size': (self.root / n).stat().st_size} for n in b.COMPONENTS}, 'inventory': {n: b.inventory((self.root / (n + '.tar')).read_bytes(), n) for n in ['files', 'public']}}
        (self.root / 'manifest.json').write_text(json.dumps(m))
        (self.root / 'manifest.json').chmod(0o600)

    def test_complete_private_backup(self):
        self.assertEqual(b.verify(self.root)['schema'], 1)

    def test_corrupt_sql_rejected_before_docker(self):
        (self.root / 'database.sql').write_bytes(b'corrupt')
        old = b.docker

        def unexpected(*a, **kw):
            raise AssertionError('Docker must not run')
        b.docker = unexpected
        try:
            with self.assertRaisesRegex(ValueError, 'checksum'):
                b.restore(self.root)
        finally:
            b.docker = old

    def test_missing_component(self):
        (self.root / 'files.tar').unlink()
        with self.assertRaises(ValueError):
            b.verify(self.root)

    def test_world_readable_sql(self):
        (self.root / 'database.sql').chmod(0o644)
        with self.assertRaises(ValueError):
            b.verify(self.root)

    def test_world_readable_manifest(self):
        (self.root / 'manifest.json').chmod(0o644)
        with self.assertRaises(ValueError):
            b.verify(self.root)

    def test_symlink_component(self):
        (self.root / 'database.sql').unlink()
        (self.root / 'database.sql').symlink_to('/etc/passwd')
        with self.assertRaises(ValueError):
            b.verify(self.root)

    def test_traversal_tar(self):
        with self.assertRaises(ValueError):
            b.inventory(self.archive('files', 'files/../../escape'), 'files')

    def test_link_tar(self):
        with self.assertRaises(ValueError):
            b.inventory(self.archive('files', kind=tarfile.SYMTYPE), 'files')

    def test_wrong_archive_root(self):
        with self.assertRaises(ValueError):
            b.inventory(self.archive('public'), 'files')

    def test_mismatched_engine_version(self):
        path = self.root / 'manifest.json'
        manifest = json.loads(path.read_text())
        manifest['engine'] = '0.42.0'
        path.write_text(json.dumps(manifest))
        with self.assertRaisesRegex(ValueError, 'versions'):
            b.verify(self.root)

    def test_incomplete_manifest(self):
        p = self.root / 'manifest.json'
        m = json.loads(p.read_text())
        del m['components']['database.sql']
        p.write_text(json.dumps(m))
        with self.assertRaises(ValueError):
            b.verify(self.root)
if __name__ == '__main__':
    unittest.main()
