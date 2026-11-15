#!/usr/bin/env python3
"""
Builds the community icon subset that replaces the full dashicons stylesheet on community pages (review W2).

    python3 bin/build-community-icons.py <wordpress dir> <buddypress plugin dir> [<buddybuilder pro dir>]

Needs fonttools and brotli (pip install fonttools brotli). Collects every dashicons glyph that community pages can
show: `dashicons-<name>` classes in our templates and BuddyPress Nouveau's templates and scripts, and
`content: "\\fxxx"` in our CSS and BuddyPress Nouveau's CSS. Writes:
- assets/fonts/community-icons.woff2: those glyphs only, from WordPress' own dashicons font (GPLv2+).
- assets/css/community-icons.css: @font-face "dashicons" (same family name, so existing `font-family: dashicons`
  rules keep working), the `.dashicons` base rules and the `.dashicons-<name>:before` rules for the subset.
Run again after a WordPress or BuddyPress update changes the glyphs.
"""
import glob
import os
import re
import subprocess
import sys

root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
wp, bp = sys.argv[1], sys.argv[2]
pro = sys.argv[3] if len(sys.argv) > 3 else ''
nouveau = os.path.join(bp, 'bp-templates', 'bp-nouveau')

dash_css = open(os.path.join(wp, 'wp-includes', 'css', 'dashicons.css'), encoding='utf-8').read()
name_to_code = dict(re.findall(r'\.dashicons-([a-z0-9-]+):before\s*\{\s*content:\s*"\\(f[0-9a-f]{3})"', dash_css))

markup_files = []
for base in [os.path.join(root, 'templates', 'buddypress'), os.path.join(root, 'src', 'Elements'), os.path.join(root, 'core', 'widgets'),
             os.path.join(nouveau, 'buddypress'), os.path.join(nouveau, 'js'), os.path.join(nouveau, 'includes')]:
    markup_files += [f for f in glob.glob(base + '/**/*', recursive=True) if f.endswith(('.php', '.js')) and not f.endswith('.min.js')]
if pro:
    # Pro's front end (templates, widgets, scripts, styles), never its admin screens.
    pro_files = [f for f in glob.glob(pro + '/**/*', recursive=True) if '/node_modules/' not in f and '/admin/' not in f and '/tests/' not in f]
    markup_files += [f for f in pro_files if f.endswith(('.php', '.js')) and not f.endswith('.min.js')]
css_files = [f for f in pro_files if f.endswith('.css') and not f.endswith('.min.css')] if pro else []
css_files += glob.glob(os.path.join(root, 'templates', 'buddypress', 'assets', '*.css')) + glob.glob(os.path.join(root, 'assets', 'css', '*.css'))
css_files += [os.path.join(nouveau, 'css', 'buddypress.css'), os.path.join(nouveau, 'css', 'priority-nav.css')]

names = set()
for f in markup_files:
    names.update(re.findall(r'dashicons-([a-z0-9-]+)', open(f, encoding='utf-8', errors='ignore').read()))
names = {n for n in names if n in name_to_code}
codes = {name_to_code[n] for n in names}
for f in css_files:
    if os.path.basename(f).startswith(('admin', 'community-icons')) or not os.path.exists(f):
        continue
    css = open(f, encoding='utf-8', errors='ignore').read()
    if 'dashicons' in css:
        codes.update(re.findall(r'content:\s*"\\(f[0-9a-f]{3})"', css))

out_font = os.path.join(root, 'assets', 'fonts', 'community-icons.woff2')
os.makedirs(os.path.dirname(out_font), exist_ok=True)
subprocess.check_call([
    'pyftsubset', os.path.join(wp, 'wp-includes', 'fonts', 'dashicons.ttf'),
    '--unicodes=' + ','.join('U+' + c.upper() for c in sorted(codes)),
    '--flavor=woff2', '--output-file=' + out_font, '--no-hinting', '--desubroutinize',
])

lines = [
    '/* Community icons: the dashicons glyphs community pages use, built by bin/build-community-icons.py from WordPress\' dashicons font (GPLv2+). Replaces the full dashicons stylesheet for visitors. */',
    '@font-face{font-family:dashicons;src:url(../fonts/community-icons.woff2) format("woff2");font-weight:400;font-style:normal;font-display:block}',
    '.dashicons,.dashicons-before:before{font-family:dashicons;display:inline-block;line-height:1;font-weight:400;font-style:normal;speak:never;text-decoration:inherit;text-transform:none;text-rendering:auto;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;width:20px;height:20px;font-size:20px;vertical-align:top;text-align:center;transition:color .1s ease-in}',
]
for n in sorted(names):
    lines.append('.dashicons-%s:before{content:"\\%s"}' % (n, name_to_code[n]))
open(os.path.join(root, 'assets', 'css', 'community-icons.css'), 'w').write('\n'.join(lines) + '\n')
print('%d glyphs (%d named), %d bytes woff2' % (len(codes), len(names), os.path.getsize(out_font)))
