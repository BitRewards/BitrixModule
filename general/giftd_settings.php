<?php
/**
 * Created by PhpStorm.
 * User: Kirill
 * Date: 7/31/14
 * Time: 12:57 AM
 */
require_once('IHtmlBuilder.php');

interface IGiftdSettings
{
    public function Update($settings);
    public function ToArray();
    public function ToHtml();
}

abstract class GiftdSettings implements IGiftdSettings
{
    protected $module_id;
    protected $html_builder;
    protected $options;
    protected $_default_class = 'class="optional"';

    public function __construct($module_id, IHtmlBuilder $html_builder)
    {
        $this->module_id = $module_id;
        $this->html_builder = $html_builder;
    }

    public function __destructor()
    {
        if($this->html_builder)
            unset($this->html_builder);
    }

    public function Update($settings)
    {

        foreach($this->ToArray() as $key)
        {
            if($dst = $this->UpdateFileForm($key, '_FILE'))
                $settings[$key] = $dst;

            if(isset($settings[$key]))
                $this->SetValue($key, $settings[$key]);
        }
    }

    public function ToArray()
    {
        return array_keys($this->options);
    }

    public function ToHtml()
    {
        $html = '';
        foreach($this->options as $key=>$func_stack)
            foreach($func_stack as $callback=>$args)
            {
                $html .= $this->Render($key, $callback, $args);
            }

        return $html;
    }

    public function Value($name)
    {
        return COption::GetOptionString($this->module_id, $name);
    }

    public function SetValue($name, $value)
    {
        COption::SetOptionString($this->module_id, $name, $value);
    }

    protected function HeadingTableRow($key, $field, $class='class="heading"')
    {
        return $this->html_builder->OneColumnTableRow($field, $class);
    }

    protected function ValueTableRow($key, $field, $class='')
    {
        $class = empty($class) ? $this->_default_class : $class;
        return $this->html_builder->GenericTableRow('', $field, $class);
    }

    protected function SettingTableRow($key, $field, $class='')
    {
        $class = empty($class) ? $this->_default_class : $class;
        return $this->html_builder->GenericTableRow(GetMessage($key), $field, $class);
    }

    protected function Text($key)
    {
        return $this->html_builder->GenericInputField($key, $this->Value($key));
    }

    protected function Checkbox($key)
    {
        return $this->html_builder->GenericInputCheckboxField($key, 'Y', GetMessage($key), $this->Value($key) == 'Y');
    }

    protected function Radio($key, $values)
    {
        return $this->html_builder->GenericInputRadioField($key, $values, $this->Value($key));
    }

    protected function File($key)
    {
        return $this->html_builder->GenericInputFileField($key, $this->Value($key));
    }

    protected function Select($key, $values)
    {
        return $this->html_builder->GenericSelectField($key, $values, $this->Value($key));
    }

    protected function Render($key, $callback, $args)
    {
        if(!method_exists($this, $callback))
            throw new Exception("Call to unknown function '$callback' with [$args]");

        if(!is_array($args))
            return call_user_func_array(array($this ,$callback), array($key, $args));

        $is_assoc = false;
        $params = array();
        foreach($args as $name=>$value)
        {
            $cb = is_numeric($name) ? $value : $name;
            if(method_exists($this, $cb))
                $params[] = $this->Render($key, $cb, $value);
            else
            {
                if(is_numeric($name))
                    $params[]=$value;
                else
                {
                    $params[$name]=$value;
                    $is_assoc = true;
                }
            }
        }

        if($is_assoc)
            $params = array($key, $params);
        else
            array_unshift($params, $key);

        //echo "$callback(".print_r($params, true).")</br>";
        return call_user_func_array(array($this, $callback), $params);
    }

    private function UpdateFileForm($key, $postfix)
    {
        if(is_array($_FILES[$key.$postfix]))
        {
            $image = $_FILES[$key.$postfix];
            if(strstr($image['type'], 'image/'))
            {
                $dst = BX_ROOT.'/modules/'.$this->module_id.'/img/'.$image['name'];
                move_uploaded_file($image['tmp_name'], $_SERVER['DOCUMENT_ROOT'].$dst);

                COption::SetOptionString($this->module_id, $key.'_TYPE',$image['type']);
                COption::SetOptionString($this->module_id, $key, $dst);

                if($image['size'] <= 3*1024)
                    COption::SetOptionString($this->module_id, $key.'_BASE64', 'Y');
                else
                    COption::SetOptionString($this->module_id, $key.'_BASE64', 'N');

                return $dst;
            }
        }

        return false;
    }
}

?>