<?php
// Pour les assets de l'administration EasyAdmin
namespace App\Controller\Admin\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

trait EasyAdminAssetsTrait
{
    /**
     * assets pour les CRUD
     * @param string $buttonPadding Padding personnalisé pour le bouton
     * @param string $hoverColor Couleur de survol personnalisée
     */
    public function configureCommonAssets(Assets $assets, string $buttonPadding = '10px', string $hoverColor = '#99cd47'): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addCssFile('styles/admin.css')
            ->addCssFile('styles/form_admin.css')
            ->addHtmlContentToBody($this->getCommonStyles($buttonPadding, $hoverColor));
    }

    /* Styles CSS communs */
    private function getCommonStyles(string $buttonPadding, string $hoverColor): string
    {
        return '
        <style>
        /***** Bouton "Créer X" *****/
        .page-actions .btn,
        .page-actions .btn.btn-primary {
            background-color: #99cd47 !important;
            --bs-btn-bg: #99cd47 !important;
            --bs-btn-hover-bg: ' . $hoverColor . ' !important;
            --bs-btn-active-bg: #99cd47 !important;
            --button-bg: #99cd47 !important;
            --button-primary-bg: #99cd47 !important;
            --button-primary-hover-bg: ' . $hoverColor . ' !important;
            border: none !important;
            padding: ' . $buttonPadding . ' !important;
            padding-bottom:26px !important;
            box-shadow: 4px 6px 4px 0 rgba(0, 0, 0, 0.25) !important;
            margin-bottom: 20px !important;
            text-decoration: none !important;
            color: white !important;
        }
        .page-actions .btn:focus,
        .page-actions .btn.btn-primary:focus,
        .page-actions .btn:active,
        .page-actions .btn.btn-primary:active,
        .page-actions .btn:focus-visible,
        .page-actions .btn.btn-primary:focus-visible {
            border: none !important;
            outline: none !important;
        }

        /***** Tableau responsive *****/
        .datagrid {
            width: 100%;
            table-layout: auto;
        }
        .datagrid td,
        .datagrid th {
            padding: 12px 15px !important;
            vertical-align: middle !important;
            word-wrap: break-word;
            max-width: 300px;
        }
        .datagrid th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            background-color: #f8f9fa;
        }
        .datagrid tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        </style>
        ';
    }
}
