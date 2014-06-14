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
	@echo "make fetch        - fetches sqlite files using rsync"
	@echo "make setup-rsync  - sets up rsync deployment and download"

.PHONY: clean setup-rsync

serve: index.php
	php -S localhost:9000 &
	open "http://localhost:9000"

setup-rsync:
	@( \
		test -e rsync_target \
		&& echo "--- The file 'rsync_target' already exists" \
	) \
	|| \
	( \
		echo -e "user@example.com:public_html/" > rsync_target \
		&& echo "--- Created 'rsync_target' for rsync deployment: " \
		&& cat rsync_target \
	)

deploy: rsync_target
	rsync -av $(DEPLOY_FILES) `cat rsync_target`

fetch: rsync_target
	rsync -av `cat rsync_target`*.sqlite
