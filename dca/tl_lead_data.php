<?php

/**
 * leads Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2011-2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/terminal42/contao-leads
 */


/**
 * Table tl_lead_data
 */
$GLOBALS['TL_DCA']['tl_lead_data'] = array
(

    // Config
    'config' => array
    (
        'dataContainer'             => 'Table',
        'ptable'                    => 'tl_lead',
        'closed'                    => true,
        'notCopyable'               => true,
        'notSortable'               => true,
        'notDeletable'              => true,
        'onload_callback' => array
        (
            array('tl_lead_data', 'checkPermission')
        ),
        'onsubmit_callback'         => [
            array('tl_lead_data', 'saveValue')
        ]
        'sql' => array
        (
            'keys' => array
            (
                'id'         => 'primary',
                'pid'        => 'index',
                'master_id'  => 'index',
            )
        )
    ),

    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                  => 4,
            'fields'                => array('sorting'),
            'flag'                  => 1,
            'panelLayout'           => 'filter;search,limit',
            'headerFields'          => array('created', 'form_id'),
            'child_record_callback' => array('tl_lead_data', 'listRows'),
            'disableGrouping'       => true,
        ),
        
        'operations' => array(
            'edit' => array(
                'href'                => 'act=edit',
                'icon'                => 'edit.gif',
             )
        ),
    ),
    
    // Palettes
    'palettes' => array(
        'default' => 'value',
    ),

    // Fields
    'fields' => array
    (
        'id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'sorting' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'master_id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'field_id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'name' => array
        (
            'sql'                     => "varchar(64) NOT NULL default ''"
        ),
        'value' => array
        (
            'input_field_callback' => array('tl_lead_data', 'generateInputField'),
            'sql'                     => "text NULL"
        ),
        'label' => array
        (
            'sql'                     => "text NULL"
        ),
    )
);


class tl_lead_data extends Backend
{

    /**
     * Check permissions to edit table
     */
    public function checkPermission()
    {
        $objUser = \BackendUser::getInstance();

        if ($objUser->isAdmin) {
            return;
        }

        $objUser->forms = deserialize($objUser->forms);

        if (!is_array($objUser->forms) || empty($objUser->forms)) {
            \System::log('Not enough permissions to access leads data ID "'.\Input::get('id').'"', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }

        $objLeads = \Database::getInstance()->prepare("SELECT master_id FROM tl_lead WHERE id=?")
                                            ->limit(1)
                                            ->execute(\Input::get('id'));

        if (!$objLeads->numRows || !in_array($objLeads->master_id, $objUser->forms)) {
            \System::log('Not enough permissions to access leads data ID "'.\Input::get('id').'"', __METHOD__, TL_ERROR);
            \Controller::redirect('contao/main.php?act=error');
        }
    }

    /**
     * Add an image to each record
     * @param array
     * @param string
     * @return string
     */
    public function listRows($row)
    {
        return $row['name'] . ': ' . \Leads\Leads::formatValue((object) $row);
    }
    
    /**
     * Generate the input field according to the corresponding form field
     */
    public function generateInputField(DataContainer $dc, string $extendedLabel)
    {
        $objFormField = FormFieldModel::findById($dc->activeRecord->field_id);
        //dump($objFormField->row());
        //dump($dc->activeRecord->row());
        $arrAttributes = [
            'id'=> $objFormField->id,
            'name' => $objFormField->name,
            'label' => $objFormField->label,
            'value' => $dc->activeRecord->value,
            'mandatory' => $objFormField->mandatory,
            'rgxp' => $objFormField->rgxp,
            'minlength' => $objFormField->minlength,
            'maxlength' => $objFormField->maxlength
        ];
        if ($objFormField->type == 'radio') {
            $arrAttributes['options'] = $objFormField->options;
            $widget = new \Contao\RadioButton($arrAttributes);
        }
        else if ($objFormField->type == 'checkbox') {
            $arrAttributes['options'] = $objFormField->options;
            dump($objFormField->multiple);
            if (count(unserialize($objFormField->options)) > 1) {
                $arrAttributes['multiple'] = $objFormField->options;
            }
            $widget = new \Contao\CheckBox($arrAttributes);
        }
        else if ($objFormField->type == 'select') {
            $arrAttributes['options'] = $objFormField->options;
            $arrAttributes['multiple'] = $objFormField->multiple;
            $arrAttributes['mSize'] = $objFormField->mSize;
            $widget = new \Contao\SelectMenu($arrAttributes);
        }
        else {
            $widget = new \Contao\TextField($arrAttributes);
        }
        return '<div class="widget"><h3>'.$widget->generateLabel().'</h3>'.$widget->generate().'</div>';
    }

    /**
     * Save the value
     */
    public function saveValue(DataContainer $dc) {
        $objFormField = FormFieldModel::findById($dc->activeRecord->field_id);
        $set['label'] = $set['value'] = \Input::post($objFormField->name);
        if ($objFormField->type == 'checkbox'){
            unset($set['label']);
            $options = unserialize($objFormField->options);
            foreach ($options as $v){
                if (\in_array($v['value'], $set['value'])){
                    $set['label'][] = $v['label'];
                }
            }
        }
        if ($objFormField->type == 'select') {
            $options = unserialize($objFormField->options);
            if ($objFormField->multiple) {
                unset($set['label']);
            }
            foreach ($options as $v){
                if (!$objFormField->multiple && $set['value'] == $v['value']){
                    $set['label'] = $v['label'];
                } else if (\in_array($v['value'], $set['value'])) {
                    $set['label'][] = $v['label'];
                }
            }
        }
        Database::getInstance()->prepare("UPDATE tl_lead_data %s WHERE id=?")->set($set)->execute($dc->activeRecord->id);
    }
}
