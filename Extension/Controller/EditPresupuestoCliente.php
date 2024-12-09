<?php
namespace FacturaScripts\Plugins\DocumentosFirma\Extension\Controller;

use Closure;
use FacturaScripts\Dinamic\Lib\AssetManager;

class EditPresupuestoCliente
{
    public function createViews(): Closure
    {
       return function() {
          // tu código aquí
          $this->addHtmlView('Firmar', 'firmarEnCanvas', 'DfDocumento', 'Firmar', 'fas fa-file-signature');
       };
    }


    public function loadData(): Closure
    {
       return function ($viewName, $view) {
          // Si el nombre de la vista es 'Firmar' y el modelo existe, añadimos el botón de firma
          if ($viewName === $this->getMainViewName() && $view->model->exists()) {
            $model = $this->views[$this->getMainViewName()]->model;
             $this->addButton($viewName, [
                "action" => 'DocumentosCliFirma?' . $model->primaryColumn() . '=' . $model->primaryColumnValue(),
                "icon" => "fas fa-file-signature",
                "label" => "Enviar a firma",
                "type" => 'link',
                "target" => '_blank'
             ]);

          }
       };
    }
}
