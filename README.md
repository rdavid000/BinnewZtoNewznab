# BinnewZtoNewznab
Work but still in developpement

#INSTALL

apt install apache2 default-mysql-server php unrar-free ffmpeg mediainfo php-curl php-mbstring php-pear php-mysql php-gd -y
cd /var/www/
svn checkout svn://svn.newznab.com/nn/branches/nnplus --username <BUYIT> --password <BUYIT>
chown root:www-data nnplus/ -R
sed -i "s/Listen 80/Listen 8080/" /etc/apache2/ports.conf
echo -e "<VirtualHost *:8080>
	<Directory /var/www/nnplus/www/>
		Options FollowSymLinks
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>

	ServerAdmin admin@example.com
	ServerName example.com
	ServerAlias www.example.com
	DocumentRoot /var/www/nnplus/www
	LogLevel warn
	ServerSignature Off
</VirtualHost>" > /etc/apache2/sites-available/nnplus.conf
a2enmod rewrite
a2ensite nnplus
sed -i "s/;date.timezone =/date.timezone = Europe\/Paris/" /etc/php/7.4/apache2/php.ini
sed -i "s/max_execution_time = 30/max_execution_time = 60/" /etc/php/7.4/apache2/php.ini
sed -i "s/memory_limit = 128M/memory_limit = 256M/" /etc/php/7.4/apache2/php.ini

systemctl reload apache2
sed -i "s/#max_allowed_packet     = 1G/max_allowed_packet     = 12582912/" /etc/mysql/mariadb.conf.d/50-server.cnf
chmod 777 /var/www/nnplus/www/lib/smarty/templates_c
chmod 777 /var/www/nnplus/www/covers/movies
chmod 777 /var/www/nnplus/www/covers/anime
chmod 777 /var/www/nnplus/www/covers/music
chmod 777 /var/www/nnplus/www/covers/tv
chmod 777 /var/www/nnplus/www
chmod 777 /var/www/nnplus/www/install
chmod -R 777 /var/www/nnplus/nzbfiles/
echo "GRANT ALL PRIVILEGES ON *.* TO 'nnplus'@'localhost' IDENTIFIED BY '1234';" | mysql -u root
wget https://bitbucket.org/ariya/phantomjs/downloads/phantomjs-2.1.1-linux-x86_64.tar.bz2
tar jxvf phantomjs-2.1.1-linux-x86_64.tar.bz2
cp ./phantomjs-2.1.1-linux-x86_64/bin/phantomjs /usr/bin/
export OPENSSL_CONF=/dev/null
