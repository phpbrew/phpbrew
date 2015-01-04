OUTPUT        = phpbrew.phar
TARGET        = phpbrew
MOVE          = mv
SUDOCP        = sudo cp
INSTALL_PATH  = /usr/local/bin
PERMISSION    = chmod +x
TEST          = phpunit

default:
		 onion compile \
			--lib src \
			--lib vendor/corneltek/cliframework/src \
			--lib vendor/corneltek/pearx/src \
			--lib vendor/corneltek/getoptionkit/src \
			--lib vendor/corneltek/curlkit/src \
			--lib vendor/corneltek/universal/src \
			--lib vendor/symfony/process \
			--lib vendor/symfony/yaml \
			--lib shell \
			--exclude Tests/ \
			--exclude CHANGELOG\|README \
			--exclude phpunit.xml \
			--classloader \
			--bootstrap scripts/phpbrew-emb.php \
			--executable \
			--no-compress \
			--output $(OUTPUT)
		$(MOVE) $(OUTPUT) $(TARGET)
		$(PERMISSION) $(TARGET)

install:
		$(SUDOCP) $(TARGET) $(INSTALL_PATH)

update:
		composer update

update/assets:
		bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
		./scripts/update-releases-json
		php bin/phpbrew github:build-topics --dir src phpbrew phpbrew

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
		git checkout -- $(TARGET)
