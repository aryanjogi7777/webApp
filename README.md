# Defaulting Accounts

A Laravel account register for managing defaulting-account data from the supplied spreadsheet. It includes a dark, responsive quick lookup by account number, name or phone; account CRUD; category/search filters; summary balances; and Excel/CSV import and export.

Dashboard lookup, account creation, account list, spreadsheet import and data export are separate pages accessible from the sidebar.

## Run locally

```bash
composer install
npm install
php artisan migrate
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. Configure a supported database in `.env` before running migrations.

## Spreadsheet columns

The importer searches the first 20 rows for a header row, so a title row above the column names is supported. Required columns are `ACCT_ID`, `NAME` and `CLOSING BALANCE` (or `CLOSING_BALANCE_LPS`). Optional columns are `old acc id`, `ADDRESS`, `Phone number`, `Category`, `Progress of the JE` and `pay`. Category values from the supplied workbook (`DS`, `NDS`, `AGRI` and `LT`) are supported. Phone numbers are extracted from address text when no phone column is present. The importer also handles rows where the category and closing-balance cells are swapped. Blank and `#N/A` values in optional fields are treated as empty.

Imports accept `.xlsx`, `.xls` and `.csv` files up to 10 MB and 5,000 rows. A repeated account ID updates its existing record; invalid rows reject the import with row-specific errors. Export downloads the currently filtered records as Excel or CSV, including the phone number.

## Tests

```bash
php artisan test
```
