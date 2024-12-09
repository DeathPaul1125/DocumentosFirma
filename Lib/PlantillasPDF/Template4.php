<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Lib\PlantillasPDF;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\DfDocumento;
use FacturaScripts\Plugins\PlantillasPDF\Lib\PlantillasPDF\Template1 as Template4Base;

class Template4 extends Template4Base
{
    public function addInvoiceFooter($model)
    {
        parent::addInvoiceFooter($model);
        if (property_exists($model, 'codproveedor')) {
            return;
        }
        

        $firma = new DfDocumento();
        $where = [new DataBaseWhere($model->primaryColumn(), $model->primaryColumnValue())];
        if (false == $firma->loadFromCode('', $where)) {
            return;
        }

        $this->writeHTML('<img src="' . $firma->getRutaFirma() . '"/>');

    }
}