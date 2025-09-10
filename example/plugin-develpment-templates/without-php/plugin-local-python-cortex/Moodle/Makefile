snow_conn_name = test_again
snow_role= MOODLE_ROLE

set-db:
	snow sql -q "CREATE DATABASE IF NOT EXISTS MOODLE_APP;"	 --connection ${snow_conn_name} --role ${snow_role}
	snow sql -q "CREATE IMAGE REPOSITORY IF NOT EXISTS MOODLE_APP.PUBLIC.IMG;"	 --connection ${snow_conn_name} --role ${snow_role}
	snow sql -q "
		USE ROLE ACCOUNTADMIN;
		CREATE or replace NETWORK RULE moodle_allow_all MODE = EGRESS TYPE = HOST_PORT VALUE_LIST = ('0.0.0.0:80', '0.0.0.0:443');
		CREATE OR REPLACE EXTERNAL ACCESS INTEGRATION moodle_eai ALLOWED_NETWORK_RULES = ('moodle_allow_all') enabled = true;
		grant usage on integration moodle_eai to role MOODLE_ROLE; "  --connection ${snow_conn_name} --role ${snow_role}

repo-login:
	snow spcs image-registry login --connection ${snow_conn_name} 

cpool:
	snow spcs compute-pool create moodle_compute_pool --if-not-exists --family CPU_X64_M --min-nodes 1 --max-nodes 1 --auto-suspend-secs 300 --connection ${snow_conn_name} --role ${snow_role}

serv:
	snow spcs service create moodle_service --database MOODLE_APP --schema PUBLIC  --if-not-exists --compute-pool moodle_compute_pool --spec-path ./moodle.yaml --connection ${snow_conn_name} --role ${snow_role} --eai_name "moodle_eai"

serv_upgrade:
	snow spcs service upgrade moodle_service --database MOODLE_APP --schema PUBLIC --spec-path ./moodle.yaml --connection ${snow_conn_name} --role ${snow_role}
build:
	docker build --platform linux/amd64 -t flask:latest .

tag: build
	$(eval moodle_tag := bitnami/moodle:5.0)
	$(eval pg_tag := bitnami/postgresql:17.5.0)
	$(eval flask_tag := flask:latest)
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	docker tag ${pg_tag} ${repository_url}/${pg_tag}
	docker tag ${moodle_tag} ${repository_url}/${moodle_tag}
	docker tag ${flask_tag} ${repository_url}/${flask_tag}

push:
	$(eval moodle_tag := bitnami/moodle:5.0)
	$(eval pg_tag := bitnami/postgresql:17.5.0)
	$(eval flask_tag := flask:latest)
	$(eval repository_url := $(shell snow spcs image-repository url MOODLE_APP.PUBLIC.IMG --connection ${snow_conn_name}))
	docker push ${repository_url}/${moodle_tag}
	docker push ${repository_url}/${pg_tag}
	docker push ${repository_url}/${flask_tag}

all: set-db repo-login build tag push









