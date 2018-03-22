BLUE='\033[1;34m'
GREEN='\033[1;32m'
WHITE='\033[1;37m'
RESET='\033[0m'

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