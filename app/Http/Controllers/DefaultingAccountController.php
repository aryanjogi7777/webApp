<?php

namespace App\Http\Controllers;

use App\Models\DefaultingAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DefaultingAccountController extends Controller
{
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
        ]);
        $search = trim($validated['q'] ?? '');
        $accounts = null;

        if ($search !== '') {
            $accounts = $this->identitySearch(DefaultingAccount::query(), $search)
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        return view('accounts.lookup', compact('accounts', 'search'));
    }

    public function index(Request $request)
    {
        $summary = DefaultingAccount::query()
            ->selectRaw(
                'COUNT(*) as total, COALESCE(SUM(closing_balance), 0) as closing_total, '.
                'COALESCE(SUM(paid_amount), 0) as paid_total, '.
                'COALESCE(SUM(CASE WHEN closing_balance > COALESCE(paid_amount, 0) '.
                'THEN closing_balance - COALESCE(paid_amount, 0) ELSE 0 END), 0) as pending_total'
            )
            ->first();

        return view('accounts.index', [
            'accounts' => $this->filteredAccounts($request)->orderBy('name')->paginate(20)->withQueryString(),
            'summary' => [
                'total' => (int) $summary->total,
                'closing_total' => (float) $summary->closing_total,
                'paid_total' => (float) $summary->paid_total,
                'pending_total' => (float) $summary->pending_total,
            ],
        ]);
    }

    public function create()
    {
        return view('accounts.form', ['account' => new DefaultingAccount]);
    }

    public function importForm()
    {
        return view('accounts.import');
    }

    public function exportForm()
    {
        return view('accounts.export');
    }

    public function store(Request $request): RedirectResponse
    {
        DefaultingAccount::create($this->validatedData($request));

        return redirect()->route('accounts.index')->with('success', 'Account record added successfully.');
    }

    public function edit(DefaultingAccount $account)
    {
        return view('accounts.form', compact('account'));
    }

    public function update(Request $request, DefaultingAccount $account): RedirectResponse
    {
        $account->update($this->validatedData($request, $account));

        return redirect()->route('accounts.index')->with('success', 'Account record updated successfully.');
    }

    public function destroy(DefaultingAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Account record deleted.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $reader = IOFactory::createReaderForFile($request->file('file')->getRealPath());
            $spreadsheet = $reader->load($request->file('file')->getRealPath());
        } catch (SpreadsheetException) {
            throw ValidationException::withMessages([
                'file' => 'Could not read the uploaded file. Please upload a valid .xlsx, .xls or .csv sheet.',
            ]);
        }
        $sheet = $spreadsheet->getActiveSheet();
        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();
        $columnCount = Coordinate::columnIndexFromString($lastColumn);

        $headerRow = null;
        $columns = [];

        for ($row = 1; $row <= min(20, $lastRow); $row++) {
            $candidate = [];
            for ($column = 1; $column <= $columnCount; $column++) {
                $candidate[] = $this->normalizeHeader((string) $sheet->getCell([$column, $row])->getFormattedValue());
            }

            $mapped = $this->mapHeaders($candidate);
            if (isset($mapped['account_id'], $mapped['name'], $mapped['closing_balance'])) {
                $headerRow = $row;
                $columns = $mapped;
                break;
            }
        }

        if ($headerRow === null) {
            throw ValidationException::withMessages([
                'file' => 'The sheet must include columns for ACCT_ID, NAME and CLOSING BALANCE.',
            ]);
        }

        if ($lastRow - $headerRow > 5000) {
            throw ValidationException::withMessages(['file' => 'Import files are limited to 5,000 data rows.']);
        }

        $records = [];
        $rowErrors = [];
        $seenIds = [];

        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            $record = [];
            foreach ($columns as $field => $column) {
                $value = trim((string) $sheet->getCell([$column, $row])->getFormattedValue());
                $record[$field] = $value === '' || in_array(strtoupper($value), ['#N/A', 'N/A'], true) ? null : $value;
                if (in_array($field, ['closing_balance', 'paid_amount'], true) && $record[$field] !== null) {
                    $record[$field] = preg_replace('/^Rs\.?\s*/i', '', $record[$field]);
                    $record[$field] = str_replace([',', '₹'], '', $record[$field]);
                }
            }

            if (isset($record['closing_balance'], $record['category'])
                && ! is_numeric($record['closing_balance'])
                && is_numeric($record['category'])) {
                [$record['closing_balance'], $record['category']] = [$record['category'], $record['closing_balance']];
            }

            $record['phone_number'] = ($record['phone_number'] ?? null) ?: $this->extractPhoneNumber($record['address'] ?? null);

            if (count(array_filter($record, fn ($value) => $value !== null)) === 0) {
                continue;
            }

            if (isset($record['category'])) {
                $record['category'] = strtoupper((string) $record['category']);
            }

            $validator = Validator::make($record, [
                'account_id' => ['required', 'string', 'max:64'],
                'old_account_id' => ['nullable', 'string', 'max:64'],
                'name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:5000'],
                'closing_balance' => ['required', 'numeric', 'min:0'],
                'category' => ['nullable', Rule::in(['DS', 'NDS', 'AGRI', 'LT'])],
                'progress' => ['nullable', 'string', 'max:5000'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                $rowErrors[] = 'Row '.$row.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            if (isset($seenIds[$record['account_id']])) {
                $rowErrors[] = 'Row '.$row.': duplicate ACCT_ID '.$record['account_id'].' in this file.';

                continue;
            }

            $seenIds[$record['account_id']] = true;
            $records[] = $validator->validated();
        }

        if ($rowErrors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($rowErrors, 0, 20)]);
        }

        if ($records === []) {
            throw ValidationException::withMessages(['file' => 'No account records were found in the uploaded sheet.']);
        }

        $counts = ['created' => 0, 'updated' => 0];

        DB::transaction(function () use ($records, &$counts): void {
            foreach ($records as $record) {
                $account = DefaultingAccount::firstOrNew(['account_id' => $record['account_id']]);
                $counts[$account->exists ? 'updated' : 'created']++;
                $account->fill($record)->save();
            }
        });

        $spreadsheet->disconnectWorksheets();

        return redirect()->route('accounts.index')->with(
            'success',
            count($records).' rows imported: '.$counts['created'].' added, '.$counts['updated'].' updated.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $format = $request->query('format', 'xlsx');
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Accounts');
        $sheet->mergeCells('A1:J1');
        $this->writeText($sheet, 'A1', 'Defaulting Accounts');

        $headers = ['S.No.', 'ACCT_ID', 'Old account ID', 'Name', 'Address', 'Phone number', 'Closing balance (LPS)', 'Category', 'Progress of the JE', 'Pay'];
        foreach ($headers as $index => $header) {
            $this->writeText($sheet, [$index + 1, 2], $header);
        }

        $row = 3;
        $number = 1;
        foreach ($this->filteredAccounts($request)->orderBy('name')->cursor() as $account) {
            $values = [
                $number++,
                $account->account_id,
                $account->old_account_id,
                $account->name,
                $account->address,
                $account->phone_number,
                (float) $account->closing_balance,
                $account->category,
                $account->progress,
                $account->paid_amount === null ? null : (float) $account->paid_amount,
            ];

            foreach ($values as $index => $value) {
                $cell = [$index + 1, $row];
                if (in_array($index, [0, 6, 9], true) && $value !== null) {
                    $sheet->setCellValue($cell, $value);
                } else {
                    $this->writeText($sheet, $cell, (string) ($value ?? ''));
                }
            }

            $row++;
        }

        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '142B4A']],
        ]);
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '245A72']],
        ]);
        $sheet->freezePane('A3');
        $sheet->setAutoFilter('A2:J2');

        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $date = now()->format('Y-m-d');
        $filename = "defaulting-accounts-{$date}.{$format}";

        return response()->streamDownload(function () use ($spreadsheet, $format): void {
            if ($format === 'csv') {
                $writer = new Csv($spreadsheet);
                $writer->setUseBOM(true);
                $writer->setLineEnding("\r\n");
            } else {
                $writer = new Xlsx($spreadsheet);
            }

            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function validatedData(Request $request, ?DefaultingAccount $account = null): array
    {
        $request->merge([
            'account_id' => trim((string) $request->input('account_id')),
            'old_account_id' => trim((string) $request->input('old_account_id')) ?: null,
            'name' => trim((string) $request->input('name')),
            'category' => $request->filled('category') ? strtoupper(trim((string) $request->input('category'))) : null,
            'phone_number' => $this->normalizePhoneNumber($request->input('phone_number')),
        ]);

        return $request->validate([
            'account_id' => ['required', 'string', 'max:64', Rule::unique('defaulting_accounts', 'account_id')->ignore($account?->id)],
            'old_account_id' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'category' => ['nullable', Rule::in(['DS', 'NDS', 'AGRI', 'LT'])],
            'progress' => ['nullable', 'string', 'max:5000'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function filteredAccounts(Request $request): Builder
    {
        return DefaultingAccount::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function (Builder $query) use ($search): void {
                    $this->identitySearch($query, $search)
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('progress', 'like', "%{$search}%");
                });
            })
            ->when(in_array($request->query('category'), ['DS', 'NDS', 'AGRI', 'LT'], true), function (Builder $query) use ($request): void {
                $query->where('category', $request->query('category'));
            });
    }

    private function normalizeHeader(string $header): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/i', ' ', strtolower($header)));
    }

    private function mapHeaders(array $headers): array
    {
        $aliases = [
            'account_id' => ['acct id', 'account id', 'account number', 'account no', 'acct no', 's no', 's no acct id'],
            'old_account_id' => ['old acc id', 'old account id', 'old acct id', 'old account number', 'old acc no'],
            'name' => ['name', 'customer name', 'account holder'],
            'address' => ['address', 'customer address'],
            'phone_number' => ['phone', 'phone number', 'mobile', 'mobile number', 'contact number', 'ph no', 'phone no'],
            'closing_balance' => ['closing balance lps', 'closing balance', 'balance lps', 'balance'],
            'category' => ['category', 'type'],
            'progress' => ['progress of the je', 'progress of je', 'progress', 'je progress', 'case progress'],
            'paid_amount' => ['pay', 'paid', 'paid amount', 'payment'],
        ];
        $columns = [];

        foreach ($headers as $index => $header) {
            foreach ($aliases as $field => $fieldAliases) {
                if (in_array($header, $fieldAliases, true)) {
                    $columns[$field] = $index + 1;
                    break;
                }
            }
        }

        return $columns;
    }

    private function identitySearch(Builder $query, string $search): Builder
    {
        $phoneDigits = $this->normalizePhoneNumber($search);

        return $query->where(function (Builder $matches) use ($search, $phoneDigits): void {
            $matches->where('account_id', 'like', "%{$search}%")
                ->orWhere('old_account_id', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");

            if (strlen((string) $phoneDigits) >= 4) {
                if (strlen((string) $phoneDigits) === 10) {
                    $matches->orWhere('phone_number', $phoneDigits);
                } else {
                    $matches->orWhere('phone_number', 'like', "{$phoneDigits}%");
                }
            }
        });
    }

    private function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber);
        $digits = ltrim($digits, '0');

        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits === '' ? null : $digits;
    }

    private function extractPhoneNumber(?string $address): ?string
    {
        if ($address !== null && preg_match('/(?<!\d)(?:\+?91[\s-]?)?([6-9]\d{9})(?!\d)/', $address, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function writeText(Worksheet $sheet, string|array $cell, string $value): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }
}
