#Dependências

##DBASE
* sudo apt-get install php-pear
* sudo apt-get install php5-dev
* sudo pecl install dbase
* php.ini => extension=dbase.so

##GDAL
sudo apt-get install python-gdal

##Curl
sudo apt-get install curl php5-curl

##Mysql
sudo apt-get install php5-mysql mysql-server-5.5

##POSTGRES
São dois cenários possíveis.

###Postgres e Postgis (ATENÇÃO: caso o servidor de dados geográficos fique no mesmo servidor da aplicação)
sudo apt-get install postgresql-9.x
sudo apt-get install postgis
sudo apt-get install postgresql-9.x-postgis-2.1

###Postgres e Postgis (ATENÇÃO: caso o servidor de dados geográficos NÃO fique no mesmo servidor da aplicação)
sudo apt-get install postgis

###Biblioteca Postgres para PHP
sudo apt-get install php5-pgsql

####Instalação limpa

Você provavelmente quer deixar o servidor de aplicação limpo, livre de serviços e aquivos desnecessário.
Para supri a dependência do sistema, que é do executável "shp2pgsql" apenas. Instale o pacote postgis, copie o executável
shp2pgsql para uma pasta pessoal, remova o pacote postgis usando: apt-get purge postgis, e em seguida mova o executável shp2pgsql
para a pasta original, provavelmente em /usr/bin/

Outra dependência é o executável "psql", e pode ser instalado o cliente para o postgres. Atente para a versão disponível.

sudo apt-get install postgresql-client-9.x

#Configuração do Doctrine
	<?php
	return array(
		'doctrine' => array(
				'connection' => array(
						// default connection name
						'orm_default' => array(
								'driverClass' => 'Doctrine\DBAL\Driver\PDOMySql\Driver',
								'params' => array(
										'host'     => '127.0.0.1',
										'port'     => '3306',
										'user'     => 'root',
										'password' => 'senha',
										'dbname'   => 'GEODATA',
										'driverOptions' => array(
												PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
										)
								)
						)
				),
		)
	);

#Global.php
	<?php
	return array(
		'view_manager' => array(
				'base_path' => '/'
		)
	);
	
#System.config.php
##Arquivo de configurações do sistema com os dados das conexões do geoserver e do postgis localizado em: module/Main/config/
	<?php
	return array(
		"geoserver" => array(
			"login" => "admin",
			"password" => "geoserver",
			"host" => "localhost:8080"
		),
		"datasource" => array(
			"dbName" => "postgres",
			"login" => "postgres",
			"password" => "postgres",
			"host" => "localhost",
			"port" => 5432
		)
	);
	
#Permissão para arquivos
1 - Ir para o diretorio do composer.json. 
2 - Executar os comandos: 
	sudo composer upload:clean (Caso já existam as pastas)
	sudo composer upload:create

#Permissão PostGIS - Não mais necessário
1 - Abrir arquivo /etc/postgresql/9.3/main/pg_hba.conf
	Editar:
		IPv4 local connections:
		host    all             all             127.0.0.1/32            md5
	Para:
		host    all             all             127.0.0.1/32            trust
2 - Reiniciar o serviço do Postgres

#Setar variável de ambiente para usuário do apache em ambiente Ubuntu 14.04
Editar /etc/apache2/envvars
Inserir no final do arquivo: export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games:/usr/local/games:/usr/lib/jvm/java-8-oracle/bin:/usr/lib/jvm/java-8-oracle/db/bin:/usr/lib/jvm/java-8-oracle/jre/bin:/opt/geogig/bin"

