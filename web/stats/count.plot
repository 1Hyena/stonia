set terminal pngcairo transparent enhanced font "arial,10" fontscale 2.0 size \
    1200, 630
set output 'count.png'
set border 3 front lt black linewidth 2.000 dashtype solid
set format x "%a\n%H:%M" timedate
set timestamp top
set format y " %4.0f"
set grid nopolar
set grid xtics nomxtics noytics nomytics noztics nomztics nortics nomrtics \
    nox2tics nomx2tics noy2tics nomy2tics nocbtics nomcbtics
set grid layerdefault linecolor rgb "gray" linewidth 0.750 dashtype solid, \
    linecolor rgb "gray" linewidth 0.750 dashtype solid
#set key fixed left top vertical Left reverse enhanced autotitle box lt black \
#    linewidth 2.000 dashtype solid
#set key noinvert samplen 2 spacing 1 width 2 height 0
set key off
set datafile separator ","
set style data lines
set xtics border in scale 1,0.5 nomirror norotate autojustify
set ytics border in scale 1,0.5 nomirror norotate autojustify
set cbtics border in scale 1,0.5 nomirror norotate autojustify
set title "Stonia Player Count"
set title  font ":Crimson-Bold,15" textcolor lt -1 norotate
set trange [ * : * ] noreverse nowriteback
set urange [ * : * ] noreverse nowriteback
set vrange [ * : * ] noreverse nowriteback
set xrange [ * : * ] noreverse writeback
set x2range [ * : * ] noreverse writeback
set yrange [ * : * ] noreverse writeback
set y2range [ * : * ] noreverse writeback
set zrange [ * : * ] noreverse writeback
set cbrange [ * : * ] noreverse writeback
set rrange [ * : * ] noreverse writeback
set colorbox vertical origin screen 0.9, 0.2 size screen 0.05, 0.6 front \
    noinvert bdefault
NO_ANIMATION = 1
data = "< grep \",total,\" count.csv"
plot data using ($1+(+2*3600)):3 lw 4 lt rgb "red" title "Online"
