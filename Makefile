TARGET        = phpbrew
SIGNATURE     = $(TARGET).asc
CP            = cp
INSTALL_PATH  = /usr/local/bin
TEST          = phpunit

COMPOSER_BIN_PLUGIN_VENDOR = vendor/bamarni/composer-bin-plugin

RECTOR_BIN = vendor-bin/rector/vendor/bin/rector
RECTOR = $(RECTOR_BIN)

$(TARGET): vendor $(shell find bin/ shell/ src/ -type f) box.json.dist .git/HEAD
	box compile
	touch -c $@

.PHONY: sign
sign: $(SIGNATURE)

$(SIGNATURE): $(TARGET)
	gpg --armor --detach-sign $(TARGET)

install: $(TARGET)
	$(CP) $(TARGET) $(INSTALL_PATH)

update/completion:
	bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
	bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

.PHONY: rector
rector: $(RECTOR_BIN)
	$(RECTOR)

.PHONY: rector_lint
rector_lint: $(RECTOR_BIN)
	$(RECTOR) --dry-run

test:
	$(TEST)

clean:
	git checkout -- $(TARGET)

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

$(COMPOSER_BIN_PLUGIN_VENDOR): composer.lock
	$(MAKE) --always-make vendor_install

.PHONY: rector_install
rector_install: $(RECTOR_BIN)

$(RECTOR_BIN): vendor-bin/rector/vendor
	touch -c $@
vendor-bin/rector/vendor: vendor-bin/rector/composer.lock $(COMPOSER_BIN_PLUGIN_VENDOR)
	composer bin rector install --ansi
	touch -c $@
vendor-bin/rector/composer.lock: vendor-bin/rector/composer.json
	composer bin rector update --lock --ansi
	touch -c $@
