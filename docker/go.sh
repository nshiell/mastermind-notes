BLUE='\033[1;34m'
RED='\033[1;31m'
GREEN='\033[1;32m'
WHITE='\033[1;37m'
RESET='\033[0m'

if [ ! -e /app/vendor ] ; then
    echo "${RED}PHP vendors not found, installing!${RESET}"
    cd /app/
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"

    ./composer.phar install && rm composer.phar && echo "${GREEN}PHP vendors now installed!${RESET}"
fi

cp /app/docker/vhost.conf /etc/apache2/sites-available/000-default.conf
sed -i -e 's/  bindIp: 127.0.0.1/  bindIp: 0.0.0.0/g' /etc/mongod.conf

echo "${BLUE}Starting mongo...${RESET}"
mongod --config /etc/mongod.conf &

echo "${BLUE}Starting apache...${RESET}"
apachectl -DFOREGROUND &

echo
echo "${WHITE}Use Ctrl+c to quit"
echo "${GREEN}tail -f /var/log/apache2/error.log${RESET}"

tail -f /var/log/apache2/error.log