<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Lib\Widget;

use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Lib\Widget\WidgetTextarea;

/**
 * Description of WidgetHtml
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class WidgetHtml extends WidgetTextarea
{

    /**
     * 
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
    }

    /**
     * Adds needed assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('css', FS_ROUTE . '/Plugins/DocumentosFirma/node_modules/summernote/dist/summernote-bs4.css');
        AssetManager::add('js', FS_ROUTE . '/Plugins/DocumentosFirma/node_modules/summernote/dist/summernote-bs4.js');
        AssetManager::add('js', FS_ROUTE . '/Dinamic/Assets/JS/WidgetHtml.js');
    }

    /**
     * 
     * @param string $type
     * @param string $extraClass
     *
     * @return string
     */
    protected function inputHtml($type = 'text', $extraClass = 'widget-html')
    {
        return parent::inputHtml($type, $extraClass);
    }

    /**
     * 
     * @return string
     */
    protected function show()
    {
        return htmlentities(parent::show());
    }
}
