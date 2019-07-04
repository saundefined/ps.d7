<?php

namespace Ps\D7;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Field
{
    private $field;

    /**
     * Field constructor.
     * @param $class string
     * @param $field
     */
    public function __construct($class, $field) {
        try {
            /** @var $class DataManager */
            Module::autoLoad($class);

            $this->field = $class::getEntity()->getField($field);
        } catch (ArgumentException $e) {
        } catch (SystemException $e) {
        }
    }

    public function modify($value) {
        $event = new Event('ps.d7', 'registerCustomModifier');
        $event->send();

        $customEntity = [];
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() === EventResult::SUCCESS) {
                    /** @var Event $parameters */
                    $parameters = $eventResult->getParameters();

                    if (class_exists($parameters['ENTITY']) && is_callable($parameters['HANDLER'])) {
                        $customEntity[$parameters['ENTITY']] = $parameters['HANDLER'];
                    }
                }
            }
        }

        $class = get_class($this->field);
        if ($customEntity[$class]) {
            $value = call_user_func($customEntity[$class], $value);
        } else {
            switch ($class) {
                case BooleanField::class:
                    $value = $value === 'Y';
                    break;
                case DateField::class:
                    $value = new Date($value);
                    break;
                case DatetimeField::class:
                    $value = new DateTime($value);
                    break;
            }
        }

        return $value;
    }
}
