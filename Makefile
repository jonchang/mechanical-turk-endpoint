# Patterned after Rust-Empty <https://github.com/bvssvni/rust-empty>, MIT License.
SHELL := /bin/bash

DEFAULT = help

DEPLOY_FILES = .htaccess *.php *.input *.ini

all:
	$(DEFAULT)

help:
	@echo "--- fake-mechanical-turk"
	@echo "make help         - show this help"
	@echo "make serve        - starts a PHP server and opens it in the browser"
	@echo "make deploy       - deploy files using rsync"
	@echo "make setup-deploy - sets up deployment with rsync"

.PHONY: clean setup-deploy

serve: index.php
	php -S localhost:9000 &
	open "http://localhost:9000"

setup-deploy:
	@( \
		test -e deploy_target \
		&& echo "--- The file 'deploy_target' already exists" \
	) \
	|| \
	( \
		echo -e "user@example.com:public_html/" > deploy_target \
		&& echo "--- Created 'deploy_target' for rsync deployment: " \
		&& cat deploy_target \
	)

deploy: deploy_target
	rsync -av $(DEPLOY_FILES) `cat deploy_target`

fetch: deploy_target
	rsync -av `cat deploy_target`*.sqlite
