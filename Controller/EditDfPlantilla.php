<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Controller;

use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Tools;
use Mpdf\Mpdf;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Plugins\DocumentosFirma\Model\DfDocumento;

/**
 * Description of EditDfPlantilla
 *
 * @author mariah
 */

class EditDfPlantilla extends \FacturaScripts\Core\Lib\ExtendedController\EditController
{
    public $documento;

    public function getModelClassName(): string
    {
        return "DfPlantilla";
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["title"] = "Plantilla";
        $data["icon"] = "fas fa-search";
        return $data;
    }

    protected function createViews()
    {
        parent::createViews();
        $this->createViewsDfDocumentos();
        $this->setTabsPosition('bottom');

        // Desactivamos el boton imprimir
        $this->setSettings($this->getMainViewName(), 'btnPrint', false);
    }

    // Añadimos la vista de documentos
    private function createViewsDfDocumentos(string $viewName = 'ListDfDocumento')
    {
        $this->addListView($viewName, 'DfDocumento', 'Documentos', 'fas fa-file');
        $this->views[$viewName]->addOrderBy(['iddocumento'], 'ID');
        $this->views[$viewName]->addOrderBy(['fechaini'], 'start-date', 2);

        // Añadimos los filtros
        $this->views[$viewName]->addFilterPeriod('fechafirma', 'date', 'fechafirma');
        $this->views[$viewName]->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'Cliente');
        $this->views[$viewName]->addFilterAutocomplete('codproveedor', 'proveedor', 'codproveedor', 'Ptoveedor');
        $this->views[$viewName]->addFilterAutocomplete('idcontacto', 'contacto', 'idcontacto', 'Contacto');
        $this->views[$viewName]->addFilterCheckbox('enviado', 'Enviado', 'enviado');
        $this->views[$viewName]->addFilterCheckbox('firmado', 'Firmado', 'firmado');
        $this->views[$viewName]->addFilterCheckbox('renovar', 'Renueva', 'renovar');

        // Añadimos los botones
        $this->addButton($viewName, [
            'action' => 'MultiEmail',
            'color' => 'warning',
            'icon' => 'fas fa-edit',
            'label' => 'Enviar Varios',
            'type' => 'modal',
        ]);

        // Ocultamos la columna Plantilla
        $this->views[$viewName]->disableColumn('idplantilla');
    }

    protected function execAfterAction($action)
    {
        if ($action === 'GeneratePDF') {
            $this->pdfAction();
            return;
        }

        parent::execAfterAction($action);
    }

    protected function execPreviousAction($action)
    {

        switch ($action) {
            case 'MultiEmail':
                $this->multiEmailAction();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }




    protected function loadData($viewName, $view)
    {
        $mvn = $this->getMainViewName();

        switch ($viewName) {
            case $mvn:
                parent::loadData($viewName, $view);
                $this->loadDataLogo($view, 'idlogo');
                $this->loadDataLogo($view, 'idattachedfile');
                if($view->model->exists() && false === strpos($view->model->html, '{firma-usuario}')){
                    Tools::log()->warning('La plantilla no contiene la etiqueta {firma-usuario}');
                }
                // Añadimos un boton para ir al GrapeJSEditor
                // $this->addButton($viewName, [
                //     'action' => 'GrapesJSEditor?code=' . $view->model->idplantilla,
                //     'color' => 'warning',
                //     'icon' => 'fas fa-edit',
                //     'label' => 'GrapesJS',
                //     'type' => 'link',
                // ]);

                // Añadimos un boton para generar el pdf
                $this->addButton($viewName, [
                    'action' => 'EditDfPlantilla?action=GeneratePDF&code=' . $view->model->primaryColumnValue(),
                    'color' => 'info',
                    'icon' => 'fas fa-print',
                    'label' => 'print',
                    'type' => 'link',
                    'target' => '_blank',
                ]);
                break;

            case 'ListDfDocumento':
                $idplantilla = $this->getViewModelValue($mvn, 'idplantilla');
                $where = [new DataBaseWhere('idplantilla', $idplantilla)];
                $view->loadData('', $where);
                break;
        }
    }

    private function loadDataLogo(BaseView $view, string $fieldName)
    {

        $columnLogo = $view->columnForName($fieldName);
        if ($columnLogo && $columnLogo->widget->getType() === 'select') {
            $images = $this->codeModel->all('attached_files', 'idfile', 'filename', true, [
                new DataBaseWhere('mimetype', 'image/gif,image/jpeg,image/png', 'IN')

            ]);
            $columnLogo->widget->setValuesFromCodeModel($images);
        }
    }

    private function multiEmailAction()
    {
        $emailList = $this->request->request->get('email-list');
        $emailtext = $this->request->request->get('email-text');
        // Saca los emails de la lista y los valida
        foreach (explode(',', $emailList) as $item) {
            $email = trim($item);
            if (false == filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // mensaje de no valido
                Tools::log()->warning('email-invalido: ' . $email);
                continue;
            }
            // Comprobamos si es un cliente
            $cliente = new Cliente();
            $where = [new DataBaseWhere('email', $email)];
            if ($cliente->loadFromCode('', $where)) {
                $newDoc = new DfDocumento();
                $newDoc->idplantilla = $this->request->get('code');
                $newDoc->codcliente = $cliente->codcliente;
                $newDoc->idcontacto = $cliente->idcontactofact;
                $newDoc->save();
                $newDoc->sendEmail($emailtext);
                continue;
            }
            // Comprobamos si es un proveedor
            $proveedor = new Proveedor();
            if ($proveedor->loadFromCode('', $where)) {
                $newDoc = new DfDocumento();
                $newDoc->idplantilla = $this->request->get('code');
                $newDoc->codproveedor = $proveedor->codproveedor;
                $newDoc->idcontacto = $proveedor->idcontacto;
                $newDoc->save();
                $newDoc->sendEmail($emailtext);
                continue;
            }
            // Si no es ni cliente ni proveedor, comprobamos si es un contacto
            $contacto = new Contacto();
            if ($contacto->loadFromCode('', $where)) {
                $newDoc = new DfDocumento();
                $newDoc->idplantilla = $this->request->get('code');
                $newDoc->idcontacto = $contacto->idcontacto;
                $newDoc->save();
                $newDoc->sendEmail($emailtext);
                continue;
            }
            // Si no es ni cliente, ni proveedor, ni contacto, sacamos un mensaje de error
            if($this->request->get('crear-contacto')) {
                $newContacto = new Contacto();
                $newContacto->email = $email;
                $newContacto->save();
                $newDoc = new DfDocumento();
                $newDoc->idplantilla = $this->request->get('code');
                $newDoc->idcontacto = $newContacto->idcontacto;
                $newDoc->save();
                $newDoc->sendEmail($emailtext);
                continue;
            }
            $this->toolBox()->i18nLog()->warning('email-no-encontrado: ' . $email);
        }
    }

    private function pdfAction()
    {
        $this->setTemplate(false);
        $this->response->headers->set('Content-Type', 'application/pdf');
        // $this->response->headers->set('Content-Disposition', 'inline; filename="DocumentoFirmado.pdf"');
        $mpdf = new Mpdf();

        // Cargamos un documento con esta plantilla
        $this->documento = new DfDocumento();
        $this->documento->idplantilla = $this->request->get('code');


        // Cargamos el HTML de la plantilla
        $html = Html::render('FirmarDocumentoPlantilla.html.twig', [
            'fsc' => $this
        ]);

        $mpdf->WriteHTML($html);
        $mpdf->Output();
    }
}
