# Patterned after Rust-Empty <https://github.com/bvssvni/rust-empty>, MIT License.
SHELL := /bin/bash

DEFAULT = make help

all:
	$(DEFAULT)

help:
	clear \
	&& echo "make help - this help" \
	&& echo "make serve - starts a php server and opens it in the browser"\
	&& echo "make asdf - do something"

.PHONY: clean

serve: index.php
	php -S localhost:9000 &
	open "http://localhost:9000"
