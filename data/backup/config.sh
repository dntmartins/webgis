
# Reset das variáveis
unset MYSQL_USER
unset MYSQL_PASSWORD
unset MYSQL_BACKUP_NAME
unset NET_USER
unset NET_PASSWORD
unset APP_DIR
unset APP_IMG_DIR
unset APP_DOC_DIR
unset NAS_BKP_DIR
unset TEMP_DIR
unset DIA
unset MES
unset ANO
unset HORA
unset MINUTO
unset BACKUP_PREFIX
unset BACKUP_NAME

# MySQL - usuario e senha
MYSQL_USER='root'
MYSQL_PASSWORD='B5N8D11E14S17'
MYSQL_BACKUP_NAME='mysql.sql'

# Caminhos de Backup
# pasta onde estão os arquivos de backup
APP_DIR='/var/www/bndes/projetobndes/module/Upload/src/Upload/fileUploads'
APP_IMG_DIR='albuns'
APP_DOC_DIR='documents'
# Atenção!!
# Instalar o pacote "cifs-utils"
# Ex.: apt-get install cifs-utils
NAS_BKP_DIR=/home/bndes/nas-mount
REMOTE_PATH_NASGEO=//192.168.3.114/BackupSVN
TEMP_DIR='/tmp'

# Data e hora
DIA=$(date +%d)
MES=$(date +%m)
ANO=$(date +%Y)
HORA=$(date +%H)
MINUTO=$(date +%M)
SEGUNDO=$(date +%S)

# Prefixo do arquivo compactado do backup
BACKUP_PREFIX='portalbndes'

# Backup name
BACKUP_NAME=$BACKUP_PREFIX-$ANO-$MES-$DIA-$HORA-$MINUTO-$SEGUNDO

# Log
LOG_FILE=log-$ANO-$MES-$DIA-$HORA-$MINUTO-$SEGUNDO.txt
LOG_PATH='/home/bndes/bkp-logs'
