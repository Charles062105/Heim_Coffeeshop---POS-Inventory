<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\ExportService;
use App\Support\BusinessDateRange;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $query = AuditLog::orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        if ($module = $request->get('module')) {
            $query->where('module', $module);
        }
        if ($role = $request->get('actor_role')) {
            $query->where('actor_role', $role);
        }
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', BusinessDateRange::startUtc($from));
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<', BusinessDateRange::endExclusiveUtc($to));
        }

        if ($request->get('export') === 'excel') {
            abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can export reports.');
            $filename = 'audit-logs-'.now(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d').'.csv';
            $columns = [
                'Log ID',
                'Date & Time',
                'Staff / Actor',
                'Role',
                'Action',
                'Module',
                'IP Address',
                'Reason',
                'Details',
            ];

            $logs = $query->get()->map(function ($log) {
                return [
                    $log->id,
                    $log->created_at ? $log->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d H:i:s') : '',
                    $log->actor_name ?? 'System',
                    ucfirst($log->actor_role ?? 'System'),
                    $log->action,
                    $log->module,
                    $log->ip_address ?? '',
                    $log->reason ?? '',
                    is_array($log->details) ? json_encode($log->details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ($log->details ?? ''),
                ];
            });

            return ExportService::streamCsv($filename, $columns, $logs);
        }

        $logs = $request->get('print') === 'all'
            ? (abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can print reports.') ?: $query->get())
            : $query->paginate(30)->withQueryString();

        $modules = AuditLog::distinct()->pluck('module')->sort()->values();
        $roles = ['owner', 'manager', 'cashier'];

        return view('audit-logs.index', compact('logs', 'modules', 'roles'));
    }
}
