# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

PHPBREW_PHAR        = phpbrew
SIGNATURE     = $(PHPBREW_PHAR).asc
CP            = cp
INSTALL_PATH  = /usr/local/bin
TEST          = phpunit

PHAR_SRC_FILES := $(shell find bin/ shell/ src/ -type f)


.DEFAULT_GOAL := help


.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'


.PHONY: build
build:	## Builds PHPBrew PHAR
build:
	rm $(PHPBREW_PHAR) 2>/dev/null || true
	$(MAKE) _build

.PHONY: _build
_build:
	$(MAKE) $(PHPBREW_PHAR)

install: PHPBREW_PHAR
	$(CP) $(PHPBREW_PHAR) $(INSTALL_PATH)

update/completion:
	bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
	bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

test:
	$(TEST)

clean:
	git checkout -- $(PHPBREW_PHAR)

$(PHPBREW_PHAR): vendor \
		$(PHAR_SRC_FILES) \
		box.json.dist \
		.git/HEAD
	box compile
	touch -c $@

PHONY: vendor_install
vendor_install:
	composer install --ansi
	touch -c composer.lock
	touch -c vendor

composer.lock: composer.json
	composer update --lock
	touch -c $@
vendor: composer.lock
	$(MAKE) vendor_install
