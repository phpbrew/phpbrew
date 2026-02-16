# PHPBrew

_Lee esto en otros idiomas: [English](README.md), [Português - BR](README.pt-br.md), [日本語](README.ja.md), [中文](README.cn.md), [Español](README.es.md)._

[![build](https://github.com/phpbrew/phpbrew/actions/workflows/build.yml/badge.svg)](https://github.com/phpbrew/phpbrew/actions/workflows/build.yml)
[![Coverage Status](https://img.shields.io/coveralls/phpbrew/phpbrew.svg)](https://coveralls.io/r/phpbrew/phpbrew)
[![Gitter](https://badges.gitter.im/phpbrew/phpbrew.svg)](https://gitter.im/phpbrew/phpbrew?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

phpbrew compila e instala múltiples versiones de PHP en tu directorio $HOME.

Lo que **phpbrew** puede hacer por ti:

- Las opciones de configuración se simplifican en variantes, ¡ya no tienes que preocuparte por las rutas!
- Compila PHP con diferentes variantes como PDO, MySQL, SQLite, debug, etc.
- Compila el módulo de PHP para Apache y sepáralo por versiones distintas.
- Compila e instala PHP en tu directorio personal, por lo que no necesitas permisos de superusuario.
- Cambia entre versiones fácilmente e integrado con los shells bash/zsh.
- Detección automática de características.
- Instala y habilita extensiones de PHP en el entorno actual con facilidad.
- Instala múltiples versiones de PHP en un entorno de todo el sistema.
- Optimización de detección de rutas para HomeBrew y MacPorts.

<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/01.png"/>
<img width="500" src="https://raw.github.com/phpbrew/phpbrew/master/screenshots/03.png"/>

## Requirement

Por favor, consulta los [Requisitos](https://github.com/phpbrew/phpbrew/wiki/Requirement) antes de comenzar. Necesitas instalar algunos paquetes de desarrollo para compilar PHP.

## INICIO RÁPIDO

Consulta la sección [Inicio Rápido](https://github.com/phpbrew/phpbrew/wiki/Quick-Start) si estás impaciente. :-p

## COMENZANDO

Bien, supongo que tienes más tiempo para trabajar en esto. Este es un tutorial paso a paso que te ayudará a comenzar.

### Instalación

```bash
curl -L -O https://github.com/phpbrew/phpbrew/releases/latest/download/phpbrew.phar
chmod +x phpbrew.phar

# Mueve el archivo a algún directorio dentro de tu variable `$PATH`
sudo mv phpbrew.phar /usr/local/bin/phpbrew
```

Inicializa un script bash para tu entorno de shell:

```bash
phpbrew init
```

Agrega estas líneas a tu archivo `.bashrc` o `.zshrc`:

```bash
[[ -e ~/.phpbrew/bashrc ]] && source ~/.phpbrew/bashrc
```

- Específicamente para usuarios del shell `fish`, agrega las siguientes líneas a tu archivo `~/.config/fish/config.fish`\*:

```fish
source ~/.phpbrew/phpbrew.fish
```

Si estás usando phpbrew a nivel del sistema, puedes configurar una raíz compartida de phpbrew, por ejemplo:

```bash
mkdir -p /opt/phpbrew
phpbrew init --root=/opt/phpbrew
```

### Configuración del prefijo de búsqueda

Puedes configurar tu prefijo predeterminado preferido para buscar bibliotecas. Las opciones disponibles son: `macports`, `homebrew`, `debian`, `ubuntu` o una ruta personalizada:

Para usuarios de Homebrew:

```bash
phpbrew lookup-prefix homebrew
```

Para usuarios de MacPorts:

```bash
phpbrew lookup-prefix macports
```

## Uso básico

Para listar las versiones conocidas:

```bash
phpbrew known

8.2: 8.2.4, 8.2.3, 8.2.2, 8.2.1, 8.2.0 ...
8.1: 8.1.17, 8.1.16, 8.1.15, 8.1.14, 8.1.13, 8.1.12, 8.1.11, 8.1.10 ...
8.0: 8.0.28, 8.0.27, 8.0.26, 8.0.25, 8.0.24, 8.0.23, 8.0.22, 8.0.21 ...
7.4: 7.4.33, 7.4.32, 7.4.30, 7.4.29, 7.4.28, 7.4.27, 7.4.26, 7.4.25 ...
7.3: 7.3.33, 7.3.32, 7.3.31, 7.3.30, 7.3.29, 7.3.28, 7.3.27, 7.3.26 ...
7.2: 7.2.34, 7.2.33, 7.2.32, 7.2.31, 7.2.30, 7.2.29, 7.2.28, 7.2.27 ...
7.1: 7.1.33, 7.1.32, 7.1.31, 7.1.30, 7.1.29, 7.1.28, 7.1.27, 7.1.26 ...
7.0: 7.0.33, 7.0.32 ...
```

Para mostrar más versiones menores:

```bash
$ phpbrew known --more
```

To update the release info:

```bash
$ phpbrew update
```

Para obtener versiones más antiguas (anteriores a la 5.4)

> Ten en cuenta que no garantizamos que puedas compilar con éxito las versiones de PHP
> que no están oficialmente soportadas. Por favor, no reportes problemas relacionados
> con versiones antiguas, ya que estos no serán corregidos.

```bash
$ phpbrew update --old
```

Para listar versiones antiguas conocidas (anteriores a la 5.4)

```bash
$ phpbrew known --old
```

## Comenzando a compilar tu propio PHP

Simplemente compila e instala PHP con la variante predeterminada:

```bash
$ phpbrew install 5.4.0 +default
```

Aquí sugerimos el conjunto de variantes `default`, que incluye las variantes más comúnmente usadas. Si necesitas una instalación mínima, simplemente elimina el conjunto de variantes `default`.

Puedes habilitar la compilación en paralelo usando la opción `-j` o `--jobs`. A continuación, un ejemplo:

```bash
$ phpbrew install -j $(nproc) 5.4.0 +default
```

Con pruebas:

```bash
$ phpbrew install --test 5.4.0
```

Con mensajes de depuración:

```bash
$ phpbrew -d install --test 5.4.0
```

Para instalar versiones antiguas (anteriores a la 5.3):

```bash
$ phpbrew install --old 5.2.13
```

Para instalar la última versión parche de una versión específica:

```bash
$ phpbrew install 5.6
```

Para instalar una versión previa (pre-release):

```bash
$ phpbrew install 7.2.0alpha1
$ phpbrew install 7.2.0beta2
$ phpbrew install 7.2.0RC3
```

Para instalar desde una etiqueta (tag) o nombre de rama (branch) en GitHub:

```bash
$ phpbrew install github:php/php-src@PHP-7.2 as php-7.2.0-dev
```

Para instalar la siguiente versión (inestable):

```bash
$ phpbrew install next as php-7.3.0-dev
```

## Limpieza del directorio de compilación

```bash
$ phpbrew clean php-5.4.0
```

## Variantes

PHPBrew organiza las opciones de configuración por ti, puedes simplemente especificar el nombre de la variante, y phpbrew detectará las rutas de inclusión y las opciones de compilación necesarias.

PHPBrew proporciona variantes por defecto y algunas variantes virtuales.

- Las variantes por defecto incluyen las más comúnmente usadas.
- Las variantes virtuales definen un conjunto de variantes; puedes usar una variante virtual para habilitar múltiples variantes a la vez.

Para ver qué incluye cada variante, ejecuta `phpbrew variants` para listar todas las variantes disponibles.

Para habilitar una variante, añade el prefijo `+` antes del nombre de la variante, por ejemplo:

```
+mysql
```

Para deshabilitar una variante, añade el prefijo `-` antes del nombre de la variante, por ejemplo:

```
-debug
```

Por ejemplo, si quieres compilar PHP con las opciones por defecto y soporte para bases de datos (mysql, sqlite, postgresql), simplemente ejecuta:

```bash
$ phpbrew install 5.4.5 +default+dbs
```

También puedes compilar PHP con variantes adicionales:

```bash
$ phpbrew install 5.3.10 +mysql+sqlite+cgi

$ phpbrew install 5.3.10 +mysql+debug+pgsql +apxs2

$ phpbrew install 5.3.10 +pdo +mysql +pgsql +apxs2=/usr/bin/apxs2
```

Para compilar PHP con la extensión pgsql (PostgreSQL):

```bash
$ phpbrew install 5.4.1 +pgsql+pdo
```

O compila la extensión pgsql especificando el directorio base de PostgreSQL en Mac OS X:

```bash
$ phpbrew install 5.4.1 +pdo+pgsql=/opt/local/lib/postgresql91/bin
```

La ruta de pgsql es la ubicación de `pg_config`; puedes encontrar `pg_config` en `/opt/local/lib/postgresql91/bin`.

Para compilar PHP con opciones neutrales, puedes especificar la variante virtual `neutral`, lo que significa que phpbrew no agregará opciones de compilación adicionales, incluyendo `--disable-all`. Sin embargo, algunas opciones (por ejemplo, `--enable-libxml`) se añaden automáticamente para soportar la instalación de `pear`.
Puedes compilar PHP con la variante `neutral` así:

```bash
$ phpbrew install 5.4.1 +neutral
```

Para más detalles, por favor consulta: [PHPBrew Cookbook](https://github.com/phpbrew/phpbrew/wiki).

## Opciones adicionales de configuración

Para pasar argumentos adicionales a la configuración, puedes hacerlo así:

```bash
$ phpbrew install 5.3.10 +mysql +sqlite -- \
    --enable-ftp --apxs2=/opt/local/apache2/bin/apxs
```

## Uso y Cambio de versión

Usar (cambiar versión temporalmente):

```bash
$ phpbrew use 5.4.22
```

Cambiar la versión de PHP (cambiar la versión predeterminada)

```bash
$ phpbrew switch 5.4.18
```

Turn Off:

```bash
$ phpbrew off
```

Si habilitas los módulos PHP para Apache, recuerda comentar o eliminar esas configuraciones.

```bash
$ sudo vim /etc/httpd/conf/httpd.conf
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.21.so
# LoadModule php5_module        /usr/lib/httpd/modules/libphp5.3.20.so
```

## El instalador de extensiones

Consulta [Extension Installer](https://github.com/phpbrew/phpbrew/wiki/Extension-Installer)

### Configurando el php.ini para la versión actual de PHP

Simplemente ejecuta:

```bash
$ phpbrew config
```

Puedes especificar la variable de entorno EDITOR con tu editor favorito:

```bash
export EDITOR=vim
phpbrew config
```

## Actualizar phpbrew

Para actualizar phpbrew, simplemente puedes ejecutar el comando `self-update`.
Este comando te permite instalar la última versión de la rama `master` desde GitHub:

```bash
$ phpbrew self-update
```

## Los PHP instalados

Para listar todos los PHP instalados, puedes ejecutar:

```bash
$ phpbrew list
```

Los PHP instalados se encuentran en `~/.phpbrew/php`, por ejemplo, PHP 5.4.20 está ubicado en:

    ~/.phpbrew/php/5.4.20/bin/php

Y deberías colocar tu archivo de configuración en:

    ~/.phpbrew/php/5.4.20/etc/php.ini

Los archivos de configuración de extensiones deben colocarse en:

    ~/.phpbrew/php/5.4.20/var/db
    ~/.phpbrew/php/5.4.20/var/db/xdebug.ini
    ~/.phpbrew/php/5.4.20/var/db/apc.ini
    ~/.phpbrew/php/5.4.20/var/db/memcache.ini
    ... etc

## Comandos rápidos para cambiar entre directorios

Cambiar al directorio de compilación de PHP

```bash
$ phpbrew build-dir
```

Cambiar al directorio de distribución de PHP

```bash
$ phpbrew dist-dir
```

Cambiar al directorio de configuración (etc) de PHP

```bash
$ phpbrew etc-dir
```

Cambiar al directorio var de PHP

```bash
$ phpbrew var-dir
```

## PHP FPM

phpbrew también ofrece subcomandos útiles para gestionar FPM. Para usarlos, recuerda habilitar la variante `+fpm` al compilar tu propio PHP.

Para configurar el servicio FPM del sistema, escribe uno de los siguientes comandos:


```bash
# Ejecuta el siguiente comando cuando systemctl esté disponible en la distribución Linux.
$ phpbrew fpm setup --systemctl

# Cuando está disponible en la distribución Linux.
$ phpbrew fpm setup --initd

# Cuando está disponible en macOS.
$ phpbrew fpm setup --launchctl
```

Para iniciar php-fpm, simplemente escribe:

```bash
$ phpbrew fpm start
```

Para detener php-fpm, escribe:

```bash
$ phpbrew fpm stop
```

Para mostrar los módulos de php-fpm:

```bash
phpbrew fpm module
```

Para probar la configuración de php-fpm:

```bash
phpbrew fpm test
```

Para editar la configuración de php-fpm:

```bash
phpbrew fpm config
```

> El `php-fpm` instalado se encuentra en `~/.phpbrew/php/php-*/sbin`.
>
> El archivo de configuración correspondiente `php-fpm.conf` está en `~/.phpbrew/php/php-*/etc/php-fpm.conf.default`.
> Puedes copiar el archivo de configuración por defecto a la ubicación deseada, por ejemplo:
>
>     cp -v ~/.phpbrew/php/php-*/etc/php-fpm.conf.default
>         ~/.phpbrew/php/php-*/etc/php-fpm.conf
>
>     php-fpm --php-ini {php config file} --fpm-config {fpm config file}

## Habilitar información de versión en el prompt

Para añadir la información de la versión de PHP en el prompt de tu terminal, puedes usar:
`"PHPBREW_SET_PROMPT=1"` variable.

El valor por defecto es `"PHPBREW_SET_PROMPT=0"` (desactivado). Para activarlo, puedes añadir esta línea a tu archivo `~/.bashrc` y colocarla antes de la línea donde haces el *source*:
`~/.phpbrew/bashrc`.

```bash
export PHPBREW_SET_PROMPT=1
```

Para mostrar la información de la versión en tu prompt, puedes usar la función de shell `phpbrew_current_php_version`, que está definida en `.phpbrew/bashrc`.
Luego, puedes configurar la información de versión en tu variable `PS1`.
Por ejemplo:

```bash
PS1=" \$(phpbrew_current_php_version) \$ "
```

## Problemas Conocidos

* Para versiones PHP 5.3 en adelante, existe el problema "Fallo al compilar intl en 64-bit en OS X"
  [https://bugs.php.net/bug.php?id=48795](https://bugs.php.net/bug.php?id=48795)

* Para compilar PHP con la extensión GD, necesitas especificar los directorios de libpng y libjpeg, por ejemplo:

   
  $ phpbrew install php-5.4.10 +default +mysql +intl +gettext +apxs2=/usr/bin/apxs2 \
   -- --with-libdir=lib/x86_64-linux-gnu \
   --with-gd=shared \
   --enable-gd-natf \
   --with-jpeg-dir=/usr \
   --with-png-dir=/usr
  

## Solución de Problemas

Por favor, consulta [TroubleShooting](https://github.com/phpbrew/phpbrew/wiki/TroubleShooting)


## Preguntas Frecuentes (FAQ)

**P:** ¿Cómo puedo tener la misma versión con diferentes opciones de compilación?

**R:** Actualmente, puedes instalar php-5.x.x y renombrar la carpeta `/Users/phpbrew/.phpbrew/php/php-5.x.x` con un nuevo nombre, por ejemplo, `php-5.x.x-super`, y luego instalar otra versión php-5.x.x.

## Contribuciones

Por favor, consulta [Contribución](https://github.com/phpbrew/phpbrew/wiki/Contribution)

## Documentación

Por favor, consulta [Wiki](https://github.com/phpbrew/phpbrew/wiki)

## Fundador y Contribuidores

Solo se listan los contribuidores con más de 1000 líneas, los nombres están ordenados alfabéticamente.

- @c9s
- @GM-Alex
- @jhdxr
- @marcioAlmada
- @morozov
- @markwu
- @peter279k
- @racklin
- @shinnya

## Licencia

Consulta el archivo [LICENSE](LICENSE)

[s-link]: https://scrutinizer-ci.com/g/phpbrew/phpbrew/?branch=master "Code Quality"
[p-link]: https://packagist.org/packages/marc/phpbrew "Packagist"
[sl-link]: https://insight.sensiolabs.com/projects/02d1fd01-8a70-4fe4-a550-381a3c0e33f3 "Sensiolabs Insight"
[c-badge]: https://coveralls.io/repos/phpbrew/phpbrew/badge.png?branch=master
