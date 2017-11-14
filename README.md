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
* `string`
* `timestamp` - uses Carbon to extend DateTime
* `array()` - accepts an array of the type within the parenthesis, ex. `array(string)`

While not strictly necessary, its important to define the primary key:
```php
static protected $primaryKey = 'id';
```

By default, phpDB will search for the plural, snake case version of your model's class name as it's table. For example, a model `UserRole` will use the table `user_roles`. If you'd like to use a different table, add the `$table` field.

```php
protected static $table = 'user_admin_roles';
```

MongoDB models can also use `$collection`.

##### Retriving a single record

The `find` method will retrieve a single record of a model, using the defined primary key.

```php
User::find(1)
```

This will retrieve the values where the primary key is equal to `1` and populate and return a `User` object.

To get back multiple results, use the `get` method.

```php
User::get()
```

This will return populated `User` objects of all entries in the `users` table.

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