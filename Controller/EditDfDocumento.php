<?php
namespace FacturaScripts\Plugins\DocumentosFirma\Controller;

use FacturaScripts\Dinamic\Lib\AssetManager;

class EditDfDocumento extends \FacturaScripts\Core\Lib\ExtendedController\EditController
{
    public function getModelClassName(): string {
        return "DfDocumento";
    }

    public function getPageData(): array {
        $data = parent::getPageData();
        $data["title"] = "Documento";
        $data["icon"] = "fas fa-search";
        return $data;
    }

    protected function loadData($viewName, $view) {
        parent::loadData($viewName, $view);
        if ($viewName === "EditDfDocumento") {
            $id = $this->getViewModelValue($viewName, "id");
            $view->loadData($id);
        }

        // Añadir botón de firma
        if ($viewName === "EditDfDocumento" && $view->model->exists()) {
            $model = $this->getModel();

            $this->addButton($viewName, [
                "action" => 'FirmarDocumento?code=' . $model->iddocumento . '&token=' . $model->token,
                "color" => "primary",
                "confirm" => true,
                "icon" => "fas fa-file-signature",
                "label" => "Firmar Documento",
                "title" => "Firmar Documento",
                "type" => 'link',
                "target" => '_blank'
            ]);

            
        }
    }
}
