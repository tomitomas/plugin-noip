#!/usr/bin/env python3
# Copyright 2017 loblab
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#       http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from datetime import date
from datetime import timedelta
import time
import sys
import os
import re
import base64
import json

class Logger:
    def __init__(self, level):
        self.level = 0 if level is None else level

    def log(self, msg, level=None):
        self.time_string_formatter = time.strftime('%Y/%m/%d %H:%M:%S', time.localtime(time.time()))
        self.level = self.level if level is None else level
        if self.level > 0:
            print("[{mydate}] - {msg}".format(mydate=self.time_string_formatter,msg=msg))


class Robot:

    USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:64.0) Gecko/20100101 Firefox/64.0"
    LOGIN_URL = "https://www.noip.com/login"
    HOST_URL = "https://my.noip.com/#!/dynamic-dns"

    def __init__(self, username, password, threshold, renew, debug):
        self.debug = debug
        self.username = username
        self.password = password
        self.threshold = threshold
        self.renew = renew
        self.browser = self.init_browser()
        self.logger = Logger(debug)
        self.data = []

    @staticmethod
    def init_browser():
        options = webdriver.ChromeOptions()
        #added for Raspbian Buster 4.0+ versions. Check https://www.raspberrypi.org/forums/viewtopic.php?t=258019 for reference.
        options.add_argument("disable-features=VizDisplayCompositor")
        options.add_argument("headless")
        options.add_argument("no-sandbox")  # need when run in docker
        options.add_argument("window-size=1200x800")
        options.add_argument("user-agent={USER_AGENT}".format(USER_AGENT=Robot.USER_AGENT))
        if 'https_proxy' in os.environ:
            options.add_argument("proxy-server=" + os.environ['https_proxy'])
        browser = webdriver.Chrome(options=options)
        browser.set_page_load_timeout(90) # Extended timeout for Raspberry Pi.
        return browser

    def login(self):
        self.logger.log("Opening {LOGIN_URL}...".format(LOGIN_URL=Robot.LOGIN_URL))
        self.browser.get(Robot.LOGIN_URL)
        if self.debug > 1:
            self.browser.save_screenshot("debug1.png")

        self.logger.log("Logging in...")
        ele_usr = self.browser.find_element_by_name("username")
        ele_pwd = self.browser.find_element_by_name("password")
        ele_usr.send_keys(self.username)
        #ele_pwd.send_keys(base64.b64decode(self.password).decode('utf-8'))
        ele_pwd.send_keys(self.password)
        self.browser.find_element_by_name("Login").click()
        if self.debug > 1:
            time.sleep(1)
            self.browser.save_screenshot("debug2.png")

    def update_hosts(self):
        count = 0

        self.open_hosts_page()
        time.sleep(1)
        iteration = 1

        hosts = self.get_hosts()
        for host in hosts:
            host_link = self.get_host_link(host, iteration) # This is for if we wanted to modify our Host IP.
            host_button = self.get_host_button(host, iteration) # This is the button to confirm our free host
            host_name = host_link.text
            expiration_days = self.get_host_expiration_days(host, iteration)
            self.logger.log("{host_name} expires in {expiration_days} days".format(host_name=host_name,expiration_days=str(expiration_days)))
            renewed = False
            if self.renew > 0 and expiration_days < self.threshold:
                self.update_host(host_button, host_name)
                count += 1
                renewed = True
            iteration += 1
            self.data.append({'hostname':host_name, 'expirationdays':expiration_days, 'renewed':renewed})
        self.browser.save_screenshot("results.png")
        self.logger.log("Confirmed hosts: {count}".format(count=str(count)))
        return True

    def open_hosts_page(self):
        self.logger.log("Opening {HOST_URL}...".format(HOST_URL=Robot.HOST_URL))
        try:
            self.browser.get(Robot.HOST_URL)
        except TimeoutException as e:
            self.browser.save_screenshot("timeout.png")
            self.logger.log("Timeout: {e}".format(e=str(e),expiration_days=expiration_days))

    def update_host(self, host_button, host_name):
        self.logger.log("Updating {host_name}".format(host_name=host_name))
        host_button.click()
        time.sleep(3)
        intervention = False
        try:
            if self.browser.find_elements_by_xpath("//h2[@class='big']")[0].text == "Upgrade Now":
                intervention = True
        except:
            pass

        if intervention:
            raise Exception("Manual intervention required. Upgrade text detected.")

        self.browser.save_screenshot("{host_name}_success.png".format(host_name=host_name))

    @staticmethod
    def get_host_expiration_days(host, iteration):
        try:
            host_remaining_days = host.find_element_by_xpath(".//a[@class='no-link-style']").text
        except:
            host_remaining_days = "Expires in 0 days"
            pass
        regex_match = re.search("\\d+", host_remaining_days)
        if regex_match is None:
            raise Exception("Expiration days label does not match the expected pattern")
        expiration_days = int(regex_match.group(0))
        return expiration_days

    @staticmethod
    def get_host_link(host, iteration):
        return host.find_element_by_xpath(".//a[@class='link-info cursor-pointer']")

    @staticmethod
    def get_host_button(host, iteration):
        return host.find_element_by_xpath(".//following-sibling::td[4]/button[contains(@class, 'btn')]")

    def get_hosts(self):
        host_tds = self.browser.find_elements_by_xpath("//td[@data-title=\"Host\"]")
        if len(host_tds) == 0:
            raise Exception("No hosts or host table rows not found")
        return host_tds

    def run(self):
        rc = 0
        self.logger.log("Debug level: {debug}".format(debug=str(self.debug)))
        try:
            self.login()
            if not self.update_hosts():
                rc = 3
        except Exception as e:
            self.logger.log(str(e))
            self.browser.save_screenshot("exception.png")
            rc = 2
        finally:
            self.browser.quit()
            myfile = open("/var/www/html/plugins/noip/data/output.json", "w")
            json.dump(self.data, myfile)
            myfile.close()
        return rc


def main(argv=None):
    noip_username, noip_password, noip_threshold, noip_renew, debug,  = get_args_values(argv)
    return (Robot(noip_username, noip_password, noip_threshold, noip_renew, debug)).run()


def get_args_values(argv):
    if argv is None:
        argv = sys.argv
    if len(argv) < 3:
        print("Usage: <noip_username> <noip_password> <threshold> <renew> [<debug-level>]")
        sys.exit(1)

    noip_username = argv[1]
    noip_password = argv[2]
    noip_threshold = int(argv[3])
    noip_renew = int(argv[4])
    debug = 1
    if len(argv) > 5:
        debug = int(argv[5])
    return noip_username, noip_password, noip_threshold, noip_renew, debug


if __name__ == "__main__":
    sys.exit(main())
