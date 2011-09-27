# http://downloads.php.net/stas/php-5.4.0beta1.tar.bz2

PHP_VERSION=5.4.0beta1
DISTNAME=php-$PHP_VERSION
TARNAME=$DISTNAME.tar.bz2
curl -O http://downloads.php.net/stas/$TARNAME
tar xvf $TARNAME
cd $DISTNAME

# export CFLAGS=" -march=prescott -O3 -msse -mmmx -funroll-loops -mfpmath=sse "
# export CXXFLAGS="${CFLAGS}"

export CFLAGS=" -O3 -msse -mmmx -funroll-loops -mfpmath=sse "
export CXXFLAGS="${CFLAGS}"

#     --with-config-file-path=/opt/local/etc/php5 \
#     --with-config-file-scan-dir=/opt/local/var/db/php5 \
#     --mandir=/opt/local/share/man \
#     --infodir=/opt/local/share/info \
#     --datadir= ..

export PREFIX=$HOME/local
./configure --prefix=$PREFIX \
    --with-config-file-path=$PREFIX/etc/php5 \
    --with-config-file-scan-dir=$PREFIX/var/db/php5 \
    --with-pear=$PREFIX/lib/php \
    --disable-all  \
    --enable-bcmath \
    --enable-zip \
    --enable-ctype \
    --enable-dom  \
    --enable-fileinfo \
    --enable-filter \
    --enable-hash \
    --enable-json \
    --enable-libxml \
    --enable-pdo \
    --enable-phar \
    --enable-session \
    --enable-simplexml \
    --enable-tokenizer \
    --enable-xml \
    --enable-xmlreader \
    --enable-xmlwriter \
    --enable-cli \
    --enable-intl \
    --enable-mbstring \
    --enable-mbregex \
    --enable-sockets \
    --enable-exif \
    --enable-short-tags \
    --with-curl=/opt/local \
    --with-bz2=/opt/local \
    --with-mhash=/opt/local \
    --with-pcre-regex=/opt/local \
    --with-readline=/opt/local \
    --with-libxml-dir=/opt/local \
    --with-zlib=/opt/local \
    --with-mysql \
    --with-gettext=/opt/local \
    --with-mysqli \
    --disable-cgi \
    --with-apxs2=/opt/local/apache2/bin/apxs \
    --enable-shmop \
    --enable-sysvsem \
    --enable-sysvshm \
    --enable-sysvmsg

echo "Building php ... "
echo ""
echo "   tail -f build.log to see the log"
echo ""
$? || make 2&>1 > build.log
$? make test 2&>1 test.log 
$? sudo make install

# failed:
#    --with-gd=/opt/local \
