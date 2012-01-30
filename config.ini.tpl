[main]
host=db-master.local
user=pasha
password=SZDFCs#21
db=default_sm
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
tmp_host=db-dev.sotmarket.ru
tmp_user=crm_test
tmp_password=xp39vfnd783hd8