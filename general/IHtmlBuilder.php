<?php

/**
 * Created by PhpStorm.
 * User: kjah
 * Date: 01.08.14
 * Time: 8:53
 */
interface IHtmlBuilder
{
    public function GenericInputField($name, $value);

    public function GenericTextareaField($name, $value);

    public function OneColumnTableRow($html, $properties = '');

    public function GenericTableRow($keyHtml, $valHtml, $properties = '');

    public function GenericInputFileField($name, $value);

    public function GenericInputCheckboxField($name, $value, $title, $isChecked);

    public function GenericInputRadioField($name, $values, $checkedValue);

    public function GenericTableColumn($innerHtml, $properties);

    public function GenericSelectField($name, $values, $selectedValue);

}