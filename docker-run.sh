#!/bin/bash

# usage docker-run.sh
# usage docker-run.sh [apachePort] [mongoPort]

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if [ -z $1 ]; then apachePort=8080; else apachePort=$1; fi
if [ -z $2 ]; then mongoPort=27017; else mongoPort=$2; fi


PURPLE='\033[0;35m'

WHITE='\033[1;37m'
RESET='\033[0m'

echo -e "${PURPLE}Running Mastermind Notes
    HTTP    on port: ${WHITE}$apachePort${PURPLE} to ${WHITE}127.0.0.1${PURPLE} only
    MongoDb on port: ${WHITE}$mongoPort${PURPLE} to ${WHITE}127.0.0.1${PURPLE} only${RESET}"

docker run                        \
    --rm                          \
    -it                           \
    -p 127.0.0.1:$apachePort:80   \
    -p 127.0.0.1:$mongoPort:27017 \
    -v "$DIR:/app/"               \
    nshiell/linux-php7.2-mongo:latest