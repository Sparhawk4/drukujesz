### Setup project
1. Replace nginx/files/.htpasswd with your own (default is `bacia/dupa04!@`)
2. Replace nginx/files/cors_support hostnames with hostnames you are going to use
3. Add your presta files to backend/presta and remove .keep file (or you need it?)
4. You probably want to use backend/parameters.php.template for your presta app/config/parameters.php file - it uses local envs for local development and hardcoded config for so called `ftp production`
5. To run the project first create the volume `docker volume create --name=presta_db_data` and later run the container `docker-compose up --force-recreate --build` (make sure it refresh the cache)
