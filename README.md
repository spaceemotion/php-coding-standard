# php-coding-standard (phpcstd)
The goal of this project is to combine various code quality tools (e.g. linting and static analysis)
into one, easy to use package which can be shared across teams and code bases.

Right now, it needs quite a lot of time to keep the configurations and tools themselves
updated across multiple projects. The more you got, the longer it takes.

With a single, packed binary, that can be easy updated, you no longer need to worry about outdated standards.

> **Disclaimer:**
Since this is an opinionated project, the default configurations are based on my personal taste.
However, most of it is using the official PSR standards and only do slight style adjustments here and there
(e.g. leaving out function arguments if they're redundant, importing global constants and functions, ...).

## Getting started
```
composer require-dev spaceemotion/php-coding-standard
```

This will install the `phpcstd` binary to your vendor folder.

```
$ phpcstd [options] <files or folders>

--ci
  Changes the output format to checkstyle.xml for better CI integration

--fix
  Try to fix any linting errors (disables other tools)

--continue
  Just run the next check if the previous one failed

--help
  Displays this help message
```

## Contents
### Tools supported
Tool | Description
-----|------------
[EasyCodingStandard](https://github.com/symplify/easy-coding-standard) | Detects styling issues using a variety of linting tools
[phpstan](https://github.com/phpstan/phpstan) | Runs logical checks
[PHP Mess Detector](https://github.com/phpmd/phpmd) | Tries to keep code complexity to a minimum
[php-parallel-lint](https://github.com/php-parallel-lint/php-parallel-lint) | Quickly lints the whole project against PHP (syntax) errors

### Extensions used
- **phpstan**
  - phpstan/phpstan-deprecation-rules
  - phpstan/phpstan-mockery
  - phpstan/phpstan-phpunit
  - phpstan/phpstan-strict-rules

## Development
### Using XDebug
This project uses [composer/xdebug-handler](https://github.com/composer/xdebug-handler) to improve performance
by disabling xdebug upon startup. To enable XDebug during development you need to set the following env variable:
`MYAPP_ALLOW_XDEBUG=1` (as written in their README).
