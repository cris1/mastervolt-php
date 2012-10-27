mastervolt-php
==============

PHP class to get data from Mastervolt solar invertors

It is written by Casper Langemeijer, and subject to the GPL license v3.0.

This work is based on Mastervolt specifications I've found everywhere on the net.
It currently supports XS3200 and XS2000 (= untested) models, but adding support for other
Mastervolt devices should be trivial. See further in this document what to do
if you have some other model and still want to use this class.

This class does **not** support mutiple devices on a single RS-485 interface. I've read
somewhere that bigger mastervolt devices are implemented as two devices in a single box.
As such, they are not supported. I want to implement this, but I have no way to test it.
If you have such a Mastervolt device, please contact me at casper@langemeijer.eu

This code probably only works on linux machines.

You can read more on this at http://blog.casperlangemeijer.nl/

Usage
-----

See the example-*.php files for examples how to use the class. It boils down to this:
Create a MasterVolt object and use the methods to query data from your device.

On creating the object, a command is sent over the serial connection to discover your
Mastervolt device. If your device is never discovered a big number of things can be wrong:
**Make sure the serial connection is working.** Use the software provided by Mastervolt
to test your hardware. Please **do not** contact me if you have not **verified** this.

Make sure you use the correct serial device. RS-485 to usb converters generally use
/dev/ttyUSBxx. If you use a USB converter: See your syslog while plugging the converter in
the machine. That should tell you your device name.

If that is not enough info: Create the MasterVolt object in debug mode (second parameter
set to *true*) ie: $MasterVolt = new MasterVolt("/dev/ttyUSB0", true); This will echo
anything that's on the serial port to the console. If you see data but still do not
understand what you see: Copy-paste your script (please make it minimal), together with the
output and email to casper@langemeijer.eu. I'm happy to help you.

Assuming you have a working connection to the Mastervolt device you can use any of these
three methods to query data:

- $MasterVolt->getDayTotal($day)
- $MasterVolt->getDeviceInfo()
- $MasterVolt->getCurrentStatus()

For more information on these methods see below.

This class is ment to be very reliable, as I've discovered the RS-485 serial wire protocol
regularly loses data. For that reason it automatically retries commands, if and incorrect
response is received, or the device does not reply to our commands.

To tweak this, you can set these parameters:
- $MasterVolt->setReadTimeout($timeout) *defaults to 3 seconds*
- $MasterVolt->setWriteTimeout($timeout) *defaults to 1 seconds*
- $MasterVolt->setCommandRetries($retries) *defaults to 5 retries*


### getDayTotal($day)

### getDeviceInfo()

### getCurrentStatus()

