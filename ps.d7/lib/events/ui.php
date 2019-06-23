<?php

namespace Ps\D7\Events;

use Bitrix\Main\Localization\Loc;
use Ps\D7\ORM\EntityTable;

Loc::loadMessages(__FILE__);

class UI
{
    function onGlobalMenu(&$aGlobalMenu, &$aModuleMenu) {
        $aGlobalMenu['global_menu_ps_d7'] = [
            'menu_id' => 'ps_d7',
            'text' => Loc::getMessage('PS_D7_MENU_TITLE'),
            'title' => Loc::getMessage('PS_D7_MENU_TITLE'),
            'sort' => 600,
            'items_id' => 'ps_d7',
            'help_section' => 'ps_d7',
            'items' => []
        ];

        $res = EntityTable::getList([]);
        while ($ar = $res->fetch()) {
            $code = strtolower(preg_replace('~[\W]~', '_', $ar['ENTITY']));

            $aModuleMenu[] = [
                'parent_menu' => 'global_menu_ps_d7',
                'section' => 'ps_d7' . $code,
                'items_id' => 'ps_d7' . $code,
                'sort' => $ar['SORT'],
                'text' => $ar['NAME'],
                'title' => $ar['NAME'],
                'items' => [
                    [
                        'section' => 'ps_d7' . $code,
                        'sort' => 10,
                        'url' => 'ps_d7_admin.php?ENTITY_ID=' . $ar['ID'],
                        'text' => Loc::getMessage('PS_D7_ELEMENT_LIST_TITLE'),
                        'title' => Loc::getMessage('PS_D7_ELEMENT_LIST_TITLE'),
                        'items_id' => 'ps_d7' . $code,
                    ],
                    [
                        'sort' => 20,
                        'url' => 'ps_d7_edit.php?ENTITY_ID=' . $ar['ID'],
                        'text' => Loc::getMessage('PS_D7_ELEMENT_ADD_TITLE'),
                        'title' => Loc::getMessage('PS_D7_ELEMENT_ADD_TITLE'),
                        'items_id' => 'ps_d7' . $code,
                    ],
                ]
            ];
        }
    }
}
