FROM python:3

ARG USER=jenkins
ARG UID=110
ARG GID=115
ARG PW=jenkins

RUN useradd -m ${USER} --uid=${UID} && echo "${USER}:${PW}" | \
    chpasswd

RUN pip install --upgrade pip && pip install ansible