<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Model;

use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Tools;

class DfPlantilla extends ModelClass
{
    use ModelTrait;

    public $creationdate;

    public $html;
    public $idattachedfile;

    public $idlogo;

    public $idplantilla;

    public $rgpd;

    public $titulo;

    public function clear()
    {
        parent::clear();
        $this->creationdate = Tools::date();
        $this->idlogo = false;
        $this->rgpd = false;
    }

    public function getLogo()
    {
        $logo = new AttachedFile();
        $logo->loadFromCode($this->idlogo);
        return $logo;
    }

    public function html()
    {
        $html = Tools::fixHtml($this->html);
        // Reemplazamos {firma} por la imagen de la firma de la empresa
        if ($this->idattachedfile && strpos($html, '{firma-empresa}') !== false) {
            $firma = new AttachedFile();
            if ($firma->loadFromCode($this->idattachedfile)) {
                $html = str_replace('{firma-empresa}', '<img src="' . $firma->url('download') . '" style="width: 50%; height: auto;" />', $html);
            }
        }
        return $html;
    }

    public static function primaryColumn(): string
    {
        return "idplantilla";
    }

    public static function tableName(): string
    {
        return "df_plantillas";
    }




    public function test(): bool
    {
        // Escapamos Html
        $this->titulo = Tools::noHtml($this->titulo);
        $this->html = Tools::noHtml($this->html);

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListAttachedFile?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
