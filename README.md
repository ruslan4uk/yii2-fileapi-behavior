Yii2 FileAPI behavior
=====================
Yii2 FileAPI Behavior

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist lav45/yii2-fileapi "*"
```

or add

```
"lav45/yii2-fileapi": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= fileapi\FileAPI::widget(); ?>
```