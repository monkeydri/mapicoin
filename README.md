# Mapicoin

# setup

## run mysql and pyhpmyadmin

- setup DB root name and password in .env file

- run `docker-compose up db pma`

## create user

### via phpmyadmin

- go to localhost:1789
- login with credentials defined in `.env` file
- click on database mapicoin on left
- go to privileges
- input user : `mapicoin` and desired password
- select grant all privileges on db
- check all permission
- click go

#### bug unknown authentication method 

#2054 - The server requested authentication method unknown to the client
mysqli_real_connect(): The server requested authentication method unknown to the client [caching_sha2_password]
mysqli_real_connect(): (HY000/2054): The server requested authentication method unknown to the client

##### solution 1 : change authentication method via mysql CLI

`docker-compose exec db bash`

login by CLI : `mysql -u root -p` => enter password (by default `secret`, change it in `.env` file).

change authentication method : `ALTER USER root IDENTIFIED WITH mysql_native_password BY 'PASSWORD';`

exit : `exit` and `exit`

restart : `docker-compose restart db`

source : https://stackoverflow.com/questions/49948350/phpmyadmin-on-mysql-8-0

##### solution 2 : use older mysql container

`image: mysql:5.7` in `docker-compose.yml`

### via CLI

CREATE USER mapicoin@localhost IDENTIFIED WITH mysql_native_password BY 'pleasechangeme';

## set database credentials for web server

- copy sample parameters.json : `cp inc/parameters.sample.json inc/parameters.json`

in `parameters.json` :

- change login and password to match those used while creating user (normally login is mapicoin).
- set db hostname. by default it is `db`, the name of the docker service.

if db hostname is set incorrectly, exception on web server will be thrown : `Call to a member function set_charset() on boolean`

real error is `Connect failed: No such file or directory`.

error seen when adding

```
if (mysqli_connect_errno())
{  
	printf("Connect failed: %s\n", mysqli_connect_error()); 
	exit();
}
```

before `$_MYSQLI->set_charset($_CONFIG->mysql->charset);`

source : https://stackoverflow.com/questions/43911570/call-to-a-member-function-set-charset-on-boolean-in-home1-ksuexpre-public-htm


## set google API keys

Need to set 2 Google API keys : client and server.

> - une "cliente" :  pour afficher la carte dans l'application
> - une "serveur" : pour autoriser l'application à faire des requêtes de géolocalisation inversée pour chopper les coordonnées GPS depuis une adresse.

source : https://forum.hardware.fr/hfr/Discussions/Viepratique/mapicoin-recherches-leboncoin-sujet_114240_3.htm

The same key should be used for both since a change in Google Mapis API => there is no browser and server keys anymore but only one API key.

source : https://stackoverflow.com/a/33922945

- generate a key on google website

- set it for `google_server_key` and `google_browser_key` in `parameters.json`

## run web server

- set desired `ServerName` in file docker/vhost.conf

- start webserver : `docker-compose up web`

- go to url defined in `ServerName` with port defined in `docker-compose.yml`, ex : http://mapicoin.example.com:8080

