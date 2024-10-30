#!/bin/bash
# Example usage: ./guildmasters.sh ./stuff/SRC/area/*.are
date_format="%a %b %d %H:%M:%S %Y"

control_c()
{
    # Run if user hits control-c.
    now=`date +"$date_format"`
    printf "\033[1;36m%s\033[0m :: Caught signal (SIGINT).\n" "$now" 1>&2

    now=`date +"$date_format"`
    printf "\033[1;36m%s\033[0m :: Closing.\n" "$now" 1>&2
    exit
}

trap control_c SIGINT

SKILLS=()

function process_line {
    local words=( $1 )
    local word="${words[0]}"
    if [ "$sect_area" = false ]
    then
        if [ "$word" = "#AREA" ]
        then
            sect_area=true
            area_name=`printf "%s" "$1" | cut -d " " -f2- | tr -d '"' | tr -d '^'`
        fi
    else
        if [ "$sect_mobiles" = false ]
        then
            if [ "$word" = "#MOBILES" ]
            then
                sect_mobiles=true
            fi
        else
            if [ "${word:0:1}" == "#" ] && [ "$mob_train" != "" ]
            then
                #End of last mobile.
                :
            fi

            if [ "$word" = "#0" ]
            then
                sect_mobiles=false
                skip_rest=true
            else
                if [[ $word == "#"+([0-9]) ]]; then
                    mob_vnum=`printf "%s" "$1" | tr -d '#'`
                    mob_train=""
                    expect="name"
                elif [ "$expect" = "name" ]
                then
                    mob_name=`printf "%s" "$1" | tr -d '"' | tr -d '^'`
                    expect="short_desc"
                elif [ "$expect" = "short_desc" ]
                then
                    mob_short_desc=`printf "%s" "$1" | tr -d '"' | tr -d '^'`
                    expect="train"
                elif [ "$expect" = "train" ] && [ "$word" = "T" ] && [ "${words[2]}" = "" ]
                then
                    if [[ ${words[1]} == +([0-9]) ]]; then
                        local gsn="${words[1]}"
                        local old="${SKILLS["$gsn"]}"
                        local fmt="{\"vnum\":%s,\"short_desc\":\"%s\",\"area\":\"%s\"}"
                        if [ "$old" = "" ]
                        then
                            SKILLS["$gsn"]=`printf "$fmt" "$mob_vnum" "$mob_short_desc" "$area_name"`
                        else
                            SKILLS["$gsn"]=`printf "%s, $fmt" "$old" "$mob_vnum" "$mob_short_desc" "$area_name"`
                        fi
                        expect="train"
                    fi
                fi
            fi
        fi
    fi
}

shopt -s nullglob
for f in $*
do
    fname=$(basename "$f")

    now=`date +"$date_format"`
    printf "\033[1;36m%s\033[0m :: Reading %s...\n" "$now" "$fname" 1>&2

    sect_area=false
    sect_mobiles=false
    skip_rest=false
    expect=""

    while read -r LINE
    do
        line=`printf "%s" "$LINE" | tr -d '\r'`
        process_line "$line"
        if [ "$skip_rest" = true ]
        then
            break
        fi
    done < "$f"
done

first=true
printf "{\n"
for i in "${!SKILLS[@]}"
do
    if [ "$first" = false ]
    then
        printf ",\n"
    fi

    printf "\"%s\": [%s]" "$i" "${SKILLS[$i]}"

    if [ "$first" = true ]
    then
        first=false
    fi
done
printf "\n}\n"

exit 0
