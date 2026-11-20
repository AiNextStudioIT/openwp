#!/bin/bash
# Builds bridge/openwp-bridge.zip (folders 755, files 644), ready to upload in WordPress.
# It first copies the skills and generates the tool list from the OpenWP server, so the plugin and the server stay in step.
set -e
cd "$(dirname "$0")"
ROOT="$(pwd)/.."
rm -rf openwp-bridge/skills && cp -R "$ROOT/skills" openwp-bridge/skills
(cd "$ROOT" && OPENWP_EXPORT_TOOLS=1 WP_URL=https://build.test WP_USERNAME=build WP_APP_PASSWORD=build node index.js) > openwp-bridge/mcp-tools.json
rm -f openwp-bridge.zip
python3 - <<'P'
import zipfile, os
root = 'openwp-bridge'
with zipfile.ZipFile('openwp-bridge.zip', 'w', zipfile.ZIP_DEFLATED) as z:
    dirs = {root}
    for dp, dn, fn in os.walk(root):
        for d in dn:
            dirs.add(os.path.join(dp, d))
    for d in sorted(dirs):
        zi = zipfile.ZipInfo(d + '/', date_time=(2026, 1, 1, 0, 0, 0)); zi.external_attr = (0o40755 << 16) | 0x10; z.writestr(zi, '')
    for dp, dn, fn in os.walk(root):
        for f in sorted(fn):
            p = os.path.join(dp, f); zi = zipfile.ZipInfo(p, date_time=(2026, 1, 1, 0, 0, 0)); zi.external_attr = 0o100644 << 16; zi.compress_type = zipfile.ZIP_DEFLATED; z.writestr(zi, open(p, 'rb').read())
print('openwp-bridge.zip ready')
P
