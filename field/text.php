<?php
/**
 * @package midgardmvc_helper_forms
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class midgardmvc_helper_forms_field_text extends midgardmvc_helper_forms_field
{

    public function validate()
    {
        // if value is NOT required and it is left empy, validate as true
        if (   isset($this->required) 
            && $this->required == false 
            && mb_strlen($this->value) == 0)
        {
            return;
        }        
        if ($this->value != strip_tags($this->value))
        {   
            $message = $this->mvc->i18n->get('HTML tags are not allowed in a text field', 'midgardmvc_helper_forms');
            throw new midgardmvc_helper_forms_exception_validation($message);        
        }
        if (mb_strlen($this->value) == 0)
        {
            $message = $this->mvc->i18n->get('The field cannot be empty', 'midgardmvc_helper_forms');
            throw new midgardmvc_helper_forms_exception_validation($message);        
        }
    }

    public function clean()
    {
        $this->value = trim(strip_tags($this->value));
    }    
}
?>
