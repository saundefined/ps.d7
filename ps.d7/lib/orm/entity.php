<?php

namespace Ps\D7\ORM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;

class EntityTable extends DataManager
{
    public static function getTableName() {
        return 'b_ps_d7_entities';
    }

    public static function getMap() {
        try {
            return [
                new IntegerField('ID', [
                    'title' => 'ID',
                    'primary' => true,
                    'autocomplete' => true,
                ]),

                new StringField('ENTITY', [
                    'title' => 'Сущность',
                ]),

                new StringField('NAME', [
                    'title' => 'Наименование',
                ]),

                new IntegerField('SORT', [
                    'title' => 'Индекс сортировки',
                    'default_value' => 500,
                ]),
            ];
        } catch (SystemException $e) {
        }

        return [];
    }
}
