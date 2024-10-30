#!/bin/bash
../../mdma/mdma --verbose -f framework.html --monolith --minify -o index.html \
    content.md
#sed -i 's/src=".\/files\/card.jpg"/src="files\/count.png"/' index.html
#sed -i 's/title="Illustration of a battle"/title="Player count chart"/' index.html
#sed -i 's/alt="Illustration of a battle"/alt="Player count chart"/' index.html
