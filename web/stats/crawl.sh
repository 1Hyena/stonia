#!/bin/bash
date_format="%a %b %d %H:%M:%S %Y"

control_c()
{
    # Run if user hits control-c.
    now=`date +"$date_format"`
    printf "\033[1;35m%s\033[0m :: interrupted\n" "$now"

    now=`date +"$date_format"`
    printf "\033[1;35m%s\033[0m :: closing\n" "$now"
    exit
}

trap control_c SIGINT

now=`date +"$date_format"`
printf "\033[1;35m%s\033[0m :: starting the main loop\n" "$now"

while [ "$close" != "true" ]
do
    now=`date +"$date_format"`
    printf "\033[1;35m%s\033[0m :: starting the crawl script\n" "$now"


    timeout --foreground --signal=INT --kill-after=60 24h \
        php ./crawl.php crawl.json

    now=`date +"$date_format"`
    printf "\033[1;35m%s\033[0m :: loop cycle has ended\n" "$now"
    sleep 10
done

now=`date +"$date_format"`
printf "\033[1;35m%s\033[0m :: closing the main loop\n" "$now"
