FROM node:latest
WORKDIR /usr/src/app
COPY . .
CMD ["tail", "-f", "/dev/null"]
