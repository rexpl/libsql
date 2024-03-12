# Libsql PHP Driver

This library is a PHP driver allowing access to libsql databases.

## Example

```php
$libsql = new \Rexpl\Libsql\Libsql('libsql://127.0.0.1:8080', $token);

$results = $libsql->query('SELECT * FROM users WHERE id = ?', [$userId]);
$user = $results->fetch(\Rexpl\Libsql\Libsql::FETCH_OBJ);

$query = $libsql->prepare('SELECT * FROM posts WHERE user_id = :user_id AND publish_date NOT NULL');
$results = $query->execute(['user_id' => $user->id]);
$posts = $results->fetchAll(\Rexpl\Libsql\Libsql::FETCH_ASSOC);
```

## Installation

```
composer require rexpl/libsql
```
