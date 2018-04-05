# Metawesome
## Crud

### Installation

Via [composer](http://getcomposer.org):

```bash
$ composer require metawesome/crud:dev-master
```

A seguir, adicionar o `CrudServiceProvider` ao array de `providers` em `config/app.php`:

```php
// config/app.php
'providers' => [
    ...
    Metawesome\Crud\CrudServiceProvider::class,
];
```

E em seguida atualize os arquivos de autoload:

```bash
$ composer dump-autoload
```