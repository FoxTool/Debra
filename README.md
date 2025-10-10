# Debra
Simply ORM for the simply projects

The Database class is used for the database connection.
This class is using the `"configs/database.php"` file with the next structure:

```php
return [
    "host" => "localhost",
    "port" => 3306,
    "database" => "<database>",
    "username" => "<username>",
    "password" => "<password>"
];
```
## Basic example
```php
use FoxTool\Debra\EntityManager;
// User class, defined for example in the "app/Entity/User.php" file
use Debra\Entity\User;

// Create EntityManager instance
$em = new EntityManager();

// Set Model and return single object
$user = $em->setModel(User::class)->find(1);

// Display user login
echo $user->getLogin();

// Display the generated query text
$em->getQuery();
```
## Examples:
## _Find record by ID_

```php
use FoxTool\Debra\EntityManager;
// User class, defined for example in the "app/Entity/User.php" file
use Debra\Entity\User;

$em = new EntityManager();
$user = $em->setModel(User::class)->find(1);
```

## _Find all records_

```php
use FoxTool\Debra\EntityManager;
// User class, defined for example in the "app/Entity/User.php" file
use Debra\Entity\User;

$em = new EntityManager();
$users = $em->setModel(User::class)->all();
```
The method `all()` returns array of objects of the `User` class

## _Find records by certain conditions_

```php
use FoxTool\Debra\EntityManager;
// User class, defined for example in the "app/Entity/User.php" file
use Debra\Entity\User;

$em = new EntityManager();
$users = $em->setModel(User::class)->where([
    "login = :login",
    "password = :password",
    "role = :role"
])->setParams([
    "login" => "bob",
    "password" => "12345678",
    "role" => "author"
])->get();
```
The method `get()` returns array of objects of the `User` class

## _Create new record_

```php
$em = new EntityManager();
$em->setModel(User::class);

$user = new User();
$user->setLogin('john');
$user->setPassword('12345678');
$user->setEmail('john.doe@gmail.com');
$user->setCreatedAt(date("Y-m-d H:i:s"));
$user->setUpdatedAt(date("Y-m-d H:i:s"));

$em->persist($user);
$em->save();
```

## _Update record_

```php
$em = new EntityManager();
$user = $em->setModel(User::class)->find(1);
$user->setLogin('john');
$user->setPassword('12345678');
$user->setEmail('john.doe@gmail.com');
$user->setCreatedAt(date("Y-m-d H:i:s"));
$user->setUpdatedAt(date("Y-m-d H:i:s"));

$em->persist($user);
$em->save();
```

## _SELECT by fields list_

```php
// All fields (*)
$user = $this->em
    ->setModel(User::class)
    ->select('*')
    ->find($id);

// Fields defined as array
$user = $this->em
    ->setModel(User::class)
    ->select(['id', 'first_name', 'last_name'])
    ->find($id);

// Fields defined as string
$user = $this->em
    ->setModel(User::class)
    ->select('id, first_name, last_name, email')
    ->find($id);
```

## _COUNT and SUM_

```php
// COUNT
$products = $this->em
    ->setModel(Product::class)
    ->count('total') // Result field name
    ->calculate();

// Can get value from the property which was sent as parameter in the "count" function
$products->total;

// SUM
$user = $this->em
    ->setModel(User::class)
    ->sum('id', 'total') // First is source field, second is result field
    ->calculate();

// Can get value from the property which was sent as second parameter in the "sum" function
$products->total;
```
