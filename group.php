<?php
/**
 * @package midgardmvc_helper_forms
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class midgardmvc_helper_forms_group
{
    private $items = array();
    private $name = '';
    private $label = '';
    protected $namespace = '';
    
    public function __construct($name='')
    {
        $this->name = $name;
    }
    
    public function __get($key)
    {
        switch ($key)
        {
            case 'name':
                return $this->name;
            case 'items':
                return $this->items;
        }
        
        if (!isset($this->items[$key]))
        {
            throw new InvalidArgumentException("Key '{$key}' not set for group '{$this->name}'");
        }
        
        if (isset($this->items[$key]))
        {
            // Return individual item
            return $this->items[$key];
        }
    }

    public function __set($key, $value)
    {    
        if (isset($this->items[$key]))
        {
            $this->items[$key] = $value;
        }      
    }

    public function set_label($label)
    {
        $this->label = $label;
    }

    public function add_group($name)
    {
        //$this->items[$name] = new midgardmvc_helper_forms_group("{$this->name}_{$name}");
        $this->items[$name] = new midgardmvc_helper_forms_group($name);
        return $this->items[$name];
    }

    public function add_field($name, $field, $required = false, array $actions = array())
    {        
        if (strpos($field, '_') === false)
        {
            // Built-in type called using the shorthand notation
            $field = "midgardmvc_helper_forms_field_{$field}";
        }  
                
        $prefixed_name = $name;
        if (strlen($this->name) > 0)
        {
            //merge group name and field name to ensure namespacing between groups
            $prefixed_name = $this->name."_".$name;     
        }

        $this->items[$name] = new $field($prefixed_name, $required, $actions);
        // If there are values stored in session, set them
        $mvc = midgardmvc_core::get_instance();

        if ($mvc->sessioning->exists('midgardmvc_helper_forms', "stored_{$this->namespace}"))
        {
            $stored = $mvc->sessioning->get('midgardmvc_helper_forms', "stored_{$this->namespace}");
            if (isset($stored[$prefixed_name]))
            {
                
                $this->items[$name]->set_value($stored[$prefixed_name]);
            }
        }        
        return $this->items[$name];        
    }

    public function process_post()
    {
        foreach ($this->items as $name => $item)
        {
            if ($item instanceof midgardmvc_helper_forms_group)
            {
                // Tell group to process items
                $item->process_post();
                continue;
            }    
            
            $prefixed_name = $name;
            if (strlen($this->name) > 0)
            {
                //merge group name and field name to ensure namespacing between groups
                $prefixed_name = $this->name."_".$name;     
            }            
            
            $value = null;
            if (isset($_POST[$prefixed_name]))
            {
                $value = $_POST[$prefixed_name];
            }
            elseif(isset($_GET[$prefixed_name]))
            {
                $value = $_GET[$prefixed_name];
            }
            elseif(isset($_FILES[$prefixed_name]))
            {
                $value = $_FILES[$prefixed_name];
            }            
            //If item is a field with the proper name, do magic        
            //if (isset($value) && $value != null)
            //{
            
                //Set value to the field
                $item->set_value($value);
                //Read actions
                $actions = $item->get_actions();
                //If there are manually defined actions in the array, run them
                if (   is_array($actions) 
                    && count($actions) > 0)
                {
                    foreach($actions as $action)
                    {
                        if (method_exists($item, $action))
                        {
                            $item->$action();
                        }
                        else
                        {
                            $classname = get_class($item);
                            throw new Exception("No action method '$action' implemented for the class '$classname'");
                        }
                    }
                }
                //...or just do what people usually want to do with form fields
                else
                {
                    //Default: First clean, then validate
                    $item->clean();
                    $item->validate();
                }           
            //}    
        }
    }
    
    public function __toString()
    {
        $form_string  = "<fieldset>\n";
        
        if ($this->label)
        {
            $form_string .= "<legend>{$this->label}</legend>\n";
        }
        
        foreach ($this->items as $item)
        {
            if ($item instanceof midgardmvc_helper_forms_group)
            {
                $form_string .= $item;
                continue;
            }
            $form_string .= $item->widget;
        }
        $form_string .= "</fieldset>\n";
        return $form_string;
    }
}
