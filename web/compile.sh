#!/bin/bash
../../mdma/mdma --verbose -f framework.html --monolith --minify -o index.html \
    content.md
sed -i 's/src=".\/files\/card.jpg"/src="files\/count.png"/' index.html
