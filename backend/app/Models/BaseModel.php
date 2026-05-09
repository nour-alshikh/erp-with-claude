<?php

namespace App\Models;

use App\Models\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base model that all ERP domain models extend.
 * Automatically scopes all queries to the authenticated user's company.
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new CompanyScope());
    }

    protected static function creating($model): void
    {
        if (empty($model->company_id) && auth()->check()) {
            $model->company_id = auth()->user()->company_id;
        }
    }
}
