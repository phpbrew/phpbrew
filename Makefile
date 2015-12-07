OUTPUT        = phpbrew.phar
TARGET        = phpbrew
MOVE          = mv -v
COPY          = cp -v
SUDOCP        = sudo cp
INSTALL_PATH  = /usr/local/bin
PERMISSION    = chmod +x
TEST          = phpunit

.PHONY: build

build/phpbrew:
	php bin/phpbrew archive --executable \
		--exclude Tests \
		--exclude CHANGELOG\|README \
		--exclude phpunit.xml \
		--no-compress \
		--add shell \
		--bootstrap scripts/phpbrew-emb.php \
		$(OUTPUT)
	$(COPY) $(OUTPUT) $(TARGET)
	$(COPY) $(OUTPUT) build/phpbrew
	$(PERMISSION) $(TARGET)

build: build/phpbrew

sign: build
	gpg --armor --detach-sign build/phpbrew

install:
		$(SUDOCP) $(TARGET) $(INSTALL_PATH)

update:
		composer update

update/topics:
		php bin/phpbrew github:build-topics --dir src phpbrew phpbrew

update/completion:
		bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
		bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

test:
		$(TEST)

test/quick:
		$(TEST) --group small

test/extension:
		$(TEST) --group extension

test/extension-installer:
	php bin/phpbrew --debug ext install openssl
	php bin/phpbrew --debug ext install opcache
	php bin/phpbrew --debug ext install xdebug
	php bin/phpbrew --debug ext install soap

test/see-coverage:
	xdg-open build/logs/coverage/index.html

clean:
	rm -rf build
	git checkout -- $(TARGET)

