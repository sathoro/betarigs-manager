## Betarigs Manager

Created for the [Betarigs API challenge](https://bitcointalk.org/index.php?topic=663828.0), the purpose of this is to make it incredibly easy to automate the rental of rigs at Betarigs. You can specify the desired hashing power, cost, and pool information and everything else is done for you.

# Steps to Install
- clone this repository to a server with PHP and Apache
- open `EXAMPLE.env.php` and set your Betarigs API key, Coinbase API key, and Coinbase API secret
- rename `EXAMPLE.env.php` to `.env.php` (the dot before `env` is important!)
- run `composer install`
- you may need to `chmod -R 777 app/storage`
- set your document root to the `public` directory

Most of the custom code for this application is in `app/routes.php` and `app/library/Betarigs.php`.

![Screenshot](http://i.imgur.com/l8j4lXw.png)
