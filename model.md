---
layout: default
title: Model
permalink: /model/
---
# Model
The model in Webiik is a standard PHP class. Out of the box, Webiik comes with the component [Database](/database) that provides a [PDO](https://www.php.net/pdo) database connection. If you want to use any ORM, add it as a service to the [Container](/container) and use it inside models. 

## Writing Model
All models lives in `private/code/models`. You can [inject dependencies](/container) from the Container to any model.  
```php
declare(strict_types=1);

namespace Webiik\Model;

use Webiik\Database\Database;

class Model
{  
    private $db;
    
    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function get(): array
    {
        // This is the MySQL example, however, you can configure 
        // the Database to use any supported language by PDO. 
        $db = $this->db->connect();
        $q = $db->prepare('SELECT * FROM some_table');
        $q->execute();
        $res = $q->fetchAll(\PDO::FETCH_ASSOC);
        return $res ? $res : [];
    }
}
```
> ⚠️ Don't forget to make a [service](/container) from your model. Only then you will be able to inject your model to controllers, middleware, and other models.