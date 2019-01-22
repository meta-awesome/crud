# Metawesome
## Crud

[![pipeline status](http://gitlab.meta.com.br/meta-awesome/crud/badges/master/pipeline.svg)](http://gitlab.meta.com.br/meta-awesome/crud/commits/master) [![coverage report](http://gitlab.meta.com.br/meta-awesome/crud/badges/master/coverage.svg)](http://gitlab.meta.com.br/meta-awesome/crud/commits/master)

### Instalação

Via [composer](http://getcomposer.org):

```bash
$ composer require metawesome/crud:dev-master
```

#### Laravel

A seguir, adicionar o `CrudServiceProvider` ao array de `providers` em `config/app.php`:

```php
// config/app.php
'providers' => [
    ...
    Metawesome\Crud\CrudServiceProvider::class,
];
```

#### Lumen

```php
// bootstrap/app.php
$app->register(Metawesome\Crud\CrudServiceProvider::class);
```

#### Finalizar

E em seguida atualize os arquivos de autoload:

```bash
$ composer dump-autoload
```