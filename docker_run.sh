#!/bin/bash
set -e

echo ""
docker build -t webapp ./site_AI
echo ""
echo ""
docker build -t aiservice ./Assist
echo ""
echo ""
docker build -t telegrambot ./msnger
echo ""

docker run -d -p 8080:80 --name my_web_server webapp
docker run -d -p 5000:5000 --name aiservice_container aiservice
python3 app.py