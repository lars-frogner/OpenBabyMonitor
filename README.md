# Raspberry Pi baby monitor

The purpose of this project is to make use of the great flexibility and availability of the [Raspberry Pi](https://www.raspberrypi.org/) mini-computer to create a user friendly and capable yet inexpensive baby monitor or babycall. Equipped with a small microphone and optionally an integrated camera, the device is controlled through a local web site accessible with a phone or computer on a wireless network. The device can then provide a live feed of audio or video to this web site, or listen passively and give a notification once the baby is crying.

## Features

* Fully DIY and open source. Simply obtain a Raspberry Pi and a few peripherals (see [Equipment](#Equipment)), download one of the pre-built disk images and install it on the Pi (see [Installation](#Installation)).
* Controlled through a web browser from any device on the local network. No special reciever required, and no client software to install.
* Can either be connected to the home Wi-Fi or provide its own wireless access point.
* Detects baby crying using either a simple loudness threshold or a neural network trained on Google's [AudioSet](https://research.google.com/audioset/) dataset to distinguish between crying, babbling and ambient sounds.
* Live audio streaming and optionally video streaming in up to 1080p resolution.
* Low power consumption (see [Power consumption](#Power-consumption)), enabling tens of hours of battery life when powered by even a modestly sized portable power bank.

## Equipment

* A Raspberry Pi computer, preferably a [Raspberry Pi Zero W](https://www.raspberrypi.com/products/raspberry-pi-zero-w/), which is priced at around $10. The non-W version of the Pi Zero will not do, as it does not have an inbuilt network adapter. Other, more powerful but pricier models like the [Zero 2 W](https://www.raspberrypi.com/products/raspberry-pi-zero-2-w/), [3B/3B+](https://www.raspberrypi.com/products/raspberry-pi-3-model-b-plus/) or [4B](https://www.raspberrypi.com/products/raspberry-pi-4-model-b/) should also work.
* A MicroSD card with at least 8 GB of storage.
* A [5.1V  power supply with Micro USB plug](https://www.raspberrypi.com/products/micro-usb-power-supply/) for Pi Zero 1/2 or Pi 3, or [with USB-C plug](https://www.raspberrypi.com/products/type-c-power-supply/) for Pi 4. These also cost around $10. To avoid the need for a wall outlet, a 5V power bank with an appropriate cable can be used instead.
* A case for the Pi, e.g. [this](https://www.raspberrypi.com/products/raspberry-pi-zero-case/) for Pi Zero, [this](https://www.raspberrypi.com/products/raspberry-pi-3-case/) for Pi 3B or [this](https://www.raspberrypi.com/products/raspberry-pi-4-case/) for Pi 4B. These official cases cost around $6. Note that the Zero models have a smaller form factor, and unlike for the non-Zero models their official case comes with a convenient mount and hole for the [Pi Camera](https://www.raspberrypi.com/products/camera-module-v2/).
* A [USB microphone](https://www.adafruit.com/product/3367), with an [adapter to Micro USB](https://www.adafruit.com/product/2910) if using a Pi Zero 1/2. This costs around $9.
* For optional video streaming, the [Raspberry Pi Camera Module 2](https://www.raspberrypi.com/products/camera-module-v2/), or its [NoIR](https://www.raspberrypi.com/products/pi-noir-camera-v2/) variant is required. These are priced at around $27. The NoIR version has no infrared blocking filter, making the camera more sensitive at the expense of colour accuracy. (Hence the NoIR version is arguably the best choice for use in a baby monitor.) Note that the cheaper ZeroCam is not supported.
* For mounting the Pi on a bed or a stroller, a flexible phone tripod can be of great use.

## Installation

## Power consumption

Below are measured values of the power consumption of a Pi Zero baby monitor in different modes of operation.

| Mode                             | Power (W) |
| -------------------------------- | --------: |
| Standby                          |      0.50 |
| Cry detection (threshold)        |      0.55 |
| Cry detection (small neural net) |    < 0.60 |
| Cry detection (large neural net) |    < 0.75 |
| Audio streaming                  |      0.60 |
| Video streaming (480p)           |      1.30 |
| Video streaming (720p)           |      1.45 |
| Video streaming (1080p)          |      1.65 |

Based on this, a 5000mAh 5V battery powering a Pi Zero baby monitor should last between 15 hours (if continuously streaming full HD video) and 50 hours (if in standby) on a single charge.

## Manual setup

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
    Try adding `.local`, `.home` or `.lan` after `raspberrypi` if it doesn't work.

5. When you have successfully SSH'ed into the device, proceed by updating the package lists and installing Git:
    ```
    sudo apt -y update
    sudo apt -y install git
    ```

6. Use Git to clone the `babymonitor` source code repository:
    ```
    git clone https://github.com/lars-frogner/babymonitor.git
    ```

7. (Optional) Edit environment variables in `babymonitor/config/setup_config.env`. The default version of the file looks like this:
    ```bash
    # The name of the Linux user that will control the baby monitor
    # (this is best left as pi)
    BM_USER=pi

    # This name will be used as the domain name of the baby monitor website and the name of the wireless access point
    BM_HOSTNAME=babymonitor

    # The wifi channel to use for the wireless access point
    # (try changing to another channel between 1 and 11 if the connection to the access point is unreasonably unstable)
    BM_AP_CHANNEL=7

    # The country code that will be used for networking
    BM_COUNTRY_CODE=NO

    # The time zone that the device should use
    BM_TIMEZONE=Europe/Oslo

    # Set to 1 to enable debugging features
    BM_DEBUG=0
    ```
    Change the values to your preference.

8.  Run the setup script:
    ```
    babymonitor/setup.sh
    ```
    You will be prompted to change the password for the `pi` user. During further execution of the script you will be asked to create a couple of new passwords. First for the baby monitor website, and then for the wireless access point. The device will reboot when finished.

After the device has rebooted you will be able to log in using the new hostname (defined by `BM_HOSTNAME` in `babymonitor/config/setup_config.env`) and the password for the `pi` user you entered in the previous step:
```
ssh pi@<hostname>
```
