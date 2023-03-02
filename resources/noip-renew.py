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
from selenium.webdriver.common.by import By
from datetime import date
from datetime import timedelta
import time
import sys
import os
import re
import base64
import json
import logging
import argparse


class jeedom_utils:
    @staticmethod
    def convert_log_level(level="error"):
        LEVELS = {
            "debug": logging.DEBUG,
            "info": logging.INFO,
            "notice": logging.WARNING,
            "warning": logging.WARNING,
            "error": logging.ERROR,
            "critical": logging.CRITICAL,
            "none": logging.CRITICAL,
        }
        return LEVELS.get(level, logging.CRITICAL)

    @staticmethod
    def set_log_level(level="error"):
        FORMAT = "[%(asctime)-15s][%(levelname)s] : %(message)s"
        logging.basicConfig(
            level=jeedom_utils.convert_log_level(level),
            format=FORMAT,
            # fmt="%(asctime)s.%(msecs)03d",
            datefmt="%Y-%m-%d %H:%M:%S",
        )


class Robot:

    USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:64.0) Gecko/20100101 Firefox/64.0"
    LOGIN_URL = "https://www.noip.com/login"
    HOST_URL = "https://my.noip.com/dynamic-dns"

    def __init__(self, username, password, threshold, renew, rootpath, debug):
        self.debug = debug
        self.username = username
        self.password = password
        self.threshold = threshold
        self.renew = renew
        self.rootpath = rootpath
        self.browser = self.init_browser()
        self.data = []

    @staticmethod
    def init_browser():
        options = webdriver.ChromeOptions()
        # added for Raspbian Buster 4.0+ versions. Check https://www.raspberrypi.org/forums/viewtopic.php?t=258019 for reference.
        options.add_argument("disable-features=VizDisplayCompositor")
        options.add_argument("headless")
        options.add_argument("no-sandbox")  # need when run in docker
        options.add_argument("window-size=1200x800")
        options.add_argument(
            "user-agent={USER_AGENT}".format(USER_AGENT=Robot.USER_AGENT)
        )
        if "https_proxy" in os.environ:
            options.add_argument("proxy-server=" + os.environ["https_proxy"])
        browser = webdriver.Chrome(options=options)
        browser.set_page_load_timeout(90)  # Extended timeout for Raspberry Pi.
        return browser

    def login(self):
        logging.info("Opening {LOGIN_URL}...".format(LOGIN_URL=Robot.LOGIN_URL))
        try:
            self.browser.get(Robot.LOGIN_URL)
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/debug1.png")
        except TimeoutException as ex:
            self.browser.save_screenshot(self.rootpath + "/data/timeout.png")
            logging.error("Timeout has been thrown. " + str(ex))
            self.browser.close()
        except Exception as ex:
            self.browser.save_screenshot(self.rootpath + "/data/exception.png")
            logging.error("Exception has been thrown. " + str(ex))
            self.browser.close()

        logging.info("Logging in...")
        # ele_usr = self.browser.find_element_by_name("username")
        ele_usr = self.browser.find_element(By.NAME, "username")
        # ele_pwd = self.browser.find_element_by_name("password")
        ele_pwd = self.browser.find_element(By.NAME, "password")
        ele_usr.send_keys(self.username)
        # ele_pwd.send_keys(base64.b64decode(self.password).decode('utf-8'))
        ele_pwd.send_keys(self.password)

        button_found = False

        try:
            self.browser.find_element(
                By.XPATH, '//button[@id="clogs-captcha-button"]'
            ).click()
            button_found = True
        except Exception as e:
            logging.info(
                "ERROR: Element by attr id=clogs-captcha-button not found: {e}".format(
                    e=str(e)
                )
            )

        if button_found == False:
            try:
                # self.browser.find_element(By.XPATH,'//button[@data-action="login"]').click()
                self.browser.find_element(
                    By.XPATH, '//button[@data-action="login"]'
                ).click()
                button_found = True
            except Exception as e:
                if self.debug > 1:
                    logging.info(
                        "DEBUG: Element by attr data-action=login not found: {e}".format(
                            e=str(e)
                        )
                    )

        if button_found == False:
            try:
                self.browser.find_element(By.NAME, "Login").click()
                button_found = True
            except Exception as e:
                if self.debug > 1:
                    logging.info(
                        "DEBUG: Element by name login not found: {e}".format(e=str(e))
                    )
                raise Exception("Login button not found")

        if self.debug > 1:
            logging.debug("-- sleeping 3")
        time.sleep(3)
        if self.debug > 1:
            self.browser.save_screenshot(self.rootpath + "/data/debug2.png")

    def update_hosts(self):
        count = 0

        self.open_hosts_page()
        if self.debug > 1:
            logging.debug("-- sleeping 3")
        time.sleep(3)
        iteration = 1

        rows = self.get_lines()
        # logging.info("all rows : " + str("\n\n".join(str(v.text) for v in rows)))
        for row in rows:
            # host details
            host = self.get_host(row)
            host_link = self.get_host_link(
                host, iteration
            )  # This is for if we wanted to modify our Host IP.
            host_button = self.get_host_button(
                host, iteration
            )  # This is the button to confirm our free host
            host_name = host_link.text
            if self.debug > 1:
                logging.info(f"Dealing with {host_name}")

            # get IP details
            ip = self.get_ip(row).text
            if self.debug > 1:
                logging.info(f"IP linked = {ip}")

            expiration_days = self.get_host_expiration_days(self, host, iteration)
            logging.info(f"{host_name} expires in {expiration_days} days")

            renewed = "ok"
            if expiration_days <= 7:
                renewed = "warning"
            if self.renew > 0 and expiration_days <= self.threshold:
                renewed = self.update_host(host_button, host_name)
                if renewed == "ok":
                    expiration_days = self.get_host_expiration_days(
                        self, host, iteration
                    )
                count += 1
            iteration += 1
            self.data.append(
                {
                    "hostname": host_name,
                    "expirationdays": expiration_days,
                    "renewed": renewed,
                    "ip": ip,
                }
            )
        self.browser.save_screenshot(self.rootpath + "/data/results.png")
        logging.info(f"Confirmed hosts: {count}")
        return True

    def open_hosts_page(self):
        logging.info("Opening {HOST_URL}...".format(HOST_URL=Robot.HOST_URL))
        try:
            self.browser.get(Robot.HOST_URL)
        except TimeoutException as ex:
            self.browser.save_screenshot(self.rootpath + "/data/timeout.png")
            logging.error("Timeout has been thrown. " + str(ex))
            self.browser.close()
        except Exception as ex:
            self.browser.save_screenshot(self.rootpath + "/data/exception.png")
            logging.error("Exception has been thrown. " + str(ex))
            self.browser.close()

    def update_host(self, host_button, host_name):
        logging.info("Updating {host_name}".format(host_name=host_name))
        host_button.click()
        if self.debug > 1:
            logging.debug("-- sleeping 3")
        time.sleep(3)
        intervention = False
        try:
            if (
                self.browser.find_elements_by_xpath("//h2[@class='big']")[0].text
                == "Upgrade Now"
            ):
                intervention = True
        except:
            pass

        if intervention:
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/intervention.png")
            logging.info(
                "{host_name} requires manual intervention for update".format(
                    host_name=host_name,
                )
            )
            return "error"
        else:
            self.browser.save_screenshot(
                self.rootpath
                + "/data/{host_name}_success.png".format(host_name=host_name)
            )
            return "ok"

    @staticmethod
    def get_host_expiration_days(self, host, iteration):
        try:
            host_remaining_days = host.find_element(
                By.XPATH, ".//a[contains(@class,'no-link-style')]"
            ).get_attribute("data-original-title")
            if host_remaining_days is None:
                host_remaining_days = host.find_element(
                    By.XPATH, ".//a[contains(@class,'no-link-style')]"
                ).text
            if self.debug > 1:
                logging.info(
                    "host remaining days found: {days}".format(
                        days=str(host_remaining_days)
                    )
                )
        except:
            host_remaining_days = "Expires in 0 days"
            pass
        regex_match = re.search("\\d+", host_remaining_days)
        # if regex_match is None:
        #    host_remaining_days = host.find_element(By.XPATH,".//a[@class='no-link-style']").text
        # regex_match = re.search("\\d+", host_remaining_days)
        if regex_match is None:
            raise Exception("Expiration days label does not match the expected pattern")
        expiration_days = int(regex_match.group(0))
        return expiration_days

    @staticmethod
    def get_host_link(host, iteration):
        return host.find_element(By.XPATH, ".//a[@class='link-info cursor-pointer']")

    @staticmethod
    def get_host_button(host, iteration):
        return host.find_element(
            By.XPATH, ".//following-sibling::td[4]/button[contains(@class, 'btn')]"
        )

    def get_host(self, row):
        if self.debug > 1:
            logging.info("Getting host detail...")
        host_tds = row.find_element(By.XPATH, './/td[@data-title="Host"]')
        # logging.info("Result : " + host_tds.text)
        if not host_tds:
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/debug3.png")
            raise Exception("No host row found")
        return host_tds

    def get_ip(self, row):
        if self.debug > 1:
            logging.info("Getting IP detail...")
        ip_span = row.find_element(By.XPATH, './/td[@data-title="IP / Target"]')
        if not ip_span:
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/debug3_ip.png")
            raise Exception("No IP span found")
        return ip_span

    def get_hosts(self):
        if self.debug > 1:
            logging.info("Getting hosts list...")
        host_tds = self.browser.find_elements(By.XPATH, '//td[@data-title="Host"]')
        if len(host_tds) == 0:
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/debug3.png")
            raise Exception("No hosts or host table rows not found")
        return host_tds

    def get_lines(self):
        if self.debug > 1:
            logging.info("Getting all lines hosts list...")
        host_trs = self.browser.find_elements(
            By.XPATH, '//tr[@class="table-striped-row"]'
        )
        if len(host_trs) == 0:
            if self.debug > 1:
                self.browser.save_screenshot(self.rootpath + "/data/debug3_1.png")
            raise Exception("No table rows found")
        return host_trs

    def run(self):
        rc = 0
        logging.info("Start running process")
        try:
            self.login()
            if not self.update_hosts():
                rc = 3
        except Exception as e:
            logging.info(str(e))
            self.browser.save_screenshot(self.rootpath + "/data/exception.png")
            self.data = {"msg": str(e)}
            rc = 2
        finally:
            self.browser.quit()
            myfile = open(self.rootpath + "/data/output.json", "w")
            json.dump(self.data, myfile)
            myfile.close()
        return rc


