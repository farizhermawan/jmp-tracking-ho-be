<?php

namespace App\Models;

use App\User;

trait Auditable
{
  protected static function boot()
  {
    parent::boot();
    /* @var $auth User */
    $auth = auth()->user();
    $auditor = $auth == null ? "system" : "{$auth->name}";
    static::saving(function ($entity) use ($auditor) {
      if (!$entity->exists) $entity->created_by = $auditor;
//      else $entity->updated_by = $auditor;
    });
  }
}
