<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Club\Document;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AuditLogger
{
    private const FIELDS = [
        'tenant' => ['name', 'slug', 'database_name', 'status', 'short_name', 'city', 'founded_year', 'primary_color', 'logo_path'],
        'user' => ['name', 'email', 'is_active'],
        'membership' => ['user_id', 'tenant_id', 'is_active'],
    ];

    public function __construct(private Request $request) {}

    public function recordCreated(Model $subject): void
    {
        $type = $this->subjectType($subject);
        $attributes = $subject->getAttributes();
        $changes = [];

        foreach (self::FIELDS[$type] as $field) {
            if (array_key_exists($field, $attributes)) {
                $changes[$field] = ['to' => $subject->getAttribute($field)];
            }
        }

        $this->write($subject, $type, $changes, 'created');
    }

    public function recordUpdated(Model $subject): void
    {
        $type = $this->subjectType($subject);
        $dirty = $subject->getDirty();
        $changes = [];

        foreach (self::FIELDS[$type] as $field) {
            if (array_key_exists($field, $dirty)) {
                $changes[$field] = [
                    'from' => $subject->getOriginal($field),
                    'to' => $subject->getAttribute($field),
                ];
            }
        }

        if ($subject instanceof User && array_key_exists('password', $dirty)) {
            $changes['password'] = ['changed' => true];
        }

        if ($changes !== []) {
            $this->write($subject, $type, $changes, 'updated');
        }
    }

    /**
     * Roles del club asignados al miembro (viven en la base del club, no en la membresía).
     *
     * @param  list<string>  $from
     * @param  list<string>  $to
     */
    public function recordMembershipRoles(TenantMembership $membership, array $from, array $to): void
    {
        sort($from);
        sort($to);

        if ($from !== $to) {
            $this->write($membership, 'membership', ['roles' => ['from' => $from, 'to' => $to]], 'roles_updated');
        }
    }

    /**
     * Acceso a un documento del club (TIZ-29): quién descargó o borró qué. Los documentos de salud
     * son datos sensibles (Ley 25.326), así que cada acceso queda registrado.
     */
    public function recordDocument(Document $document, Tenant $tenant, string $event): void
    {
        AuditLog::query()->create([
            'actor_id' => $this->request->user()?->getKey(),
            'action' => 'document.'.$event,
            'subject_type' => 'document',
            'subject_id' => $document->getKey(),
            'changes' => ['tenant' => $tenant->slug, 'kind' => $document->kind, 'owner' => $document->documentable_type.':'.$document->documentable_id],
            'ip' => $this->request->ip(),
        ]);
    }

    public function recordDeleted(Model $subject): void
    {
        $type = $this->subjectType($subject);

        $this->write($subject, $type, null, 'deleted');
    }

    private function write(Model $subject, string $type, ?array $changes, string $event): void
    {
        AuditLog::query()->create([
            'actor_id' => $this->request->user()?->getKey(),
            'action' => $type.'.'.$event,
            'subject_type' => $type,
            'subject_id' => $subject->getKey(),
            'changes' => $changes,
            'ip' => $this->request->ip(),
        ]);
    }

    private function subjectType(Model $subject): string
    {
        return match (true) {
            $subject instanceof Tenant => 'tenant',
            $subject instanceof User => 'user',
            $subject instanceof TenantMembership => 'membership',
            default => throw new InvalidArgumentException('Unsupported audit subject: '.$subject::class),
        };
    }
}
