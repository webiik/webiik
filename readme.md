# Webiik
From [indie hacker](https://mihal.me) for [indie hackers](https://www.indiehackers.com), [KISS](https://en.wikipedia.org/wiki/KISS_principle) platform for quick creation of websites and web apps. Stop learning over bloated frameworks and start creating right now.

[Create API]()
[Create website]()
[Create blog]()
Create your story!

## Comparison
Webiik is ultra minimalistic, but powerful.

|   |Webiik Core|Lumen|Webiik Skeleton|Laravel|
|---|---|---|---|---|
|requests per second|---|---|---|---|
|memory usage|---|---|---|---|
|data weight|---|---|---|---|

<!--Todo: Fill table with benchmark results. -->

## Webiik Core
If you want to stay tiny use Core as foundation of your web project and connect only [services](#available-services) you really need. Just 50 Kb and you will get router, middleware and dependency injection.

[Core](core.md)

## Webiik Skeleton
Everything what modern website needs just in 159 Kb. Localization, user management, security, error reporting and many more. Just focus on your amazing website or web app. Skeleton inherits from Core, so everything what works in Core, works in Skeleton too.

[Skeleton]()

## Available services
List of available services that come with Webiik platform. Sorted by usage.

Core
[Middleware]()
[Request]()
[Route]()
[Router]()

Skeleton
[Attempts]()
[Auth](auth.md)
[AuthMw](authMw.md)
[Config]()
[Connection]()
[Conversion]()
[Csrf]()
[Error]()
[Filesystem]()
[Flash]()
[Log]()
[Sessions]()
[Token]()
[Translation]()

Unused
[Autoload]()
[Download]()
[Http]()
[Mail]()
[OAuth1Client](oauth1client.md)
[OAuth2Client](oauth2client.md)
[Response]()
[Utils]()
[Validate]()