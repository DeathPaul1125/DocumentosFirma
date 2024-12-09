<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Controller;

use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DocumentosFirma\Model\DfDocumento;
use Mpdf\Mpdf;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Session;

class FirmarDocumento extends \FacturaScripts\Core\Base\Controller
{
    /**
     * @var DfDocumento
     */
    public $documento;

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data["menu"] = "Firma";
        $data["title"] = "FirmarDocumento";
        $data["icon"] = "fas fa-file";
        $data["showonmenu"] = false;
        return $data;
    }

    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);
        // Cargamos el Documento
        $this->documento = new DfDocumento();
        $this->documento->loadFromCode($this->request->get('code'));

        $token = $this->request->get('token', '');
        if ($token != $this->documento->token) {
            Tools::log()->error('No autorizado.');
            $this->documento->clear();
            return;
        }

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'documento_firmado':
                $this->documentoFirmadoAction();
                break;

            case 'pdf':
                $this->pdfAction();
                break;
        }
    }

    public function publicCore(&$response)
    {
        parent::publicCore($response);
        

        // Cargamos el Documento
        $this->documento = new DfDocumento();
        if (false == $this->documento->loadFromCode($this->request->get('code'))) {
            Tools::log()->error('No se ha encontrado el documento.');
            return;
        }
        $token = $this->request->get('token', '');
        if ($token != $this->documento->token) {
            Tools::log()->error('No autorizado.');
            return;
        }

        $this->setTemplate($this->getClassName());

        
        $action = $this->request->get('action', '');
        switch ($action) {
            case 'documento_firmado':
                $this->documentoFirmadoAction();
                break;

            case 'pdf':
                $this->pdfAction();
                break;
        }
    }

    private function documentoFirmadoAction()
    {
        // Comprobamos que exista la carpeta de firmas 
        if (!file_exists('MyFiles/firmas')) {
            if (!mkdir('MyFiles/firmas', 0777, true)) {
                Tools::log()->error('Error al crear la carpeta de firmas.');
                return;
            }
        }
        // Nos guardamos la firma en la carpeta de firmas
        $encoded_image = explode(",", $this->request->get('signature_data'))[1];
        $decoded_image = base64_decode($encoded_image);
        $filepath = 'MyFiles/firmas/' . $this->documento->iddocumento . '.png';
        if (false === file_put_contents($filepath, $decoded_image)) {
            Tools::log()->error('Error al guardar la imagen de la firma.');
            return;
        }
        // Si se ha guardado la imagen de firma, actualizamos el Documento
        $this->documento->niffirma = $this->request->get('niffirma', '');
        $this->documento->nombrefirma = $this->request->get('nombrefirma', '');
        $this->documento->ip = Session::getClientIp();
        $this->documento->fechafirma = date('d-m-Y H:i:s');
        $this->documento->useragent = $this->request->server->get('HTTP_USER_AGENT');
        $this->documento->firmado = true;
        if ($this->documento->save()) {
            Tools::log()->info('Firma guardada correctamente.');
        }

        // Si la Plantilla es rgpd marcamos el cliente como informado
        if ($this->documento->getPlantilla()->rgpd) {
            $cliente = $this->documento->getCliente();
            $cliente->rgpd = true;
            $cliente->save();
        }
    }

    private function pdfAction()
    {
        $this->setTemplate(false);
        $this->response->headers->set('Content-Type', 'application/pdf');
        $this->response->headers->set('Content-Disposition', 'inline; filename="DocumentoFirmado.pdf"');
        $mpdf = new Mpdf();

        // Cargamos el HTML de la plantilla
        $html = Html::render('FirmarDocumentoPlantilla.html.twig', [
            'fsc' => $this
        ]);

        $mpdf->WriteHTML($html);
        $this->response->setContent($mpdf->Output('', 'S'));
    }
}
