#!/bin/sh
#
# Upload the built site to shared hosting over FTP.
#
# Credentials are never stored in this repository — pass them in the
# environment:
#
#   FTP_HOST=...  FTP_USER=...  FTP_PASS=...  ./tools/upload_ftp.sh list
#   FTP_HOST=...  FTP_USER=...  FTP_PASS=...  ./tools/upload_ftp.sh upload
#
# list    shows what is already on the server and changes nothing.
# upload  copies build/deploy into the remote folder, leaving any file that
#         is already there and is not part of the site untouched. Nothing is
#         ever deleted; clearing the folder out is a deliberate, separate act.
#
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
local_dir="$root/build/deploy"
remote_dir=${FTP_DIR:-public_html}
mode=${1:-list}

for var in FTP_HOST FTP_USER FTP_PASS; do
    eval "value=\${$var:-}"
    if [ -z "$value" ]; then
        echo "$var is not set — see the comment at the top of this script." >&2
        exit 1
    fi
done

if [ ! -d "$local_dir" ]; then
    echo "nothing built yet: $local_dir does not exist" >&2
    echo "run: php tools/build_deploy.php" >&2
    exit 1
fi

# Hostinger presents a certificate for its own hostname rather than the
# per-site one, so verification is turned off while encryption stays on.
common="set ftp:ssl-allow true
set ftp:ssl-protect-data true
set ssl:verify-certificate no
set ftp:ssl-force true
set net:max-retries 2
set net:timeout 20
open -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\""

case "$mode" in
    list)
        lftp -c "$common
cls -l --sort=name \"$remote_dir\" || echo '(no such folder)'
find \"$remote_dir\" | head -200"
        ;;
    upload)
        lftp -c "$common
mirror --reverse --parallel=4 --verbose \"$local_dir\" \"$remote_dir\""
        ;;
    *)
        echo "usage: $0 [list|upload]" >&2
        exit 1
        ;;
esac
