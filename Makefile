.DEFAULT_GOAL := help

phar: clean ## [CI] Build a phar with box
	composer install
	box compile
.PHONY: phar

configure-dev: ## [DEV] Prepare dev environment
	mkcert -install
	mkcert -cert-file ssl/mercure-router.local.pem -key-file ssl/mercure-router.local-key.pem "mercure-router.local"
	@echo "Please add mercure-router.local to your /etc/hosts file to complete the installation!"
.PHONY: configure

compile: ## [DEV] Generates the .phar file (requires cpx installed globally)
	cpx humbug/box compile
.PHONY: compile

clean:
	rm -rf vendor logs composer.lock bin/drumkit.phar
.PHONY: clean

help: SHELL=/bin/bash
help: ## Dislay this help
	@IFS=$$'\n'; for line in `grep -h -E '^[a-zA-Z_#-]+:?.*?## .*$$' $(MAKEFILE_LIST)`; do if [ "$${line:0:2}" = "##" ]; then \
	echo $$line | awk 'BEGIN {FS = "## "}; {printf "\n\033[33m%s\033[0m\n", $$2}'; else \
	echo $$line | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'; fi; \
	done; unset IFS;
.PHONY: help
