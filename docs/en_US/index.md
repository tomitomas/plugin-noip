# Plugin No-Ip Renew

This plugin allows to automatically renew your free no-ip.com hostnames.
Such hostnames expire every 30 days if not renewed 7 days before expiration date.

You like this plugin? You can, if you wish, encourage its developer:

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/paypalme/hugoKs3)

# Installation

The plugin relies on a [Python script](https://github.com/loblab/noip-renew) developed by [loblab](https://github.com/loblab) and adapted for Jeedom.
This python script requires some dependencies that will be setup during plugin's installation:
- python3
- python3-pip
- chromium-chromedriver / chromium-driver / chromedriver
- chromium-browser
- selenium

# Configuration

## Cron

The plugin has its own cron.
It runs once every day at random hour and minute.
Until expiration date is reached, the plugion won't renew the hostnames and will just gather expiration date.
When expiraion date cloe to be reached, it ill renew th hostname(s).

## Plugin configuration

On the plugin configuration page, you can choose:
- when the plugin is supposed to renew your free hostnames (by default, 7 days before expiration but you can choose less)
- the default room for each hostname created

## Equipments configuration

To access the different **No-Ip** equipments, go to the menu **Plugins → Monitoring → No-Ip Renew**.

Click on "Add a No-Ip account"

On the equipment's page, fill in your No-Ip login and password.

Click then on "Scan" to get your domains.

For each domain, a dedicated equipment will be created.

# Usage

For each No-Ip acount, the following commands are available:
- refresh to force a refresh (with potantial renewal)
- the date/time for next automatic check by the cron

For each hostname, the following commands are available:
- the hostname
- the number of days before expiration
- its renew status: ok if renewed or not expired, warning if number of days before expiration < 7, error if renewal failed (nost probably because manual intervention is required)

A dedicated widget's template is available to view your hostnames and their status in a nice way.

# Limitations

Sometimes the renewal will fail because captcha verification is required (happens randomly). In such case, the command "renew" of concerned hostname will return "error". I encourage you to define an alert on this command to be warned.

# Contributions

This plugin is opened for contributions and even encouraged! Please submit your pull requests for improvements/fixes on <a href="https://github.com/hugoKs3/plugin-noip" target="_blank">Github</a>

# Credits

This plugin has been inspired by the work done by:

- [loblab](https://github.com/loblab) through his [noip-renew Python script](https://github.com/loblab/noip-renew)

# Disclaimer

-   This code does not pretend to be bug-free
-   Although it should not harm your Jeedom system, it is provided without any warranty or liability

# ChangeLog
Available [here](./changelog.html).
