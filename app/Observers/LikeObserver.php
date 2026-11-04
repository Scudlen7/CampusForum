<?php

namespace App\Observers;

use App\Models\Like;
use Illuminate\Database\Eloquent\Model;

class LikeObserver
{
    public function created(Like $like): void
    {
        $this->adjustCount($like->likeable, 1);
    }

    public function deleted(Like $like): void
    {
        $this->adjustCount($like->likeable, -1);
    }

    private function adjustCount(?Model $likeable, int $delta): void
    {
        if (! $likeable) {
            return;
        }

        if ($delta > 0) {
            $likeable->increment('like_count');
        } else {
            $likeable->decrement('like_count');
        }
    }
}
