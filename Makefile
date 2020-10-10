.PHONY: build run service

build:
	docker-compose build
	docker run --rm --interactive --tty --volume $(PWD):/app composer install

run:
	docker-compose run gpx2img php gpx2image.php ${file}

service:
	docker-compose run gpx2img php -m
