#!/bin/bash
# Builds bridge/openwp-bridge.zip (folders 755, files 644), ready to upload in WordPress.
cd "$(dirname "$0")" || exit 1
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
