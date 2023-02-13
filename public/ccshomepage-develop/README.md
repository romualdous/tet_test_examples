# CCS home page

## Run locally on existed webserver
Load /src folder through any webserver 

## Run on docker
docker build -t <image_tag> . && docker run -p 80:80 -v {PATH_TO_PROJECT}/ccshome/src:/usr/share/nginx/html <image_tag> 
