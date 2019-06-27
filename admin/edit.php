<?php

use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ScalarField;
use Ps\D7\Exception\ElementNotFoundException;
use Ps\D7\Exception\EntityNotDefinedException;
use Ps\D7\Exception\EntityNotFoundException;
use Ps\D7\Field;
use Ps\D7\Form\AdminForm;
use Ps\D7\History\History;
use Ps\D7\Interfaces\Versioned;
use Ps\D7\Module;
use Ps\D7\ORM\EntityTable;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/prolog.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

Loc::loadMessages(__FILE__);

$request = Context::getCurrent()->getRequest();

try {
    Loader::includeModule('fileman');
    Loader::includeSharewareModule('ps.d7');

    $entityId = $request->get('ENTITY_ID');
    if (!$entityId) {
        throw new EntityNotDefinedException(Loc::getMessage('PS_D7_EDIT_ENTITY_ERROR'));
    }

    $row = EntityTable::getList([
        'filter' => ['ID' => $entityId]
    ])->fetch();

    if (!$row['ID']) {
        throw new EntityNotFoundException(Loc::getMessage('PS_D7_EDIT_ENTITY_NOT_FOUND'));
    }

    $elementId = $request->get('ID');

    /** @var DataManager $class */
    $class = $row['ENTITY'];

    Module::autoLoad($class);

    if (!(new $row['ENTITY'] instanceof DataManager)) {
        throw new EntityNotDefinedException(Loc::getMessage('PS_D7_EDIT_ENTITY_WRONG'));
    }

    $entity = $class::getEntity();
    $fields = $entity->getFields();

    if ($elementId > 0) {
        $element = $class::getList([
            'filter' => ['ID' => $elementId]
        ])->fetch();

        if (!$element['ID']) {
            throw new ElementNotFoundException(Loc::getMessage('PS_D7_EDIT_ELEMENT_NOT_FOUND'));
        }
    }

    $APPLICATION->SetTitle($elementId > 0 ? Loc::getMessage('PS_D7_EDIT_ELEMENT_EDIT') : Loc::getMessage('PS_D7_EDIT_ELEMENT_NEW'));

    $formId = 'form_' . mb_strtolower($entity->getCode()) . '_edit';

    $menu = [];

    $menu[] = [
        'TEXT' => Loc::getMessage('PS_D7_EDIT_ELEMENT_LIST'),
        'LINK' => 'ps_d7_admin.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId . '&set_default=Y',
        'ICON' => 'btn_list',
        'TITLE' => Loc::getMessage('PS_D7_EDIT_ELEMENT_LIST'),
    ];
    $menu[] = ['SEPARATOR' => 'Y'];

    $menu[] = [
        'TEXT' => Loc::getMessage('PS_D7_EDIT_ELEMENT_ADD'),
        'LINK' => 'ps_d7_edit.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId,
        'ICON' => 'btn_new',
        'TITLE' => Loc::getMessage('PS_D7_EDIT_ELEMENT_ADD'),
    ];

    $menu[] = ['SEPARATOR' => 'Y'];

    $context = new CAdminContextMenu($menu);
    $context->Show();

    if (
        $request->isPost() &&
        ($request->getPost('save') !== '' || $request->getPost('apply') !== '' || $request->getPost('save_and_add') !== '') &&
        check_bitrix_sessid()
    ) {
        $ID = 0;
        $values = $request->getPostList()->toArray();

        if ($values['ID']) {
            $ID = $values['ID'];
            unset($values['ID']);
        }

        $allowedFields = array_keys($fields);
        foreach ($values as $k => &$value) {
            if (!in_array($k, $allowedFields, false)) {
                unset($values[$k]);
            } else {
                $field = new Field($class, $k);
                $value = $field->modify($value);
            }
        }
        unset($value);

        if ($ID > 0) {
            $el = $class::update($ID, $values);
        } else {
            $el = $class::add($values);
        }

        if ($el->isSuccess() && (new $class instanceof Versioned)) {
            $history = new History($class, $el->getId());
            $history->addVersion($values, $element);
        }

        if ($el->isSuccess()) {
            $redirect_url_list = '/bitrix/admin/ps_d7_admin.php?lang=' . LANG . '&ENTITY_ID=' . $entityId;
            $redirect_url_edit = '/bitrix/admin/ps_d7_edit.php?lang=' . LANG . '&ENTITY_ID=' . $entityId;

            if ($request->getPost('save')) {
                LocalRedirect($redirect_url_list);
            } elseif ($request->getPost('apply')) {
                LocalRedirect($redirect_url_edit . '&ID=' . $el->getId());
            } elseif ($request->getPost('save_and_add')) {
                LocalRedirect($redirect_url_edit . '&ID=0');
            }
        } else {
            $e = new CAdminException([['text' => implode('<br />', $el->getErrorMessages())]]);
            $message = new CAdminMessage(Loc::getMessage('PS_D7_EDIT_SAVE_ERROR'), $e);
            echo $message->Show();
        }
    }

    $tabs = [];
    $tabs[] = [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('PS_D7_EDIT_ELEMENT_TAB'),
        'ICON' => 'main_user_edit',
        'TITLE' => Loc::getMessage('PS_D7_EDIT_ELEMENT_TITLE')
    ];

    if ($elementId > 0 && (new $class instanceof Versioned)) {
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

    $tabControl->AddHiddenField('ENTITY_ID', $entityId);

    if ($elementId > 0) {
        $tabControl->AddHiddenField('ID', $elementId);
    }

    $customFields = [];
    $events = EventManager::getInstance()->findEventHandlers('ps.d7', 'onGetCustomFields');
    foreach ($events as $event) {
        $result = (array)ExecuteModuleEventEx($event);
        foreach ($result as $class => $handler) {
            if (class_exists($class) && is_callable($handler)) {
                $customFields[$class] = $handler;
            }
        }
    }

    foreach ($fields as $field) {
        if ($field instanceof ScalarField &&
            !$field->isAutocomplete()
        ) {
            $fieldClass = get_class($field);
            $value = $element[$field->getName()];

            if (isset($customFields[$fieldClass]) && is_callable($customFields[$fieldClass])) {
                call_user_func($customFields[$fieldClass], $tabControl, $field, $value);
            } else {
                switch ($fieldClass) {
                    case 'Bitrix\Main\ORM\Fields\EnumField':
                        $values = $field->getValues();
                        $values = array_combine(array_values($values), array_values($values));

                        $tabControl->AddDropDownField($field->getName(), $field->getTitle(), $field->isRequired(),
                            $values, $value);
                        break;
                    case 'Bitrix\Main\ORM\Fields\BooleanField':
                        $tabControl->AddCheckBoxField($field->getName(), $field->getTitle(), $field->isRequired(),
                            ['Y', 'N'], $value);
                        break;
                    case 'Bitrix\Main\ORM\Fields\DateField':
                        $tabControl->AddCalendarField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Bitrix\Main\ORM\Fields\DatetimeField':
                        $tabControl->AddDateTimeField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Bitrix\Main\ORM\Fields\TextField':
                        $tabControl->AddLightEditorField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Ps\D7\Fields\FileField':
                        $tabControl->AddFileField($field->getName(), $field->getTitle(), $value, [],
                            $field->isRequired());
                        break;
                    case 'Ps\D7\Fields\D7EntityField':
                        $tabControl->AddD7EntityField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Bitrix\Main\ORM\Fields\StringField':
                    default:
                        $tabControl->AddEditField($field->getName(), $field->getTitle(), $field->isRequired(),
                            [], $value);
                        break;
                }
            }
        }
    }

    if ($elementId > 0 && new $class instanceof Versioned) {
        $tabControl->BeginNextFormTab();
        $tabControl->BeginCustomField('HISTORY', 'История изменений');
        ?>
        <p>123</p>
        <?php

        $tabControl->EndCustomField('HISTORY');
    }


    $tabControl->Buttons([
        'disabled' => false,
        'btnSaveAndAdd' => true,
        'btnSave' => true,
        'btnApply' => true,
        'btnCancel' => true,
        'back_url' => 'ps_d7_list.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId,
    ]);

    $tabControl->Show();
} catch (Exception $exception) {
    $e = new CAdminException([['text' => $exception->getMessage()]]);
    $message = new CAdminMessage(Loc::getMessage('PS_D7_EDIT_ERROR'), $e);
    echo $message->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
