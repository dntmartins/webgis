#!/bin/bash

# pasta onde estão estes scripts, para garantir a execução pelo cron
SCRIPT_DIR='/var/www/bndes/projetobndes/data/backup'
cd $SCRIPT_DIR

source config.sh
source functions.sh

# Entra na pasta da aplicação
cd $APP_DIR
log "Iniciando o backup ${DIA}/${MES}/${ANO} ás ${HORA}:${MINUTO}:${SEGUNDO}"

# Backup do mysql
# Opções
# -x guarda o estado do banco, antes de gerar o dump. Evitando inconsistências
# -e melhora a performance da SQL gerada
# -A backup completo, todas as bases de dados são salvas
#
log "Gerando dump do MySQL"
mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD -x -e -A > $MYSQL_BACKUP_NAME

# Compacta todos os arquivos
log "Compactando ${$BACKUP_NAME.tar.gz}"
tar czf $BACKUP_NAME.tar.gz $APP_IMG_DIR $APP_DOC_DIR $MYSQL_BACKUP_NAME

# Delete arquivo de dump do MySQL depois de utilizado
log "Deletando arquivo ${$MYSQL_BACKUP_NAME}"
if [ -f $MYSQL_BACKUP_NAME ]; then
    rm -f $MYSQL_BACKUP_NAME
fi

# Monta a pasta do NAS no sistema de arquivos, usuário e senha estão no arquivo /etc/samba/user
log "Montando NAS"
mount -t cifs $REMOTE_PATH_NASGEO $NAS_BKP_DIR -o credentials=/etc/samba/user,noexec

# Remove a penultima versão do backup no NAS
log "Removendo a penúltima versão de backup"
removeOld $NAS_BKP_DIR

# Remove a penultima versão do backup local
log "Removendo a penúltima versão local"
removeOld $APP_DIR

# Copia para o NAS
log "Copiando para o NAS"
cp ./$BACKUP_NAME.tar.gz $NAS_BKP_DIR/

# Desmonta a partição do NAS
log "Desmontando NAS"
umount $NAS_BKP_DIR

log "Finalizando backup "$(date +%d)"/"$(date +%m)"/"$(date +%Y)" ás "$(date +%H)":"$(date +%M)":"$(date +%S)
