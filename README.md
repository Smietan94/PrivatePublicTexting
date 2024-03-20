# PrivatePublicTexting

## Description

This chat application is built with PHP 8 on the Symfony 6.4 framework. To establish real-time communication it use the Mercure protocol and leverages PostgreSQL for its database needs. The application allows users to add others to their friends list, engage in private conversations with those on the list, and create group chats. It also supports sending images within messages. The frontend is designed with Bootstrap 5 and incorporates Symfony UX Turbo and Stimulus for a dynamic and engaging user interface.

## Installation

1. Download / Clone repository:
  - ssh: _`git clone git@github.com:Smietan94/PrivatePublicTexting.git`_
  - https: _`git clone https://github.com/Smietan94/PrivatePublicTexting.git`_
2. Create a Docker container by running the following command in your terminal (remember! You use this command from your docker directory): _`docker-compose up -d --build`_
3. Remember that configs in `.env ` are just variables use in development, before switching to production change them!!!
4. Now open docker container _`docker exec -it PrivatePublicTexting-app bash`_
4. Next install composer _`composer install`_
5. You have to carry out migrations _`symfony console doctrine:migrations:migrate`_
6. Last step is to take care of encore/webpack
    - exit docker container
    - install yarn _`yarn install`_
    - and integrate webpack with symfony application _`yarn encore dev`_
    - finally run _`yarn watch`_ to update all css and js changes in real time

## User manual



## Screenshots



## Author:
[Smietan94](https://github.com/Smietan94)
