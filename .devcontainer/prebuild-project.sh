#!/bin/bash
set -e

curl -fsSL https://ddev.com/install.sh | bash
ddev start -y --skip-hooks
ddev composer install --no-interaction
ddev exec rm -f /var/tmp/logpipe # fixes randomly occurring "mkfifo: cannot create fifo '/var/tmp/logpipe': File exists"
ddev poweroff
