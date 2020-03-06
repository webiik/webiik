---
layout: default
title: Constants
permalink: /constants/
---
# Constants
Webiik defines a few handy constants you could use.

When application is in development mode this constant equals true. Value of this constant is set in [the main configuration file](/configuration):
```php
WEBIIK_DEBUG
```

Directory path of Webiik application (directory app). Value of this constant is filled automatically:
```php
WEBIIK_BASE_DIR
```

Base URI of Webiik application (usually /). Value of this constant is set in [the main configuration file](/configuration):
```php
WEBIIK_BASE_URI
```

Base URL of Webiik application (usually http(s)://sub.domain.tld). Value of this constant comes from WEBIIK_BASE_URI:
```php
WEBIIK_BASE_URL
```

Base path is similar to WEBIIK_BASE_URL but always includes trailing slash.
```php
WEBIIK_BASE_PATH
```

Current language. Value of this constant is filled automatically. It depends on current URI and language settings in [the main configuration file](/configuration):
```php
WEBIIK_LANG
``` 