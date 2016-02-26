#!/bin/bash
# load the variable value of the apache environment using a php bridge
APPLICATION_ENV=`curl -s --url http://localhost/getEnvironment.php`

# in development environment use the ip of the host (ex.: 192.168.3.103)
if [ $APPLICATION_ENV = "development" ]
then
        HOST=`hostname  -I | cut -f1 -d' '`
else
# in production environment use the domain name (ex.:www.funcate.org.br)
        HOST=www.funcate.org.br
fi

LOGFILE=/tmp/bndes-getmail.log

echo "[$(date)]" >> $LOGFILE
curl -s --url http://$HOST/email/getEmail >/dev/null 2>&1 >> $LOGFILE
echo -e "\n------------------------------------------------------------------------------------" >> $LOGFILE