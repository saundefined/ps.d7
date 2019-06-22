# Генератор админки

## Кастомные поля

Все поля должны наследоваться от ``Bitrix\Main\ORM\Fields\ScalarField``.

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

$event = \Bitrix\Main\EventManager::getInstance();
$event->addEventHandler('ps.d7', 'registerCustomField', 'registerCustomFieldHandler');

function registerCustomFieldHandler(\Bitrix\Main\Event $event) {
    
    // Тип поля для которого устанавливаем кастомный обработчик
    $event->setParameter('ENTITY', 'Ps\\D7\\Fields\\D7EntityField');
    
    // Функция для обработки
    $event->setParameter('HANDLER', 'addEntityHandler');

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
