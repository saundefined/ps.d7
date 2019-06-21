<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ScalarField;
use Ps\D7\Form\AdminForm;
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

$elementId = $request->get('ID');
if ($elementId > 0) {
    $element = $class::getList([
        'filter' => ['ID' => $elementId]
    ])->fetch();
}

$APPLICATION->SetTitle($elementId > 0 ? 'Редактирование элемента' : 'Добавление элемента');

$formId = 'form_' . mb_strtolower($entity->getCode()) . '_edit';

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$menu = [];

$menu[] = [
    'TEXT' => 'Список элементов',
    'LINK' => 'ps_d7_admin.php?lang=' . LANGUAGE_ID . '&set_default=Y',
    'ICON' => 'btn_list',
    'TITLE' => 'Список элементов',
];
$menu[] = ['SEPARATOR' => 'Y'];

$menu[] = [
    'TEXT' => 'Добавить',
    'LINK' => 'ps_d7_edit.php?lang=' . LANGUAGE_ID,
    'ICON' => 'btn_new',
    'TITLE' => 'Добавить новый элемент',
];

$menu[] = ['SEPARATOR' => 'Y'];

$context = new CAdminContextMenu($menu);
$context->Show();

$tabs = [];
$tabs[] = [
    'DIV' => 'edit1',
    'TAB' => 'Элемент',
    'ICON' => 'main_user_edit',
    'TITLE' => 'Редактирование элемента'
];

if ($elementId > 0) {
    $tabs[] = [
        'DIV' => 'edit2',
        'TAB' => 'История изменений',
        'ICON' => 'main_user_edit',
        'TITLE' => 'История изменений элемента'
    ];
}

$tabControl = new AdminForm($formId, $tabs);

$tabControl->BeginPrologContent();

CAdminCalendar::ShowScript();

$tabControl->EndPrologContent();
$tabControl->BeginEpilogContent();

echo bitrix_sessid_post();

$tabControl->EndEpilogContent();

$tabControl->Begin([
    'FORM_ACTION' => $APPLICATION->GetCurPage() . '?id=' . $elementId . '&lang=' . LANG
]);

$tabControl->BeginNextFormTab();

foreach ($fields as $field) {
    if ($field instanceof ScalarField &&
        !$field->isAutocomplete()
    ) {
        $class = get_class($field);
        $value = $element[$field->getName()];

        switch ($class) {
            case 'Bitrix\Main\ORM\Fields\IntegerField':
                // todo:
                break;
            case 'Bitrix\Main\ORM\Fields\EnumField':
                // todo:
                break;
            case 'Ps\D7\Fields\FileField':
                // todo:
                break;
            case 'Bitrix\Main\ORM\Fields\BooleanField':
                $tabControl->AddCheckBoxField($field->getName(), $field->getTitle(), $field->isRequired(),
                    $field->getDefaultValue(), $value);
                break;
            case 'Bitrix\Main\ORM\Fields\DateField':
                $tabControl->AddCalendarField($field->getName(), $field->getTitle(), $value, $field->isRequired());
                break;
            case 'Bitrix\Main\ORM\Fields\DatetimeField':
                $tabControl->AddDateTimeField($field->getName(), $field->getTitle(), $value, $field->isRequired());
                break;
            case 'Bitrix\Main\ORM\Fields\StringField':
                $tabControl->AddEditField($field->getName(), $field->getTitle(), $field->isRequired(), [], $value);
                break;
            case 'Bitrix\Main\ORM\Fields\TextField':
                // todo: LHE
                break;
        }
    }
}

if ($elementId > 0) {
    $tabControl->BeginNextFormTab();

    $tabControl->BeginCustomField('HISTORY', 'История изменений');
    // todo:
    ?>
    <p>История</p>
    <?
    $tabControl->EndCustomField('HISTORY');
}


$tabControl->Buttons([
    'disabled' => false,
    'btnSaveAndAdd' => true,
    'btnSave' => true,
    'btnApply' => true,
    'btnCancel' => true,
    'back_url' => 'ps_d7_list.php?lang=' . LANGUAGE_ID,
]);

$tabControl->Show();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
