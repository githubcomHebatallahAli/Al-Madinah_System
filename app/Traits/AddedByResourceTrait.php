<?php

namespace App\Traits;

trait AddedByResourceTrait
{
    public function addedByAttribute()
    {
        return $this->whenLoaded('creator', function () {
            return [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email ?? null,
                'role_id' => $this->creator->role_id,
                'role_name' => optional($this->creator->role)->name ?? '',
                'type' => $this->added_by_type,
                'branch' => $this->when(
                    method_exists($this->creator, 'branch') && $this->creator->relationLoaded('branch'),
                    function () {
                        return [
                            'id' => optional($this->creator->branch)->id,
                            'name' => optional($this->creator->branch)->name,
                        ];
                    }
                ),
            ];
        });
    }
}
