#!/usr/bin/env python3
import re
import json


def parse(iwlist_output, max_frequency=3.0):
    SSIDs = re.findall(r'^\s*ESSID:"(.+)"$', iwlist_output, flags=re.MULTILINE)
    frequencies = re.findall(r'^\s*Frequency:(\d+\.?\d*) GHz \(Channel \d+\)$',
                             iwlist_output,
                             flags=re.MULTILINE)
    auth = re.findall(r'^\s*Encryption key:(\w+)$',
                      iwlist_output,
                      flags=re.MULTILINE)
    qualities = re.findall(r'^\s*Quality=(\d+\/\d+)',
                           iwlist_output,
                           flags=re.MULTILINE)

    auth = list(map(lambda on_off: on_off == 'on', auth))
    qualities = list(map(eval, qualities))

    return {
        SSID: dict(zip(['frequency', 'authentication', 'quality'], data))
        for SSID, data in zip(SSIDs, zip(frequencies, auth, qualities))
        if float(data[0]) <= max_frequency
    }


if __name__ == '__main__':
    import sys

    parsed = parse(sys.stdin.read())
    json.dump(parsed, sys.stdout)
