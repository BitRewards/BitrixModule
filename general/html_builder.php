<?php
/**
 * Created by PhpStorm.
 * User: kjah
 * Date: 01.08.14
 * Time: 8:53
 */
require_once('IHtmlBuilder.php');

class GenericHtmlBuilder implements IHtmlBuilder
{
    public function GenericTableRow($keyHtml, $valHtml, $properties='')
    {
        return
            '<tr '.$properties.'>'
                .$this->GenericTableColumn($keyHtml,  'class="adm-detail-content-cell-l" width="50%"')
                .$this->GenericTableColumn($valHtml,  'class="adm-detail-content-cell-r" width="50%"')
            .'</tr>';
    }

    public function OneColumnTableRow($html, $properties='')
    {
        return '<tr '.$properties.'>'.$this->GenericTableColumn($html, 'colspan="2"').'</tr>';
    }

    public function GenericTableColumn($innerHtml, $properties)
    {
        return '<td '.$properties.'>'.$innerHtml.'</td>';
    }

    public function GenericInputField($name, $value)
    {
        $html = '<input type="text" name="'.$name.'" value="'.$value.'">';
        return $html;
    }

    public function GenericTextareaField($name, $value, $default = null)
    {
        return "<textarea name='$name'>" . str_replace(" ", "&nbsp;", htmlspecialcharsbx($value ?: $default)) . "</textarea>";
    }

    public function GenericInputCheckboxField($name, $value, $title, $isChecked)
    {
        $html = $title.' <input type="checkbox" name="'.$name.'" value="'.$value.'" '.($isChecked ? 'checked' : '').'>';
        return $html;
    }

    public function GenericInputRadioField($name, $values, $checkedValue)
    {
        $html = '';
        foreach($values as $title=>$value) {
            $html.= ' <input type="radio" name="'.$name.'" value="'.$value.'" '.($checkedValue === $value ? 'checked' : '').'> '.$title;
        }

        return $html;
    }

    public function GenericInputFileField($name, $value, $postfix = '_FILE')
    {
        $html = $this->GenericInputField($name, $value).' <input type="file" name="'.$name.$postfix.'">';
        return $html;
    }

    public function GenericSelectField($name, $values, $selectedValue)
    {
        $html = '<select name="'.$name.'">';
        foreach($values as $title=>$value) {
            $title = GetMessage($title) ?: $title; //hack because of select options in giftd_component_settings
            $html.= ' <option value="'.$value.'" '.($selectedValue === $value ? 'selected' : '').'>'.$title.'</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
?>