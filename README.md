# Libsql PHP Driver

This library is a PHP driver allowing access to libsql databases.

## Installation

```
composer require rexpl/libsql
```

## Examples

Execute a SQL query:
```php
$libsql = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', $token);

$results = $libsql->query('SELECT * FROM users WHERE id = 1');
$user = $results->fetch();
// or
$numberOfAffectedRows = $libsql->exec('UPDATE users SET email = "email@example.com" WHERE id = 1');
```

Execute a SQL query with arguments:
```php
$libsql = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', $token);

$results = $libsql->query('SELECT * FROM users WHERE id = ?', [$userId]);
$user = $results->fetch(\Rexpl\Libsql\Libsql::FETCH_OBJ);
// or with named arguments
$query = $libsql->prepare('SELECT * FROM users WHERE email LIKE :email');
$results = $query->execute(['email' => '%@example.com']);
$posts = $results->fetchAll(\Rexpl\Libsql\Libsql::FETCH_ASSOC);
```

Use multiple streams on the same connection:
```php
$firstStream = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', $token);
$secondStream = $firstStream->newStream();

$firstStream->beginTransaction();

var_dump($firstStream->inTransaction()); // true
var_dump($secondStream->inTransaction()); // false
```

Connect to a local libsql database by settings the token to `null` and the secure argument to `false`:
```php
$libsql = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', null, false);
```

Batch queries:
```php
$libsql = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', $token);
$batch = $libsql->newBatch();

$batch->addStep('BEGIN TRANSACTION');
$userInsert = $batch->addStep('INSERT INTO users (name, email) VALUES (?, ?)', [$name, $email]);

$insertOnSuccess = $batch->addConditionalStep(
    \Rexpl\Libsql\Batch\Condition::ok($userInsert),
    'INSERT INTO users_log (action, email) VALUES ("insert_success", :email)',
    ['email' => $email]
);
$insertOnFailure = $batch->addConditionalStep(
    \Rexpl\Libsql\Batch\Condition::error($userInsert),
    'INSERT INTO users_log (action, email) VALUES ("insert_failed", :email)',
    ['email' => $email]
);

$successCondition = \Rexpl\Libsql\Batch\Condition::or(
    \Rexpl\Libsql\Batch\Condition::ok($insertOnSuccess),
    \Rexpl\Libsql\Batch\Condition::ok($insertOnFailure)
);

$batch->addConditionalStep($successCondition, 'COMMIT');
$batch->addConditionalStep(\Rexpl\Libsql\Batch\Condition::not($successCondition), 'ROLLBACK');

$getUserQuery = $batch->addStep('SELECT * FROM users WHERE email = ?', [$email]);

$batchResults = $batch->execute();

$userInsertResults = $batchResults->getResultForStep($userInsert);

// example:
$userInsertResults->affectedRowCount();
// or
$user = $batchResults->getResultForStep($getUserQuery)->fetch(\Rexpl\Libsql\Libsql::FETCH_OBJ);
```
