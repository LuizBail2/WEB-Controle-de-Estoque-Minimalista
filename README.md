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
1. Responsividade para celulares/tablets
2. implemnetação do mysql (antes utilzava o sqlite)

# 🛠️ Como Rodar o Projeto
Entre no CMD

## Clone o projeto
git clone https://github.com/LuizBail2/WEB-Controle-de-Estoque-Minimalista

# Entre na pasta
cd WEB-Controle-de-Estoque-Minimalista

## Instalar as dependências do PHP
'composer install'

## Criar o arquivo .env
copy .env.example .env

## Configure o Banco de Dados
1. DB_CONNECTION=mysql
2. DB_HOST=127.0.0.1
3. DB_PORT=3306
4. DB_DATABASE=estoque
5. DB_USERNAME=root
6. DB_PASSWORD=

## Gere a chave do Laravel
'php artisan key:generate'

## Rode as migrations
'php artisan migrate'

## Iniciar o servidor
Execute o projeto com 'php artisan serve'
