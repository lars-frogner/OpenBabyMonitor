#!/usr/bin/env python3

if __name__ == '__main__':
    import mic
    import standby
    mic.select_mic(auto_choice=True)
    standby.set_standby()
