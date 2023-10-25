
## Badges

Badges are from: [shields.io](https://shields.io/)

![Symfony](https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)


# ToDoandCo

This project consists in auditing, testing, profiling & making improvements to an existing to-do list app built in Symfony 3.1 and upgrading it to 5.4

Click here to check how to [contribute](https://github.com/BenjVA/ToDoandCo/blob/main/CONTRIBUTING.md)


## Requirements

- PHP 8.1 or above
- [Composer](https://getcomposer.org/download/)
- PhpMyAdmin 5.2.1 (conflicts with <PHP8.1)
- Download the [Symfony CLI](https://symfony.com/download).
## Installation

Fork or clone the repository [here](https://github.com/BenjVA/ToDoandCo)


Then run 
```bash
composer install
```
This will install all libraries used for this project

Then create the database and update your data fixtures


```bash
php bin/console doctrine:database:create
```
- Generate the database schema :
```bash
php bin/console doctrine:schema:update --force
```
- And run this command to load the initial data fixtures :
```bash
php bin/console doctrine:fixtures:load
```

Launch your symfony server
```bash
symfony server:start
```
    
## Environment Variables

To run this project, you will need to add the following environment variables to your .env file

`APP_ENV=dev`

`DATABASE_URL=yourdatabase`

