<?php

namespace FacturaScripts\Plugins\DocumentosFirma\Extension\Controller;

use Closure;

class ListAttachedFile
{
   public function createViews(): Closure
   {
      return function () {
         $this->createViewsPlantilla();
         $this->createViewsDocumento();
      };
   }

   public function createViewsPlantilla(): Closure
   {
      return function ($viewName = 'ListDfPlantilla') {
         $this->addView($viewName, 'DfPlantilla', 'Plantillas', 'fas fa-file');
      };
   }

   public function createViewsDocumento(): Closure
   {
      return function ($viewName = 'ListDfDocumento') {
         $this->addView($viewName, 'DfDocumento', 'Documentos', 'fas fa-file-signature');
      };
   }

   
}
