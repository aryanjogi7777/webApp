<?php

namespace App\Http\Controllers;

use App\Models\DefaultingAccount;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DefaultingAccountReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredAccounts($filters);
        $summary = (clone $query)->selectRaw(
            'COUNT(*) as total, COALESCE(SUM(closing_balance), 0) as closing_total, '.
            'COALESCE(SUM(paid_amount), 0) as paid_total, '.
            'COALESCE(SUM(CASE WHEN closing_balance > COALESCE(paid_amount, 0) '.
            'THEN closing_balance - COALESCE(paid_amount, 0) ELSE 0 END), 0) as pending_total'
        )->first();
        $preview = (clone $query)->orderBy('name')->limit(5)->get();

        return view('accounts.report', [
            'filters' => $filters,
            'preview' => $preview,
            'summary' => $summary,
        ]);
    }

    public function download(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $accounts = $this->filteredAccounts($filters)->orderBy('name')->get();

        $options = new Options;
        $options->set('isRemoteEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('accounts.report-pdf', [
            'accounts' => $accounts,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->render());
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="account-payment-report-'.now()->format('Y-m-d').'.pdf"',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(['DS', 'NDS', 'AGRI', 'LT'])],
            'status' => ['nullable', Rule::in(array_keys(DefaultingAccount::STATUSES))],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'min_paid' => ['nullable', 'numeric', 'min:0'],
            'max_paid' => ['nullable', 'numeric', 'min:0', 'gte:min_paid'],
        ]);
    }

    private function filteredAccounts(array $filters): Builder
    {
        return DefaultingAccount::query()
            ->when(isset($filters['search']) && $filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $search = trim($search);
                $query->where(function (Builder $matches) use ($search): void {
                    $matches->where('account_id', 'like', "%{$search}%")
                        ->orWhere('old_account_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, string $category): Builder => $query->where('category', $category))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['start_date'] ?? null, fn (Builder $query, string $date): Builder => $query->where('payment_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, string $date): Builder => $query->where('payment_date', '<=', $date))
            ->when(isset($filters['min_paid']) && $filters['min_paid'] !== '', fn (Builder $query): Builder => $query->where('paid_amount', '>=', $filters['min_paid']))
            ->when(isset($filters['max_paid']) && $filters['max_paid'] !== '', fn (Builder $query): Builder => $query->where('paid_amount', '<=', $filters['max_paid']));
    }
}
