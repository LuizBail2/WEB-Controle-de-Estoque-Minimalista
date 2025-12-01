<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


## WEB-Controle-de-Estoque-de-Eletrônicos
Feito para pequenos comerciantes de eletrônicos para facilitar a gerência de estoque de seus produtos, tendo assim um controle maior sobre seus negocios.

## Funcionalidades Adicionadas
1. Implementação de Relatórios em PDF
2. Botão na interface para gerar PDF
3. Criação da ReportController
4. Nova rota adicionada
5. Criação  da view Reports - para gerar a tela do PDF


# Guia de Instalação da dependência
## Usei a dependência 'barryvdh/laravel-dompdf'
1. composer require barryvdh/laravel-dompdf


# Controller adicionada
## Criação da ReportController
1. 'php artisan make:controller ReportController'

# Rota adicionada
1. Adição da ReportController na routes/web.php - (somente para usúarios logados)

# View(Blade) de PDF adicionada
1. Criação da view reports/low_stock.blade

