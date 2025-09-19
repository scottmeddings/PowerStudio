<?php

// app/Models/TenantModel.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\OwnedByCurrentUserScope;

abstract class TenantModel extends Model
{
    use BelongsToOwner;

   protected static function booted(): void
{
    static::addGlobalScope(new OwnedByCurrentUserScope);
}
}
