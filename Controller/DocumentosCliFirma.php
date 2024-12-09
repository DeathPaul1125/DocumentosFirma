<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Controller;

use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\DfDocumento;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Session;

class DocumentosCliFirma extends \FacturaScripts\Core\Base\Controller
{
    public $pdffile;
    public $documento;
    public $firma;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["menu"] = "admin";
        $data["title"] = "DocumentosCliFirma";
        $data["icon"] = "fas fa-file";
        $data["showonmenu"] = false;
        return $data;
    }



    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $idalbaran = $this->request->get('idalbaran');
        if ($idalbaran) {
            $this->documento = new AlbaranCliente();
            $this->documento->loadFromCode($idalbaran);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idalbaran', $this->documento->idalbaran)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idalbaran = $this->documento->idalbaran;
            }
        }

        $idfactura = $this->request->get('idfactura');
        if ($idfactura) {
            $this->documento = new FacturaCliente();
            $this->documento->loadFromCode($idfactura);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idfactura', $this->documento->idfactura)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idfactura = $this->documento->idfactura;
            }
        }

        $idpedido = $this->request->get('idpedido');
        if ($idpedido) {
            $this->documento = new PedidoCliente();
            $this->documento->loadFromCode($idpedido);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idpedido', $this->documento->idpedido)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idpedido = $this->documento->idpedido;
            }
        }

        $idpresupuesto = $this->request->get('idpresupuesto');
        if ($idpresupuesto) {
            $this->documento = new PresupuestoCliente();
            $this->documento->loadFromCode($idpresupuesto);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idpresupuesto', $this->documento->idpresupuesto)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idpresupuesto = $this->documento->idpresupuesto;
            }
        }

        if ($this->firma->exists() == false || empty($this->firma->token)) {
            $this->firma->save();
        }
        $token = $this->request->get('token', '');
        if (empty($token)) {
            $this->redirect('DocumentosCliFirma?' . $this->documento->primaryColumn() . '=' . $this->documento->primaryColumnValue() . '&token=' . $this->firma->token);
            return;
        }


        $action = $this->request->get('action', '');
        if ($action == 'documento_firmado') {
            $this->documentoFirmadoAction();
        }

        // Generamos el pdf
        if ($this->documento) {
            $this->generarPdf();
        }
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);

        $idalbaran = $this->request->get('idalbaran');
        if ($idalbaran) {
            $this->documento = new AlbaranCliente();
            $this->documento->loadFromCode($idalbaran);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idalbaran', $this->documento->idalbaran)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idalbaran = $this->documento->idalbaran;
            }
        }

        $idfactura = $this->request->get('idfactura');
        if ($idfactura) {
            $this->documento = new FacturaCliente();
            $this->documento->loadFromCode($idfactura);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idfactura', $this->documento->idfactura)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idfactura = $this->documento->idfactura;
            }
        }

        $idpedido = $this->request->get('idpedido');
        if ($idpedido) {
            $this->documento = new PedidoCliente();
            $this->documento->loadFromCode($idpedido);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idpedido', $this->documento->idpedido)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idpedido = $this->documento->idpedido;
            }
        }

        $idpresupuesto = $this->request->get('idpresupuesto');
        if ($idpresupuesto) {
            $this->documento = new PresupuestoCliente();
            $this->documento->loadFromCode($idpresupuesto);

            $this->firma = new DfDocumento();
            $where = [new DataBaseWhere('idpresupuesto', $this->documento->idpresupuesto)];
            if (false == $this->firma->loadFromCode('', $where)) {
                $this->firma->idpresupuesto = $this->documento->idpresupuesto;
            }
        }

        // Comprobamos si el token que nos llega por url, es el mismo que tenemos en $firma
        $token = $this->request->get('token');
        if ($token != $this->firma->token) {
            $this->toolBox()->i18nLog()->error('El token no es correcto.');
            return;
        }

        $this->setTemplate($this->getClassName());

        $action = $this->request->get('action', '');
        if ($action == 'documento_firmado') {
            $this->documentoFirmadoAction();
        }

        // Generamos el pdf
        if ($this->documento) {
            $this->generarPdf();
        }
    }

    private function documentoFirmadoAction()
    {
        // Comprobamos que exista la carpeta de firmas 
        if (!file_exists('MyFiles/firmas')) {
            if (!mkdir('MyFiles/firmas', 0777, true)) {
                $this->toolBox()->i18nLog()->error('Error al crear la carpeta de firmas.');
                return;
            }
        }
        $this->firma->save();

        // Nos guardamos la firma en la carpeta de firmas
        $encoded_image = explode(",", $this->request->get('signature_data'))[1];
        $decoded_image = base64_decode($encoded_image);
        $filepath = 'MyFiles/firmas/' . $this->firma->iddocumento . '.png';
        if (false === file_put_contents($filepath, $decoded_image)) {
            $this->toolBox()->i18nLog()->error('Error al guardar la imagen de la firma.');
            return;
        }
        // Si se ha guardado la imagen de firma, actualizamos el Documento
        $this->firma->niffirma = $this->request->get('niffirma', '');
        $this->firma->nombrefirma = $this->request->get('nombrefirma', '');
        $this->firma->ip = Session::getClientIp();
        $this->firma->fechafirma = date('d-m-Y H:i:s');
        $this->firma->useragent = $this->request->server->get('HTTP_USER_AGENT');
        $this->firma->firmado = true;
        $this->firma->codcliente = $this->documento->codcliente;

        if ($this->firma->save()) {
            Tools::log()->info('Firma guardada correctamente.');
        }
    }

    private function generarPdf()
    {
        // Comprobamos si existe la carpeta de pdfs
        $folder = FS_FOLDER . '/MyFiles/DocumentosFirma/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        $pdf = new ExportManager();
        $filename = $this->documento->modelClassName() . '_' . $this->documento->primaryColumnValue() . '.pdf';
        $pdf->newDoc('PDF', $filename);

        $pdf->addBusinessDocPage($this->documento);

        if (file_put_contents($folder . $filename, $pdf->getDoc())) {
            $this->pdffile = 'MyFiles/DocumentosFirma/' . $filename;
            $this->pdffile .= '?myft=' . MyFilesToken::get($this->pdffile, false);
        }
    }
}
