# Popular o banco BNDES com a carga inicial de trabalho usando o arquivo de insers.sql
# Exemplo no servidor de produção
mysql -u root -pB5N8D11E14S17 -e "source /var/www/bndes/projetobndes/database/script/inserts.sql" BNDES --default-character-set=UTF8

