# phpDM

#### Basic usage

This project currently supports MySQL and MongoDB.

First, create a connection with the `ConnectionManager`.

MySQL:
```php
\phpDM\Connections\ConnectionManager::addConnection(
	[
		'type' => 'mysql',
		'hostname' => 'localhost',
		'database' => 'yourdb',
		'username' => 'username',
		'password' => 'password'
	]
);
```

MongoDB:
```php
\phpDM\Connections\ConnectionManager::addConnection(
	[
		'type' => 'mysql',
		'database' => 'yourdb',
	]
);
```

The `addConnection` method also takes a second optional parameter, `name`, through which you can name your connection, in case you'd like to directly reference the connection later on (such as using multiple databases of the same type).

Next, create a model class which extends off the appropriate Model base.

* MySQL => `MysqlModel`
* MongoDB => `MongoModel`

The minimum necessary field to retrieve data is `$fields`, which will define the fields to populate your model and what type they must be.

```php
class User extends \phpDM\Models\MysqlModel
{

	protected static $fields = [
		'userID' => 'int',
		'username' => 'string',
		'password' => 'string',
		'joinDate' => 'timestamp'
	];

}
```

The following field types are available:

* `int`/`integer`
* `float`
* `bool`/`boolean`
* `str`/`string`
* `timestamp` - uses Carbon to extend DateTime
* `array()` - accepts an array of the type within the parenthesis, ex. `array(string)`
* `object:\Models\User` - accepts a class (with namespace) after the colon, to be used as an embedded object. If you're looking to embed objects that are only being used to model that embedded data, you should use the `GenericModel` class. If you intend on using the class independently, the class **must** be a phpDM Model of the same type as the model embedding it. In MySQL, this is converted to JSON on save.

While not strictly necessary, its important to define the primary key:
```php
static protected $primaryKey = 'id';
```

By default, phpDB will search for the plural, snake case version of your model's class name as it's table. It requires following PSR-1 class naming conventions. For example, a model `UserRole` will use the table `user_roles`. If you'd like to use a different table, add the `$table` field.

```php
protected static $table = 'user_admin_roles';
```

If you prefer to use camel case, you can pass an array with the key of `options` when adding a connection, with a `case` key with the value of 'camel'. phpDM will now use a pluralized version of your model's class name, with a lower-cased first letter.

```php
'options' => ['case' => 'camel']
```

MongoDB models can also use `$collection`.

##### Special types

###### Generic embedded object

If you'd like to embed an object but don't need a full model for it, you can use the verbose type format:

```php
protected static $fields = [
	'user' => [
    	'type' => 'object',
        'fields' => [
        	'userId' => 'int',
            'username' => 'str'
        ]
    ]
];
```

The inner `fields` element is populated the same as the static `$fields` property. You can have objects embedded within objects.

If you'd prefer to keep the object separate or create an array of objects, you can create a model extending from `GenericModel`. It functions the same as a model created through the verbose format shown above.

###### Special timestamps

There are 3 special timestamps included

* `createdTimestamp` - This field is auto-populated with the current timestamp when a model is created.
* `updatedTimestamp` - This field is auto-populated when the current timestamp when a model is updated
* `deletedTimestamp` - This field is used for soft deletes and is auto-populated with the current timestamp when a model is deleted

##### Retriving a single record

The `find` method will retrieve a single record of a model, using the defined primary key.

```php
User::find(1)
```

This will retrieve the values where the primary key is equal to `1` and populate and return a `User` object.

##### Retriving multiple records

To get back multiple results, use the `get` method.

```php
User::get()
```

This will return populated `User` objects of all entries in the `users` table.

##### Conditions

You can add conditions with the  `where`, `orWhere`, or `whereIn` methods.

The `where` and `orWhere` methods can take either 2 parameters:

```php
where('username', 'rohit')
```

which checks where the values are equivilent, or you may put a comparitor in the second parameter:

```php
where('votes`, '>=', 30)
```

The `where` and `orWhere` methods will also accept a function to create prioritized conditions (such as using parenthesis in MySQL):

```php
where(function ($query) {
	return $query->where('role', null)->orWhere('active', 0);
});
```

The `whereIn` method takes 2 parameters, a field and an array of values:

```php
whereIn('username', ['rohit', 'rsodhia'])
```

Chain your conditions with `get` to retrieve values.

Any query builder methods started staticly off a model return a query builder instance, and thus are chained directly thereon:

```php
User::where('username', 'rohit')->orWhere('active', '!=', 0)->get()
```

##### Sorting

To add sorting, simply chain the `sort` function onto the Query Builder. The first parameter is the field to sort on, the second is the direction to sort on (`asc` for ascending or `desc` for descending). The default direction is ascending.

```
sort('registeredOn', 'desc');
```

To sort on multiple fields, chain multile sort functions together.

##### Limiting

To retrieve a limited number of rows, you can use the `limit` method, simply passing it an integer greater than zero. You can also skip entries using `skip`, passing it an integer greater than zero.

You can do both together using `paginate`. Paginate takes two values, first the number of entries you want returned, second the page number, starting with 1.

```
paginate(20, 1)
```

will retrieve entries 0-19.

```
paginate(20, 2)
```

will retrieve entries 20-39.

##### Saving data

Whether creating a new database entry or updating an existing row, use the `save` method. Called on an instance of a model, it will check for any data that has been changed and save that data to the database. If there is no primary key, it will attempt to insert a new entry; if there is a primary key, it will attempt to update an entry. If the databse allows it, it will return the number of affected rows on success, or `false` on failure.

```
$user = new User();
$user->username = 'rohit';
$user->save();
```

This will insert a new entry with the `username` 'rohit'.

```
$user = User::find(1);
$user->email = 'test@test.com';
$user->save();
```

As this will retrieve the entry with the id '1', it will update the email and update the database.

In MySQL, values of type `array` or `object` will be converted to json for storage.

##### Deleting

To delete a query, simply call `delete` on a model.

```
User::where('userId', 4)->delete()
```

If the model has a `deletedTimestamp` field, the field will be updated to the current time and the retrival functions (`first`, `find`, and `get`) won't retrieve the model(s). If there is no `deletedTimestamp` field, it will be removed from the database.

##### Empty query

In case you want to start a model query but don't yet have a method to chain, you can call `query`.

```
$queryBuilder = User::query();
```

This can be chained to as any other query builder method later.