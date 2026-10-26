<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Concerns\HasRevisions;
use App\Domain\Content\Concerns\Publishable;
use App\Domain\Content\Contracts\PublishableContent;
use App\Domain\Content\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Base des contenus éditoriaux : publication contrôlée et historique des versions.
 *
 * @property int $id
 * @property ContentStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $updated_at
 * @property int $sort
 */
abstract class ContentModel extends Model implements PublishableContent
{
    use HasRevisions;
    use Publishable;
}
