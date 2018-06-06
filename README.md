# Database Library

## Controller
Extends PDO class.
#### Function q($query, $args, &$stmt)
Executes `pdo->prepare()`, `pdo->execute()` and `pdo->fetch()` all at once.
#### Function import($pathToSqlFile)
Imports a `.sql` file to the database.
#### Function dumb()
Requires a constant `DATABASE_DUMP_PATH` with a path where all the dump files will be stored. 

## Maintenance
Requires a `settings` table with 2 columns, example:
```
CREATE TABLE `settings` (
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
INSERT INTO `settings` VALUES ('database_version', '1');
```
Requires a constant `DATABASE_UPDATE_FOLDER` with a path where all `.sql` and `.php` files will be stored and used to keep the database updated.
 
## Query
Extends Controller class.

### Usage
Insert a record.
```
$values = [
    'firstName' => 'John',
    'lastName' => 'Doe',
];

$id = $db->insert('clients', $values);
```
Insert a record, on duplicate key update.
```
$values = [
    'firstName' => 'John',
    'lastName' => 'Doe',
];

$id = $db->insert('clients', $values, true);
```
Insert a record, on duplicate key update some values.
```
$values = [
    'firstName' => 'John',
    'lastName' => 'Doe',
];

$update = [
    'lastName' => 'Doe',
];

$id = $db->insert('clients', $values, $update);
```
Update a record.
```
$values = [
    'firstName' => 'John',
    'lastName' => 'Doe',
];
$where = [
    'id' => 2
];

$rowsAffected = $db->update('clients', $values, $where);
```
Select records.
```
$array = $db->select('clients', ['firstName' => 'John' ]);
$array = $db->select('clients', 'firstName = "John"']);
```
Select records using id.
```
$array = $db->select('clients', 2);
```
Select records using `like` or `not like`
```
$array = $db->select('clients', ['firstName' => ['like', 'Jo%']]);
```
Select records using `>=` or `<=` or others.
```
$array = $db->select('clients', ['id' => ['>=', 2]]);
```
Select records using `in` or `not in`.
```
$array = $db->select('clients', ['id' => ['in', [1,2,3]]]);
```
Define the order.
```
$array = $db->select('clients', ['order' => 'name desc']]);
$array = $db->select('clients', ['order' => 'name desc, id asc']]);
```
Set a limit.
```
$array = $db->select('clients', ['limit' => 1]]);
$array = $db->select('clients', ['limit' => '1, 5']]);
```
Delete a record.
```
$int = $db->delete('clients', $where]);
```
Count records of a table.
```
$int = $db->count('clients', $where]);
```
Get the sum a column.
```
$int = $db->sum('age', 'clients', $where]);
```
Get the average value of a column.
```
$int = $db->avg('age', clients', $where]);
```
Get the minimum value of a column.
```
$int = $db->min('age', clients', $where]);
```
Get the maximum value of a column.
```
$int = $db->max('age', 'clients', $where]);
```