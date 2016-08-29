# Highco SlackErrorNotifier Bundle

## What is it?

This bundle format exception and php error to send it in slack. You can also be notified of 404 or PHP fatal errors.
It's highly inspired from [Elao error notifier bundle](https://github.com/Elao/ErrorNotifierBundle)


## Installation

### Symfony >= 2.3

Add this in your `composer.json`

    "require": {
        "highco/slack-error-notifier-bundle" : "dev-master"
    },

And run `php composer.phar update highco/slack-error-notifier-bundle`


### Register the bundle in `app/AppKernel.php`

```php
public function registerBundles()
{
    return array(
        // ...
        new Highco\SlackErrorNotifierBundle\HighcoSlackErrorNotifierBundle(),
    );
}
```

## Configuration

Add in your `config_prod.yml` file, you don't need error notifier when you are in dev environment.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    handle404: true # default :  false
    handleHTTPcodes: ~
    handlePHPErrors: true # catch fatal erros and log them
    handlePHPWarnings: true # catch warnings and log them
    handleSilentErrors: false # don't catch error on method with an @
    ignoredClasses: ~
```


### How to ignore errors raised by given classes ?

Sometimes, you want the bundle not to send logs for errors raised by a given class. You can now do it by adding the name of the class raising the error in the `ignored_class` key.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    ignoredClasses:
        - "Guzzle\Http\Exception\ServerErrorResponseException"
        - ...
```

### How to handle other HTTP errors by given error code ?

If you want the bundle to send logs for other HTTP errors than 500 and 404, you can now specify the list of error codes you want to handle.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    handleHTTPcodes:
        - 405
        - ...
```

### How to avoid sending many same messages for one error ?

If an error occurs on a website with a lot of active visitors you'll get spammed by the notifier for the same error.

In order to avoid getting spammed, use the `repeatTimeout` option.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    repeatTimeout: 3600
```

In this example, if an errors X occurs, and the same error X occurs again within 1 hour, you won't recieve a 2nd log.

## Twig Extension

There are also some extensions that you can use in your Twig templates (thanks to [Goutte](https://github.com/Goutte)).

Extends Twig with:

```twig
{{ "my string, whatever" | pre }}  --> wraps with <pre>
{{ myBigVar | yaml_dump | pre }} as {{ myBigVar | ydump }} or {{ myBigVar | dumpy }}
{{ myBigVar | var_dump | pre }}  as {{ myBigVar | dump }}

You may control the depth of recursion with a parameter, say foo = array('a'=>array('b'=>array('c','d')))

{{ foo | dumpy(0) }} --> 'array of 1'
{{ foo | dumpy(2) }} -->
                           a:
                             b: 'array of 2'
{{ foo | dumpy(3) }} -->
                           a:
                             b:
                               - c
                               - d
```

Default value is 1 (MAX_DEPTH const).

### How to ignore sending HTTP errors if request comes from any of given IPs?

If you want to ignore sending HTTP errors if the request comes from specific IPs, you can now specify the list of ignored IPs.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    ignoredIPs:
        - "178.63.45.100"
        - ...
```

### How to ignore sending HTTP errors if the user agent match a given pattern?

For some reasons you may need to ignore sending notifications if request comes from some user agents.
Often you will need to use this feature with annoying crawlers which uses artificial intelligence 
to generate URLs which may not exist in your site.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    ignoredAgentsPattern: "(Googlebot|bingbot)"
```

### How to ignore sending HTTP errors if the URI match a given pattern?

For example if you want to ignore all not exist images errors you may do something like that.

```yml
# app/config/config_prod.yml
highco_slack_error_notifier:
    ignoredUrlsPattern: "\.(jpg|png|gif)"
```
