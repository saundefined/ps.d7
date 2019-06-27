<?php

namespace Ps\D7\Form;

use Bitrix\Main\EventManager;
use Bitrix\Main\ORM\Data\DataManager;
use CLightHTMLEditor;

class AdminForm extends \CAdminForm
{
    function AddDateTimeField($id, $label, $value, $required = false) {
        $html = CalendarDate($id, $value, $this->GetFormName(), 20);

        $value = htmlspecialcharsbx(htmlspecialcharsback($value));

        $this->tabs[$this->tabIndex]['FIELDS'][$id] = [
            'id' => $id,
            'required' => $required,
            'content' => $label,
            'html' => '<td>' . ($required ? '<span class="adm-required-field">' . $this->GetCustomLabelHTML($id,
                        $label) . '</span>' : $this->GetCustomLabelHTML($id, $label)) . '</td><td>' . $html . '</td>',
            'hidden' => '<input type="hidden" name="' . $id . '" value="' . $value . '">',
        ];
    }

    function AddHiddenField($id, $value) {
        $this->tabs[$this->tabIndex]['FIELDS'][$id] = [
            'id' => $id,
            'html' => '<input type="hidden" name="' . $id . '" value="' . $value . '">',
        ];
    }

    function AddD7EntityField($id, $label, $value, $required = false) {
        $classes = [];

        $events = EventManager::getInstance()->findEventHandlers('ps.d7', 'onGetEntityList');
        foreach ($events as $event) {
            $result = (array)ExecuteModuleEventEx($event);
            foreach ($result as $classObject) {
                if ($classObject instanceof DataManager) {
                    $class = get_class($classObject);
                    $classes[$class] = $class;
                }
            }
        }

        $this->AddDropDownField($id, $label, $required, $classes, $value);
    }

    function AddLightEditorField($id, $label, $value, $required = false) {
        $this->BeginCustomField($id, $label, $required);
        ?>
        <tr>
            <td>
                <?= $required ? '<span class="adm-required-field">' .
                    $this->GetCustomLabelHTML($id, $label) . '</span>' :
                    $this->GetCustomLabelHTML($id, $label); ?>
            </td>
            <td>
                <?php

                $editor = new CLightHTMLEditor();
                $editor->Show([
                    'id' => $id,
                    'content' => $value,
                    'bBBCode' => true,
                    'bUseFileDialogs' => true,
                    'bUseMedialib' => true,
                    'arSmiles' => true,
                    'arFonts' => true,
                    'arFontSizes' => true,
                    'inputName' => $id,
                    'inputId' => $id,
                ]);
                ?>
            </td>
        </tr>
        <?
        $this->EndCustomField($id);
    }
}
