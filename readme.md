# Metawesome
## Crud

### Installation

```
composer require metawesome/crud:dev-master
```

A seguir, adicionar o CrudServiceProvider ao array de providers em config/app.php:

> // config/app.php
> 'providers' => [
>    ...
>    Metawesome\Crud\CrudServiceProvider::class,
>];