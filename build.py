#!/usr/bin/env python3
"""
Tidewrack (mod_tidewrack) build script — Chaotic Fish Design Studio.

    python3 build.py                 build, then release through the CFD update server
    python3 build.py --no-release    build only

Steps:
  1. Read the version from mod_tidewrack.xml and check it matches every other place it appears
     (Dispatcher asset version, CSS and JS source headers, README, CHANGELOG entry).
  2. Check CSS property order (alphabetical within each rule; @keyframes skipped).
  3. Minify tidewrack.css / tidewrack.js into the .min files the site loads (keeps the /*! header).
     Uses terser and csso when they're on the PATH (as Wayfin's build does), else rjsmin/rcssmin.
  4. Render the standalone demo from the real template (demo/render.php + demo/shell.html, needs PHP),
     with the minified CSS/JS and every image inlined, so it's one self-contained file.
  5. Write dist/mod_tidewrack-<version>.zip (install), dist/mod_tidewrack-<version>-source.zip
     (everything, including this script and demo/) and dist/tidewrack-demo.html.
  6. Call cfd_update.release('mod_tidewrack', <install zip>) from the shared generator, found through
     the CFD_UPDATES environment variable (default: sibling folder ../cfd-updates).

Needs Python 3.8+, PHP 8.2+ for the demo, and either terser + csso (npm i -g terser csso-cli)
or the pure-Python minifiers (pip install rjsmin rcssmin).
"""

import base64
import json
import os
import re
import shutil
import subprocess
import sys
import zipfile
from pathlib import Path
from xml.etree import ElementTree

ROOT = Path(__file__).resolve().parent
DIST = ROOT / 'dist'
ELEMENT = 'mod_tidewrack'

# Install zip: what Joomla needs. The demo and this script go in the source zip only.
INSTALL = [
    'mod_tidewrack.xml', 'script.php', 'services', 'src', 'tmpl', 'language', 'media',
    'README.md', 'CHANGELOG.md', 'LICENSE.txt',
]
SKIP_NAMES = {'.DS_Store', 'Thumbs.db', '__pycache__'}


class BuildError(Exception):
    pass


def read(rel):
    return (ROOT / rel).read_text(encoding='utf-8')


def write(rel, text):
    (ROOT / rel).write_text(text, encoding='utf-8')


def manifest_version():
    tree = ElementTree.parse(ROOT / 'mod_tidewrack.xml')
    root = tree.getroot()
    if root.get('type') != 'module' or root.get('client') != 'site':
        raise BuildError('Manifest is not a site module.')
    if root.find('updateservers') is None:
        raise BuildError('Manifest has no <updateservers>; sites would never be offered updates.')
    if root.findtext('scriptfile') != 'script.php':
        raise BuildError('Manifest does not name script.php as its <scriptfile>.')
    version = (root.findtext('version') or '').strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+', version):
        raise BuildError('Manifest version is missing or not x.y.z: %r' % version)
    return version


def check_versions(v):
    checks = {
        'src/Dispatcher/Dispatcher.php': r"\$ver\s*=\s*'([^']+)'",
        'media/css/tidewrack.css': r'Tidewrack (\d+\.\d+\.\d+)',
        'media/js/tidewrack.js': r'Tidewrack (\d+\.\d+\.\d+)',
        'README.md': r'Version (\d+\.\d+\.\d+)',
    }
    bad = []
    for rel, pattern in checks.items():
        m = re.search(pattern, read(rel))
        if not m or m.group(1) != v:
            bad.append('%s says %s' % (rel, m.group(1) if m else 'nothing'))
    if not re.search(r'^## ' + re.escape(v) + r'\b', read('CHANGELOG.md'), re.M):
        bad.append('CHANGELOG.md has no "## %s" entry' % v)
    if bad:
        raise BuildError('Version %s does not match everywhere:\n  ' % v + '\n  '.join(bad))


def check_css_order():
    css = re.sub(r'/\*.*?\*/', '', read('media/css/tidewrack.css'), flags=re.S)
    css = re.sub(r'@keyframes[^{]*\{(?:[^{}]*\{[^}]*\})*\s*\}', '', css)
    bad = []
    for selector, body in re.findall(r'([^{}]+)\{([^{}]*)\}', css):
        names = [p.split(':', 1)[0].strip() for p in re.split(r';(?![^(]*\))', body) if p.strip()]
        if names != sorted(names):
            bad.append(selector.strip()[:80])
    if bad:
        raise BuildError('CSS properties out of alphabetical order in:\n  ' + '\n  '.join(bad))


def header(text):
    m = re.match(r'\s*(/\*!.*?\*/)', text, re.S)
    return m.group(1) + '\n' if m else ''


