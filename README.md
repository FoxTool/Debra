# debra

```php
use FoxTool\Debra\Core\EntityManager;
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