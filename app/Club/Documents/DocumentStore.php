<?php

namespace App\Club\Documents;

use App\Models\Club\Document;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Guarda archivos del club en el disco privado de documentos, separados por club:
 * `clubs/<club>/<entidad>/<id>/<uuid>.<ext>`. Nunca se publican: se descargan por la API.
 */
class DocumentStore
{
    public function disk(): string
    {
        return config('filesystems.documents_disk');
    }

    public function store(UploadedFile $file, Model $owner, string $kind, Tenant $tenant, ?int $uploadedBy): Document
    {
        $type = $owner->getMorphClass();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs(
            "clubs/{$tenant->slug}/{$type}/{$owner->getKey()}",
            Str::uuid().'.'.$extension,
            ['disk' => $this->disk()],
        );

        return Document::create([
            'documentable_type' => $type,
            'documentable_id' => $owner->getKey(),
            'kind' => $kind,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'disk' => $this->disk(),
            'path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /** Borra el archivo y da de baja el registro: un apto médico no queda dando vueltas. */
    public function delete(Document $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }
}
