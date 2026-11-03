<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registro de actividad de la plataforma (FRO-30): altas, cambios y bajas de clubes, usuarios y
 * membresías (con los roles antes y después), documentos y restablecimientos de contraseña.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'subject_type' => ['sometimes', 'nullable', 'in:tenant,user,membership,document,auth'],
            'actor_id' => ['sometimes', 'nullable', 'integer'],
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $page = AuditLog::query()
            ->when($filters['subject_type'] ?? null, fn ($q, $type) => $q->where('action', 'like', $type.'.%'))
            ->when($filters['actor_id'] ?? null, fn ($q, $id) => $q->where('actor_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<', date('Y-m-d', strtotime($to.' +1 day'))))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 30);

        $logs = collect($page->items());
        $actors = User::query()->whereKey($logs->pluck('actor_id')->filter()->unique())->get(['id', 'name', 'email'])->keyBy('id');
        $labels = $this->subjectLabels($logs);

        return response()->json([
            'data' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                /** Qué se tocó, en palabras: el club, el email del usuario o "usuario en club". */
                'subject_label' => $labels[$log->subject_type.':'.$log->subject_id] ?? null,
                'actor' => ($a = $actors->get($log->actor_id)) ? ['id' => $a->id, 'name' => $a->name, 'email' => $a->email] : null,
                /** Antes y después de cada campo: {campo: {from, to}}. */
                'changes' => $log->changes,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->values(),
            'meta' => ['total' => $page->total(), 'current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem()],
        ]);
    }

    /** @return array<string, string> */
    private function subjectLabels($logs): array
    {
        $ids = fn (string $type) => $logs->where('subject_type', $type)->pluck('subject_id')->unique()->all();
        $labels = [];

        foreach (Tenant::withTrashed()->whereKey($ids('tenant'))->get(['id', 'name']) as $t) {
            $labels['tenant:'.$t->id] = $t->name;
        }
        foreach (User::query()->whereKey($ids('user'))->get(['id', 'email']) as $u) {
            $labels['user:'.$u->id] = $u->email;
        }
        foreach (TenantMembership::query()->whereKey($ids('membership'))->with(['user:id,email', 'tenant' => fn ($q) => $q->withTrashed()->select('id', 'name')])->get() as $m) {
            $labels['membership:'.$m->id] = ($m->user?->email ?? 'Usuario').' en '.($m->tenant?->name ?? 'club');
        }

        return $labels;
    }
}
