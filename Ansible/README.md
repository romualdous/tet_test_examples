# Ansible scripts for ProjectVoice-backend

## Before running the scripts

1. Place backend build without Ansible folder and local configuration and cache files in Ansible/deploy folder
2. Place SSH key for the server in Ansible/keys folder

## Running on dev server

Deploying backend:

`ansible-playbook backend-deploy.yml -i hosts.yml --extra-vars host=dev --extra-vars key=<key file location>`