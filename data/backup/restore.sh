#!/bin/bash

source config.sh

# monta partição no nas
smbmount $REMOTE_PATH_NASGEO $NAS_BKP_DIR -o user=$NET_USER,password=$NET_PASSWORD

# Entra na pasta da aplicação
cd $APP_DIR

# Descompacta
tar xf $NAS_BKP_DIR/$1

# Desmonta a partição do NAS
umount $NAS_BKP_DIR

# restaura banco
mysqlimport -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_BACKUP_NAME

# Restaura permissões das pastas
chown -R www-data:www-data $APP_DOC_DIR $APP_DOC_DIR
