<?php
namespace FacturaScripts\Plugins\DocumentosFirma;

class Init extends \FacturaScripts\Core\Base\InitClass
{
    public function init()
    {
        // se ejecuta cada vez que carga FacturaScripts (si este plugin está activado).
        $this->loadExtension(new Extension\Controller\ListAttachedFile());
        $this->loadExtension(new Extension\Controller\EditPresupuestoCliente());
        $this->loadExtension(new Extension\Controller\EditPedidoCliente());
        $this->loadExtension(new Extension\Controller\EditFacturaCliente());
        $this->loadExtension(new Extension\Controller\EditAlbaranCliente());
    }

    public function update()
    {
        // se ejecuta cada vez que se instala o actualiza el plugin.
    }
}