#!/bin/bash

# usage docker-run.sh
# usage docker-run.sh [[[apachePort] mongoPort] nohup]

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if [ -z $1 ]; then apachePort=8080; else apachePort=$1; fi
if [ -z $2 ]; then mongoPort=27017; else mongoPort=$2; fi

PURPLE='\033[0;35m'
WHITE='\033[1;37m'
RESET='\033[0m'

# Warm up mongoDB dir, and apache log dir - will break if not done
if [ ! -e "$DIR/docker/log" ] ; then
    mkdir -p "$DIR/docker/log/apache2/"

    touch "$DIR/docker/log/apache2/error.log"
    mkdir "$DIR/docker/log/mongodb"
    chmod 777 -Rf "$DIR/docker/log"

    mkdir "$DIR/docker/mongodb"
    chmod 777 -Rf "$DIR/docker/mongodb"

    mkdir "$DIR/docker/Hydrators"
    chmod 777 "$DIR/docker/Hydrators"
fi

echo -e "${PURPLE}Running Mastermind Notes
    HTTP    on port: ${WHITE}$apachePort${PURPLE} to ${WHITE}127.0.0.1${PURPLE} only
    MongoDb on port: ${WHITE}$mongoPort${PURPLE} to ${WHITE}127.0.0.1${PURPLE} only${RESET}"


if [ "$3" == "nohup" ]; then
    echo -e "${PURPLE}Running container nohup.."
    echo -e "use docker stop mastermind-notes${RESET}"

    nohup docker run                  \
        --rm                          \
        -i                            \
        -p 127.0.0.1:$apachePort:80   \
        -p 127.0.0.1:$mongoPort:27017 \
        -v "$DIR:/app/"               \
        --name mastermind-notes       \
        nshiell/linux-php7.2-mongo:latest &
else
    echo -e "${PURPLE}Running container interactive...${RESET}"

    docker run                        \
        --rm                          \
        -it                           \
        -p 127.0.0.1:$apachePort:80   \
        -p 127.0.0.1:$mongoPort:27017 \
        -v "$DIR:/app/"               \
        --name mastermind-notes       \
        nshiell/linux-php7.2-mongo:latest
fi