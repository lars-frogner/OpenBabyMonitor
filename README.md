# Raspberry Pi baby monitor

## Equipment

* [Raspberry Pi Zero W](https://www.raspberrypi.com/products/raspberry-pi-zero-w/)
* [5.1V / 2.5A DC power supply with Micro USB plug](https://www.raspberrypi.com/products/micro-usb-power-supply/)
* [Case for Raspberry Pi Zero](https://www.raspberrypi.com/products/raspberry-pi-zero-case/)
* [USB microphone](https://www.adafruit.com/product/3367) with [adapter to Micro USB](https://www.adafruit.com/product/2910)

## Setup

1. Write a [Raspbian Buster Lite image](https://downloads.raspberrypi.org/raspbian_lite/images/raspbian_lite-2020-02-14/2020-02-13-raspbian-buster-lite.zip) to an SD card, using for instance the [Raspberry Pi Imager](https://www.raspberrypi.com/software/).

2. Create an empty file called `ssh` and a text file called `wpa_supplicant.conf` containing
    ```
    ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
    update_config=1
    country=<2-character country code>

    network={
    ssid="<network name>"
    psk="<network password>"
    }
    ```
    in the `boot` directory on the SD card. Insert the country code, SSID and password for your local wireless network. Make sure to keep the double quotes around the password. The `wpa_supplicant.conf` file lets the device connect to the local wireless network, and the `ssh` file enables us to acces to the device remotely via SSH.

3. Insert the SD card into the Raspberry Pi.

4. Open a terminal and access the Raspberry Pi using SSH (the password is `raspberry`):
    ```
    ssh pi@raspberrypi
    ```
    Try `raspberrypi.local` or `raspberrypi.home` as hostname if `raspberrypi` doesn't work.

5. When you have successfully SSH'ed into the device, proceed by installing Git:
    ```
    sudo apt -y install git
    ```

6. Use Git to clone the `babymonitor` source code repository:
    ```
    git clone https://github.com/lars-frogner/babymonitor.git
    ```

7. (Optional) Edit environment variables in `babymonitor/config/setup_config.env`. The default version of the file looks like this:
    ```bash
    # Whether to enable features requiring a camera (set to false if the device has no camera)
    BM_USE_CAM=true

    # This name will be used as the domain name of the baby monitor website and the name of the wireless access point
    BM_HOSTNAME=babymonitor

    # The name of the Linux user that will control the baby monitor (the user must already exist)
    BM_USER=pi

    # The country code that will be used for networking
    BM_COUNTRY_CODE=NO

    # The time zone that the device should use
    BM_TIMEZONE=Europe/Oslo
    ```
    Change the values to your preference.

8. Run the device setup script:
    ```
    sudo babymonitor/setup_device.sh
    ```
    You will be asked to enter a new device password. The device will reboot when finished.

9.  Log in using the new hostname (defined by `BM_HOSTNAME` in `babymonitor/config/setup_config.env`) and the password you entered in the previous step:
    ```
    ssh pi@<hostname>
    ```

10. Run the main setup script:
    ```
    babymonitor/setup.sh
    ```
    You will be asked to enter a new password for the baby monitor website.

11. Run the network configuration script:
    ```
    babymonitor/setup_network.sh
    ```
    You will be asked to enter a new password for the baby monitor wireless access point.
