#!/bin/bash
PROGRESS_FILE=/tmp/jeedom/noip/dependency
touch ${PROGRESS_FILE}
touch /tmp/jeedom/noip/dependancy_noip
echo 0 > ${PROGRESS_FILE}
echo "Launch install of noip dependencies"
echo ""
echo "-- Current OS version :"
sudo lsb_release -d
echo ""
echo "-- Updating repo..."
sudo apt-get update
echo 20 > ${PROGRESS_FILE}
echo ""
echo "-- Installation of python3 and dependencies"
sudo apt-get install -y python3 python-dev build-essential
echo ""
echo "-- Installed version of Python :"
python3 -V
pyver=$(python3 -V 2>&1 | sed 's/.* \([0-9]\).\([0-9]\+\).*/\1\2/')
if [ "$pyver" -lt "35" ]; then  # using 3.4 that is deprecated
    echo "  Your version of python is not compatible with this plugin, installation might not work correctly !"
else
    echo "  Your version of python is compatible with this plugin."
fi
echo 50 > ${PROGRESS_FILE}
echo ""
echo "-- Installation of pip for python3 and necessary libraries"
sudo apt-get install -y python3-dev python-requests python3-pip
echo 68 > ${PROGRESS_FILE}
echo ""
echo "-- Installation of chromium"
# sudo apt-get install -y chromium-chromedriver || \
# sudo apt-get install -y chromium-driver || \
# sudo apt-get install -y chromedriver
# sudo mv /usr/bin/chromedriver /usr/bin/chromedriver.bak

OS=$(hostnamectl | grep -i "operating system")
case $OS in
    *Arch?Linux*)
        ;;
    *)
        sudo apt-get install -y chromium
        ;;
esac
echo 71 > ${PROGRESS_FILE}
echo ""
# get pip3 command (different depending of OS such as raspberry)
pip3cmd=$(compgen -ac | grep -E '^pip-?3' | sort -r | head -1)
if [[ -z  $pip3cmd ]]; then     # pip3 not found
    if python3 -m pip -V 2>&1 | grep -q -i "^pip " ; then     # but try other way
        pip3cmd="python3 -m pip"
    else # something is wrong with pip3 so reinstall it
        echo "-- Something is wrong with pip3, trying to re-install :"
        sudo python3 -m pip uninstall -y pip
        sudo apt-get -y --reinstall install python3-pip
        pip3cmd=$(compgen -ac | grep -E '^pip-?3' | sort -r | head -1)
    fi
fi
if [[ ! -z  $pip3cmd ]]; then     # pip3 found
    echo ""
    echo "-- Installed version of pip :"
    echo $($pip3cmd -V)
    echo ""
    echo "-- Installation of python library 'selenium' with command $pip3cmd"
    $(sudo $pip3cmd install 'selenium' > /tmp/jeedom/noip/dependancy_noip)
    cat /tmp/jeedom/noip/dependancy_noip
    echo 100 > ${PROGRESS_FILE}
    echo ""
    echo "-- Installation of dependencies is done !"
    rm -f /tmp/jeedom/noip/dependancy_noip
else
    echo ""
    echo "Error: Cound not found pip3 program to install python dependencies ! Check doc FAQ for possible resolution."
fi
echo 100 > ${PROGRESS_FILE}
rm ${PROGRESS_FILE}