# scout-apm-plugin

A symfony1 plugin to instrument web requests, CLI tasks, and Doctrine queries in a symfony1 application.

[![MIT License](https://img.shields.io/apm/l/atomic-design-ui.svg?)](https://github.com/tterb/atomic-design-ui/blob/master/LICENSEs)
![phpstan](https://img.shields.io/badge/PHPStan-level%204-success?style=flat)

More details on Scout APM can be found on their [website](https://scoutapm.com/), [docs](https://scoutapm.com/docs/php), and [scout-apm-php readme](https://github.com/scoutapp/scout-apm-php#scout-php-apm-agent).

## Installation

1. Install plugin via composer

```bash 
  composer require waytohealth/scout-apm-plugin
``` 
Note: This will install the plugin at `plugins/scoutApmPlugin` (not in `vendor/` as is typically the case with composer dependencies). You may need to adjust your gitignore accordingly.

2. Enable the plugin in project configuration:
```php
class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    parent::setup();
    
    // Add the below line:
    $this->enablePlugins('scoutApmPlugin');
  }
}
```
3. Set up environment variables, as documented in the [Scout APM PHP docs](https://scoutapm.com/docs/php/configuration).
```
SCOUT_KEY=xxxxxxx
SCOUT_MONITOR=true
SCOUT_NAME="Your application (production)"
SCOUT_REVISION_SHA=xxxxxxx
```
The exact mechanism will depend on your deployment environment. In our application, we use [vlucas/phpdotenv](https://packagist.org/packages/vlucas/phpdotenv) to make variables in `.env` files available to `getenv()`. If your deployment environment doesn't natively support environment variables, either install a library such as `vlucas/phpdotenv` or open a PR to this repo enabling configuration of ScoutAPM using `\Scoutapm\Config::fromArray`.


# Built and used by
![Way to Health](https://www.waytohealth.org/images/bg/w2h.inn.logo.jpg)
([We're hiring!](https://www.waytohealth.org/careers/))
