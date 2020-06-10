---
layout: default
title: Log
permalink: /log/
---
# Log
## Service Name in Container
Webiik\Log\Log

## Purpose
Logging.

## Configuration
Available in `private/config/resources.php`. Also inherits configuration from `private/config/app.php`.

> ⚠️ In production, always set app `mode` to `production`. In the `production` mode, failed loggers don't throw exceptions, but these exceptions are logged with other set loggers.

## Definition
It is defined in `private/config/container/services.php`. By default, the Log service is defined to utilize two loggers. The first logger to store logs in `private/tmp/logs`. The second logger to optionally send error logs by email. 

## Usual Usage
In [route controller](/routing). Used by [Error Service](/error).

## Documentation
[Read documentation](https://github.com/webiik/log) to learn more about Log component.