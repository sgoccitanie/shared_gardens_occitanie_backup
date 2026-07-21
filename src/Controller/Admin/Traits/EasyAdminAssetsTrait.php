<?php

namespace App\Controller\Admin\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

trait EasyAdminAssetsTrait
{
    /**
     * Configure les assets communs à tous les CRUDs
     */
    public function configureCommonAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addCssFile('styles/variables.css')  // ← toujours en premier
            ->addCssFile('styles/admin.css')
            ->addCssFile('styles/form_admin.css')
            ->addCssFile('styles/admin_datagrid.css');
    }
}