def main(argv=None):
    try:

        parser = argparse.ArgumentParser(description="Python for NoIp plugin")
        parser.add_argument("--loglevel", help="Log Level for the script", type=str)
        parser.add_argument("--user", help="username", type=str)
        parser.add_argument("--pwd", help="password", type=str)
        parser.add_argument(
            "--threshold", help="Threshold to renew the domain", type=int
        )
        parser.add_argument("--renew", help="Renew enable", type=int)
        parser.add_argument("--noip_path", help="path to the plugin", type=str)
        # parser.add_argument("--pid", help="Value to write", type=str)
        args = parser.parse_args()

        _noip_username = args.user
        _noip_password = args.pwd
        _noip_threshold = args.threshold
        _noip_renew = args.renew
        _noip_rootpath = args.noip_path
        _debug = args.loglevel

        jeedom_utils.set_log_level(_debug)

        logging.info("Log level : " + str(_debug))
        logging.info("User : " + str(_noip_username))
        logging.info("Threshold : " + str(_noip_threshold))
        logging.info("Renew : " + str(_noip_renew))

        return (
            Robot(
                _noip_username,
                _noip_password,
                _noip_threshold,
                _noip_renew,
                _noip_rootpath,
                2 if _debug == "debug" else 0,
            )
        ).run()
    except TimeoutException as ex:
        logging.error("Timeout has been thrown. " + str(ex))
    except Exception as ex:
        logging.error("Exception has been thrown. " + str(ex))


if __name__ == "__main__":
    sys.exit(main())
