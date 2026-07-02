<?php

namespace App\Models;

use Database\Factories\MediumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $filename
 * @property string $mime
 * @property int $size
 * @property string|null $alt
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['disk', 'path', 'filename', 'mime', 'size', 'alt', 'uploaded_by'])]
class Medium extends Model
{
    /** @use HasFactory<MediumFactory> */
    use HasFactory;

    /**
     * Get the table associated with the model.
     */
    protected $table = 'media';

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the public URL for this media file.
     */
    public function url(): string
    {
        /** @var FilesystemAdapter $disk */
        $disk = storage()->disk($this->disk);

        return $disk->url($this->path);
    }
}
