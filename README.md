<p align="center">
  <img src="public/images/logo.png" alt="Nexo Estoque" width="90">
</p>

<h1 align="center">Nexo Estoque</h1>

<p align="center">
  Sistema web de controle de estoque para pequenos comércios — cadastro de produtos,
  movimentações, pedidos de compra, validade de lotes, financeiro, relatórios e equipe com permissões.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white" alt="MySQL 8">
  <img src="https://img.shields.io/badge/Docker-pronto-2496ED?logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/licen%C3%A7a-MIT-green" alt="MIT">
</p>

---

## 📑 Sumário

- [Sobre](#-sobre)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias](#-tecnologias)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação (Docker — recomendado)](#-instalação-docker--recomendado)
- [Configuração de e-mail (SMTP)](#-configuração-de-e-mail-smtp)
- [Primeiro acesso](#-primeiro-acesso)
- [Portas e serviços](#-portas-e-serviços)
- [Comandos úteis](#-comandos-úteis)
- [Solução de problemas](#-solução-de-problemas)
- [Estrutura do projeto](#-estrutura-do-projeto)
- [Autor](#-autor)
- [Licença](#-licença)

---

## 📦 Sobre

O **Nexo Estoque** é um sistema web feito para pequenos comerciantes terem controle total do
estoque dos seus produtos: o que entra, o que sai, o que está acabando e o que está vencendo.
Tem painel com indicadores, controle de equipe com permissões por aba, fluxo de aprovação de
pedidos e movimentações, relatórios em PDF/CSV e tema claro/escuro.

> Projeto desenvolvido com Laravel 12 + MySQL, rodando em Docker.

---

## ✨ Funcionalidades

- Dashboard com total de produtos, valor em estoque, itens em estoque baixo e alertas de validade.
- Cadastro de produtos com SKU, categoria, preço, fornecedor e estoque mínimo.
- Movimentações de entrada, saída, ajustes e transferências com histórico.
- Pedidos de compra com aprovação e relatório em PDF.
- Gestão de categorias e fornecedores.
- Controle de lotes e validade de produtos.
- Financeiro: contas a pagar e receber.
- Controle de equipe com permissões por módulo.
- Cadastro de usuários com aprovação do administrador.
- Notificações no sistema e por e-mail.
- Recuperação de senha por e-mail.
- Tema claro/escuro salvo por usuário.

---

## 🛠 Tecnologias

| Camada        | Tecnologia                              |
|--------------|------------------------------------------|
| Back-end     | PHP 8.2+, Laravel 12                     |
| Banco        | MySQL 8.0                                |
| Front-end    | Blade, Bootstrap 5, JavaScript, Chart.js |
| Ambiente     | Docker + Docker Compose                  |
| Admin DB     | phpMyAdmin                               |

---

## ✅ Pré-requisitos

- Docker Desktop
- Git

---

## 🚀 Instalação (Docker — recomendado)

### 1. Clonar o repositório
```bash
git clone https://github.com/LuizBail2/NOME-DO-REPOSITORIO.git
cd NOME-DO-REPOSITORIO