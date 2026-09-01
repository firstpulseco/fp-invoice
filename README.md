# FP Invoice
> Beautiful invoices, without the accounting software

A small, self-hosted invoicing application for freelancers, studios, and small businesses. Create clients, build invoices with hourly or fixed-price services, and print polished US Letter invoices using your browser's Save as PDF workflow.

## Features

- Client directory with preserved historical billing details
- Hourly and fixed-price invoice items
- Automatic line-item and invoice totals
- Draft, Sent, Paid, and Void statuses
- Configurable business identity, logo, payment methods, and invoice terms
- Print-ready, multi-page invoices
- Light and dark application themes

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- SQLite or MySQL

## Installation

```bash
git clone <repository-url> invoice
cd invoice
composer run setup
composer run dev
```

The setup command installs dependencies, creates `.env`, generates an application key, runs migrations, and builds frontend assets.

Open the local application URL, register the first user, and enter your business information under **Settings**.

## Database

SQLite is configured by default. To use MySQL, update the `DB_*` values in `.env` before running migrations.

## Printing

Open an invoice preview and select **Print**. Use your browser's **Save as PDF** option to create a client-ready PDF.

## Development

```bash
composer run dev
php artisan test --compact
npm run build
```

## License

This project is open-sourced under the [MIT license](LICENSE). © 2026 First Pulse LLC.
