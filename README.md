# Генератор админки

## Предупреждение

Модуль **не предназначен** для рядовых пользователей. Используйте его, только если вы **понимаете**, что делать.
В противном случае, пожалуйста, обратитесь к специалисту.

## Зачем?

Таблицы на D7 создаются практически в каждом проекте, но иногда необходимо реализовывать интерфейс для них.

Чтобы не копипастить каждый раз из проекта в проект страницы в админке для управления таблицами и был создан модуль.

## Подключение своей таблицы

Чтобы модуль узнал про вашу таблицу D7, необходимо зарегистрировать обработчик

```php
<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

$event = EventManager::getInstance();
$event->addEventHandler('ps.d7', 'registerEntities', 'registerMyEntity');

function registerMyEntity(Event $event) {
    // Если подключаете таблицу из стороннего модуля
    Loader::includeSharewareModule('ps.demo');
    
    $event->addResult(new EventResult(EventResult::SUCCESS, [
        // Классы всех сущностей, для которых нужно будет управление
        Bitrix\Main\UserTable::class,
        Ps\Demo\ORM\DemoTable::class,
    ]));

    return $event;
}
```

## Кастомные поля

Все поля должны наследовать ``Bitrix\Main\ORM\Fields\ScalarField``.

Модуль по умолчанию поддерживает следующие поля:

``Bitrix\Main\ORM\Fields\EnumField``
``Bitrix\Main\ORM\Fields\BooleanField``
``Bitrix\Main\ORM\Fields\DateField``
``Bitrix\Main\ORM\Fields\DatetimeField``
``Bitrix\Main\ORM\Fields\TextField``
``Ps\D7\Fields\FileField``
``Ps\D7\Fields\D7EntityField``
``Bitrix\Main\ORM\Fields\StringField``

Иногда может потребоваться собственное поле, рассмотрим добавление кастомного поля на примере ``D7EntityField``

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
    $event->addResult(new EventResult(EventResult::SUCCESS, [
        // Тип поля для которого устанавливаем кастомный обработчик
        'ENTITY' => 'Ps\\D7\\Fields\\D7EntityField',
        
        // Функция для обработки
        'HANDLER' => 'addEntityHandler'
    ]));

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

## Модификация данных

Иногда может потребоваться изменить тип данных перед добавлением или обновлением, например, для поля `DatetimeField`:

Модуль по умолчанию модифицирует следующие типы данных:

``Bitrix\Main\ORM\Fields\BooleanField``
``Bitrix\Main\ORM\Fields\DateField``
``Bitrix\Main\ORM\Fields\DatetimeField``

```php
<?php

use Bitrix\Main\EventResult;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;

$event = EventManager::getInstance();
$event->addEventHandler('ps.d7', 'registerCustomModifier', 'registerCustomModifierHandler');

function registerCustomModifierHandler(Event $event) {
    $event->addResult(new EventResult(EventResult::SUCCESS, [
        'ENTITY' => 'Bitrix\\Main\\ORM\\Fields\\DatetimeField',
        'HANDLER' => 'customDateTimeHandler',
    ]));

    return $event;
}


function customDateTimeHandler($value) {
    return new \Bitrix\Main\Type\DateTime($value);
}

```
