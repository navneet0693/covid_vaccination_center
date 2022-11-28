# COVID Vaccination Center #

This repositry contains codebase and database for a demo project called "COVID Vaccination Center".
This website provides functionality for "authenticated" user traffic to register on a COVID vaccination center in its city.

Useful links for developers:
- [Drupal 9](https://www.drupal.org/project/drupal/releases/9.4.8)
- [Getting Started with Drupal](https://www.drupal.org/docs/develop/using-composer/starting-a-site-using-drupal-composer-project-templates)
- [Composer](https://getcomposer.org/)
- [Drush](https://packagist.org/packages/drush/drush)

# Installation #

### Install with Composer ###

1. Clone the repository locally.
```
git clone git@github.com:navneet0693/covid_vaccination_center.git
```
2. Run composer install.
```
composer install
```
3. Create a database locally.
4. Update the database credentials in `settings.php`
```
vim web/sites/default/settings.php
```
5. Extract the database located in folder `db`.
6. Import the database using Drush or any other tool.
```
drush sql:cli < db/covid_vaccination_center.sql
```
7. You need to take care of following things:
- You will need `PHP 8.0` or `PHP 8.1` to run this project.
- Settings up the access URL.
- Composer 2.

# Contribute #
Feel free to create an issues or to raise pull request. The development takes place [here](https://github.com/navneet0693/covid_vaccination_center).

Do you want to join us in this effort? We are welcoming your contribution, reachout to me via [drupal.org](https://www.drupal.org/u/navneet0693).
