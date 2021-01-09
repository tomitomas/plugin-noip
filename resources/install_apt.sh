#!/bin/bash

# This file is part of Plugin openzwave for jeedom.
#
#  Plugin openzwave for jeedom is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  Plugin openzwave for jeedom is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with Plugin openzwave for jeedom. If not, see <http://www.gnu.org/licenses/>.

#set -x  # make sure each command is printed in the terminal
PROGRESS_FILE=/tmp/jeedom/noip/dependency
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "Lancement de l'installation/mise à jour des dépendances no-ip"

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

function apt_install {
  sudo apt-get -y install "$@"
  if [ $? -ne 0 ]; then
    echo "could not install $1 - abort"
    rm ${PROGRESS_FILE}
    exit 1
  fi
}

function pip_install {
  sudo python3 -m pip install "$@"
  if [ $? -ne 0 ]; then
    echo "could not install $p - abort"
    rm ${PROGRESS_FILE}
    exit 1
  fi
}

echo 20 > ${PROGRESS_FILE}
sudo rm -f /var/lib/dpkg/updates/*
sudo apt-get clean
echo 40 > ${PROGRESS_FILE}
sudo apt-get update
echo 60 > ${PROGRESS_FILE}
echo "Installation des dependances"
sudo apt -y install chromium-chromedriver || \
sudo apt -y install chromium-driver || \
sudo apt -y install chromedriver
apt_install chromium-browser python3 python3-pip
echo 80 > ${PROGRESS_FILE}
# Python
echo "Installation des dependances Python"
pip_install selenium
echo 100 > ${PROGRESS_FILE}
echo "Everything is successfully installed!"
rm ${PROGRESS_FILE}