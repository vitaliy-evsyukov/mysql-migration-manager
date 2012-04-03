[main]
host=db-master.local
user=user
password=12345
db=test_db
cachedir=data/cache
savedir=data/migrations
datasetsdir=data/datasets
schemadir=data/schema
reqtables=tables.json
reqdata=data.sql
versionfile=revisions.txt
version_marker=marker.txt
verbose=3
mysqldiff_command="mysqldiff --logs-folder=/var/web/mysql-migration-manager/logs"
prefix=username

[tmpdb]
tmp_host=localhost
tmp_user=test
tmp_password=12345