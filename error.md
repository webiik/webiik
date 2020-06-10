---
layout: default
title: Error
permalink: /error/
---
# Error
## Service Name in Container
Webiik\Error\Error

## Purpose
Error handling and logging.  

## Configuration
Inherits configuration from `private/config/app.php`.

> ⚠️ In production, always set app `mode` to `production`. In the `production` mode, PHP`s error messages are replaced by a custom error message. You can change this custom message within the Error service definition.

## Definition
It is defined in `private/config/container/services.php`. By default, Error Service is defined to use [Log Service](/log) for logging errors.

## Usual Usage
When PHP error is thrown.

## Documentation
[Read documentation](https://github.com/webiik/error) to learn more about Error component.