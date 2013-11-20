#!/usr/bin/env python

import yaml
import os
import sys
import re
from bs4 import BeautifulSoup

config_file = "local/config.yaml"
asciidoc_backend = "local/asciidoc/simple.conf"
index_page = "server/index.html"
nav_bar = "server/core/nav.inc"

if not os.path.exists(config_file):
  sys.stderr.write("Error, file " + config_file + "not found\n")
  sys.exit(1)

config_data = yaml.load(open(config_file, "r"))

# modify asciidoc/simple.conf
sys.stdout.write("Modify file " + asciidoc_backend + " ...\n")
asciidoc_conf = open(asciidoc_backend, "r+")
asciidoc_data = asciidoc_conf.read()
asciidoc_data = re.sub("blogroot=(.*)", "blogroot=" + config_data["blogroot"],
	asciidoc_data)
asciidoc_data = re.sub("blogtitle=(.*)", "blogtitle=" + config_data["blogtitle"],
	asciidoc_data)
asciidoc_conf.seek(0)
asciidoc_conf.write(asciidoc_data)
asciidoc_conf.truncate()
asciidoc_conf.close()

# modify index.html title
sys.stdout.write("Modify file " + index_page + " ...\n")
findex = open(index_page, "r+")
index_soup = BeautifulSoup(findex.read())
index_soup.title.string = config_data["blogtitle"]
findex.seek(0)
findex.write(index_soup.prettify())
findex.truncate()
findex.close()

#modify nav bar
sys.stdout.write("Modify file " + nav_bar + " ...\n")
fnav = open(nav_bar, "r+")
nav_soup = BeautifulSoup(fnav.read())
nav_item_soup = nav_soup.find(id="nav_item")
# change home url
nav_item_li = nav_item_soup.find("li")
nav_item_li.a["href"] = config_data["blogroot"]
# add other nav item from config.yaml
for item in config_data["nav_items"]:
  key = str(item)
  value = config_data["nav_items"][key]
  nav_a = nav_soup.new_tag("a")
  nav_a["href"] = config_data["blogroot"] + "/" + value["url"]
  nav_a_icon = nav_soup.new_tag("i")
  nav_a_icon["class"] = "icon-" + value["icon"] + " icon-white"
  nav_a_str = nav_soup.new_string(key)
  nav_a.append(nav_a_icon)
  nav_a.append(nav_a_str)
  nav_li = nav_soup.new_tag("li")
  nav_li.append(nav_a)
  nav_item_soup.append(nav_li)
fnav.seek(0)
fnav.write(nav_soup.prettify())
fnav.truncate()
fnav.close()

