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
Available.

⚠️ In production, always set silent mode to true.

## Definition
By default, Error Service is defined to use [Log Service](/log) for logging errors to **private/tmp/logs/error.log** and to send error logs by email.

## Usual Usage
Automatic, when PHP error is thrown.

## Documentation
[Read documentation](https://github.com/webiik/components/blob/master/src/Webiik/Error/README.md) to learn more about Error component.