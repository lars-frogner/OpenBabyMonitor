#!/usr/bin/env python3
import re
import json


def parse(iwlist_output):
    SSIDs = re.findall(r'^\s*ESSID:"(.+)"$', iwlist_output, flags=re.MULTILINE)
    auth = re.findall(r'^\s*Encryption key:(\w+)$',
                      iwlist_output,
                      flags=re.MULTILINE)
    qualities = re.findall(r'^\s*Quality=(\d+\/\d+)',
                           iwlist_output,
                           flags=re.MULTILINE)

    auth = list(map(lambda on_off: on_off == 'on', auth))
    qualities = list(map(eval, qualities))

    return {
        SSID: dict(zip(['authentication', 'quality'], data))
        for SSID, data in zip(SSIDs, zip(auth, qualities))
    }


if __name__ == '__main__':
    import sys

    assert len(
        sys.argv
    ) > 1, 'Usage: sudo iwlist <interface> scan | {} <Output path>'.format(
        sys.argv[0])
    output_path = sys.argv[1]

    parsed = parse(sys.stdin.read())

    with open(output_path, 'w') as f:
        json.dump(parsed, f)
