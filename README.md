# Cycling UK application

Glue between Webform Drupal module and the AlexaCRM/dynamics-webapi-toolkit library
which provides moderated applications that are submitted to Dynamics.

## Versioning

This module uses [semantic versioning](https://semver.org/). Once a commit has been made to the main branch a new release should be created in GitHub, the tag name should match the version number in the [modules info file](cycling_uk_application.info.yml). Projects using this repository should include the release number they are using.

Using Composer:

```bash
composer require cycling_uk/cycling_uk_application:dev-main#v1.0.0
```

## Installation

Add the following to the `repositories` section of your composer.json:

```json
{ "type": "vcs", "url": "https://gitlab.com/cyclinguk/apply.git" }
```

Then install using Composer:

```bash
composer require cycling_uk/cycling_uk_application:dev-main
```
