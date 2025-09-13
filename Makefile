
.DEFAULT_GOAL := build

build:
	docker build . -t agendav-builder:dev


test:
	docker build . -f Dockerfile.tests


# pour le reste test et autre voir dans ../agendav-docker
#
