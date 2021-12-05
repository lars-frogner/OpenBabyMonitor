#!/bin/bash

sed -n 's/[[:space:]]*alpha_2_code="\(.*\)"$/\1/p' /usr/share/xml/iso-codes/iso_3166.xml
