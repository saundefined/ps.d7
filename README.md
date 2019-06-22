# Генератор админки

## Зачем?

Таблицы на D7 создаются практически в каждом проекте, но иногда необходимо реализовывать интерфейс для них.

Чтобы не копипастить каждый раз из проекта в проект страницы в админке для управления таблицами и был создан модуль.

## Подключение своей таблицы

Чтобы модуль узнал про вашу таблицу D7, необходимо зарегистрировать обработчик

```php
<?php

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

$event = EventManager::getInstance();
$event->addEventHandler('ps.d7', 'registerEntities', 'registerMyEntity');

function registerMyEntity(Event $event) {
    $event->addResult(new EventResult(EventResult::SUCCESS, [
        // Классы всех сущностей, для которых нужно будет управление
        Bitrix\Main\UserTable::class,
    ]));

    return $event;
}
```

## Кастомные поля

Все поля должны наследовать ``Bitrix\Main\ORM\Fields\ScalarField``.

Рассмотрим добавление кастомного поля ``D7EntityField``

```php
<?php

namespace Ps\D7\Fields;

use Bitrix\Main\ORM\Fields\ScalarField;

class D7EntityField extends ScalarField
{
    public function cast($value) {
        return $value;
    }

    public function convertValueFromDb($value) {
        return $this->getConnection()->getSqlHelper()->convertFromDbString($value);
    }

    public function convertValueToDb($value) {
        return $this->getConnection()->getSqlHelper()->convertToDbString($value);
    }
}

``` 

В таблице D7 указываем наше поле

```php
<?php

namespace Ps\D7\ORM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\SystemException;
use Ps\D7\Fields\D7EntityField;

class EntityTable extends DataManager
{
    // ...

    public static function getMap() {
        try {
            return [
                new D7EntityField('ENTITY', [
                    'title' => 'Сущность',
                ]),
            ];
        } catch (SystemException $e) {
        }

        return [];
    }
}
``` 

Регистрируем обработчик:

```php
<?php

use Bitrix\Main\EventResult;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;

$event = EventManager::getInstance();
$event->addEventHandler('ps.d7', 'registerCustomField', 'registerCustomFieldHandler');

function registerCustomFieldHandler(Event $event) {
    $event->addResult(new EventResult(EventResult::SUCCESS), [
        // Тип поля для которого устанавливаем кастомный обработчик
        'ENTITY' => 'Ps\\D7\\Fields\\D7EntityField',
        
        // Функция для обработки
        'HANDLER' => 'addEntityHandler'
    ]);

    return $event;
}

/**
 * @param $tabControl CAdminForm
 * @param $field Bitrix\Main\ORM\Fields\ScalarField
 * @param $value
 */
function addEntityHandler($tabControl, $field, $value) {
    $classes = [];
    foreach (get_declared_classes() as $class) {
        if (is_subclass_of($class, '\\Bitrix\\Main\\ORM\\Data\\DataManager')) {
            $classes[$class] = $class;
        }
    }

    $tabControl->AddDropDownField($field->getName(), $field->getTitle(), $field->isRequired(),
        $classes, $value);
}
```
