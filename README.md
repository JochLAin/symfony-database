# Symfony Database Helpers

## Configuration

```yaml
# config/services.yaml

services:
    ## With autowire and autoconfigure
    _defaults:
        autowire: true
        autoconfigure: true
        public: false 
    ...

    Jochlain\Database\:
        resource: "../vendor/jochlain/database/src"
        exclude: "../vendor/jochlain/database/src/{DQL,ORM,Subscriber}"
        public: true

    Jochlain\Database\Subscriber\:
        resource: "../vendor/jochlain/database/src/Subscriber"
        tags: ["doctrine.event_subscriber"]
        public: false

    ## Without autowire and autoconfigure
    Jochlain\Database\Manager\QueryManager:
        arguments: ["@doctrine.orm.entity_manager"]

    Jochlain\Database\Manager\Query\CountManager:
        arguments: ["@doctrine.orm.entity_manager"]

    Jochlain\Database\Manager\Query\FetchManager:
        arguments: ["@doctrine.orm.entity_manager"]

    Jochlain\Database\Manager\Query\FilterManager:
        arguments: ["@doctrine.orm.entity_manager"]

    Jochlain\Database\Subscriber\InheritanceSubscriber:
        tags: ["doctrine.event_subscriber"]
```

```yaml
# config/packages/doctrine.yaml

doctrine:
    orm:
        dql:
            string_functions:
                ARRAY_CONTAINS: "Jochlain\\Database\\DQL\\Contains"
                DISTINCT_ON: "Jochlain\\Database\\DQL\\Unique"
        # Optional
        default_repository_class: "Jochlain\\Database\\ORM\\Repository"
```
