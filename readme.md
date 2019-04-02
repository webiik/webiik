Webiik
======
Webiik is a PHP framework that has only 1062 lines of code. It's fast, flexible and simple. Webiik is for everyone who wants to use a framework but wants to have a code of his/her application fully under control. 
 
| Framework | LLOC | Classes | Dependencies | Req/s |
| :-------- | ---: | ------: | -----------: | ----: |
| Webiik  | 1 717 (10 765)* | 54 (505)* | 18 (22)* | 1 781 (1 239)* |
| Slim | 4 797 | 168 | 9 | 998 |
| Lumen | 55 594 | 1 558 | 50 | 1 339 |
| Silex | 88 962 | 3 029 | 45 | 736 |
| Laravel | 80 271 | 2 457 | 43 | 🐌 329 |
| Symfony | 🤯 182 807 | 5 510 | 83 | 456** |

\* with optional predefined services (incl. Twig and PHPMailer), \** with Twig

LLOC = logical lines of code incl. vendor folder. Benchmark was performed by `./hey -n 200 -c 50`. All measurements were performed on a fresh installation of each framework. Each framework was set to production mode. Development dependencies were not installed. 

Documentation
-------------
Become the Webiik master within a few days, just read the straightforward [documentation][1].

Contributing
------------
I appreciate everyone who considers contributing. Currently, I’m looking for: 

*  [Grammar corrections and proofreading](grammar.md).

If you decide to contribute, send pull request or contact me at jiri@mihal.me or [@JiriMihal](https://twitter.com/jirimihal).

Security Issues
---------------
If you discover a security vulnerability within Webiik, please send me an email with the vulnerability description to jiri@mihal.me.

License
-------
Webiik and all its components are provided under [MIT license][2]. 

[1]: https://www.webiik.com
[2]: http://opensource.org/licenses/MIT