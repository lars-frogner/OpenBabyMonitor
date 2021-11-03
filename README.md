
# Setup

1. Write a Raspbian Buster Lite image to an SD card.

2. Create empty file called `ssh` and a text file called `wpa_supplicant.conf` containing
```
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
country=<country code>

network={
ssid="<network name>"
psk="<network password>"
}
```
in the boot directory of the SD card.

3. Insert the SD card into the Raspberry Pi.

4. SSH into the Raspberry Pi (the password is `raspberry`):
```
ssh pi@raspberrypi
```
Try `raspberrypi.local` or `raspberrypi.home` as hostname if `raspberrypi` doesn't work.

5. Install Git:
```
sudo apt -y install git
```

6. Clone the babymonitor repository:
```
git clone https://github.com/lars-frogner/babymonitor.git
```

7. (Optional) Edit environment variables in `babymonitor/config/setup_config.env`.

8. Run the device setup script as root:
```
sudo babymonitor/setup_device.sh
```
You will be asked for a new password. The device will reboot when finished.

9. Log in using the new hostname (defined by `BM_HOSTNAME` in `babymonitor/config/setup_config.env`) and password:
```
ssh pi@<hostname>
```

10. Run the main setup script (as `pi`):
```
babymonitor/setup.sh
```

11. Run the network configuration script:
```
babymonitor/setup_network.sh
```
