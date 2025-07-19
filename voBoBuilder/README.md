## How to use

- $`composer install`
- $`php voBoBuilder.php "./file.sql"`

If you want to generate camelCase parameters for VO classes, remove the `--snakecase=1` for the `voBoBuilder.php` command

### What is a VO and a BO?

- VO: Value Object. It is an immutable object that contains a set of values. It is used to define a structure of data.
- BO: Business Object. It is an object that implements the business logic of the application. It is used to encapsulate the data and the methods that operate on that data.

```php

$userVO = (new UserBO())->getOneById(1); // Get the user with id 1

$personVO = new PersonVO();

$personVO
->setName("John")
->setLastName("Doe")
->setAge(30)
->setAddress("123 Main St")
->setCity("New York");

$personBO = new PersonBO();

$id = $personBO->insert($personVO);
$personBO->update($personVO);

$personVO->setLastName("Smith");

$personBO->delete($personVO, $userVO->getId()); // $vo and $userId (User that is deleting the item)
$personBO->getOneById($id);
$personBO->getAll();
```


