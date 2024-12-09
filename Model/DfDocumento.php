<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\DocumentosFirma\Model\DfPlantilla;
use FacturaScripts\Core\Base\MyFilesToken;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Dinamic\Lib\Email\NewMail;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Colonia;
use FacturaScripts\Dinamic\Model\Lote;
use FacturaScripts\Dinamic\Model\PedidoCliente;

class DfDocumento extends ModelClass
{
    use ModelTrait;

    public $codcliente;
    public $codproveedor;
    public $fechafin;
    public $fechafirma;
    public $fechaini;
    public $firmado;
    public $idalbaran;
    public $idcontacto;
    public $iddocumento;
    public $idfactura;
    public $idpedido;
    public $idplantilla;
    public $idpresupuesto;
    public $ip;
    public $renovar;
    public $token;
    public $useragent;
    public $codlote;

    public function clear()
    {
        parent::clear();
        $this->fechaini = Tools::date();
        $this->firmado = false;
        $this->renovar = false;
        $this->token = Tools::randomString(32);
    }

    public function getAlbaran()
    {
        $albaran = new AlbaranCliente();
        $albaran->loadFromCode($this->idalbaran);
        return $albaran;
    }

    // Nostraemos el Cliente
    public function getCliente()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    //Traemos el dato del Lote
    public function getLote()
    {
        $lote = new Lote();
        $lote->loadFromCode('', [new DataBaseWhere('codlote', $this->codlote)]);
        return $lote;
    }
  
    public function getColonia()
    {
        $colonia = new Colonia();
        $colonia->loadFromCode($this->getLote()->colonia);
        return $colonia;
    }

    

    // Nostraemos la Factura relacionada
    public function getFactura()
    {
        $factura = new FacturaCliente();
        $factura->loadFromCode($this->idfactura);
        return $factura;
    }

    public function getPedido()
    {
        $pedido = new PedidoCliente();
        $pedido->loadFromCode($this->idpedido);
        return $pedido;
    }

    // Nos traemos la plantilla de documento
    public function getPlantilla()
    {
        $plantilla = new DfPlantilla();
        $plantilla->loadFromCode($this->idplantilla);
        return $plantilla;
    }

    public function getPresupuesto()
    {
        $presupuesto = new PresupuestoCliente();
        $presupuesto->loadFromCode($this->idpresupuesto);
        return $presupuesto;
    }

    // Nostraemos el Proveedor
    public function getProveedor()
    {
        $proveedor = new Proveedor();
        $proveedor->loadFromCode($this->codproveedor);
        return $proveedor;
    }

    // Devolvemos la ruta del archivo de la firma
    public function getRutaFirma()
    {
        return FS_FOLDER . '/MyFiles/firmas/' . $this->iddocumento . '.png';
    }

    // Devolvemos el link para firmar el documento
    public function getLinkFirma(): string
    {
        $domain = 'http://' . $_SERVER['HTTP_HOST'] . FS_ROUTE . '/';
        $url = $domain . 'FirmarDocumento?code=' . $this->iddocumento . '&token=' . $this->token;
        return $url;
    }

