TARGET        = phpbrew
SIGNATURE     = $(TARGET).asc
CP            = cp
INSTALL_PATH  = /usr/local/bin
TEST          = phpunit

$(TARGET): vendor $(shell find bin/ shell/ src/ -type f) box.json.dist .git/HEAD
	./box compile
	touch -c $@

vendor: composer.lock
	composer install
	touch $@

.PHONY: sign
sign: $(SIGNATURE)

$(SIGNATURE): $(TARGET)
	gpg --armor --detach-sign $(TARGET)

install: $(TARGET)
	$(CP) $(TARGET) $(INSTALL_PATH)

update/completion:
	bin/phpbrew zsh --bind phpbrew --program phpbrew > completion/zsh/_phpbrew
	bin/phpbrew bash --bind phpbrew --program phpbrew > completion/bash/_phpbrew

test:
	$(TEST)

clean:
	git checkout -- $(TARGET)
