<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Support\QuantityFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', array_keys($this->reports->types()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $type = $filters['type'] ?? 'inventory_balances';
        $definition = $this->reports->definition($type, $filters);

        return view('reports.index', [
            'types' => $this->reports->types(),
            'type' => $type,
            'title' => $definition['title'],
            'columns' => $definition['columns'],
            'rows' => $definition['query']->paginate(50)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys($this->reports->types()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $definition = $this->reports->definition($filters['type'], $filters);
        $columns = $definition['columns'];
        $filename = Str::slug($definition['title']).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($definition, $columns): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, array_values($columns));
            foreach ($definition['query']->cursor() as $row) {
                fputcsv($stream, array_map(fn ($key) => $this->csvValue(QuantityFormatter::reportValue($key, $row->{$key} ?? null)), array_keys($columns)));
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return preg_match('/^[\s=+@\-]/', $value) ? "'".$value : $value;
    }
}