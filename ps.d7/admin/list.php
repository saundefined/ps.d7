<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ScalarField;
use Ps\D7\ORM\EntityTable;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/prolog.php';

Loader::includeSharewareModule('ps.d7');

$request = Context::getCurrent()->getRequest();

$entityId = $request->get('ENTITY_ID');
if (!$entityId) {
    // todo: Ошибка
}

$row = EntityTable::getList([
    'filter' => ['ID' => $entityId]
])->fetch();

if (!$row['ID']) {
    // todo: Ошибка
}

/** @var DataManager $class */
$class = $row['ENTITY'];

$entity = $class::getEntity();
$fields = $entity->getFields();

$APPLICATION->SetTitle('Список элементов');

$tableId = 'tbl_' . mb_strtolower($entity->getCode()) . '_list';

$sort = new \CAdminSorting($tableId, $entity->getAutoIncrement(), 'DESC');
$lAdmin = new \CAdminUiList($tableId, $sort);

try {
    if ($lAdmin->EditAction()) {
        foreach ($request->getPost('fields') as $id => $fields) {
            $class::update($id, $fields);
        }
    }
    if ($ids = $lAdmin->GroupAction()) {
        foreach ($ids as $id) {
            switch ($request->getPost('action_button_' . $tableId)) {
                case 'delete':
                    $class::delete($id);
                    break;
            }
        }
    }
} catch (Exception $e) {
}

$headers = [];
foreach ($fields as $field) {
    if ($field instanceof ScalarField) {
        $headers[] = [
            'id' => $field->getName(),
            'content' => $field->getTitle(),
            'sort' => $field->getName(),
            'default' => true
        ];
    }
}

$lAdmin->AddHeaders($headers);

$res = $class::getList([
    'select' => ['*'],
    'order' => [$sort->getField() => $sort->getOrder()],
    'limit' => CAdminResult::GetNavSize($tableId),
]);

$res = new CAdminResult($res, $tableId);
$res->NavStart();

while ($ar = $res->Fetch()) {
    $row =& $lAdmin->AddRow($ar['ID'], $ar);

    $actions = [];
    $actions[] = [
        'ICON' => 'edit',
        'TEXT' => 'Изменить',
        'LINK' => 'ps_d7_edit.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId . '&ID=' . $ar['ID'],
        'DEFAULT' => true
    ];
    $actions[] = [
        'ICON' => 'delete',
        'TEXT' => 'Удалить',
        'ACTION' => 'if(confirm(\'Вы уверены?\')) ' . $lAdmin->ActionDoGroup($ar['ID'],
                'delete', 'ENTITY_ID=' . $entityId)
    ];
    $row->AddActions($actions);
}

$aContext = [];
$aContext[] = [
    'TEXT' => 'Новый элемент',
    'LINK' => 'ps_d7_edit.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId,
    'ICON' => 'btn_new',
    'TITLE' => 'Новый элемент',
];

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->AddGroupActionTable([
    'delete' => true,
]);

$lAdmin->CheckListMode();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
