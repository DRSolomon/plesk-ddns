# plesk-ddns
Dynamic DNS Service for Plesk.
setup is working, but documentation to be done.

## Features
* easy, understandable code
* cron update of dns records in plesk
* Recalculation of Serial (YYYYMMDDNN Format) for Slave Server updates
* Call Buddyns to retrieve dns zone update (useful if you need additional nameservers)

## Credits
I was able to build the script thanks to the following blogs. However, it was either developed to complex for me to understand (I must admit, that I am no PHP Developer) or I did not like the concept (direct update in psa tables and using updatefiles).
* https://www.weyand.biz/2017/07/12/dyndns-mit-plesk-ipv4-ipv6-und-php7-224.html
* http://viisauksena.de/blog/eigener-dyndns-ddns-auf-server-mit-plesk/
