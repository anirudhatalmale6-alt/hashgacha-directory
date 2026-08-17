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

# Where the site goes, relative to wherever the FTP account lands on login.
#
# The default is "." because a per-site FTP account is normally jailed to that
# site's public_html already — its login directory IS the document root, and
# appending "public_html" buries the whole site one level too deep, where the
# web server serves a stale placeholder instead. A plan-wide account that lands
# a level higher needs FTP_DIR=public_html passed in explicitly.
#
# Do not guess: run "list" first and look at what is beside your upload.
remote_dir=${FTP_DIR:-.}
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