def minify():
    css = read('media/css/tidewrack.css')
    js = read('media/js/tidewrack.js')
    body_css = re.sub(r'^\s*/\*!.*?\*/', '', css, count=1, flags=re.S)
    body_js = re.sub(r'^\s*/\*!.*?\*/', '', js, count=1, flags=re.S)
    terser, csso = shutil.which('terser'), shutil.which('csso')
    if terser and csso:
        min_js = subprocess.run([terser, '-c', '-m'], input=body_js, capture_output=True, text=True, check=True).stdout
        min_css = subprocess.run([csso, '--no-restructure'], input=body_css, capture_output=True, text=True, check=True).stdout
        tool = 'terser + csso'
    else:
        try:
            import rcssmin
            import rjsmin
        except ImportError:
            raise BuildError('No minifier. Run: npm i -g terser csso-cli   (or: pip install rjsmin rcssmin)')
        min_js, min_css, tool = rjsmin.jsmin(body_js), rcssmin.cssmin(body_css), 'rjsmin + rcssmin'
    write('media/css/tidewrack.min.css', header(css) + min_css.strip() + '\n')
    write('media/js/tidewrack.min.js', header(js) + min_js.strip() + '\n')
    return tool


def data_uri(rel, mime):
    return 'data:%s;base64,%s' % (mime, base64.b64encode((ROOT / rel).read_bytes()).decode('ascii'))


IMAGES = {
    'cleaner-wrasse.svg': ('wrasse', 'image/svg+xml'),
    'kokido-peek--240.webp': ('kokido', 'image/webp'),
    'hawke.svg': ('hawke', 'image/svg+xml'),
    'tancho.svg': ('tancho', 'image/svg+xml'),
    'tancho-face.webp': ('tancho-face', 'image/webp'),
    'ziggy-face.webp': ('ziggy-face', 'image/webp'),
}


def render_demo(version):
    php = shutil.which('php')
    if not php:
        print('Demo: PHP not found; kept the existing tidewrack-demo.html.')
        return
    run = lambda *a: subprocess.run([php, str(ROOT / 'demo' / 'render.php')] + list(a),
                                    capture_output=True, text=True, check=True).stdout
    module = run('{}')
    art = json.loads(run('--art'))
    uris = {name: data_uri('media/images/' + name, mime) for name, (_, mime) in IMAGES.items()}
    for name, uri in uris.items():
        module = module.replace('MEDIA/images/' + name, uri)
    attrs = ''.join(' data-twk-img-%s="%s"' % (key, uris[name]) for name, (key, _) in IMAGES.items())
    module = module.replace('data-twk-media="MEDIA/"', 'data-twk-media=""' + attrs, 1)
    if 'MEDIA/' in module:
        raise BuildError('Demo still has an un-inlined MEDIA/ path.')
    html = read('demo/shell.html')
    for key, value in (('CSS', read('media/css/tidewrack.min.css').strip()),
                       ('JS', read('media/js/tidewrack.min.js').strip()),
                       ('ART', json.dumps(art)), ('VERSION', version), ('MODULE', module)):
        html = html.replace('{{%s}}' % key, value)
    write('tidewrack-demo.html', html)


def walk(rel):
    path = ROOT / rel
    if path.is_file():
        yield path
        return
    for p in sorted(path.rglob('*')):
        if p.is_file() and not (set(p.relative_to(ROOT).parts) & SKIP_NAMES) and not p.name.startswith('.'):
            yield p


def make_zip(target, entries):
    target.parent.mkdir(exist_ok=True)
    if target.exists():
        target.unlink()
    with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for rel in entries:
            for p in walk(rel):
                z.write(p, p.relative_to(ROOT).as_posix())
    with zipfile.ZipFile(target) as z:
        bad = z.testzip()
        if bad:
            raise BuildError('Zip check failed on %s' % bad)


def release(zip_path):
    base = os.environ.get('CFD_UPDATES') or str(ROOT.parent / 'cfd-updates')
    if not (Path(base) / 'cfd_update.py').is_file():
        print('Update server: cfd_update.py not found at %s; skipped. '
              'Set CFD_UPDATES, or run it from the update-server chat with this zip.' % base)
        return
    sys.path.insert(0, base)
    import cfd_update  # noqa: E402
    try:
        cfd_update.release(ELEMENT, str(zip_path))
    except getattr(cfd_update, 'Fail', Exception) as e:
        print('Update server: not released (%s). The zip is built; release it once that is fixed.' % e)


def main():
    v = manifest_version()
    check_versions(v)
    check_css_order()
    tool = minify()
    render_demo(v)

    install = DIST / ('%s-%s.zip' % (ELEMENT, v))
    source = DIST / ('%s-%s-source.zip' % (ELEMENT, v))
    make_zip(install, INSTALL)
    make_zip(source, INSTALL + ['build.py', 'demo', 'tidewrack-demo.html'])
    (DIST / 'tidewrack-demo.html').write_bytes((ROOT / 'tidewrack-demo.html').read_bytes())

    kb = install.stat().st_size / 1024
    print('Minified with %s' % tool)
    print('Built %s (%.0f KB)' % (install.name, kb))
    print('Built %s and dist/tidewrack-demo.html' % source.name)
    print('Tools page: %.0f KB · PHP 8.2+ · Joomla 5 and 6' % kb)

    if '--no-release' not in sys.argv:
        release(install)


if __name__ == '__main__':
    try:
        main()
    except BuildError as e:
        sys.exit('Build stopped: %s' % e)