    // Devolvemos el html del Documento
    public function html(): string
    {
        $html = $this->getPlantilla()->html();
        // Reemplazamos {firma-cliente} por la imagen de la firma del cliente, proveedor o Contacto
        if ($this->firmado && strpos($html, '{firma-usuario}') !== false) {
            $filename = 'MyFiles/firmas/' . $this->iddocumento . '.png';
            if (file_exists($filename)) {
                // Obtenemos la url con el token para descargar la imagen
                $url = $filename . '?myft=' . MyFilesToken::get($filename, true);
                $sustituciones = [
                    '{firma-usuario}' => '<img src="' . $url . '" style="width: 50%; height: auto;" />',
                ];
                $html = str_replace(array_keys($sustituciones), array_values($sustituciones), $html);
            }
        }


        $cliente = $this->getCliente();
        
        $contacto = new Contacto();
        if (false == $contacto->loadFromCode($cliente->codcliente)){    
        }        

       
        $proveedor = $this->getProveedor();
        $direccionProveedor = $proveedor->getDefaultAddress();
        $factura = $this->getFactura();
        $presupuesto = $this->getPresupuesto();
        $albaran = $this->getAlbaran();
        $pedido = $this->getPedido();
        $lote = $this->getLote();
        $colonia = $this->getColonia();

        // Reemplazamos las variables de la plantilla
        $sustituciones = [
            '{cliente-nombre}' => $cliente->nombre,
            '{cliente-razon-social}' => $cliente->razonsocial,
            '{cliente-cifnif}' => $cliente->cifnif,
            '{cliente-direccion}' => $contacto->direccion . ', ' . $contacto->codpostal . ' ' . $contacto->ciudad,
            '{contacto-nombre}' => $contacto->nombre,
            '{contacto-apellidos}' => $contacto->apellidos,
            '{contacto-email}' => $contacto->email,
            '{contacto-telefono}' => $contacto->telefono1,
            '{proveedor-nombre}' => $proveedor->nombre,
            '{proveedor-razon-social}' => $proveedor->razonsocial,
            '{proveedor-cifnif}' => $proveedor->cifnif,
            '{proveedor-direccion}' => $direccionProveedor->direccion . ', ' . $direccionProveedor->codpostal . ' ' . $direccionProveedor->ciudad,
            '{factura-numero}' => $factura->codigo,
            '{factura-fecha}' => $factura->exists() ? $factura->fecha : '',
            '{factura-total}' => $factura->total,
            '{presupuesto-numero}' => $presupuesto->codigo,
            '{presupuesto-fecha}' => $presupuesto->exists() ? $presupuesto->fecha : '',
            '{presupuesto-total}' => $presupuesto->total,
            '{albaran-numero}' => $albaran->codigo,
            '{albaran-fecha}' => $albaran->fecha,
            '{albaran-total}' => $albaran->total,
            '{pedido-numero}' => $pedido->codigo,
            '{pedido-fecha}' => $pedido->fecha,
            '{pedido-total}' => $pedido->total,

            '{cliente-cui}' => $cliente->cui,
            '{cliente-cui-letras}' => $cliente->letrascui,
            '{cliente-edad}' => $cliente->edad, 
            '{cliente-estadocivil}' => $cliente->estadocivil, 
            '{cliente-profesion}' => $cliente->profesion,
            '{cliente-nacionalidad}' => $cliente->nacionalidad,
            '{contacto-nombre}' => $contacto->nombre,
            '{contacto-apellidos}' => $contacto->apellidos,
            '{contacto-email}' => $contacto->email,
            '{contacto-telefono}' => $contacto->telefono1,
            '{contacto-departamento}' => $contacto->provincia,


            '{finca}' => $lote->finca,
            '{sector}' => $lote->sector,
            '{manzana}' => $lote->manzana,
            '{lote}' => $lote->lote,
            '{direccion}' => $lote->direccion,
            '{folio}' => $lote->folio,
            '{libro}' => $lote->libro,
            //medidas
            '{frente}' => $lote->frente,
            '{fondo}' => $lote->fondo,
            '{total-metros}' => $lote->totalmetros,



            //colonia
            '{colonia}' => $colonia->nombre,
            '{representante}' => $colonia->representante,
            '{representante-edad-letras}' => $colonia->edadletras,
            '{representante-edad-letras}' => $colonia->edadletras,
            '{representante-profesion}' => $colonia->profesion,
            '{representante-estado-civil}' => $colonia->estadocivil,
            '{representante-cui}' => $colonia->cuirepresentante,
            '{representante-cui-letras}' => $colonia->cuiletras,

        ];
        $html = str_replace(array_keys($sustituciones), array_values($sustituciones), $html);

        return $html;
    }

    public function install(): string
    {
        // Cargamos las dependencias
        new DfPlantilla();
        return parent::install();
    }

    public function publicLink(): string
    {
        return 'DocumentosCliFirma?code=' . $this->iddocumento . '&token=' . $this->token;
    }

    public function sendEmail(string $texto): bool
    {
        // Enviamos el email con el documento
        $mail = new NewMail();
        $mail->to($contacto->email);
        $mail->title = $this->getPlantilla()->titulo;
        $mail->setUser(Session::user());
        $mail->text = $texto . '<br/> <a href="' . $this->getLinkFirma() . '">Ver Documento para Firmar</a>';
        return $mail->send();
    }

    public static function primaryColumn(): string
    {
        return "iddocumento";
    }

    public static function tableName(): string
    {
        return "df_documentos";
    }

    public function url(string $type = 'auto', string $list = 'ListAttachedFile?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    public function save(): bool
    {
        if (empty($this->token)) {
            $this->token = Tools::randomString(20);
        }
        return parent::save();
    }
    public function primaryDescription(): string
    {
        return 'Contrato del Lote ' . $this->codlote;
    }
}
